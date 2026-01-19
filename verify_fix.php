<?php
// verify_fix.php
ini_set('memory_limit', '512M');
$url = "https://as.aih.edu.au/student-numbers/test/scheduled_unit_counts.php?force=1"; // Force refresh to test new logic

echo "Fetching $url...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
// curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Match prod settings
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Error: HTTP $httpCode\nResponse: " . substr($response, 0, 500) . "\n");
}

$json = json_decode($response, true);
if (!$json) {
    die("Error: Invalid JSON\nResponse: " . substr($response, 0, 500) . "\n");
}

echo "API OK.\n";
echo "Active Unique Students: " . ($json['unique_student_count'] ?? 0) . "\n";

// Verify Groups (Inactive/Active List)
$groups = $json['groups'] ?? [];
$badCodes = 0;
$badCampuses = 0;
$sampleCode = "";
$sampleCampus = "";

echo "Checking " . count($groups) . " groups...\n";

foreach ($groups as $g) {
    $code = $g['unit_code'] ?? "Unknown";
    $campus = $g['campus'] ?? "Unknown";

    if ($code === "Unknown" || !preg_match('/^[A-Z]{3,4}\d{4}[a-z]?$/i', $code)) {
        if ($code !== "MATERIAL_FEE") { // Ignore known non-unit
            $badCodes++;
            if ($badCodes < 5)
                echo "Invalid Code: $code\n";
        }
    } else {
        if (!$sampleCode)
            $sampleCode = $code;
    }

    if (in_array($campus, ['AIHE', 'CAMPUS_MEL', 'CAMPUS_COMB'])) {
        $badCampuses++;
        if ($badCampuses < 5)
            echo "Unmapped Campus: $campus\n";
    } else {
        if (!$sampleCampus && $campus !== 'Unknown')
            $sampleCampus = $campus;
    }
}

echo "Invalid Unit Codes: $badCodes\n";
echo "Unmapped Campuses: $badCampuses\n";
echo "Sample Valid Code: $sampleCode\n";
echo "Sample Valid Campus: $sampleCampus\n";

// Verify Legacy Key
if (isset($json['campus_breakdown_detail'])) {
    echo "Legacy key 'campus_breakdown_detail' is PRESENT.\n";
} else {
    echo "Legacy key 'campus_breakdown_detail' is MISSING.\n";
}

if ($badCodes == 0 && $badCampuses == 0 && isset($json['campus_breakdown_detail'])) {
    echo "VERIFICATION PASSED.\n";
} else {
    echo "VERIFICATION FAILED.\n";
}
