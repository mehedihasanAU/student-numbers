<?php
// minimal_fetch.php
// A bare-bones script to test retrieving the JSON report.
// Usage: Visit https://as.aih.edu.au/.../minimal_fetch.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '512M'); // Bump memory for large JSON decode
header('Content-Type: text/plain');

$config = require __DIR__ . '/config.php';
$url = $config['report_url'] . "&limit=1&report_format=JSON&report_from_url=1"; // Fetch only 1 record, ensure JSON

echo "Target: $url\n\n";
echo "Connecting...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increased timeout to 60s
curl_setopt($ch, CURLOPT_VERBOSE, true); // Show debug info if possible on output

// Force IPv4 just in case
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$result = curl_exec($ch);
$info = curl_getinfo($ch);
$err = curl_error($ch);
curl_close($ch);

if ($result) {
    echo "SUCCESS! Data received (" . strlen($result) . " bytes).\n\n";
    // MEMORY OPTIMIZATION:
    // The full JSON is 31MB -> 300MB RAM to decode. Server limit is 128MB.
    // We only need the KEYS from the first row.
    // So we grab the first 5000 chars and parse the first object manually.

    $chunk = substr($result, 0, 5000);
    $startPos = strpos($chunk, '{');
    if ($startPos !== false) {
        // Find the matching closing brace for the first object
        // Simple search for "}," which usually ends the first object in a list array
        $endPos = strpos($chunk, '},', $startPos);
        if ($endPos !== false) {
            $firstObjectJson = substr($chunk, $startPos, $endPos - $startPos + 1); // include }
            $firstRow = json_decode($firstObjectJson, true);

            if (is_array($firstRow)) {
                echo "JSON Snippet Parsed OK. Analyzing First Row Keys:\n";
                echo "------------------------------------------------\n";
                foreach ($firstRow as $k => $v) {
                    $kNorm = strtolower(str_replace(['_', ' '], '', $k));
                    echo "Key: '$k' -> Norm: '$kNorm' (Value: " . substr(json_encode($v), 0, 20) . ")\n";
                }
                exit; // Done
            }
        }
    }

    echo "ERROR: Could not parse first object from snippet.\n";
    echo "First 500 chars:\n" . substr($chunk, 0, 500) . "\n";

} else {
    echo "FAILED.\n";
    echo "HTTP Code: " . $info['http_code'] . "\n";
    echo "Error: " . $err . "\n";
    echo "Time: " . $info['total_time'] . " seconds\n";

    if ($info['http_code'] == 0 && $info['total_time'] >= 10) {
        echo "\nCONCLUSION: TIMEOUT (Firewall Block confirmed).\n";
    }
}
