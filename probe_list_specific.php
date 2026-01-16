<?php
$config = require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// We suspect 16157 is in Block 4 (B4).
// Let's check all dates just in case.
$targetId = 16157;
$foundCount = 0;

echo "Searching for Unit ID $targetId in all block dates...\n";

foreach ($config['block_dates'] as $date) {
    $url = $config['paradigm_host'] . "/api/rest/ScheduledUnit?startDate={$date}";

    // Simple curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $units = json_decode($response, true);
        if (is_array($units)) {
            foreach ($units as $unit) {
                // Check ID (mapped to id or scheduledUnitId)
                $uId = $unit['id'] ?? $unit['scheduledUnitId'] ?? $unit['eduScheduledUnitId'] ?? 'unknown';

                if ($uId == $targetId) {
                    $foundCount++;
                    echo "\n[MATCH #{$foundCount}] Date: $date\n";
                    echo "ID: " . $uId . "\n";
                    echo "Name: " . ($unit['unitName'] ?? 'N/A') . "\n";
                    echo "Code: " . ($unit['unitCode'] ?? 'N/A') . "\n";
                    echo "Participants: " . ($unit['currentParticipants'] ?? '0') . "\n";
                    echo "Structure:\n";
                    print_r($unit); // Print full structure of the first match to see duplicate info
                }
            }
        }
    } else {
        echo "Failed to fetch $date: HTTP $httpCode\n";
    }
}

if ($foundCount == 0) {
    echo "\nUnit $targetId NOT FOUND in any configured block dates.\n";
} else {
    echo "\nFound $foundCount occurrences of ID $targetId.\n";
}
?>