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

set_time_limit(300); // 5 minutes max

// Caching (disk)
$CACHE_DIR = __DIR__ . "/.cache_paradigm";
$CACHE_TTL_SECONDS = 300; // 5 minutes

ensureCacheDir($CACHE_DIR);

$force = (isset($_GET['force']) && $_GET['force'] == "1");

// -------------------- 1. FETCH SCHEDULED UNITS (By Date) --------------------
// Instead of a hardcoded list, we fetch units for each known Block Start Date.

$listsCachePath = $CACHE_DIR . "/units_list_v1.json";
$listsCacheTtl = 3600; // Cache lists for 1 hour (units don't appear that often)

$unitList = null;

if (!$force && file_exists($listsCachePath) && (time() - filemtime($listsCachePath) < $listsCacheTtl)) {
    $unitList = json_decode(file_get_contents($listsCachePath), true);
}

if ($unitList === null) {
    // Fetch concurrently
    $urls = [];
    foreach ($blockDates as $date) {
        $urls[$date] = "{$paradigmHost}/api/rest/ScheduledUnit?startDate={$date}";
    }

    $results = curlMultiFetchJson($urls, $apiUser, $apiPw, $CONNECT_TIMEOUT, $TIMEOUT, $CONCURRENCY);

    $unitList = [];
    foreach ($results as $date => $res) {
        if ($res['ok'] && is_array($res['data'])) {
            foreach ($res['data'] as $unit) {
                // We only need the ID to fetch details/merge
                // The API actually returns a lot of detail here already!
                // Let's store the whole object so we might not even need a second fetch?
                // The previous code fetched /ScheduledUnit/{id}. 
                // Let's check if the list item has 'currentParticipants'.
                // If the list endpoint returns full objects, we hit the jackpot.
                // Based on standard Paradigm ReST, list items are usually summaries, but let's check.
                // If it lacks 'currentParticipants', we still need to fetch individual units.
                // BUT: We can optimize. The list filter probably gives us the IDs we need.

                // For now, let's assume we need to re-fetch if we want live participants?
                // Actually, let's store the ID and the object.
                $id = $unit['eduScheduledUnitId'] ?? null;
                if ($id) {
                    $unitList[$id] = $unit;
                }
            }
        }
    }

    // Save to cache
    file_put_contents($listsCachePath, json_encode($unitList));
}

// -------------------- 2. PREPARE UNIT IDs --------------------
// The old script had a massive array. Now we have $unitList keys.
$scheduledUnitIds = array_keys($unitList);

// -------------------- BLOCK MAP --------------------
function getBlockLabelFromStartEnd($startYmd, $endYmd)
{
    if (!$startYmd)
        return "";
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
        [$a, $b, $label] = $r;
        if ($startYmd >= $a && $startYmd <= $b)
            return $label;
    }
    if ($endYmd) {
        foreach ($ranges as $r) {
            [$a, $b, $label] = $r;
            if ($endYmd >= $a && $endYmd <= $b)
                return $label;
        }
    }
    return "";
}

function paradigmTsToYmd($ts)
{
    if (!$ts || strlen($ts) < 8)
        return "";
    return substr($ts, 0, 4) . substr($ts, 4, 2) . substr($ts, 6, 2);
}

// -------------------- CACHE HELPERS --------------------
function ensureCacheDir($dir)
{
    if (!is_dir($dir))
        @mkdir($dir, 0755, true);
}
function cachePath($dir, $id)
{
    return rtrim($dir, "/") . "/scheduledUnit_" . intval($id) . ".json";
}
function cacheGet($dir, $id, $ttlSeconds)
{
    $p = cachePath($dir, $id);
    if (!file_exists($p))
        return null;
    $raw = @file_get_contents($p);
    if ($raw === false)
        return null;
    $obj = json_decode($raw, true);
    if (!is_array($obj) || !isset($obj["_cached_at"]))
        return null;
    $age = time() - intval($obj["_cached_at"]);

    if ($ttlSeconds > 0 && $age > $ttlSeconds)
        return null;
    return $obj;
}
function cacheSet($dir, $id, $payload)
{
    $p = cachePath($dir, $id);
    $payload["_cached_at"] = time();
    @file_put_contents($p, json_encode($payload, JSON_UNESCAPED_SLASHES));
}

