<?php

/**
 * Shared Test Utilities for Standalone PHP Tests
 * 
 * Contains common functionality used across all test files:
 * - Server management (start/stop/restart)
 * - Database operations
 * - Authentication helpers
 * - Command line argument parsing
 * - PHPUnit-style output formatting
 */

// Environment file loader
function loadEnvironmentFile($envFlag = '') {
    $envFile = '.env';
    
    // Determine which .env file to load based on flag
    if (strpos($envFlag, 'testing') !== false) {
        $envFile = '.env.testing';
    } elseif (strpos($envFlag, 'local') !== false) {
        $envFile = '.env.local';
    }
    
    // Look for .env file in project root (one level up from tests/)
    $envPath = __DIR__ . '/../' . $envFile;
    
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue; // Skip comments
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'"); // Remove quotes
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}


// Fallback env() function if Laravel is not available
if (!function_exists('env')) {
    function env($key, $default = null) {
        
        $value = $_ENV[$key];
        if ($value === false) {
            return $default;
        }
        return $value;
    }
}


class TestUtils
{
    public static string $baseUrl = 'http://127.0.0.1:12368';
    public static int $serverPort = 12368;
    
    /**
     * Parse command line arguments
     */
    public static function parseArguments(array $argv): array
    {
        $result = [
            'env' => null,
            'env_flag' => '',
            'role' => null,
            'verbose' => false
        ];
        
        foreach ($argv as $arg) {
            if (strpos($arg, '--env=') === 0) {
                $env = substr($arg, 6);
                $result['env'] = $env;
                $result['env_flag'] = ' --env=' . $env;
            } elseif ($arg === '--verbose' || $arg === '-v') {
                $result['verbose'] = true;  
            } elseif (!str_starts_with($arg, '--') && !str_ends_with($arg, '.php') && $arg !== basename($_SERVER['SCRIPT_NAME'])) {
                $result['role'] = $arg; // Role argument (no flag)
            }
        }
        
        return $result;
    }
    
