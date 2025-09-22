<?php

/**
 * Simple API Test - Login and List Users
 * 
 * This is the most basic test to verify API authentication and authorization works:
 * 1. Login to get Bearer token
 * 2. Use token to access /api/users endpoint
 */

echo "=== SIMPLE API TEST: LOGIN & LIST USERS ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Configuration
$baseUrl = 'http://localhost:8000';
$testUsers = [
    'superadmin@example.com' => 'Super Admin',
    'admin@example.com' => 'Admin', 
    'viewer@example.com' => 'Viewer',
    'editor@example.com' => 'Editor'
];

function makeApiRequest($method, $url, $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => array_merge([
            'Accept: application/json',
            'Content-Type: application/json'
        ], $headers)
    ]);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => $error,
            'status_code' => 0
        ];
    }
    
    return [
        'success' => true,
        'status_code' => $httpCode,
        'body' => $response,
        'data' => json_decode($response, true)
    ];
}

function testUserLogin($email, $password, $baseUrl) {
    echo "🔐 Testing login for: $email\n";
    
    $loginData = [
        'email' => $email,
        'password' => $password
    ];
    
    $response = makeApiRequest('POST', "$baseUrl/api/login", $loginData);
    
    if (!$response['success']) {
        echo "   ❌ CURL Error: {$response['error']}\n";
        return null;
    }
    
    if ($response['status_code'] !== 200) {
        echo "   ❌ Login failed - Status: {$response['status_code']}\n";
        echo "   Response: " . substr($response['body'], 0, 200) . "\n";
        return null;
    }
    
    $token = $response['data']['token'] ?? null;
    if (!$token) {
        echo "   ❌ No token in response\n";
        echo "   Response: " . $response['body'] . "\n";
        return null;
    }
    
    echo "   ✅ Login successful - Token: " . substr($token, 0, 20) . "...\n";
    return $token;
}

function testApiAccess($token, $baseUrl, $userEmail) {
    echo "🔍 Testing API access for: $userEmail\n";
    
    $headers = ["Authorization: Bearer $token"];
    $response = makeApiRequest('GET', "$baseUrl/api/users", null, $headers);
    
    if (!$response['success']) {
        echo "   ❌ CURL Error: {$response['error']}\n";
        return false;
    }
    
    echo "   📊 Status Code: {$response['status_code']}\n";
    
    if ($response['status_code'] === 200) {
        $users = $response['data']['data'] ?? [];
        $userCount = is_array($users) ? count($users) : 0;
        echo "   ✅ SUCCESS - Retrieved $userCount users\n";
        
        // Show first few users
        if ($userCount > 0) {
            echo "   👥 Sample users:\n";
            foreach (array_slice($users, 0, 3) as $user) {
                echo "      - ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}\n";
            }
        }
        return true;
        
    } elseif ($response['status_code'] === 403) {
        echo "   ❌ FORBIDDEN - User does not have permission to access /api/users\n";
        return false;
        
    } else {
        echo "   ❌ UNEXPECTED STATUS - {$response['status_code']}\n";
        echo "   Response: " . substr($response['body'], 0, 300) . "\n";
        return false;
    }
}

// Main test execution
echo "🚀 Starting API tests...\n\n";

$results = [];

foreach ($testUsers as $email => $roleName) {
    echo str_repeat("-", 60) . "\n";
    echo "TESTING: $roleName ($email)\n";
    echo str_repeat("-", 60) . "\n";
    
    // Step 1: Login
    $token = testUserLogin($email, 'password', $baseUrl);
    
    if ($token) {
        // Step 2: Test API access
        $canAccess = testApiAccess($token, $baseUrl, $email);
        $results[$email] = [
            'role' => $roleName,
            'login' => true,
            'api_access' => $canAccess
        ];
    } else {
        $results[$email] = [
            'role' => $roleName,
            'login' => false,
            'api_access' => false
        ];
    }
    
    echo "\n";
}

// Summary
echo str_repeat("=", 80) . "\n";
echo "TEST RESULTS SUMMARY\n";
echo str_repeat("=", 80) . "\n";

$totalTests = count($results);
$loginSuccess = 0;
$apiSuccess = 0;

foreach ($results as $email => $result) {
    $loginStatus = $result['login'] ? '✅ LOGIN' : '❌ LOGIN';
    $apiStatus = $result['api_access'] ? '✅ API' : '❌ API';
    
    echo sprintf("%-25s %-20s %s %s\n", $result['role'], "($email)", $loginStatus, $apiStatus);
    
    if ($result['login']) $loginSuccess++;
    if ($result['api_access']) $apiSuccess++;
}

echo str_repeat("-", 80) . "\n";
echo "Login Success Rate: $loginSuccess/$totalTests (" . round(($loginSuccess/$totalTests)*100, 1) . "%)\n";
echo "API Access Success Rate: $apiSuccess/$totalTests (" . round(($apiSuccess/$totalTests)*100, 1) . "%)\n";

if ($loginSuccess === $totalTests && $apiSuccess > 0) {
    echo "\n🎉 BASIC API FUNCTIONALITY WORKING!\n";
} elseif ($loginSuccess === $totalTests) {
    echo "\n⚠️ LOGIN WORKS, BUT SOME USERS LACK API PERMISSIONS\n";
} else {
    echo "\n❌ SOME TESTS FAILED - CHECK CONFIGURATION\n";
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";