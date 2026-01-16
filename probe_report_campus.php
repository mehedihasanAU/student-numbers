<?php
$config = require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$reportUrl = $config['report_url'];
echo "Fetching Report from: $reportUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $reportUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!is_array($data)) {
    die("Failed to decode JSON or empty report.\nSample: " . substr($response, 0, 100));
}

echo "Total Rows: " . count($data) . "\n";

// Keys to inspect for containing 'AIHE', 'MEL', 'SYD'
$potentialKeys = [
    'location',
    'location_code',
    'campus',
    'campus_code',
    'home_institution',
    'home_institution_code',
    'institution',
    'offering_location',
    'site',
    'mode' // sometimes mixed with location
];

$valueCounts = [];

// inspect first row to see available keys
if (count($data) > 0) {
    echo "\n--- Available Keys in First Row ---\n";
    print_r(array_keys($data[0]));

    // Auto-detect keys if the list above is missing some
    $potentialKeys = array_unique(array_merge($potentialKeys, array_keys($data[0])));
}

foreach ($data as $row) {
    foreach ($potentialKeys as $key) {
        if (isset($row[$key])) {
            $val = $row[$key];
            if (!isset($valueCounts[$key][$val])) {
                $valueCounts[$key][$val] = 0;
            }
            $valueCounts[$key][$val]++;
        }
    }
}

echo "\n--- Value Distributions ---\n";
foreach ($valueCounts as $key => $counts) {
    // Only show interesting keys (those with multiple values or matching keyword)
    if (count($counts) > 1 || stripos($key, 'campus') !== false || stripos($key, 'loc') !== false) {
        echo "Key: [$key]\n";
        foreach ($counts as $val => $count) {
            echo "  '$val': $count\n";
        }
        echo "\n";
    }
}
?>