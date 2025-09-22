<?php

// Simple test to understand Laravel Orion batch format
$baseUrl = 'http://127.0.0.1:8000';

// Step 1: Login
$loginData = [
    'email' => 'editor@example.com',
    'password' => 'password'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$loginResponse = json_decode($response, true);
$token = $loginResponse['token'];

echo "=== Testing different batch formats ===\n\n";

// Format 1: Simple array
echo "Format 1: Simple array\n";
$data1 = [
    'resources' => [
        [
            'name' => 'Simple User',
            'email' => 'simple_' . uniqid() . '@example.com',
            'password' => 'UniquePass' . rand(100,999) . '!#'
        ]
    ]
];

echo "Request: " . json_encode($data1, JSON_PRETTY_PRINT) . "\n";
$result1 = testBatch($baseUrl, $token, $data1);
echo "Result: HTTP {$result1['code']}\n";
echo "Response: " . json_encode($result1['response'], JSON_PRETTY_PRINT) . "\n\n";

// Format 2: With indexed keys  
echo "Format 2: With indexed keys\n";
$data2 = [
    'resources' => [
        0 => [
            'name' => 'Indexed User',
            'email' => 'indexed_' . uniqid() . '@example.com',
            'password' => 'UniquePass' . rand(100,999) . '!@'
        ]
    ]
];

echo "Request: " . json_encode($data2, JSON_PRETTY_PRINT) . "\n";
$result2 = testBatch($baseUrl, $token, $data2);
echo "Result: HTTP {$result2['code']}\n";
echo "Response: " . json_encode($result2['response'], JSON_PRETTY_PRINT) . "\n\n";

function testBatch($baseUrl, $token, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/users/batch");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        "Authorization: Bearer $token"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}