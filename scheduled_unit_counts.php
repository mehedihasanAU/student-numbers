<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
error_reporting(E_ALL);
ini_set('display_errors', 0);

$config = require __DIR__ . '/config.php';

// Params
$reportUrl = $config['report_url'];
$paradigmHost = $config['paradigm_host'];
$apiUser = $config['api_user'];
$apiPw = $config['api_pw'];
$blockDates = $config['block_dates'];

// Tuning
$CONNECT_TIMEOUT = $config['connect_timeout'];
$TIMEOUT = $config['timeout'];
$CONCURRENCY = $config['concurrency'];

// Safety wrapper for restricted environments (though ignored by some hosts)
@ini_set('memory_limit', '1024M');
@set_time_limit(300);


// Caching (disk)
$CACHE_DIR = __DIR__ . "/.cache_paradigm";
$CACHE_TTL_SECONDS = 300; // 5 minutes

ensureCacheDir($CACHE_DIR);

$force = (isset($_GET['force']) && $_GET['force'] == "1");

// -------------------- 1. STREAMING PARSER (Fixes 128MB Limit) --------------------

function processStreamAndCount($url, $user, $pw, &$counts, &$statusCounts, &$unitMeta, &$uniqueStudents, &$uniqueRisks, &$studentRisks)
{

    // 1. Open Stream
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Basic " . base64_encode("$user:$pw") . "\r\n" .
                "User-Agent: Enrolment-Insights-Backend/1.0\r\n" .
                "Accept: application/json\r\n",
            'timeout' => 300
        ]
    ]);

    $fp = @fopen($url, 'r', false, $ctx);
    if (!$fp) {
        $err = error_get_last();
        return ['error' => 'Could not open stream: ' . ($err['message'] ?? 'Unknown error')];
    }

    // 2. Stream Buffer Logic
    $buffer = '';
    $depth = 0;
    $inString = false;
    $processedRows = 0;

    // We are looking for objects "{ ... }" inside the main array "[ ... ]"
    // Limitations: This simple parser assumes the JSON structure is an array of objects.
    // robust enough for Paradigm output.

    while (!feof($fp)) {
        $chunk = fread($fp, 8192); // 8KB chunks
        if ($chunk === false)
            break;

        $len = strlen($chunk);
        for ($i = 0; $i < $len; $i++) {
            $char = $chunk[$i];

            // Toggle String State (to ignore braces inside strings)
            if ($char === '"' && ($i === 0 || $chunk[$i - 1] !== '\\')) {
                $inString = !$inString;
            }

            if (!$inString) {
                if ($char === '{') {
                    if ($depth === 0)
                        $buffer = ''; // Start capturing new object
                    $depth++;
                }
            }

            if ($depth > 0) {
                $buffer .= $char;
            }

            if (!$inString && $char === '}') {
                $depth--;
                if ($depth === 0) {
                    // End of an object at root level
                    $row = json_decode($buffer, true);
                    if (is_array($row)) {
                        processSingleRow(
                            $row,
                            $processedRows,
                            $counts,
                            $statusCounts,
                            $unitMeta,
                            $uniqueStudents,
                            $uniqueRisks,
                            $studentRisks
                        );
                    }
                    $buffer = ''; // Clear buffer
                }
            }
        }
    }
    fclose($fp);

    return $processedRows;
}

