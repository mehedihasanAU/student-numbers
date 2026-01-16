<?php
$config = require 'config.php';
$unitId = 16157;

function probeEndpoint($config, $endpoint)
{
    echo "Probing $endpoint...\n";
    $url = $config['paradigm_host'] . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['api_user'] . ":" . $config['api_pw']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Status: $httpCode\n";
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "Count: " . (is_array($data) ? count($data) : 'Not Array') . "\n";
        print_r($data);
    } else {
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
    echo "--------------------------------------------------\n";
}

// Try common REST patterns
probeEndpoint($config, "/api/rest/Session?scheduledUnitId=$unitId");
probeEndpoint($config, "/api/rest/Activity?scheduledUnitId=$unitId");
probeEndpoint($config, "/api/rest/ScheduledUnitSession?scheduledUnitId=$unitId");
?>