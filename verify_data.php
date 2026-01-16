<?php
require 'config.php';

// 1. Fetch live data using the same logic as scheduled_unit_counts.php
// We reuse the basic structure but only care about the list of IDs

function getCombinedScheduledUnits($config)
{
    $allUnits = [];
    $mh = curl_multi_init();
    $handles = [];

    // Prioritize B1, B2, B3, B4 for 2026 (based on config)
    // We trust config.php has the correct dates.
    foreach ($config['block_dates'] as $date) {
        $url = $config['paradigm_host'] . "/api/rest/ScheduledUnit?startDate={$date}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_multi_add_handle($mh, $ch);
        $handles[$date] = $ch;
    }

    $active = null;
    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($active);

    foreach ($handles as $date => $ch) {
        $response = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($httpCode !== 200) {
            echo "[DEBUG] Failed for date {$date}. HTTP: {$httpCode}. Error: {$error}\n";
            echo "[DEBUG] Response: " . substr($response, 0, 100) . "...\n";
        }

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (is_array($data)) {
                $allUnits = array_merge($allUnits, $data);
            } else {
                echo "[DEBUG] JSON Decode failed for {$date}. Response snippet: " . substr($response, 0, 50) . "\n";
            }
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    // Extract strictly the IDs
    $ids = [];
    foreach ($allUnits as $unit) {
        if (isset($unit['id'])) {
            $ids[] = $unit['id'];
        }
    }
    return array_unique($ids);
}

// 2. Read the CSV file
$csvFile = 'reference_timetable.csv';
$csvIds = [];
$handle = fopen($csvFile, "r");
if ($handle) {
    // Read header
    $header = fgetcsv($handle);
    // Find 'Sched unit ID' index
    $idIndex = array_search('Sched unit ID', $header);

    if ($idIndex !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            if (isset($row[$idIndex]) && is_numeric($row[$idIndex])) {
                $csvIds[] = intval($row[$idIndex]);
            }
        }
    }
    fclose($handle);
}

// 3. Compare
$csvIds = array_unique($csvIds);
$fetchedIds = getCombinedScheduledUnits($config);

$missingInFetch = array_diff($csvIds, $fetchedIds);
$extraInFetch = array_diff($fetchedIds, $csvIds);

echo "Total IDs in CSV (sample): " . count($csvIds) . "\n";
echo "Total IDs fetched from API: " . count($fetchedIds) . "\n";

if (!empty($missingInFetch)) {
    echo "\n[WARNING] The following IDs are in the CSV but NOT being fetched by your dashboard:\n";
    echo implode(", ", $missingInFetch) . "\n";
    echo "This indicates that 'config.php' might be missing some block start dates.\n";
} else {
    echo "\n[SUCCESS] All IDs in the provided CSV sample are successfully being fetched by the dashboard.\n";
}

// Optional: Output extra IDs
// echo "\n[INFO] IDs fetched but not in CSV (Sample): " . count($extraInFetch) . "\n";

?>