    /**
     * Check if port is in use and restart Laravel server if needed
     * 
     * @param string $envFlag Environment flag (e.g., '--env=testing')
     * @param bool $forceKill Always kill existing server process if found
     */
    public static function checkAndRestartServer(string $envFlag = '', bool $forceKill = true): void
    {
        echo "🔍 Checking Laravel server status...\n";
        
        // Load the appropriate environment file first
        loadEnvironmentFile($envFlag);
        echo "   🌍 Loaded environment: " . ($envFlag ? $envFlag : 'default') . "\n";
        
        // Always run database migrations and seeders first
        echo "🗄️  Preparing database (fresh migration + seeding)...\n";
        
        // Create SQLite database file if needed
        if (strpos($envFlag, 'testing') !== false) {
            $testingDbPath = 'database/testing.sqlite';

            // Nếu DB_CONNECTION=sqlite thì mới check:
            if (env('DB_CONNECTION') == 'sqlite')
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
            if (env('DB_CONNECTION') == 'sqlite')
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
        $migrationCommand = "php artisan migrate:fresh --seed --force";
        if (!empty($envFlag)) {
            $migrationCommand .= " $envFlag";
        }
        exec("$migrationCommand 2>&1", $output, $exitCode);
        
        if ($exitCode === 0) {
            echo "   ✅ Fresh database migration and seeding completed\n";
        } else {
            echo "   ⚠️  Database preparation warnings:\n";
            foreach (array_slice($output, -5) as $line) {
                echo "      $line\n";
            }
        }
        
        // Check if port is in use
        $output = [];
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            exec("netstat -ano | findstr \":".self::$serverPort.'"', $output);
        } else {
            exec('netstat -tuln | grep :'.self::$serverPort, $output);
        }
        
        // Debug: Show netstat output
        echo "🔧 DEBUG: netstat output for port ".self::$serverPort.":\n";
        foreach ($output as $line) {
            echo "   $line\n";
        }
        
        $serverRunning = false;
        $processIds = [];
        
        // Parse netstat output to find ALL listening processes
        foreach ($output as $line) {
            if (strpos($line, 'LISTENING') !== false || strpos($line, 'LISTEN') !== false) {
                $serverRunning = true;
                if ($isWindows && preg_match('/\s+(\d+)$/', $line, $matches)) {
                    $pid = $matches[1];
                    if (!in_array($pid, $processIds)) {
                        $processIds[] = $pid;
                    }
                }
            }
        }
        
        if ($serverRunning) {
            echo "⚠️  Port ".self::$serverPort." is in use";
            if (!empty($processIds)) {
                echo " (PIDs: " . implode(', ', $processIds) . ")";
            }
            echo "\n";
            
            $shouldKill = $forceKill;
            
            if (!$forceKill) {
                // Test if it's actually Laravel responding
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, self::$baseUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                
                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    echo "✅ Laravel server is responding on port ".self::$serverPort."\n";
                    return;
                } else {
                    echo "❌ Port ".self::$serverPort." occupied but not responding to Laravel requests\n";
                    $shouldKill = true;
                }
            } else {
                echo "🔄 Force kill mode enabled - will restart server\n";
            }
            
            // Kill all processes using file-based approach
            if ($shouldKill && !empty($processIds)) {
                echo "🔄 Requesting kill for " . count($processIds) . " processes via file...\n";
                
                // Write all PIDs to file, one per line
                $pidFile = __DIR__ . '/pid.txt';
                file_put_contents($pidFile, implode("\n", $processIds));
                echo "   📝 Wrote " . count($processIds) . " PIDs to $pidFile\n";
                
                // Wait for background killer to process it
                echo "   ⏳ Waiting for background killer to handle process...\n";
                $maxWait = 10; // Maximum 10 seconds wait
                $waited = 0;
                
                while ($waited < $maxWait) {
                    sleep(1);
                    $waited++;
                    
                    // Check if PID file is cleared (means killer processed it)
                    if (file_exists($pidFile)) {
                        $content = trim(file_get_contents($pidFile));
                        if (empty($content)) {
                            echo "   ✅ Background killer processed the request\n";
                            break;
                        }
                    }
                    
                    if ($waited >= $maxWait) {
                        echo "   ⚠️  Timeout waiting for background killer, continuing anyway...\n";
                    }
                }
                
                // Additional wait for process termination  
                sleep(1);
                
                // Verify each PID is actually killed - more thorough check
                echo "🔍 Verifying process termination...\n";
                $verifyAttempts = 0;
                $maxVerifyAttempts = 15; // Increased timeout
                $allKilled = false;
                
                while ($verifyAttempts < $maxVerifyAttempts && !$allKilled) {
                    $stillRunningPids = [];
                    
                    // Check each PID individually
                    foreach ($processIds as $pid) {
                        if ($isWindows) {
                            exec("tasklist /PID $pid /FO CSV 2>NUL", $checkOutput, $checkExitCode);
                            if ($checkExitCode === 0 && count($checkOutput) > 1) {
                                $stillRunningPids[] = $pid;
                            }
                        } else {
                            // Linux: Check if process still exists
                            exec("kill -0 $pid 2>/dev/null", $checkOutput, $checkExitCode);
                            if ($checkExitCode === 0) {
                                $stillRunningPids[] = $pid;
                            }
                        }
                    }
                    
                    if (empty($stillRunningPids)) {
                        echo "✅ All processes killed successfully\n";
                        $allKilled = true;
                        break;
                    }
                    
                    $verifyAttempts++;
                    echo "   ⏳ Still running PIDs: " . implode(', ', $stillRunningPids) . " (attempt $verifyAttempts/$maxVerifyAttempts)\n";
                    
                    // Try force kill if normal kill didn't work after several attempts
                    if ($verifyAttempts > 5 && $verifyAttempts % 3 === 0) {
                        echo "   💪 Attempting force kill for remaining processes...\n";
                        
                        if ($isWindows) {
                            // Write remaining PIDs back to file for background killer
                            $pidFile = __DIR__ . '/pid.txt';
                            file_put_contents($pidFile, implode("\n", $stillRunningPids));
                            
                            // Give background killer time to force kill
                            sleep(2);
                        } else {
                            // Linux: Direct force kill with SIGKILL
                            foreach ($stillRunningPids as $pid) {
                                echo "      🔫 Force killing PID $pid with kill -9...\n";
                                exec("kill -9 $pid 2>/dev/null", $killOutput, $killExitCode);
                                if ($killExitCode === 0) {
                                    echo "      ✅ Force killed PID $pid\n";
                                } else {
                                    echo "      ⚠️  Failed to force kill PID $pid\n";
                                }
                            }
                            sleep(1);
                        }
                    } else {
                        sleep(1);
                    }
                    
                    if (empty($stillRunningPids)) {
                        echo "✅ All processes killed successfully\n";
                        $allKilled = true;
                        break;
                    }
                    
                    $verifyAttempts++;
                    echo "   ⏳ Still running PIDs: " . implode(', ', $stillRunningPids) . " (attempt $verifyAttempts/$maxVerifyAttempts)\n";
                    
                    // Try force kill if normal kill didn't work after several attempts
                    if ($verifyAttempts > 5 && $verifyAttempts % 3 === 0) {
                        echo "   💪 Attempting force kill for remaining processes...\n";
                        
                        // Write remaining PIDs back to file for force kill
                        $pidFile = __DIR__ . '/pid.txt';
                        file_put_contents($pidFile, implode("\n", $stillRunningPids));
                        
                        // Give background killer time to force kill
                        sleep(2);
                    } else {
                        sleep(1);
                    }
                }
                
                if (!$allKilled) {
                    echo "❌ CRITICAL: Failed to kill all processes after $maxVerifyAttempts attempts!\n";
                    echo "   Still running PIDs: " . implode(', ', $stillRunningPids) . "\n";
                    echo "   Please manually kill these processes or restart the system.\n";
                    
                    // Don't continue - this could cause port conflicts
                    die("🛑 Aborting test due to process cleanup failure. Manual intervention required.\n");
                }
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
        
        $command = "php artisan serve --port=".self::$serverPort;
        if (!empty($envFlag)) {
            $command .= " $envFlag";
        }
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
            curl_setopt($ch, CURLOPT_URL, self::$baseUrl);
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
    
    /**
     * Authenticate user and get token
     */
    public static function authenticateUser(string $email, string $password): ?string
    {
        echo "🔐 Getting authentication token...\n";
        
        $loginData = json_encode(['email' => $email, 'password' => $password]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$baseUrl . "/api/login");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json', 
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "❌ Authentication failed - cURL Error: $error\n";
            return null;
        }
        
        if ($httpCode !== 200) {
            echo "❌ Authentication failed - HTTP $httpCode\n";
            if ($response) {
                echo "Response: " . substr($response, 0, 200) . "\n";
            }
            return null;
        }
        
        $loginResponse = json_decode($response, true);
        if (!isset($loginResponse['token'])) {
            echo "❌ Authentication failed - No token in response\n";
            return null;
        }
        
        echo "✅ Authentication successful\n\n";
        return $loginResponse['token'];
    }
    
    /**
     * Simple cURL request helper
     */
    public static function makeRequest(string $url, string $method = 'GET', $data = null, array $headers = [], int $timeout = 10): array
    {
        $ch = curl_init();
        
        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: PHPUnit Test Suite'
        ];
        
        $headers = array_merge($defaultHeaders, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
            }
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        return [
            'body' => $responseBody,
            'http_code' => $httpCode,
            'error' => $error,
            'data' => $responseBody ? json_decode($responseBody, true) : null
        ];
    }
    
