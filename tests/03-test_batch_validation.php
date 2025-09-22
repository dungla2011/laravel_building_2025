<?php

// Test Batch Validation with Weak Password

$baseUrl = 'http://127.0.0.1:8000';

// Step 1: Login to get token
echo "🔐 Getting authentication token...\n";
$loginData = json_encode(['email' => 'editor@example.com', 'password' => 'password']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$loginResponse = json_decode($response, true);
if (!isset($loginResponse['token'])) {
    die("❌ Login failed\n");
}

$token = $loginResponse['token'];
echo "✅ Authentication successful\n\n";

// Test 1: Batch create with weak passwords (should fail)
echo "🧪 Test 1: Batch create with WEAK passwords (should fail validation)\n";
echo str_repeat("-", 60) . "\n";

$weakPasswordData = [
    'resources' => [
        [
            'name' => 'User One',
            'email' => 'weak_user1_' . uniqid() . '@example.com',
            'password' => 'weak' // This should fail validation
        ],
        [
            'name' => 'User Two',
            'email' => 'weak_user2_' . uniqid() . '@example.com',
            'password' => 'password123' // This should also fail (no uppercase, no symbols)
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/users/batch");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($weakPasswordData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$responseData = json_decode($response, true);

if ($httpCode === 422) {
    echo "✅ EXPECTED: Validation failed (HTTP 422)\n";
    if (isset($responseData['errors'])) {
        foreach ($responseData['errors'] as $field => $errors) {
            echo "  - $field: " . implode(', ', $errors) . "\n";
        }
    }
} else {
    echo "❌ UNEXPECTED: Should have failed validation but got HTTP $httpCode\n";
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

// Test 2: Batch create with strong passwords (should succeed)
echo "🧪 Test 2: Batch create with STRONG passwords (should succeed)\n";
echo str_repeat("-", 60) . "\n";

$strongPasswordData = [
    'resources' => [
        [
            'name' => 'User Alpha',
            'email' => 'strong_user1_' . uniqid() . '@example.com',
            'password' => 'Complex!9Pwd'
        ],
        [
            'name' => 'User Beta',
            'email' => 'strong_user2_' . uniqid() . '@example.com',
            'password' => 'Secure#8Pass'
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/users/batch");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($strongPasswordData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$responseData = json_decode($response, true);

if ($httpCode === 200) {
    echo "✅ EXPECTED: Users created successfully (HTTP 200)\n";
} else {
    echo "❌ UNEXPECTED: Should have succeeded but got HTTP $httpCode\n";
    if (isset($responseData['errors'])) {
        foreach ($responseData['errors'] as $field => $errors) {
            echo "  - $field: " . implode(', ', $errors) . "\n";
        }
    }
}

echo "\n=== BATCH VALIDATION TEST COMPLETED ===\n";