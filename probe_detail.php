<?php
$config = require 'config.php';
$unitId = 16157; // User provided example

// 1. Fetch Unit Details from REST API
$url = $config['paradigm_host'] . "/api/rest/ScheduledUnit/" . $unitId;
echo "Fetching Unit Details from: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

echo "--- Unit API Response ---\n";
print_r(json_decode($response, true));


// 2. Fetch Sessions - SKIPPED (Cloudflare conflict identified)
echo "\n--- (Sessions Probe Skipped) ---\n";
?>