<?php

// Test duplicate email error handling

$baseUrl = 'http://127.0.0.1:8000';

// Step 1: Login to get token
$loginData = json_encode(['email' => 'editor@example.com', 'password' => 'password']);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$loginResponse = json_decode($response, true);
if ($httpCode !== 200 || !isset($loginResponse['token'])) {
    die("❌ Login failed\n");
}

$token = $loginResponse['token'];
echo "✅ Login successful, got token\n";

// Step 2: Try to create user with duplicate email
$userData = json_encode([
    'name' => 'Duplicate Test User',
    'email' => 'editor@example.com', // This email already exists
    'password' => 'password123'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/users");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $userData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\n🧪 Testing duplicate email creation:\n";
echo "HTTP Code: $httpCode\n";
echo "Response: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "\n";

// Check if error handling is working correctly
$responseData = json_decode($response, true);
if ($httpCode === 422 && isset($responseData['errors']['email'])) {
    echo "✅ Error handling works correctly! Returns 422 with proper validation error\n";
} else if ($httpCode === 500) {
    echo "❌ Still getting 500 error - error handling not working\n";
} else {
    echo "⚠️ Unexpected response - HTTP $httpCode\n";
}