function processSingleRow($row, &$processedRows, &$counts, &$statusCounts, &$unitMeta, &$uniqueStudents, &$uniqueRisks, &$studentRisks)
{
    if (!is_array($row))
        return;
    $processedRows++;

    $unitCode = null;
    $campus = null;
    $status = null;
    $startDateRaw = null;
    $blockRaw = null;
    $studentId = null;
    $courseName = null;

    $lecturerFirst = null;
    $lecturerLast = null;
    $maxParticipants = 0;
    $visaExpire = null;
    $progression = null;
    $courseEnd = null;
    $firstName = null;
    $lastName = null;
    $enrolStatusDesc = null;
    $scheduledUnitId = null;
    $teacherFree = null;
    $supFirst = null;
    $supLast = null;

    foreach ($row as $k => $v) {
        $kNorm = strtolower(str_replace(['_', ' '], '', $k));

        if ($kNorm === 'scheduledunitcode' || $kNorm === 'unitcode')
            $unitCode = $v;
        if ($kNorm === 'homeinstitutioncode' || $kNorm === 'homeinstitute') {
            $campus = $v;
        }
        if ($kNorm === 'id' || $kNorm === 'scheduledunitid')
            $scheduledUnitId = $v;

        if (strpos($kNorm, 'enrolmentstatus') !== false && strpos($kNorm, 'unit') !== false)
            $status = $v;
        if (strpos($kNorm, 'startdate') !== false && strpos($kNorm, 'unit') !== false)
            $startDateRaw = $v;
        if (strpos($kNorm, 'termperiod') !== false)
            $blockRaw = $v;
        if ($kNorm === 'studentnumber' || $kNorm === 'studentid')
            $studentId = $v;
        if (strpos($kNorm, 'coursename') !== false || $kNorm === 'course')
            $courseName = $v;

        if ($kNorm === 'scheduledunitteacherfirstname')
            $lecturerFirst = $v;
        if ($kNorm === 'scheduledunitteacherlastname')
            $lecturerLast = $v;
        if ($kNorm === 'teachernamefreetext')
            $teacherFree = $v;
        if ($kNorm === 'supervisorfirstname')
            $supFirst = $v;
        if ($kNorm === 'supervisorlastname')
            $supLast = $v;

        if ($kNorm === 'maximumparticipants')
            $maxParticipants = intval($v);
        if ($kNorm === 'visaexpiredate')
            $visaExpire = $v;
        if ($kNorm === 'progressionstatusdescription')
            $progression = $v;
        if ($kNorm === 'courseexpectedenddate')
            $courseEnd = $v;
        if ($kNorm === 'firstname')
            $firstName = $v;
        if ($kNorm === 'lastname')
            $lastName = $v;
        if ($kNorm === 'courseenrolmentstatusdescription')
            $enrolStatusDesc = $v;

        // Campus fallback
        if (!$campus && ($kNorm === 'location' || $kNorm === 'campusname' || $kNorm === 'campus'))
            $campus = $v;
    }

    if (!$unitCode)
        return;
    $unitCode = trim($unitCode);

    $isEnrolled = ($status && stripos($status, 'Enrolled') !== false);

    if ($isEnrolled) {
        $statusCounts['Enrolled']++;
        if ($studentId)
            $uniqueStudents[$studentId] = true;
    } else {
        $statusCounts['Other']++;
    }

    if (!isset($unitMeta[$unitCode]))
        $unitMeta[$unitCode] = ['start_date' => '', 'course_name' => ''];
    if ($courseName && empty($unitMeta[$unitCode]['course_name']))
        $unitMeta[$unitCode]['course_name'] = $courseName;

    if ($startDateRaw && empty($unitMeta[$unitCode]['start_date'])) {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $startDateRaw, $m)) {
            $unitMeta[$unitCode]['start_date'] = $m[0] ? str_replace('-', '', $m[0]) : "";
        }
    }

    $block = "Unknown Block";
    if ($blockRaw) {
        $block = preg_replace('/^\d{4}\s*-\s*/', '', trim($blockRaw));
        $block = trim($block, " -");
    }

    if (!$campus)
        $campus = "Unknown";
    $campusUpper = strtoupper(trim($campus));
    if ($campusUpper === 'AIHE')
        $campus = 'SYD';
    elseif ($campusUpper === 'CAMPUS_MEL' || $campusUpper === 'MEL')
        $campus = 'MEL';
    elseif ($campusUpper === 'CAMPUS_COMB' || $campusUpper === 'COMB')
        $campus = 'COMB';

    if ($isEnrolled) {
        // Risks
        $risk = [];
        if ($visaExpire && $courseEnd && $visaExpire < $courseEnd) {
            $risk[] = "Visa: Visa ($visaExpire) < End ($courseEnd)";
        }
        if ($progression) {
            $p = strtolower($progression);
            if (strpos($p, 'sar') !== false || strpos($p, 'risk') !== false || strpos($p, 'probation') !== false) {
                $risk[] = "Academic: $progression";
            }
        }
        if ($enrolStatusDesc && strpos(strtolower($enrolStatusDesc), 'encumbered') !== false) {
            $risk[] = "Status: $enrolStatusDesc";
        }
        if (!empty($risk)) {
            $riskString = implode(", ", $risk);
            $riskKey = $studentId . '_' . md5($riskString);
            if (!isset($uniqueRisks[$riskKey])) {
                $uniqueRisks[$riskKey] = true;
                $studentRisks[] = [
                    'id' => $studentId,
                    'name' => trim($firstName . ' ' . $lastName),
                    'risk' => $riskString,
                    'unit' => 'Multiple/All',
                    'campus' => $campus
                ];
            }
        }

        // Aggregate
        if (!isset($counts[$unitCode]))
            $counts[$unitCode] = [];
        if (!isset($counts[$unitCode][$block])) {
            $counts[$unitCode][$block] = ['count' => 0, 'students' => [], 'campus_breakdown' => [], 'lecturers' => []];
        }

        $counts[$unitCode][$block]['count']++;

        if (!isset($counts[$unitCode][$block]['campus_breakdown'][$campus])) {
            $counts[$unitCode][$block]['campus_breakdown'][$campus] = 0;
        }
        $counts[$unitCode][$block]['campus_breakdown'][$campus]++;

        $lecturerName = trim("$lecturerFirst $lecturerLast");
        if ($teacherFree)
            $lecturerName = $teacherFree;
        elseif ($supFirst || $supLast)
            $lecturerName = trim("$supFirst $supLast");

        if (!$lecturerName)
            $lecturerName = "TBA";
        if (!isset($counts[$unitCode][$block]['lecturers'][$lecturerName])) {
            $counts[$unitCode][$block]['lecturers'][$lecturerName] = 0;
        }
        $counts[$unitCode][$block]['lecturers'][$lecturerName]++;
    }
}


