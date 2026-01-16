<?php
/**
 * scheduled_unit_sessions.php?id=XXXX
 * Returns JSON sessions for a scheduled unit.
 *
 * Strategy:
 * 1) Try the legacy AJAX endpoint you discovered:
 *    https://aihe.edu.net.au/php/scheduled_unit_session_edit.php?ajax=1&scheduled_unit_id=XXXX
 * 2) If it returns HTML, parse the sessions table into JSON.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-error.log');

header("Content-Type: application/json; charset=utf-8");

// ---- Config ----
$config = require_once __DIR__ . '/config.php';
$paradigmHost = $config['paradigm_host'];
$apiUser = $config['api_user'];
$apiPw = $config['api_pw'];

$CONNECT_TIMEOUT = 6;
$TIMEOUT = 18;

// -------------------- INPUT --------------------
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Missing/invalid id"], JSON_UNESCAPED_SLASHES);
    exit;
}

function curlGet($url, $apiUser, $apiPw, $connectTimeout, $timeout)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $apiUser . ":" . $apiPw,
        CURLOPT_HTTPHEADER => [
            "Accept: */*",
            "User-Agent: AIHE-Enrolment-Insights/1.0"
        ],
    ]);

    $body = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    return [$http, $ct, $err ?: null, $body ?: ""];
}

function isJsonCt($ct)
{
    return is_string($ct) && stripos($ct, "application/json") !== false;
}

/**
 * Attempt to parse sessions from HTML table.
 * We’ll map columns by header names (loose matching).
 */
function parseSessionsFromHtml($html)
{
    $out = [];

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();

    // Many pages aren't UTF-8 clean; this wrapper helps
    $wrapped = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . $html;
    if (!$dom->loadHTML($wrapped))
        return $out;

    $xp = new DOMXPath($dom);

    // Find the first table that has at least one row
    $tables = $xp->query("//table");
    if (!$tables || $tables->length === 0)
        return $out;

    $bestTable = null;
    foreach ($tables as $t) {
        $rows = $xp->query(".//tr", $t);
        if ($rows && $rows->length >= 2) {
            $bestTable = $t;
            break;
        }
    }
    if (!$bestTable)
        $bestTable = $tables->item(0);

    // Headers
    $headers = [];
    $thNodes = $xp->query(".//tr[1]//th", $bestTable);
    if ($thNodes && $thNodes->length > 0) {
        foreach ($thNodes as $th) {
            $headers[] = strtolower(trim(preg_replace('/\s+/', ' ', $th->textContent)));
        }
    } else {
        // Sometimes header row uses td
        $tdNodes = $xp->query(".//tr[1]//td", $bestTable);
        foreach ($tdNodes as $td) {
            $headers[] = strtolower(trim(preg_replace('/\s+/', ' ', $td->textContent)));
        }
    }

    // Helper to find header index by keywords
    $findIdx = function ($keywords) use ($headers) {
        foreach ($headers as $i => $h) {
            foreach ($keywords as $k) {
                if (strpos($h, $k) !== false)
                    return $i;
            }
        }
        return -1;
    };

    $idxSubject = $findIdx(["session subject", "subject"]);
    $idxRoom = $findIdx(["room"]);
    $idxGroup = $findIdx(["group number", "group"]);
    $idxCurr = $findIdx(["current participants", "current"]);

    // Data rows start from 2nd tr
    $rows = $xp->query(".//tr[position()>1]", $bestTable);
    foreach ($rows as $r) {
        $cells = $xp->query(".//td", $r);
        if (!$cells || $cells->length === 0)
            continue;

        $get = function ($idx) use ($cells) {
            if ($idx < 0 || $idx >= $cells->length)
                return "";
            return trim(preg_replace('/\s+/', ' ', $cells->item($idx)->textContent));
        };

        $subject = $get($idxSubject);
        $room = $get($idxRoom);
        $groupNo = $get($idxGroup);
        $curr = $get($idxCurr);

        // If the row is totally empty, skip
        if ($subject === "" && $room === "" && $groupNo === "" && $curr === "")
            continue;

        $out[] = [
            "session_subject" => $subject,
            "room" => $room,
            "group_number" => $groupNo,
            "current_participants" => ($curr === "" ? null : (is_numeric($curr) ? intval($curr) : $curr)),
        ];
    }

    return $out;
}

// -------------------- TRY LEGACY AJAX ENDPOINT --------------------
$tested = [];
$urlsToTry = [
    rtrim($paradigmHost, "/") . "/php/scheduled_unit_session_edit.php?ajax=1&scheduled_unit_id=" . $id,
    rtrim($paradigmHost, "/") . "/php/scheduled_unit_session_edit.php?ajax=1&id=" . $id,
    rtrim($paradigmHost, "/") . "/php/scheduled_unit_session_edit.php?ajax=1&eduScheduledUnitId=" . $id,
];

$sessions = null;
$rawSnippet = null;
$finalErr = null;

foreach ($urlsToTry as $u) {
    [$http, $ct, $err, $body] = curlGet($u, $apiUser, $apiPw, $CONNECT_TIMEOUT, $TIMEOUT);

    $tested[] = [
        "url" => $u,
        "http" => $http,
        "content_type" => $ct,
        "curl_error" => $err,
        "body_snippet" => substr($body, 0, 240),
    ];

    if ($http < 200 || $http >= 300) {
        $finalErr = "HTTP $http";
        continue;
    }

    // If JSON, return as-is if we can find a sessions array
    if (isJsonCt($ct)) {
        $j = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($j)) {
            // Try common shapes
            if (isset($j["sessions"]) && is_array($j["sessions"])) {
                $sessions = $j["sessions"];
                break;
            }
            if (isset($j["data"]) && is_array($j["data"])) {
                $sessions = $j["data"];
                break;
            }
            if (isset($j["items"]) && is_array($j["items"])) {
                $sessions = $j["items"];
                break;
            }
            // If it's an array itself
            if (array_keys($j) === range(0, count($j) - 1)) {
                $sessions = $j;
                break;
            }
        }
    }

    // Otherwise parse HTML table
    $parsed = parseSessionsFromHtml($body);
    if (!empty($parsed)) {
        $sessions = $parsed;
        break;
    }

    $rawSnippet = substr($body, 0, 800);
}

if ($sessions === null) {
    echo json_encode([
        "ok" => false,
        "generated_at" => gmdate("c"),
        "scheduled_unit_id" => $id,
        "error" => $finalErr ?: "Could not parse sessions from endpoint response.",
        "tested" => $tested,
        "raw_snippet" => $rawSnippet,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

// Normalize sessions rows for your dashboard filters
$norm = [];
foreach ($sessions as $s) {
    if (!is_array($s))
        continue;
    $norm[] = [
        "session_subject" => $s["session_subject"] ?? $s["Session Subject"] ?? $s["subject"] ?? ($s["name"] ?? ""),
        "room" => $s["room"] ?? $s["Room"] ?? ($s["location"] ?? ""),
        "group_number" => $s["group_number"] ?? $s["Group Number"] ?? ($s["group"] ?? ""),
        "current_participants" => $s["current_participants"] ?? $s["Current Participants"] ?? ($s["current"] ?? null),
    ];
}

echo json_encode([
    "ok" => true,
    "generated_at" => gmdate("c"),
    "scheduled_unit_id" => $id,
    "count" => count($norm),
    "sessions" => $norm,
    "tested" => $tested,
], JSON_UNESCAPED_SLASHES);