    /**
     * Generate unique email for testing
     */
    public static function makeUniqueEmail(string $prefix = 'test'): string 
    {
        return $prefix . '_' . uniqid() . '@example.com';
    }

    /**
     * PHPUnit-style test output formatting
     */
    public static function startTestSuite(string $suiteName): float
    {
        echo "PHPUnit-Style $suiteName by Standalone PHP\n";
        echo "\n";
        return microtime(true);
    }
    
    /**
     * PHPUnit-style test summary
     */
    public static function finishTestSuite(float $startTime, int $totalTests, int $failedTests, int $totalAssertions = 0): int
    {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo "\n";
        echo "Time: $duration seconds, Memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
        echo "\n";
        
        if ($failedTests === 0) {
            echo "OK ($totalTests tests)\n";
            return 0;
        } else {
            echo "FAILURES!\n";
            $output = "Tests: $totalTests";
            if ($totalAssertions > 0) {
                $output .= ", Assertions: $totalAssertions";
            }
            $output .= ", Failures: $failedTests";
            echo "$output\n";
            return 1;
        }
    }
    
    /**
     * Display test progress dot
     */
    public static function displayProgress(bool $passed, bool $newLine = false): void
    {
        echo $passed ? '.' : 'F';
        if ($newLine) {
            echo '';
        }
    }
    
