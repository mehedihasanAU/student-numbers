<?php
header("Content-Type: application/json");
$config = require __DIR__ . '/config.php';
$user = $config['api_user'];
$pw = $config['api_pw'];
$url = $config['paradigm_host'] . "/api/rest/ScheduledUnit?startDate=20260223"; // Block 1 start date
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "$user:$pw");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
$res = curl_exec($ch);
curl_close($ch);
$json = json_decode($res, true);
if (is_array($json) && count($json) > 0) {
    echo json_encode(['first_item_keys' => array_keys($json[0]), 'first_item' => $json[0]]);
} else {
    echo json_encode(['error' => 'No data or fetch failed', 'raw' => substr($res, 0, 500)]);
}
