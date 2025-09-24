<?php

// Test User Validation Rules

/**
 * Check if port 12368 is in use and restart Laravel server if needed
 */
function checkAndRestartServer(string $envFlag = ''): void
{
    echo "🔍 Checking Laravel server status...\n";
    
    // Always run database migrations and seeders first
    echo "🗄️  Preparing database (fresh migration + seeding)...\n";
    
    // Create SQLite database file if needed
    if (strpos($envFlag, 'testing') !== false) {
        $testingDbPath = 'database/testing.sqlite';
        if (!file_exists($testingDbPath)) {
            echo "   Creating testing SQLite database file...\n";
            if (!is_dir('database')) {
                mkdir('database', 0755, true);
            }
            touch($testingDbPath);
            echo "   ✅ Created $testingDbPath\n";
        } else {
            echo "   ✅ Testing SQLite database exists\n";
        }
    } elseif (strpos($envFlag, 'local') !== false || empty($envFlag)) {
        // Check for local SQLite database
        $localDbPath = 'database/database.sqlite';
        if (!file_exists($localDbPath)) {
            echo "   Creating local SQLite database file...\n";
            if (!is_dir('database')) {
                mkdir('database', 0755, true);
            }
            touch($localDbPath);
            echo "   ✅ Created $localDbPath\n";
        } else {
            echo "   ✅ Local SQLite database exists\n";
        }
    }
    
    // Run fresh migration with seeding in one command
    exec("php artisan migrate:fresh --seed --force$envFlag 2>&1", $output, $exitCode);
    
    if ($exitCode === 0) {
        echo "   ✅ Fresh database migration and seeding completed\n";
    } else {
        echo "   ⚠️  Database preparation warnings:\n";
        foreach (array_slice($output, -5) as $line) {
            echo "      $line\n";
        }
    }
    
    // Check if port 12368 is in use
    $output = [];
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    if ($isWindows) {
        exec('netstat -ano | findstr ":12368"', $output);
    } else {
        exec('netstat -tuln | grep :12368', $output);
    }
    
    $serverRunning = false;
    $processId = null;
    
    // Parse netstat output to find listening process
    foreach ($output as $line) {
        if (strpos($line, 'LISTENING') !== false || strpos($line, 'LISTEN') !== false) {
            $serverRunning = true;
            if ($isWindows && preg_match('/\s+(\d+)$/', $line, $matches)) {
                $processId = $matches[1];
            }
            break;
        }
    }
    
    if ($serverRunning) {
        echo "⚠️  Port 12368 is in use";
        if ($processId) {
            echo " (PID: $processId)";
        }
        echo "\n";
        
        // Test if it's actually Laravel responding
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:12368');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ Laravel server is responding on port 12368\n";
            return;
        } else {
            echo "❌ Port 12368 occupied but not responding to Laravel requests\n";
        }
        
        // Kill the process to restart with fresh code
        if ($processId && $isWindows) {
            echo "🔄 Killing process $processId to restart with fresh code...\n";
            exec("taskkill /PID $processId /F 2>nul", $killOutput);
            sleep(2); // Wait for process to terminate
        }
    }
    
    // Start Laravel development server
    echo "🚀 Starting Laravel development server...\n";
    
    // Use proc_open for better cross-platform background process handling
    $descriptorspec = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout  
        2 => ['pipe', 'w']   // stderr
    ];
    
    $command = "php artisan serve --port=12368$envFlag";
    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        // Close pipes to detach process
        fclose($pipes[0]);
        fclose($pipes[1]); 
        fclose($pipes[2]);
        
        // Don't wait for process to finish (run in background)
        // proc_close($process); // Commented out to keep it running
        
        echo "✅ Server process started in background\n";
    } else {
        echo "❌ Failed to start server process\n";
    }
    
    // Wait for server to start and verify
    $maxAttempts = 10;
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        sleep(1);
        $attempts++;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:12368');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ Laravel server started successfully (attempt $attempts/$maxAttempts)\n";
            return;
        }
        
        echo "⏳ Waiting for server to start (attempt $attempts/$maxAttempts)...\n";
    }
    
    die("❌ Failed to start Laravel server after $maxAttempts attempts\nPlease manually run: php artisan serve\n");
}

// Parse command line arguments
$envFlag = '';
foreach ($argv as $arg) {
    if (strpos($arg, '--env=') === 0) {
        $envFlag = ' --' . substr($arg, 2);
        break;
    }
}

// Check and restart server if needed
checkAndRestartServer($envFlag);
echo "\n";

$baseUrl = 'http://127.0.0.1:12368';

// Step 1: Login to get token
echo "🔐 Getting authentication token...\n";
$loginData = json_encode(['email' => 'superadmin@example.com', 'password' => 'password']);
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

// Run tests with PHPUnit-like output
$startTime = microtime(true);

echo "PHPUnit-Style User Validation Test by Standalone PHP\n";
echo "\n";

// Test cases
$testCases = [
    [
        'name' => 'Invalid Name (forbidden word)',
        'data' => [
            'name' => 'Admin User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!'
        ],
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
        'expected_error' => 'email'
    ],
    [
        'name' => 'Weak Password',
        'data' => [
            'name' => 'Valid User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak'
        ],
        'expected_error' => 'password'
    ],
    [
        'name' => 'Valid Data',
        'data' => [
            'name' => 'John Doe',
            'email' => 'john_' . uniqid() . '@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!'
        ],
        'expected_error' => null
    ]
];

$totalTests = count($testCases);
$failedTests = 0;
$totalAssertions = 0;

echo str_repeat(".", 50) . " ";

foreach ($testCases as $index => $test) {
    echo "Testing: {$test['name']}\n";
    
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
    $totalAssertions++;
    $testPassed = false;
    
    if ($httpCode === 422) {
        // Expected validation error
        if ($test['expected_error'] && isset($responseData['errors'][$test['expected_error']])) {
            $testPassed = true;
            echo "   ✅ PASS - Validation error for '{$test['expected_error']}' (HTTP 422)\n";
        } else {
            echo "   ❌ FAIL - Unexpected validation error (HTTP 422)\n";
        }
    } elseif ($httpCode === 201) {
        // User created successfully
        if ($test['expected_error'] === null) {
            $testPassed = true;
            echo "   ✅ PASS - Valid data accepted (HTTP 201)\n";
        } else {
            echo "   ❌ FAIL - Expected validation error but user was created (HTTP 201)\n";
        }
    } else {
        echo "   ❌ FAIL - Unexpected response: HTTP $httpCode\n";
        if (isset($responseData['message'])) {
            echo "   Message: {$responseData['message']}\n";
        }
    }
    
    if (!$testPassed) {
        $failedTests++;
        echo "F";
    } else {
        echo ".";
    }
    
    if ($index < count($testCases) - 1) {
        echo " ";
    }
}

echo "\n\n";

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "Time: $duration seconds, Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
echo "\n";

if ($failedTests === 0) {
    echo "OK ($totalTests tests)\n";
    exit(0);
} else {
    echo "FAILURES!\n";
    echo "Tests: $totalTests, Assertions: $totalAssertions, Failures: $failedTests\n";
    exit(1);
}