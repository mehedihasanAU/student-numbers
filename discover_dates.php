<?php
$config = require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Scan first 3 months of 2026
$startDate = new DateTime('2026-01-01');
$endDate = new DateTime('2026-04-01');

echo "Scanning for valid start dates between {$startDate->format('Y-m-d')} and {$endDate->format('Y-m-d')}...\n";

$datesToScan = [];
while ($startDate <= $endDate) {
    $datesToScan[] = $startDate->format('Y-m-d');
    $startDate->modify('+1 day');
}

// Batches of 20
$batches = array_chunk($datesToScan, 20);

foreach ($batches as $batch) {
    $mh = curl_multi_init();
    $handles = [];

    foreach ($batch as $date) {
        $url = $config['paradigm_host'] . "/api/rest/ScheduledUnit?startDate={$date}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        curl_multi_add_handle($mh, $ch);
        $handles[$date] = $ch;
    }

    $active = null;
    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($active);

    foreach ($handles as $date => $ch) {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content = curl_multi_getcontent($ch);

        if ($httpCode === 200) {
            $data = json_decode($content, true);
            $count = is_array($data) ? count($data) : 0;
            if ($count > 0) {
                echo "[FOUND] {$date}: {$count} units\n";
            }
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    // Tiny sleep to be nice
    usleep(100000);
}
echo "Scan complete.\n";
