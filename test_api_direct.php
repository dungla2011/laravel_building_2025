<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test API POST /api/users Directly ===\n";

// Get super admin user
$superAdmin = User::where('email', 'superadmin@example.com')->first();
if (!$superAdmin) {
    echo "❌ Super admin not found\n";
    exit;
}

// Create Sanctum token for API authentication
$token = $superAdmin->createToken('test-token')->plainTextToken;
echo "✅ Created API token: " . substr($token, 0, 20) . "...\n";

// Test API endpoint using cURL
$url = 'http://localhost:8000/api/users';
$data = [
    'name' => 'Test User via API',
    'email' => 'testapi@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ],
    CURLOPT_TIMEOUT => 30,
]);

echo "\n=== Making API Request ===\n";
echo "URL: $url\n";
echo "Method: POST\n";
echo "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "\n=== API Response ===\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 201) {
    echo "✅ SUCCESS: User created successfully!\n";
} elseif ($httpCode === 403) {
    echo "❌ UNAUTHORIZED: Permission denied\n";
} else {
    echo "❓ UNEXPECTED: HTTP $httpCode\n";
}