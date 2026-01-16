<?php
// debug_api.php
// Probes a single Scheduled Unit ID to see what the API returns.

header("Content-Type: text/plain");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Config
$apiUser = "A.Hasan";
$apiPw   = "AlphaUniform9";
$testId  = 15834; // One of the IDs from the user's list

$url = "https://aihe.edu.net.au/api/rest/ScheduledUnit/" . $testId;

echo "Fetching: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "$apiUser:$apiPw");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "User-Agent: Debug-Probe/1.0"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "cURL Error: $err\n";
echo "\nResponse Body:\n";
echo $response;
echo "\n\n";

// Attempt Parse
$json = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON Parse: OK\n";
    echo "Current Participants: " . ($json['currentParticipants'] ?? 'N/A') . "\n";
    echo "Unit Code: " . ($json['unitCode'] ?? 'N/A') . "\n";
    echo "EduOtherUnitId: " . ($json['eduOtherUnitId'] ?? 'N/A') . "\n";
} else {
    echo "JSON Parse: FAILED (" . json_last_error_msg() . ")\n";
}
?>
