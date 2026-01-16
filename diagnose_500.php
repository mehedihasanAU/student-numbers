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

echo "\n--- STEP 4: FETCH TEST (PARADIGM) ---\n";
echo "Attempting connectivity check to: " . $config['report_url'] . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['report_url'] . "&limit=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Force IPv4
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36"); // Mimic Browser

$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode == 200) {
    echo "Success: Paradigm Connectivity OK.\n";
} else {
    echo "FAILURE: Paradigm Request Failed.\n";
    echo "Error: $err\n";
}

echo "\n--- STEP 5: CONTROL TEST (GOOGLE) ---\n";
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, "https://www.google.com");
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

if ($httpCode2 >= 200 && $httpCode2 < 400) {
    echo "Control Test (Google): SUCCESS (Server has Internet).\n";
} else {
    echo "Control Test (Google): FAILED (Server might be offline or blocked completely).\n";
}

echo "\n--- DIAGNOSTIC END ---\n";
