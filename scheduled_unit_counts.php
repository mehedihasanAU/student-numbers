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

@ini_set('memory_limit', '1024M');
@set_time_limit(300);

$CACHE_DIR = __DIR__ . "/.cache_paradigm";
$CACHE_TTL_SECONDS = 300;

ensureCacheDir($CACHE_DIR);

$force = (isset($_GET['force']) && $_GET['force'] == "1");

// -------------------- 1. STREAMING PARSER --------------------

function processStreamAndCount($url, $user, $pw, &$counts, &$statusCounts, &$unitMeta, &$uniqueStudents, &$uniqueRisks, &$studentRisks, &$retentionTemp, &$debugStats)
{

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

    $buffer = '';
    $depth = 0;
    $inString = false;
    $processedRows = 0;

    while (!feof($fp)) {
        $chunk = fread($fp, 8192);
        if ($chunk === false)
            break;

        $len = strlen($chunk);
        for ($i = 0; $i < $len; $i++) {
            $char = $chunk[$i];

            if ($char === '"' && ($i === 0 || $chunk[$i - 1] !== '\\')) {
                $inString = !$inString;
            }

            if (!$inString) {
                if ($char === '{') {
                    if ($depth === 0)
                        $buffer = '';
                    $depth++;
                }
            }

            if ($depth > 0) {
                $buffer .= $char;
            }

            if (!$inString && $char === '}') {
                $depth--;
                if ($depth === 0) {
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
                            $studentRisks,
                            $retentionTemp,
                            $debugStats
                        );
                    }
                    $buffer = '';
                }
            }
        }
    }
    fclose($fp);

    return $processedRows;
}

function processSingleRow($row, &$processedRows, &$counts, &$statusCounts, &$unitMeta, &$uniqueStudents, &$uniqueRisks, &$studentRisks, &$retentionTemp, &$debugStats)
{
    if (!is_array($row))
        return;

    if ($processedRows === 0 && empty($debugStats['keys'])) {
        $debugStats['keys'] = array_keys($row);
    }

    $processedRows++;

    $unitCode = null;
    $campus = null;
    $status = null;
    $startDateRaw = null;
    $blockRaw = null;
    $studentId = null;
    $courseName = null;
    $unitType = null;

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
        if ($kNorm === 'unittype')
            $unitType = $v;
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

        // ID Extraction fix: Ensure we capture it if it's there
        if ($kNorm === 'studentnumber' || $kNorm === 'studentid')
            $studentId = $v;

        if ($kNorm === 'course_name' || $kNorm === 'coursename' || $kNorm === 'course')
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

    // Filter out Non-Tuition units (Material Fees etc)
    if ($unitType && $unitType === 'OTHER_FEE') {
        // file_put_contents('debug_log.txt', "REJECT: Other Fee $unitCode\n", FILE_APPEND);
        return;
    }

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

    if ($processedRows < 5) {
        $debugStats['rows'][] = "Block='$block' | Status='$status' | UnitType='$unitType'";
    }

    if (strpos($block, 'Summer') !== false) {
        $debugStats['summer_rows']++;
        if ($processedRows < 20) { // Limit log size
            $debugStats['summer_examples'][] = "Enrolled: " . ($isEnrolled ? 'Y' : 'N') . " | UnitType: $unitType";
        }
    }

    if (!$campus)
        $campus = "Unknown";
    $campusUpper = strtoupper(trim($campus));
    if ($campusUpper === 'AIHE')
        $campus = 'SYD';
    elseif ($campusUpper === 'CAMPUS_MEL' || $campusUpper === 'MEL') {
        $campus = 'MEL';
    } elseif ($campusUpper === 'CAMPUS_COMB' || $campusUpper === 'COMB') {
        $campus = 'COMB';
    } else {
        // Normalize 'Sydney' to 'SYD' etc if needed, but sticking to basics
    }

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
                    'campus' => $campus,
                    'course' => $courseName // Added course
                ];
            }
        }

        // Aggregate
        if (!isset($counts[$unitCode]))
            $counts[$unitCode] = [];
        if (!isset($counts[$unitCode][$block])) {
            $counts[$unitCode][$block] = ['groups' => []];
        }

        $lecturerName = trim("$lecturerFirst $lecturerLast");
        if ($teacherFree)
            $lecturerName = $teacherFree;
        elseif ($supFirst || $supLast)
            $lecturerName = trim("$supFirst $supLast");
        if (!$lecturerName)
            $lecturerName = "TBA";

        // Key by Campus|Lecturer to preserve distinct groups
        $groupKey = "$campus|$lecturerName";

        if (!isset($counts[$unitCode][$block]['groups'][$groupKey])) {
            $counts[$unitCode][$block]['groups'][$groupKey] = [
                'id' => $scheduledUnitId ?: 0, // Best effort ID
                'campus' => $campus,
                'lecturer' => $lecturerName,
                'enrolled_count' => 0,
                'capacity' => $maxParticipants,
                'is_synthetic' => false,
                'unit_code' => $unitCode
            ];
        }
        $counts[$unitCode][$block]['groups'][$groupKey]['enrolled_count']++;

        // Track retention (Moved outside group init)
        if ($studentId && $block && $block !== "Unknown Block") {
            if (!isset($retentionTemp[$block]))
                $retentionTemp[$block] = [];
            $retentionTemp[$block][$studentId] = true;
        }
    }
}


