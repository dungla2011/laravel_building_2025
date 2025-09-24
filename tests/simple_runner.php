<?php

/**
 * Simple Test Runner - Debug version
 */

$testFiles = [
    '01-test-permission-standalone.php',
    '02-test_user_validation.php', 
    '03-test_batch_validation.php'
];

$envFlag = '--env=testing';

foreach ($testFiles as $testFile) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "🧪 Running: $testFile\n";
    echo str_repeat("=", 80) . "\n";
    
    $command = "php tests/$testFile $envFlag";
    
    // Add role for permission test
    if (strpos($testFile, '01-test-permission') !== false) {
        $command .= " super-admin";
    }
    
    echo "Command: $command\n\n";
    
    // Use system() for simplest execution
    $exitCode = 0;
    system($command, $exitCode);
    
    echo "\n📊 Test Result: ";
    if ($exitCode === 0) {
        echo "✅ PASSED";
    } else {
        echo "❌ FAILED";
    }
    echo " (Exit code: $exitCode)\n";
    
    // Wait a bit between tests
    sleep(1);
}

echo "\n🎉 All tests completed!\n";