// -------------------- 2. FETCH SCHEDULED UNITS (List) --------------------

$listsCachePath = $CACHE_DIR . "/units_list_v1.json";
$listsCacheTtl = 3600;

$unitList = null;
if (!$force && file_exists($listsCachePath) && (time() - filemtime($listsCachePath) < $listsCacheTtl)) {
    $unitList = json_decode(file_get_contents($listsCachePath), true);
}

if ($unitList === null) {
    // Need curlMultiFetchJson helper
    $urls = [];
    foreach ($blockDates as $date) {
        $urls[$date] = "{$paradigmHost}/api/rest/ScheduledUnit?startDate={$date}";
    }

    // Inline simpler multi fetch to save context switches
    // Or call helper defined below
    $results = curlMultiFetchJson($urls, $apiUser, $apiPw, $CONNECT_TIMEOUT, $TIMEOUT, $CONCURRENCY);

    $unitList = [];
    foreach ($results as $date => $res) {
        if ($res['ok'] && is_array($res['data'])) {
            foreach ($res['data'] as $unit) {
                $id = $unit['eduScheduledUnitId'] ?? null;
                if ($id)
                    $unitList[$id] = $unit;
            }
        }
    }
    file_put_contents($listsCachePath, json_encode($unitList));
}


// -------------------- 3. MAIN EXECUTION (Report) --------------------

$reportCachePath = $CACHE_DIR . "/report_11472_v7_stream.json"; // New cache file for stream
$reportResult = null;

if (!$force && file_exists($reportCachePath) && (time() - filemtime($reportCachePath) < $CACHE_TTL_SECONDS)) {
    $decoded = json_decode(file_get_contents($reportCachePath), true);
    // Self-healing: Ensure it has data
    if ((isset($decoded['unique_student_count']) && $decoded['unique_student_count'] > 0) || isset($decoded['counts'])) {
        $reportResult = $decoded;
    }
}

if ($reportResult === null) {
    // PREPARE REFERENCE VARIABLES
    $counts = [];
    $statusCounts = ['Enrolled' => 0, 'Other' => 0];
    $unitMeta = [];
    $uniqueStudents = [];
    $uniqueRisks = [];
    $studentRisks = [];

    $apiUrl = $reportUrl . "&report_format=JSON&report_from_url=1&limit=100000";

    // STREAM EXECUTION
    $fetchResult = processStreamAndCount(
        $apiUrl,
        $apiUser,
        $apiPw,
        $counts,
        $statusCounts,
        $unitMeta,
        $uniqueStudents,
        $uniqueRisks,
        $studentRisks
    );

    if (is_array($fetchResult) && isset($fetchResult['error'])) {
        header('Content-Type: application/json');
        echo json_encode($fetchResult);
        exit;
    }

    $processedRows = $fetchResult;

    // Map internal structure to output structure
    $detailedGroups = [];
    foreach ($counts as $u => $blocks) {
        foreach ($blocks as $b => $data) {
            foreach ($data['lecturers'] as $lecName => $lecCount) {
                // Synthetic group for lecturer
                // This is an approximation since we don't track group IDs perfectly in stream yet
                // But it's better than nothing
                $grp = [
                    'unit_code' => $u,
                    'block' => $b,
                    'lecturer' => $lecName,
                    'enrolled_count' => $lecCount,
                    'campus_breakdown' => $data['campus_breakdown']
                ];
                if (!isset($detailedGroups[$u]))
                    $detailedGroups[$u] = [];
                if (!isset($detailedGroups[$u][$b]))
                    $detailedGroups[$u][$b] = [];
                $detailedGroups[$u][$b][] = $grp;
            }
        }
    }

    $reportResult = [
        'unique_student_count' => count($uniqueStudents),
        'counts' => $counts,
        'status_counts' => $statusCounts,
        'meta' => $unitMeta,
        'student_risks' => $studentRisks,
        'detailed_groups' => $detailedGroups,
        'processed_rows' => $processedRows
    ];

    if ($processedRows > 0) {
        file_put_contents($reportCachePath, json_encode($reportResult));
    }
}


// -------------------- 4. FINAL MERGE & OUTPUT --------------------

$reportCounts = $reportResult['counts'] ?? [];
$reportMeta = $reportResult['meta'] ?? [];
$reportDetailed = $reportResult['detailed_groups'] ?? [];