// -------------------- 2. FETCH LIST --------------------

$listsCachePath = $CACHE_DIR . "/units_list_v2.json"; // Bumped to v2
$unitList = null;
if (!$force && file_exists($listsCachePath) && (time() - filemtime($listsCachePath) < 3600)) {
    $unitList = json_decode(file_get_contents($listsCachePath), true);
}

if ($unitList === null) {
    $urls = [];
    foreach ($blockDates as $date) {
        $urls[$date] = "{$paradigmHost}/api/rest/ScheduledUnit?startDate={$date}";
    }
    $results = curlMultiFetchJson($urls, $apiUser, $apiPw, $CONNECT_TIMEOUT, $TIMEOUT, $CONCURRENCY);
    $unitList = [];
    foreach ($results as $date => $res) {
        if ($res['ok'] && is_array($res['data'])) {
            foreach ($res['data'] as $unit) {
                // Robust ID extraction
                $p = array_change_key_case($unit, CASE_LOWER);
                $id = $p['eduscheduledunitid'] ?? ($p['scheduledunitid'] ?? ($p['scheduled_unit_id'] ?? ($p['id'] ?? null)));

                if ($id)
                    $unitList[$id] = $unit;
            }
        }
    }
    file_put_contents($listsCachePath, json_encode($unitList));
}


// -------------------- 3. MAIN REPORT EXECUTION --------------------

$reportCachePath = $CACHE_DIR . "/report_11472_v8_stream.json";
$reportResult = null;

if (!$force && file_exists($reportCachePath) && (time() - filemtime($reportCachePath) < $CACHE_TTL_SECONDS)) {
    $decoded = json_decode(file_get_contents($reportCachePath), true);
    if ((isset($decoded['unique_student_count']) && $decoded['unique_student_count'] > 0) || isset($decoded['counts'])) {
        $reportResult = $decoded;
    }
}

