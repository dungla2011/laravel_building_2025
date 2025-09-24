<?php

// Test Batch Validation with Weak Password
require_once __DIR__ . '/TestUtils.php';

// Check and restart server if needed
$envFlag = isset($args['env']) ? ' --env=' . $args['env'] : '';
TestUtils::checkAndRestartServer($envFlag);
echo "\n";

// Authenticate and get token
$token = TestUtils::authenticateUser('superadmin@example.com', 'password');
if (!$token) {
    die("❌ Failed to authenticate\n");
}

// Start test suite
$startTime = TestUtils::startTestSuite('Batch Validation Test');

// Test cases
$testCases = [
    [
        'name' => 'Batch create with WEAK passwords',
        'data' => [
            'resources' => [
                [
                    'name' => 'User One',
                    'email' => TestUtils::makeUniqueEmail('weak_user1'),
                    'password' => 'weak' // This should fail validation
                ],
                [
                    'name' => 'User Two',
                    'email' => TestUtils::makeUniqueEmail('weak_user2'),
                    'password' => 'password123' // This should also fail (no uppercase, no symbols)
                ]
            ]
        ],
        'expected_status' => 422,
        'expected_error' => null // Batch validation error
    ],
    [
        'name' => 'Batch create with STRONG passwords',
        'data' => [
            'resources' => [
                [
                    'name' => 'User Alpha',
                    'email' => TestUtils::makeUniqueEmail('strong_user1'),
                    'password' => 'Complex!9Pwd'
                ],
                [
                    'name' => 'User Beta',
                    'email' => TestUtils::makeUniqueEmail('strong_user2'),
                    'password' => 'Secure#8Pass'
                ]
            ]
        ],
        'expected_status' => 200,
        'expected_error' => null
    ]
];

$totalTests = count($testCases);
$failedTests = 0;
$totalAssertions = count($testCases);

echo str_repeat(".", 50) . " ";

foreach ($testCases as $index => $test) {
    $response = TestUtils::makeRequest(
        TestUtils::$baseUrl . '/api/users/batch',
        'POST',
        $test['data'],
        ["Authorization: Bearer $token"]
    );
    
    $testPassed = TestUtils::validateBatchTestResult(
        $test['name'],
        $test['expected_status'],
        $response['http_code'],
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
exit($exitCode);