// -------------------- HTTP (cURL multi) --------------------
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
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0, // Relax SSL for testing if needed
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $apiUser . ":" . $apiPw,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "User-Agent: AIHE-Enrolment-Insights/1.0"
            ],
        ]);
        $handles[(int) $ch] = ["id" => $id, "ch" => $ch];
        curl_multi_add_handle($mh, $ch);
    };

    // Prime pool
    $inFlight = 0;
    foreach ($queue as $id => $url) {
        if ($inFlight >= $concurrency)
            break;
        $addHandle($id, $url);
        unset($queue[$id]);
        $inFlight++;
    }

    do {
        $status = curl_multi_exec($mh, $active);
        if ($active) {
            curl_multi_select($mh, 0.5);
        }

        while ($info = curl_multi_info_read($mh)) {
            $ch = $info["handle"];
            $meta = $handles[(int) $ch] ?? null;
            $id = $meta ? $meta["id"] : null;

            $body = curl_multi_getcontent($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $err = curl_error($ch);

            $okJson = false;
            $json = null;
            if ($body !== false && $body !== "" && stripos((string) $ct, "application/json") !== false) {
                $json = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE)
                    $okJson = true;
            }

            $results[$id] = [
                "ok" => ($http >= 200 && $http < 300 && $okJson),
                "http" => $http,
                "content_type" => $ct,
                "curl_error" => ($err ?: null),
                "json" => $okJson,
                "data" => $okJson ? $json : null,
            ];

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($handles[(int) $ch]);
            $inFlight--;

            // Add next from queue
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

// -------------------- REPORT PARSING --------------------
// (Kept mostly same, but using Config)
function fetchAndParseReport($baseUrl, $user, $pw)
{
    $url = $baseUrl . "&report_format=JSON&report_from_url=1&limit=100000";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pw");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: Enrolment-Insights-Backend/1.0",
        "Accept: application/json"
    ]);

    $jsonData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$jsonData)
        return [];

    $rows = json_decode($jsonData, true);
    if (!is_array($rows))
        return [];

    $counts = [];
    $detailedCounts = [];
    $unitMeta = [];
    $uniqueStudents = [];
    $statusCounts = ['Enrolled' => 0, 'Other' => 0];

    // New Data Structures
    $unitDetails = []; // [UnitCode][Block] => {lecturer, capacity}
    $studentRisks = [];
    $retentionData = []; // [Block] => [StudentIDs]

    foreach ($rows as $row) {
        $unitCode = null;
        $campus = null;
        $status = null;
        $startDateRaw = null;
        $blockRaw = null;
        $studentId = null;
        $courseName = null;

        // New Fields
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

        foreach ($row as $k => $v) {
            $kNorm = strtolower(str_replace(['_', ' '], '', $k));

            // Existing Mappings
            if ($kNorm === 'scheduledunitcode' || $kNorm === 'unitcode')
                $unitCode = $v;
            if ($kNorm === 'campus' || $kNorm === 'institution')
                $campus = $v;
            if ($kNorm === 'id' || $kNorm === 'scheduledunitid' || $kNorm === 'eduscheduledunitid')
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

            // New Mappings
            if ($kNorm === 'scheduledunitteacherfirstname')
                $lecturerFirst = $v;
            if ($kNorm === 'scheduledunitteacherlastname')
                $lecturerLast = $v;
            if ($kNorm === 'teachernamefreetext' && !$lecturerFirst)
                $lecturerFirst = $v; // Fallback
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
        }

        if (!$unitCode)
            continue;
        $unitCode = trim($unitCode);

        // Filter for "Enrolled" only
        $isEnrolled = ($status && stripos($status, 'Enrolled') !== false);

        if ($isEnrolled) {
            $statusCounts['Enrolled']++;
        } else {
            $statusCounts['Other']++;
            // We usually only count enrolled students for numbers, but maybe we want risks for all?
            // stick to enrolled for consistency
        }

        if ($studentId)
            $uniqueStudents[$studentId] = true;

        if (!isset($unitMeta[$unitCode]))
            $unitMeta[$unitCode] = ['start_date' => '', 'course_name' => ''];
        if ($courseName && empty($unitMeta[$unitCode]['course_name']))
            $unitMeta[$unitCode]['course_name'] = $courseName;

        // Start Date Logic
        if ($startDateRaw && empty($unitMeta[$unitCode]['start_date'])) {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $startDateRaw, $m)) {
                $unitMeta[$unitCode]['start_date'] = $m[0] ? str_replace('-', '', $m[0]) : "";
            }
        }

        // Block Logic
        $block = "Unknown Block";
        if ($blockRaw) {
            $block = preg_replace('/^\d{4}\s*-\s*/', '', trim($blockRaw));
            $block = trim($block, " -");
        }

        // Campus Logic
        if (!$campus)
            $campus = "Unknown";
        $campusUpper = strtoupper(trim($campus));
        if ($campusUpper === 'AIHE')
            $campus = 'SYD';
        elseif ($campusUpper === 'CAMPUS_MEL' || $campusUpper === 'MEL')
            $campus = 'MEL';
        elseif ($campusUpper === 'CAMPUS_COMB' || $campusUpper === 'COMB')
            $campus = 'COMB';

        // Aggregations (Enrolled Only)
        if ($isEnrolled) {
            if (!isset($counts[$unitCode]))
                $counts[$unitCode] = [];
            if (!isset($counts[$unitCode][$campus]))
                $counts[$unitCode][$campus] = 0;
            $counts[$unitCode][$campus]++;

            if (!isset($detailedCounts[$unitCode]))
                $detailedCounts[$unitCode] = [];
            if (!isset($detailedCounts[$unitCode][$block]))
                $detailedCounts[$unitCode][$block] = [];
            if (!isset($detailedCounts[$unitCode][$block][$campus]))
                $detailedCounts[$unitCode][$block][$campus] = 0;
            $detailedCounts[$unitCode][$block][$campus]++;

            // Retention Data
            if ($studentId && $block) {
                if (!isset($retentionData[$block]))
                    $retentionData[$block] = [];
                // Use array key for simpler uniqueness, though input should be unique tuple
                if (!in_array($studentId, $retentionData[$block])) {
                    $retentionData[$block][] = $studentId; // Keep it simple array for JSON
                }
            }
        }

        if ($isEnrolled && $unitCode) {
            // Use Scheduled Unit ID as unique key for "Group"
            // If not present, fallback to a synthetic key (Unit+Block+Campus)
            $groupId = $v['scheduled_unit_id'] ?? $v['id'] ?? ($unitCode . $block . $campus); // Try to get ID from row if mapped

            // We need to map 'id' in the loop first.
        }

        // Risk Analysis (Visa & Academic) - Check ALL students or just Enrolled? 
        // Let's check Enrolled for now to be actionable.
        if ($isEnrolled && $studentId) {
            $risk = [];

            // 1. Visa Risk
            if ($visaExpire && $courseEnd) {
                if ($visaExpire < $courseEnd) {
                    $risk[] = "Visa expires ($visaExpire) before course end ($courseEnd)";
                }
            }

            // 2. Academic Risk / SAR
            // Logic: Filter for "SAR", "Risk", "Probation", "Show Cause"
            // Exclude "Good Standing" and "Satisfactory"
            if ($progression) {
                $p = strtolower($progression);
                if (strpos($p, 'sar') !== false || strpos($p, 'risk') !== false || strpos($p, 'probation') !== false || strpos($p, 'show cause') !== false) {
                    $risk[] = "Academic: $progression";
                } elseif (strpos($p, 'good') === false && strpos($p, 'satisfactory') === false) {
                    // Catch-all for other non-good statuses
                    $risk[] = "Academic: $progression";
                }
            }

            // 3. Encumbrance (Financial/Library Fines etc)
            if ($enrolStatusDesc) {
                $e = strtolower($enrolStatusDesc);
                if (strpos($e, 'encumbered') !== false) {
                    $risk[] = "Status: $enrolStatusDesc";
                }
            }

            if (!empty($risk)) {
                $studentRisks[] = [
                    'id' => $studentId,
                    'name' => trim($firstName . ' ' . $lastName),
                    'unit' => $unitCode,
                    'course' => $courseName,
                    'campus' => $campus,
                    'risk' => implode(", ", $risk)
                ];
            }
        }
    }

    return [
        'counts' => $counts,
        'detailed' => $detailedCounts,
        'meta' => $unitMeta,
        'unique_student_count' => count($uniqueStudents),
        'status_counts' => $statusCounts,
    // structured_groups: [UnitCode][Block][] = {GroupObject}
    $structuredGroups = [];
    foreach ($granularGroups as $g) {
        $u = $g['unit_code'];
        $b = $g['block'];
        if (!isset($structuredGroups[$u])) $structuredGroups[$u] = [];
        if (!isset($structuredGroups[$u][$b])) $structuredGroups[$u][$b] = [];
        $structuredGroups[$u][$b][] = $g;
    }

    return [
        'counts' => $counts,
        'detailed' => $detailedCounts, // Keep for backward compat if needed
        'meta' => $unitMeta,
        'unique_student_count' => count($uniqueStudents),
        'status_counts' => $statusCounts,
        'unit_details' => $unitDetails,
        'student_risks' => $studentRisks,
        'retention_data' => $retentionData,
        'detailed_groups' => $structuredGroups // Validated granular data
    ];
}

