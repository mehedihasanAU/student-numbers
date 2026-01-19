<?php
// config.php

return [
    'paradigm_host' => 'https://aihe.edu.net.au',
    'api_user' => 'A.Hasan',
    'api_pw' => 'AlphaUniform9',
    'report_url' => 'https://aihe.edu.net.au/php/external_report_builder_call_httpauth.php?report_id=11472',

    // Performance settings
    'connect_timeout' => 6,
    'timeout' => 12,
    'concurrency' => 10,

    // Block Start Dates for 2026 (Found via analysis of debug dump)
    'block_dates' => [
        '2026-01-08', // Summer School / Key Intake (500 units found)
        '2026-02-23', // Block 1
        '2026-03-23', // Block 2
        '2026-05-04', // Block 3
        '2026-06-01', // Block 4
        '2026-07-06', // Winter School
        '2026-08-03', // Block 5
        '2026-09-07', // Block 6
        '2026-10-12', // Block 7
        '2026-11-16', // Block 8
    ],
    'admin_password' => 'Admin2026!' // Simple shared request
];
