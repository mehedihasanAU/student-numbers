<?php
$config = require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "--- Probing for Lecturer/Staff Data ---\n";

// 1. Check API (Scheduled Unit)
$date = $config['block_dates'][0]; // Use first start date
$url = $config['paradigm_host'] . "/api/rest/ScheduledUnit?startDate={$date}";
echo "\n1. Fetching API Sample from: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$apiData = json_decode($response, true);
if (is_array($apiData) && count($apiData) > 0) {
    echo "API returned " . count($apiData) . " units.\n";
    echo "Keys in first API unit:\n";
    $first = $apiData[0];
    print_r(array_keys($first));

    // Search deep for staff keywords
    echo "Searching API data for 'staff', 'lecturer', 'teacher', 'instructor'...\n";
    $found = false;
    foreach ($first as $k => $v) {
        if (preg_match('/staff|lecturer|teacher|instructor|user/i', $k)) {
            echo "  Found key: [$k] => " . print_r($v, true) . "\n";
            $found = true;
        }
    }
    if (!$found)
        echo "  No obvious staff keys found in API object.\n";
} else {
    echo "API fetch failed or empty.\n";
}

// 2. Check Report 11472
$reportUrl = $config['report_url'];
echo "\n2. Fetching Report 11472...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $reportUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$reportData = json_decode($response, true);
if (is_array($reportData) && count($reportData) > 0) {
    echo "Report returned " . count($reportData) . " rows.\n";
    echo "Keys in first Report row:\n";
    $first = $reportData[0];
    print_r(array_keys($first));

    echo "Searching Report for 'staff', 'lecturer', 'teacher', 'instructor', 'name'...\n";
    foreach ($first as $k => $v) {
        if (preg_match('/staff|lecturer|teacher|instructor|name|coordinator/i', $k)) {
            echo "  Found key: [$k]\n";
            // Show a few distinct values
            $values = [];
            foreach (array_slice($reportData, 0, 20) as $row) {
                if (isset($row[$k]))
                    $values[] = $row[$k];
            }
            echo "    Sample values: " . implode(", ", array_unique($values)) . "\n";
        }
    }
} else {
    echo "Report fetch failed or empty.\n";
}
?>