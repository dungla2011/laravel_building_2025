<?php

// Test User Validation Rules

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

// Test cases
$testCases = [
    [
        'name' => '🧪 Test 1: Invalid Name (forbidden word)',
        'data' => [
            'name' => 'Admin User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!'
        ],
        'expected_error' => 'name'
    ],
    [
        'name' => '🧪 Test 2: Invalid Email (blocked domain)',
        'data' => [
            'name' => 'Valid User',
            'email' => 'test@10minutemail.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!'
        ],
        'expected_error' => 'email'
    ],
    [
        'name' => '🧪 Test 3: Weak Password',
        'data' => [
            'name' => 'Valid User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak'
        ],
        'expected_error' => 'password'
    ],
    [
        'name' => '🧪 Test 4: Valid Data',
        'data' => [
            'name' => 'John Doe',
            'email' => 'john_' . uniqid() . '@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!'
        ],
        'expected_error' => null
    ]
];

foreach ($testCases as $test) {
    echo $test['name'] . "\n";
    echo str_repeat("-", 50) . "\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/users");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        "Authorization: Bearer $token"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($httpCode === 422) {
        echo "❌ Validation Error (Expected):\n";
        if (isset($responseData['errors'])) {
            foreach ($responseData['errors'] as $field => $errors) {
                echo "  - $field: " . implode(', ', $errors) . "\n";
            }
        }
        
        if ($test['expected_error'] && isset($responseData['errors'][$test['expected_error']])) {
            echo "✅ Test PASSED - Expected validation error for '{$test['expected_error']}'\n";
        } else {
            echo "⚠️ Test result unclear\n";
        }
    } elseif ($httpCode === 201) {
        echo "✅ User created successfully\n";
        if ($test['expected_error'] === null) {
            echo "✅ Test PASSED - Valid data accepted\n";
        } else {
            echo "❌ Test FAILED - Expected validation error but user was created\n";
        }
    } else {
        echo "⚠️ Unexpected response: $httpCode\n";
        if (isset($responseData['message'])) {
            echo "Message: {$responseData['message']}\n";
        }
    }
    
    echo "\n";
}