    /**
     * Validate test result and display status
     */
    public static function validateTestResult(string $testName, int $expectedStatus, int $actualStatus, ?string $expectedError = null, ?array $responseData = null): bool
    {
        echo "Testing: $testName\n";
        
        if ($actualStatus === $expectedStatus) {
            if ($expectedStatus === 422 && $expectedError && isset($responseData['errors'][$expectedError])) {
                echo "   ✅ PASS - Validation error for '$expectedError' (HTTP $actualStatus)\n";
                return true;
            } elseif ($expectedStatus === 201 && $expectedError === null) {
                echo "   ✅ PASS - Valid data accepted (HTTP $actualStatus)\n";
                return true;
            } elseif ($expectedStatus === 422) {
                echo "   ✅ PASS - Validation failed as expected (HTTP $actualStatus)\n";
                return true;
            } elseif ($expectedStatus === 200) {
                echo "   ✅ PASS - Request successful (HTTP $actualStatus)\n";
                return true;
            }
        }
        
        // Test failed
        if ($expectedStatus === 422 && $actualStatus === 201) {
            echo "   ❌ FAIL - Expected validation error but request succeeded (HTTP $actualStatus)\n";
        } elseif ($expectedStatus === 201 && $actualStatus === 422) {
            echo "   ❌ FAIL - Expected success but got validation error (HTTP $actualStatus)\n";
            if ($responseData && isset($responseData['errors'])) {
                foreach ($responseData['errors'] as $field => $errors) {
                    echo "      - $field: " . implode(', ', $errors) . "\n";
                }
            }
        } else {
            echo "   ❌ FAIL - Expected HTTP $expectedStatus but got HTTP $actualStatus\n";
        }
        
        return false;
    }

    /**
     * Validate batch test result and display status
     */
    public static function validateBatchTestResult(string $testName, int $expectedStatus, int $actualStatus, ?array $responseData = null): bool
    {
        echo "Testing: $testName\n";
        
        if ($actualStatus === $expectedStatus) {
            if ($expectedStatus === 422) {
                echo "   ✅ PASS - Validation failed as expected (HTTP $actualStatus)\n";
                return true;
            } elseif ($expectedStatus === 201) {
                echo "   ✅ PASS - Batch creation successful (HTTP $actualStatus)\n";
                return true;
            } elseif ($expectedStatus === 200) {
                echo "   ✅ PASS - Batch operation successful (HTTP $actualStatus)\n";
                return true;
            }
        }
        
        // Test failed
        echo "   ❌ FAIL - Expected HTTP $expectedStatus but got HTTP $actualStatus\n";
        if ($responseData && isset($responseData['message'])) {
            echo "   Message: {$responseData['message']}\n";
        }
        
        return false;
    }
}