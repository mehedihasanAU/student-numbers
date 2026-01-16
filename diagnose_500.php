<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "--- DIAGNOSTIC START ---\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Time Limit: " . ini_get('max_execution_time') . "\n";

echo "\n--- STEP 1: LOADING CONFIG ---\n";
if (!file_exists(__DIR__ . '/config.php')) {
    die("FATAL: config.php not found!");
}
try {
    $config = require __DIR__ . '/config.php';
    echo "Config loaded successfully.\n";
    echo "API Host: " . ($config['paradigm_host'] ?? 'MISSING') . "\n";
} catch (Throwable $e) {
    echo "FATAL ERROR loading config: " . $e->getMessage() . "\n";
    exit;
}

echo "\n--- STEP 2: CHECKING EXTENSIONS ---\n";
if (!function_exists('curl_init')) {
    echo "FATAL: cURL extension is missing!\n";
} else {
    echo "cURL is available.\n";
}

if (!function_exists('json_decode')) {
    echo "FATAL: JSON extension is missing!\n";
} else {
    echo "JSON is available.\n";
}

echo "\n--- STEP 3: CHECKING CACHE PERMISSIONS ---\n";
$cacheDir = __DIR__ . "/.cache_paradigm";
echo "Cache Dir: $cacheDir\n";
if (!file_exists($cacheDir)) {
    echo "Cache directory does not exist. Attempting creation...\n";
    if (@mkdir($cacheDir, 0755, true)) {
        echo "Success: Created cache directory.\n";
    } else {
        echo "WARNING: Failed to create cache directory. Check parent folder permissions.\n";
    }
} else {
    echo "Cache directory exists.\n";
    if (is_writable($cacheDir)) {
        echo "Success: Cache directory is writable.\n";
    } else {
        echo "WARNING: Cache directory is NOT writable.\n";
    }
}

echo "\n--- STEP 4: FETCH TEST ---\n";
echo "Attempting simple connectivity check to: " . $config['report_url'] . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['report_url'] . "&limit=1"); // Tiny fetch
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode == 200) {
    echo "Success: Connectivity OK.\n";
    echo "Data Length: " . strlen($data) . " bytes\n";
} else {
    echo "FAILURE: cURL Request Failed.\n";
    echo "Error: $err\n";
    echo "Response Snippet: " . substr($data, 0, 200) . "\n";
}

echo "\n--- DIAGNOSTIC END ---\n";
