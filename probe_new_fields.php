<?php
$config = require 'config.php';
header('Content-Type: text/plain');

echo "--- Probing Report 11472 for New Fields ---\n";
echo "1. Fetching Report...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['report_url']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (!is_array($data) || count($data) === 0) {
    die("Report fetch failed or empty.\n");
}

echo "Report returned " . count($data) . " rows.\n\n";

// Fields to check
$targets = [
    'scheduled_unit_teacher_first_name',
    'scheduled_unit_teacher_last_name',
    'maximum_participants',
    'visa_expire_date',
    'progression_status_description',
    'course_start_date' // For cohort analysis
];

// Check coverage
$stats = array_fill_keys($targets, 0);
$samples = array_fill_keys($targets, []);

foreach ($data as $row) {
    foreach ($targets as $field) {
        if (isset($row[$field]) && $row[$field] !== '' && $row[$field] !== null) {
            $stats[$field]++;
            if (count($samples[$field]) < 3) {
                $samples[$field][] = $row[$field];
            }
        }
    }
}

echo "--- Field Coverage ---\n";
foreach ($targets as $field) {
    if (array_key_exists($field, $data[0])) {
        echo "[$field]: Found. Non-empty in " . $stats[$field] . " / " . count($data) . " rows.\n";
        echo "   Samples: " . implode(", ", $samples[$field]) . "\n";
    } else {
        echo "[$field]: NOT FOUND in dataset keys.\n";
    }
    echo "\n";
}
?>