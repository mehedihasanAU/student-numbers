<?php
// minimal_fetch.php
// A bare-bones script to test retrieving the JSON report.
// Usage: Visit https://as.aih.edu.au/.../minimal_fetch.php

ini_set('display_errors', 1);
error_reporting(E_ALL);
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
    $json = json_decode($result, true);
    if (!is_array($json)) {
        echo "ERROR: Could not decode JSON (Status: " . json_last_error_msg() . ")\n";
        echo "First 100 chars: " . substr($result, 0, 100) . "\n";
    } else {
        echo "JSON Parsed OK. Found " . count($json) . " rows.\n";
        if (count($json) > 0) {
            echo "--- First Row Keys Analysis ---\n";
            $firstRow = $json[0];
            foreach ($firstRow as $k => $v) {
                $kNorm = strtolower(str_replace(['_', ' '], '', $k));
                echo "Key: '$k' -> Norm: '$kNorm' (Value: " . substr(json_encode($v), 0, 20) . ")\n";
            }
        }
    }
} else {
    echo "FAILED.\n";
    echo "HTTP Code: " . $info['http_code'] . "\n";
    echo "Error: " . $err . "\n";
    echo "Time: " . $info['total_time'] . " seconds\n";

    if ($info['http_code'] == 0 && $info['total_time'] >= 10) {
        echo "\nCONCLUSION: TIMEOUT (Firewall Block confirmed).\n";
    }
}