if ($reportResult === null) {
    $counts = [];
    $statusCounts = ['Enrolled' => 0, 'Other' => 0];
    $unitMeta = [];
    $uniqueStudents = [];
    $uniqueRisks = [];
    $studentRisks = [];
    $retentionTemp = [];
    $debugStats = ['keys' => []];

    $apiUrl = $reportUrl . "&report_format=JSON&report_from_url=1&limit=100000";

    $fetchResult = processStreamAndCount(
        $apiUrl,
        $apiUser,
        $apiPw,
        $counts,
        $statusCounts,
        $unitMeta,
        $uniqueStudents,
        $uniqueRisks,
        $studentRisks,
        $retentionTemp,
        $debugStats
    );

    if (is_array($fetchResult) && isset($fetchResult['error'])) {
        header('Content-Type: application/json');
        echo json_encode($fetchResult);
        exit;
    }

    $processedRows = $fetchResult;

    // FIX: Convert 'groups' from associative array (Campus|Lecturer) to indexed array for frontend
    // AND Unwrap it so detailedGroups[u][b] = [group1, group2...] 
    foreach ($counts as $u => &$blocks) {
        foreach ($blocks as $b => &$bData) {
            if (isset($bData['groups']) && is_array($bData['groups'])) {
                $bData = array_values($bData['groups']);
            }
        }
    }
    unset($blocks, $bData);

    $detailedGroups = $counts; // aliases

    // Calculate Total Groups
    $totalGroups = 0;
    foreach ($detailedGroups as $u => $blocks) {
        foreach ($blocks as $b => $groups) {
            if (is_array($groups)) {
                $totalGroups += count($groups);
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
        'processed_rows' => $processedRows,
        'total_group_count' => $totalGroups,
        'retention_data' => array_map('array_keys', $retentionTemp)
    ];

    if ($processedRows > 0) {
        file_put_contents($reportCachePath, json_encode($reportResult));
    }
}


// -------------------- 4. OUTPUT --------------------

$reportCounts = $reportResult['counts'] ?? [];
$groups = [];

// Fallback logic for groups list (KPIs)
foreach ($unitList as $id => $payload) {
    if (!$id)
        continue;

    // Robust Key Handling (API return casing varies)
    $p = array_change_key_case($payload, CASE_LOWER);

    // Unit Code Healing:
    // API is inconsistent. Sometimes eduOtherUnitId is the code (BACC2001), sometimes eduUnitId is (MBIS5019).
    // Sometimes eduOtherUnitId is the Name.
    // Strategy: Prefer values that look like Unit Codes (3-4 letters + 4 digits).

    $candidates = [];
    if (isset($p['eduotherunitid']))
        $candidates[] = $p['eduotherunitid'];
    if (isset($p['eduunitid']))
        $candidates[] = $p['eduunitid'];
    if (isset($p['scheduledunitcode']))
        $candidates[] = $p['scheduledunitcode'];
    if (isset($p['unitcode']))
        $candidates[] = $p['unitcode'];

    $finalUnitCode = "Unknown";
    foreach ($candidates as $c) {
        // Look for typical format: XXXX1234 or XXX1234
        if (preg_match('/^[A-Z]{3,4}\d{4}[a-z]?$/i', $c)) {
            $finalUnitCode = strtoupper($c);
            break;
        }
    }
    // Fallback if no regex match
    if ($finalUnitCode === "Unknown" && !empty($candidates)) {
        // Avoid "MATERIAL_FEE" acting as a code if possible, unless it's the only one
        $finalUnitCode = $candidates[0];
    }

    $campus = $p["campus"] ?? ($p["location"] ?? ($p["homeinstitutioncode"] ?? ($p["home_institution_code"] ?? "Unknown")));

    // Apply standard mapping
    $cUpper = strtoupper(trim($campus));
    if ($cUpper === 'AIHE')
        $campus = 'SYD';
    elseif ($cUpper === 'CAMPUS_MEL' || $cUpper === 'MEL')
        $campus = 'MEL';
    elseif ($cUpper === 'CAMPUS_COMB' || $cUpper === 'COMB')
        $campus = 'COMB';
    $startDate = $p["startdate"] ?? ($p["start_date"] ?? null);
    $endDate = $p["enddate"] ?? ($p["end_date"] ?? null);
    $currentParticipants = isset($p["currentparticipants"]) ? intval($p["currentparticipants"]) : (isset($p["current_participants"]) ? intval($p["current_participants"]) : 0);

    $startYmd = paradigmTsToYmd($startDate);
    $endYmd = paradigmTsToYmd($endDate);
    $block = getBlockLabelFromStartEnd($startYmd, $endYmd);

    $groups[] = [
        "scheduled_unit_id" => intval($id),
        "enrolled_count_live" => $currentParticipants,
        "unit_code" => $finalUnitCode, // Use healed code
        "eduOtherUnitId" => $finalUnitCode, // Legacy dash needs this key specifically!
        "campus" => $campus,
        "block" => $block,
        "source" => "list_cache"
    ];
}

echo json_encode([
    "generated_at" => gmdate("c"),
    "unique_student_count" => $reportResult['unique_student_count'] ?? 0,
    "status_counts" => $reportResult['status_counts'] ?? [],
    "groups" => $groups,
    "detailed_groups" => $reportResult['detailed_groups'] ?? [],
    "campus_breakdown_detail" => $reportResult['detailed_groups'] ?? [], // LEAGCY SUPPORT
    "risk_data" => $reportResult['student_risks'] ?? [],
    "retention_data" => $reportResult['retention_data'] ?? [],
    "total_group_count" => $reportResult['total_group_count'] ?? 0,
    "debug_stats" => $debugStats ?? []
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
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
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