$groups = [];

foreach ($unitList as $id => $payload) {
    if (!$id)
        continue;

    $eduUnitId = $payload["eduUnitId"] ?? null;
    $eduOtherUnitId = $payload["eduOtherUnitId"] ?? null;
    $unitCode = $payload["scheduledUnitCode"] ?? ($payload["unitCode"] ?? "Unknown"); // Use list's code
    $campus = $payload["campus"] ?? ($payload["location"] ?? "Unknown");
    $startDate = $payload["startDate"] ?? null;
    $endDate = $payload["endDate"] ?? null;
    $currentParticipants = isset($payload["currentParticipants"]) ? intval($payload["currentParticipants"]) : 0;

    $startYmd = paradigmTsToYmd($startDate);
    $endYmd = paradigmTsToYmd($endDate);

    // Find matching report data
    $breakdown = [];
    $block = getBlockLabelFromStartEnd($startYmd, $endYmd);

    // Report is keyed by UnitCode -> Block
    if ($unitCode && isset($reportCounts[$unitCode]) && isset($reportCounts[$unitCode][$block])) {
        $breakdown = $reportCounts[$unitCode][$block]['campus_breakdown'] ?? [];
    }

    $groups[] = [
        "scheduled_unit_id" => intval($id),
        "enrolled_count_live" => $currentParticipants,
        "unit_code" => $unitCode, // Pass unit code for frontend
        "eduUnitId" => $eduUnitId,
        "campus" => $campus,
        "startDate" => $startDate,
        "block" => $block,
        "source" => "list_cache",
        "campus_breakdown" => $breakdown
    ];
}

echo json_encode([
    "generated_at" => gmdate("c"),
    "unique_students" => $reportResult['unique_student_count'] ?? 0,
    "status_counts" => $reportResult['status_counts'] ?? [],
    "groups" => $groups,
    "detailed_groups" => $reportDetailed, // Detailed breakdown for popups
    "risk_data" => $reportResult['student_risks'] ?? []
], JSON_UNESCAPED_SLASHES);


// -------------------- HELPERS --------------------

function ensureCacheDir($dir)
{
    if (!is_dir($dir))
        @mkdir($dir, 0755, true);
}

function paradigmTsToYmd($ts)
{
    if (!$ts || strlen($ts) < 8)
        return "";
    return substr($ts, 0, 4) . substr($ts, 4, 2) . substr($ts, 6, 2);
}

function getBlockLabelFromStartEnd($startYmd, $endYmd)
{
    $ranges = [
        ["20260119", "20260213", "Summer School"],
        ["20260223", "20260320", "Block 1"],
        ["20260323", "20260424", "Block 2"],
        ["20260504", "20260529", "Block 3"],
        ["20260601", "20260626", "Block 4"],
        ["20260706", "20260724", "Winter School"],
        ["20260803", "20260828", "Block 5"],
        ["20260907", "20261002", "Block 6"],
        ["20261012", "20261106", "Block 7"],
        ["20261116", "20261211", "Block 8"],
    ];
    foreach ($ranges as $r) {
        if ($startYmd >= $r[0] && $startYmd <= $r[1])
            return $r[2];
    }
    return "Unknown Block";
}

function curlMultiFetchJson($urls, $apiUser, $apiPw, $connectTimeout, $timeout, $concurrency)
{
    $mh = curl_multi_init();
    $handles = [];
    $queue = $urls;
    $results = [];

    $addHandle = function ($id, $url) use (&$mh, &$handles, $apiUser, $apiPw, $connectTimeout, $timeout) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $apiUser . ":" . $apiPw,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4 // IPv4 Safety
        ]);
        $handles[(int) $ch] = ["id" => $id];
        curl_multi_add_handle($mh, $ch);
    };

    $inFlight = 0;
    foreach ($queue as $id => $url) {
        if ($inFlight >= $concurrency)
            break;
        $addHandle($id, $url);
        unset($queue[$id]);
        $inFlight++;
    }

    do {
        curl_multi_exec($mh, $active);
        if ($active)
            curl_multi_select($mh, 0.1);

        while ($info = curl_multi_info_read($mh)) {
            $ch = $info["handle"];
            $id = $handles[(int) $ch]["id"];
            $body = curl_multi_getcontent($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $json = json_decode($body, true);
            $results[$id] = ["ok" => ($http == 200 && is_array($json)), "data" => $json];

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($handles[(int) $ch]);
            $inFlight--;

            if (!empty($queue)) {
                $nextId = array_key_first($queue);
                $nextUrl = $queue[$nextId];
                unset($queue[$nextId]);
                $addHandle($nextId, $nextUrl);
                $inFlight++;
            }
        }
    } while ($active || $inFlight > 0);

    curl_multi_close($mh);
    return $results;
}