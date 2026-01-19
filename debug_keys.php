<?php
header("Content-Type: text/plain");
$config = require __DIR__ . '/config.php';
$url = $config['report_url'] . "&report_format=JSON&report_from_url=1&limit=5"; // Small limit
$user = $config['api_user'];
$pw = $config['api_pw'];

echo "Fetching: $url\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => $user . ":" . $pw,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
]);
$data = curl_exec($ch);
curl_close($ch);

if (!$data) {
    echo "No data returned.\n";
    exit;
}

echo "First 1000 bytes:\n" . substr($data, 0, 1000) . "\n\n";

$json = json_decode($data, true);
if (is_array($json)) {
    if (count($json) > 0) {
        $firstItem = is_array($json) ? (array_values($json)[0] ?? null) : $json;
        echo "Keys in first item:\n";
        print_r(array_keys($firstItem));
        echo "\nDumping first item:\n";
        print_r($firstItem);
    } else {
        echo "JSON array is empty.\n";
    }
} else {
    echo "Could not decode JSON or not an array.\n";
}
