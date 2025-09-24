<?php

// Test User Validation Rules

require_once __DIR__ . '/TestUtils.php';

// Parse command line arguments
$args = TestUtils::parseArguments($argv);

// Check and restart server if needed
TestUtils::checkAndRestartServer($args['env_flag']);
echo "\n";

// Authenticate user
$token = TestUtils::authenticateUser('superadmin@example.com', 'password');
if (!$token) {
    die("❌ Authentication failed\n");
}

// Start test suite
$startTime = TestUtils::startTestSuite('User Validation Test');

// Test cases
$testCases = [
    [
        'name' => 'Invalid Name (forbidden word)',
        'data' => [
            'name' => 'Admin User',
            'email' => TestUtils::makeUniqueEmail('admin'),
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!'
        ],
        'expected_status' => 422,
        'expected_error' => 'name'
    ],
    [
        'name' => 'Invalid Email (blocked domain)',
        'data' => [
            'name' => 'Valid User',
            'email' => 'test@10minutemail.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!'
        ],
        'expected_status' => 422,
        'expected_error' => 'email'
    ],
    [
        'name' => 'Weak Password',
        'data' => [
            'name' => 'Valid User',
            'email' => TestUtils::makeUniqueEmail('weak'),
            'password' => 'weak',
            'password_confirmation' => 'weak'
        ],
        'expected_status' => 422,
        'expected_error' => 'password'
    ],
    [
        'name' => 'Valid Data',
        'data' => [
            'name' => 'John Doe',
            'email' => TestUtils::makeUniqueEmail('valid'),
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!'
        ],
        'expected_status' => 201,
        'expected_error' => null
    ]
];

$totalTests = count($testCases);
$failedTests = 0;
$totalAssertions = count($testCases);

echo str_repeat(".", 50) . " ";

foreach ($testCases as $index => $test) {
    $response = TestUtils::makeRequest(
        TestUtils::$baseUrl . '/api/users',
        'POST',
        $test['data'],
        ["Authorization: Bearer $token"]
    );
    
    $testPassed = TestUtils::validateTestResult(
        $test['name'],
        $test['expected_status'],
        $response['http_code'],
        $test['expected_error'],
        $response['data']
    );
    
    if (!$testPassed) {
        $failedTests++;
    }
    
    TestUtils::displayProgress($testPassed);
    
    if ($index < count($testCases) - 1) {
        echo " ";
    }
}

echo "\n\n";

// Finish test suite
$exitCode = TestUtils::finishTestSuite($startTime, $totalTests, $failedTests, $totalAssertions);

// Only exit if running standalone (not included by test suite)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    exit($exitCode);
}