// -------------------- 3. INDIVIDUAL FETCH LOOP --------------------
// The list endpoint MIGHT return currentParticipants, but let's assume we need to refresh individual units to be safe/granular,
// OR we just use the list data if it's rich enough.
// PROBE showed: "currentParticipants":"53". This means the list endpoint DOES return the counts!
// We can SKIP the massive secondary fetch loop entirely and just use $unitList!
// This is a HUGE optimization.

// Fetch Report
$reportCachePath = $CACHE_DIR . "/report_11472_v5.json";
$reportCacheTtl = 300;
$reportResult = null;

if (!$force && file_exists($reportCachePath) && (time() - filemtime($reportCachePath) < $reportCacheTtl)) {
    $reportResult = json_decode(file_get_contents($reportCachePath), true);
}

if ($reportResult === null) {
    $reportResult = fetchAndParseReport($reportUrl, $apiUser, $apiPw);
    if ($reportResult) {
        file_put_contents($reportCachePath, json_encode($reportResult));
    }
}

$reportCounts = $reportResult['counts'] ?? [];
$reportMeta = $reportResult['meta'] ?? [];

$groups = [];

// Since we have ALL units from the list fetch, we iterate that.
foreach ($unitList as $id => $payload) {
    if (!$id)
        continue;

    // Check if we need to "re-verify" live? 
    // If the list is cached for 1 hour, the participants count might be stale.
    // However, fetching 500 units individually is slow.
    // A compromise: Use list data, but if 'force=1' is passed, clear the list cache.
    // The previous logic had a "pending" mechanism. We can remove that since we fetch ALL at once now.

    $currentParticipants = isset($payload["currentParticipants"]) ? intval($payload["currentParticipants"]) : 0;
    $eduUnitId = $payload["eduUnitId"] ?? null;
    $eduOtherUnitId = $payload["eduOtherUnitId"] ?? null;
    $campus = $payload["campus"] ?? ($payload["location"] ?? "Unknown");
    $startDate = $payload["startDate"] ?? null;
    $endDate = $payload["endDate"] ?? null;

    $startYmd = paradigmTsToYmd($startDate);
    $endYmd = paradigmTsToYmd($endDate);

    // Try to match with Report Data
    // The report is keyed by Unit Code (e.g., MBIS4001). 
    // The API has 'eduUnitId' (MBIS5019) and 'eduOtherUnitId'. 
    // We try both.
    $breakdown = [];
    $matchedKey = null;

    if ($eduUnitId && isset($reportCounts[$eduUnitId])) {
        $matchedKey = $eduUnitId;
    } elseif ($eduOtherUnitId && isset($reportCounts[$eduOtherUnitId])) {
        $matchedKey = $eduOtherUnitId;
    }

    if ($matchedKey) {
        $breakdown = $reportCounts[$matchedKey];
        // If we found a match in report, deeper logic might also fix start dates
        if (!$startYmd && isset($reportMeta[$matchedKey])) {
            $startYmd = $reportMeta[$matchedKey]['start_date'] ?? null;
        }
    }

    $block = getBlockLabelFromStartEnd($startYmd, $endYmd);

    $groups[] = [
        "scheduled_unit_id" => intval($id),
        "enrolled_count_live" => $currentParticipants,
        "eduUnitId" => $eduUnitId,
        "eduOtherUnitId" => $eduOtherUnitId,
        "campus" => $campus,
        "startDate" => $startDate,
        "block" => $block,
        "source" => "list_cache", // accurate enough
        "error" => null,
        "campus_breakdown" => $breakdown
    ];
}

echo json_encode([
    "generated_at" => gmdate("c"),
    "unique_groups" => count($groups),
    "unique_students" => $reportResult['unique_student_count'] ?? 0,
    "status_counts" => $reportResult['status_counts'] ?? ['Enrolled' => 0, 'Other' => 0],
    "campus_breakdown" => $reportCounts,
    "campus_breakdown_detail" => $reportResult['detailed'] ?? [],
    "unit_details" => $reportResult['unit_details'] ?? [],
    "risk_data" => $reportResult['student_risks'] ?? [],
    "retention_data" => $reportResult['retention_data'] ?? [],
    "meta" => $reportResult['meta'] ?? [],
    "groups" => $groups,
], JSON_UNESCAPED_SLASHES);