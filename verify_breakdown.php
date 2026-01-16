<?php
$config = require 'config.php';
// Fetch the Dashboard backend directly
$url = $config['paradigm_host'] . "/student-numbers/test/scheduled_unit_counts.php?force=1"; // Force refresh to get new logic
// Wait, we can't call the localhost URL easily if we are ON the server, but let's try calling the public URL
// Or better, just include the script? No, it outputs headers.
// Let's us curl to self?
// Actually, let's just use the known URL.
$url = "https://as.aih.edu.au/student-numbers/test/scheduled_unit_counts.php";

echo "Fetching Backend from: $url\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (!is_array($data) || !isset($data['groups'])) {
    die("Failed to fetch groups. " . substr($response, 0, 200));
}

$foundBreakdown = 0;
foreach ($data['groups'] as $g) {
    if (!empty($g['campus_breakdown'])) {
        $foundBreakdown++;
        if ($foundBreakdown <= 3) {
            echo "Found Breakdown for Unit ID " . $g['scheduled_unit_id'] . ":\n";
            print_r($g['campus_breakdown']);
        }
    }
}

if ($foundBreakdown > 0) {
    echo "\nSUCCESS: Found campus breakdown in $foundBreakdown units.\n";
} else {
    echo "\nFAILURE: No campus breakdown found in any unit.\n";
}
?>