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


// 2. Fetch Sessions using the legacy logic (copied from scheduled_unit_sessions.php)
$sessionUrl = $config['paradigm_host'] . "/admin/web_scheduled_unit_sessions_list_ajax.php?scheduled_unit_id=" . $unitId;
echo "\n\nFetching Sessions from: $sessionUrl\n";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $sessionUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch2, CURLOPT_COOKIE, "Paradigm=$session_id_placeholder"); // Might need a real session? The original script didn't seem to have one, relies on Basic Auth usually?
// actually checking original script, it uses Basic Auth for the Report Builder but the AJAX one might need headers.
// Let's just try Basic Auth first as used in api.
curl_setopt($ch2, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);

$sessionResponse = curl_exec($ch2);
curl_close($ch2);

echo "--- Session AJAX Response (Snippet) ---\n";
echo substr($sessionResponse, 0, 500) . "...\n";

?>