<?php
$config = require 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting duplicate investigation...\n";

if (!function_exists('curl_multi_init')) {
    die("CRITICAL: curl_multi_init is NOT available on this server.\n");
}

function getAndInspectUnits($config, $targetId)
{
    echo "Fetching for dates: " . implode(", ", $config['block_dates']) . "\n";
    $allUnits = [];
    $mh = curl_multi_init();
    $handles = [];

    // Use proven curl options from verify_data.php
    foreach ($config['block_dates'] as $date) {
        $url = $config['paradigm_host'] . "/api/rest/ScheduledUnit?startDate={$date}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Important match
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

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (is_array($data)) {
                $allUnits = array_merge($allUnits, $data);
            }
        } else {
            echo "[WARN] Failed for $date: $httpCode\n";
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    echo "Total units fetched: " . count($allUnits) . "\n";

    // Analyze Target ID
    $found = [];
    foreach ($allUnits as $unit) {
        // Check both ID fields
        $id = $unit['id'] ?? $unit['eduScheduledUnitId'] ?? null;
        if ($id == $targetId) {
            $found[] = $unit;
        }
    }

    echo "Occurrences of ID $targetId: " . count($found) . "\n";

    foreach ($found as $i => $u) {
        echo "--- Occurrence #" . ($i + 1) . " ---\n";
        echo "Name: " . ($u['unitName'] ?? 'N/A') . "\n";
        echo "Participants: " . ($u['currentParticipants'] ?? 'N/A') . "\n";
        echo "Campus: " . ($u['campusName'] ?? 'N/A') . "\n"; // Guessing field name
        echo "Location: " . ($u['location'] ?? 'N/A') . "\n";
        // Print full JSON for the first one to debug structure
        if ($i === 0) {
            echo "Full JSON structure:\n";
            print_r($u);
        }
    }
}

getAndInspectUnits($config, 16157);
?>