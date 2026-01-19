<?php
ini_set('memory_limit', '1024M');
$config = require __DIR__ . '/config.php';
$url = $config['report_url'] . "&report_format=JSON&report_from_url=1&limit=100000";
$user = $config['api_user'];
$pw = $config['api_pw'];

echo "Fetching and analyzing: $url\n";

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Basic " . base64_encode("$user:$pw") . "\r\n",
        'timeout' => 300
    ]
]);

$fp = fopen($url, 'r', false, $ctx);
if (!$fp) die("Failed to open stream\n");

$buffer = '';
$depth = 0;
$inString = false;
$stats = [];
$allStatuses = [];

while (!feof($fp)) {
    $chunk = fread($fp, 8192);
    if ($chunk === false) break;
    $len = strlen($chunk);
    for ($i = 0; $i < $len; $i++) {
        $char = $chunk[$i];
        if ($char === '"' && ($i === 0 || $chunk[$i-1] !== '\\')) $inString = !$inString;
        if (!$inString && $char === '{') {
            if ($depth === 0) $buffer = '';
            $depth++;
        }
        if ($depth > 0) $buffer .= $char;
        if (!$inString && $char === '}') {
            $depth--;
            if ($depth === 0) {
                $row = json_decode($buffer, true);
                if ($row) processRow($row);
                $buffer = '';
            }
        }
    }
}
fclose($fp);

function processRow($row) {
    global $stats, $allStatuses;
    
    // Normalize keys
    $block = "Unknown";
    $status = "Unknown";
    $id = null;
    $unitCode = null;

    foreach ($row as $k => $v) {
        $kNorm = strtolower(str_replace(['_', ' '], '', $k));
        if (strpos($kNorm, 'termperiod') !== false) {
             // Extract Block
             $b = preg_replace('/^\d{4}\s*-\s*/', '', trim($v));
             $block = trim($b, " -");
        }
        if (strpos($kNorm, 'enrolmentstatus') !== false && strpos($kNorm, 'unit') !== false) {
            $status = $v;
        }
        if ($kNorm === 'studentnumber' || $kNorm === 'studentid') {
            $id = $v;
        }
        if ($kNorm === 'scheduledunitcode') $unitCode = $v;
    }

    if (!$id || !$unitCode) return;
    
    // Track stats
    if (!isset($stats[$block])) {
        $stats[$block] = ['total' => [], 'enrolled' => [], 'active' => []];
    }
    
    $stats[$block]['total'][$id] = true;
    
    if (stripos($status, 'Enrolled') !== false) {
        $stats[$block]['enrolled'][$id] = true;
    }
    if (stripos($status, 'Active') !== false) {
        $stats[$block]['active'][$id] = true;
    }
    
    if (!isset($allStatuses[$block])) $allStatuses[$block] = [];
    if (!isset($allStatuses[$block][$status])) $allStatuses[$block][$status] = 0;
    $allStatuses[$block][$status]++;
}

echo "\n--- Unique Student Counts Per Block ---\n";
printf("%-20s | %-10s | %-10s | %-10s\n", "Block", "Total", "Enrolled", "Active");
echo str_repeat("-", 60) . "\n";

ksort($stats);
foreach ($stats as $block => $counts) {
    printf("%-20s | %-10d | %-10d | %-10d\n", 
        substr($block, 0, 20), 
        count($counts['total']), 
        count($counts['enrolled']), 
        count($counts['active'])
    );
}

echo "\n--- Status Breakdown Per Block ---\n";
foreach ($allStatuses as $block => $sCounts) {
    echo "$block:\n";
    foreach ($sCounts as $s => $c) {
        echo "  - $s: $c rows\n";
    }
}
