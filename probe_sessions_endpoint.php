<?php
header("Content-Type: application/json; charset=utf-8");
set_time_limit(8);
ini_set('max_execution_time', '8');

// ---- Config ----
$paradigmHost = "https://aihe.edu.net.au"; // e.g. https://abc.edu.net.au  :contentReference[oaicite:3]{index=3}
$apiUser      = "A.Hasan";
$apiPw        = "AlphaUniform9";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(["error"=>"Missing/invalid id"]); exit; }

function httpGet($url, $user, $pw) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_TIMEOUT        => 4,
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
    CURLOPT_USERPWD        => "{$user}:{$pw}",
    CURLOPT_HTTPHEADER     => ["Accept: application/json"],
  ]);
  $body = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $ct   = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  curl_close($ch);
  return ["code"=>$code,"ct"=>$ct,"err"=>$err,"body"=>$body];
}

$candidates = [
  "{$paradigmHost}/api/rest/ScheduledUnit/{$id}/Session",
  "{$paradigmHost}/api/rest/ScheduledUnit/{$id}/Sessions",
  "{$paradigmHost}/api/rest/ScheduledUnitSession/?eduScheduledUnitId={$id}",
  "{$paradigmHost}/api/rest/ScheduledUnitSessions/?eduScheduledUnitId={$id}",
  "{$paradigmHost}/api/rest/TimetableSession/?eduScheduledUnitId={$id}",
  "{$paradigmHost}/api/rest/ClassSession/?eduScheduledUnitId={$id}",
];

$results = [];
foreach ($candidates as $u) {
  $r = httpGet($u, $apiUser, $apiPw);
  $snippet = substr((string)$r["body"], 0, 160);

  $isJson = false;
  $j = json_decode((string)$r["body"], true);
  if (is_array($j)) $isJson = true;

  $results[] = [
    "url" => $u,
    "http" => $r["code"],
    "content_type" => $r["ct"],
    "curl_error" => $r["err"] ?: null,
    "json" => $isJson,
    "body_snippet" => $snippet
  ];
}

echo json_encode([
  "generated_at" => date("c"),
  "scheduled_unit_id" => $id,
  "tested" => $results
], JSON_PRETTY_PRINT);
