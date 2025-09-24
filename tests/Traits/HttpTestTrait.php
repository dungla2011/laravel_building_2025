<?php

namespace Tests\Traits;

use Exception;

/**
 * Shared HTTP testing functionality for Feature Tests
 * 
 * Provides common methods for:
 * - Server port determination (CI vs Local)
 * - HTTP request handling
 * - CSRF token management
 * - Database preparation
 * - Server health checks
 */
trait HttpTestTrait
{
    private string $baseUrl;
    private int $serverPort;
    private string $cookieJar;
    private ?string $userToken = null;
    private ?string $csrfToken = null;

    /**
     * Initialize HTTP test environment
     */
    protected function initializeHttpTest(string $cookiePrefix = 'phpunit_cookies'): void
    {
        echo "🔍 Step 1: Determining server port...\n";
        // Determine server port based on environment
        $this->serverPort = $this->determineServerPort();
        $this->baseUrl = "http://127.0.0.1:{$this->serverPort}";
        echo "✅ Server port determined: {$this->serverPort}\n";
        
        echo "🔍 Step 2: Creating cookie jar...\n";
        // Create cookie jar for session management
        $this->cookieJar = tempnam(sys_get_temp_dir(), $cookiePrefix);
        echo "✅ Cookie jar created: {$this->cookieJar}\n";
        
        echo "🔍 Step 3: Starting server check and restart process...\n";
        // Start Laravel server and prepare database
        $this->checkAndRestartServer();
        echo "✅ Server check and restart completed\n";
        
    }

    /**
     * Cleanup HTTP test resources
     */
    protected function cleanupHttpTest(): void
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }

    /**
     * Determine which port to use for testing
     * CI uses 8000, local testing uses 12368
     */
    private function determineServerPort(): int
    {
        // Check if we're in CI environment
        if (getenv('CI') || getenv('GITHUB_ACTIONS') || getenv('RUNNER_OS')) {
            echo "🔍 Detected CI environment, using port 8000\n";
            return 8000;
        }
        
        // Check if port 8000 is already in use (likely CI or existing server)
        $output = [];
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            exec('netstat -ano | findstr ":8000"', $output);
        } else {
            exec('netstat -tuln | grep :8000', $output);
        }
        
        foreach ($output as $line) {
            if (strpos($line, 'LISTENING') !== false || strpos($line, 'LISTEN') !== false) {
                echo "🔍 Port 8000 in use, assuming CI environment\n";
                return 8000;
            }
        }
        
        // Default to local testing port
        echo "🔍 Local environment detected, using port 12368\n";
        return 12368;
    }

    /**
     * Complete server management: kill old server, init DB, start new server
     */
    private function checkAndRestartServer(): void
    {
        echo "🔍 " . (app()->environment('testing') ? 'CI' : 'Local') . " environment detected, using port {$this->serverPort}\n";
        
        // In CI environment, use existing server setup
        if (getenv('CI') || getenv('GITHUB_ACTIONS')) {
            echo "� CI environment - using existing server and database\n";
            $this->prepareDatabaseForTesting();
            if ($this->isServerResponding()) {
                echo "✅ CI server is responding on port {$this->serverPort}\n";
                return;
            } else {
                $this->fail("CI server not responding on port {$this->serverPort}");
            }
        }
        
        // Step 1: Check and kill existing server
        echo "\n📋 Step 1: Checking for existing server on port {$this->serverPort}...\n";
        $this->killServerOnPort($this->serverPort);
        
        // Step 2: Verify server is killed
        echo "\n� Step 2: Verifying server is completely stopped...\n";
        $this->verifyServerStopped();
        
        // Step 3: Initialize fresh database
        echo "\n📋 Step 3: Initializing fresh database...\n";
        $this->initializeFreshDatabase();
        
        // Step 4: Start new server
        echo "\n📋 Step 4: Starting new Laravel server...\n";
        $this->startNewServer();
        
        // Step 5: Verify new server is responding
        echo "\n📋 Step 5: Verifying new server is responding...\n";
        if ($this->isServerResponding()) {
            echo "✅ Complete server restart successful!\n";
        } else {
            $this->fail("❌ Failed to start new server after complete restart process");
        }
    }

    /**
     * Prepare database for testing (simple version for CI)
     */
    private function prepareDatabaseForTesting(): void
    {
        echo "🗄️  Preparing database for CI environment...\n";
        
        $dbPath = database_path('testing.sqlite');
        if (file_exists($dbPath)) {
            echo "   ✅ Testing SQLite database exists\n";
        } else {
            echo "   📄 Creating SQLite database file\n";
            touch($dbPath);
        }
        
        // Run fresh migrations with seeding for CI
        exec("php artisan migrate:fresh --seed --force --env=testing 2>&1", $output, $exitCode);
        
        if ($exitCode === 0) {
            echo "   ✅ Database preparation completed\n";
        } else {
            echo "   ⚠️  Database preparation warnings (continuing):\n";
            foreach (array_slice($output, -3) as $line) {
                echo "      $line\n";
            }
        }
    }

    /**
     * Check if Laravel server is responding
     */
    private function isServerResponding(): bool
    {
        // Use simple cURL check with short timeout to avoid hanging
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3, // Very short timeout for server check
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_NOBODY => true, // HEAD request, faster
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!$error && $httpCode === 200) {
            return true;
        }
        
        echo "⚠️  Server check failed. HTTP Code: $httpCode, Error: $error\n";
        return false;
    }

    /**
     * Make HTTP request using cURL with retry logic for CI
     */
    protected function makeRequest(string $url, string $method = 'GET', array $data = [], array $headers = []): ?array
    {
        $maxRetries = (getenv('CI') || getenv('GITHUB_ACTIONS')) ? 2 : 1;
        $lastError = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init();
            
            // Basic cURL options with CI-friendly timeout
            $timeout = (getenv('CI') || getenv('GITHUB_ACTIONS')) ? 15 : 30;
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10, // Add connection timeout
                CURLOPT_COOKIEJAR => $this->cookieJar,
                CURLOPT_COOKIEFILE => $this->cookieJar,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CUSTOMREQUEST => $method,
            ]);
            
            // Add headers
            $defaultHeaders = ['User-Agent: PHPUnit Test Suite'];
            if ($this->csrfToken) {
                $defaultHeaders[] = 'X-CSRF-TOKEN: ' . $this->csrfToken;
            }
            if ($this->userToken) {
                $defaultHeaders[] = 'Authorization: Bearer ' . $this->userToken;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
            
            // Add data for POST/PUT/PATCH requests
            if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                if (in_array('Content-Type: application/json', $headers)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if (!$error && $httpCode !== 0) {
                // Success
                return [
                    'body' => $response,
                    'http_code' => $httpCode
                ];
            } else {
                // Error occurred
                $lastError = $error ?: "HTTP Code: $httpCode";
                if ($attempt < $maxRetries) {
                    echo "⚠️  Attempt $attempt failed: $lastError. Retrying...\n";
                    sleep(1); // Brief pause before retry
                } else {
                    echo "⚠️  cURL Error after $maxRetries attempts: $lastError\n";
                }
            }
        }
        
        return null;
    }

    /**
     * Get CSRF token from various sources (with aggressive timeout)
     */
    protected function getCsrfToken(): void
    {
        echo "🔍 Getting CSRF token with simple curl request...\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/admin/role-permissions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, // Very short timeout
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => ['User-Agent: PHPUnit Test Suite']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!$error && $httpCode === 200 && $response) {
            echo "   ✅ Got response (HTTP $httpCode, " . strlen($response) . " bytes)\n";
            
            // Look for meta tag CSRF token
            if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $matches)) {
                $this->csrfToken = $matches[1];
                echo "   ✅ CSRF token found: " . substr($this->csrfToken, 0, 10) . "...\n";
                return;
            }
            
            // Fallback: look for form CSRF token
            if (preg_match('/name="_token" value="([^"]+)"/', $response, $matches)) {
                $this->csrfToken = $matches[1];
                echo "   ✅ CSRF token found in form: " . substr($this->csrfToken, 0, 10) . "...\n";
                return;
            }
            
            echo "   ⚠️ No CSRF token found in response\n";
        } else {
            echo "   ❌ Request failed - HTTP $httpCode, Error: $error\n";
        }
        
        echo "⚠️  Warning: Failed to obtain CSRF token. Continuing without CSRF token.\n";
    }

    /**
     * Login user and get authentication token (with simple cURL)
     */
    protected function loginUser(string $email, string $password): bool
    {
        echo "🔐 Attempting login for $email...\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/api/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'password' => $password]),
            CURLOPT_TIMEOUT => 10, // Short timeout
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: PHPUnit Test Suite'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!$error && $httpCode === 200 && $response) {
            $tokenData = json_decode($response, true);
            if (isset($tokenData['token'])) {
                $this->userToken = $tokenData['token'];
                echo "   ✅ Login successful, token obtained\n";
                return true;
            }
        }
        
        echo "   ❌ Login failed - HTTP $httpCode, Error: $error\n";
        if ($response && strlen($response) < 500) {
            echo "   Response: " . $response . "\n";
        }
        return false;
    }

    /**
     * Kill any existing server process on the specified port
     */
    private function killServerOnPort(int $port): void
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $output = [];
        
        if ($isWindows) {
            exec("netstat -ano | findstr \":$port\"", $output);
        } else {
            exec("netstat -tuln | grep :$port", $output);
        }
        
        $processIds = [];
        foreach ($output as $line) {
            if (strpos($line, 'LISTENING') !== false || strpos($line, 'LISTEN') !== false) {
                if ($isWindows && preg_match('/\s+(\d+)$/', $line, $matches)) {
                    $processIds[] = $matches[1];
                } elseif (!$isWindows && preg_match('/(\d+)\//', $line, $matches)) {
                    $processIds[] = $matches[1];
                }
            }
        }
        
        if (!empty($processIds)) {
            foreach ($processIds as $pid) {
                echo "   🔄 Killing process PID $pid...\n";
                if ($isWindows) {
                    exec("taskkill /PID $pid /F 2>nul");
                } else {
                    exec("kill -9 $pid 2>/dev/null");
                }
            }
            sleep(3); // Wait for processes to terminate completely
            echo "   ✅ Killed " . count($processIds) . " process(es)\n";
        } else {
            echo "   ✅ No existing server found on port $port\n";
        }
    }

    /**
     * Verify server is completely stopped
     */
    private function verifyServerStopped(): void
    {
        $maxAttempts = 5;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
            $output = [];
            
            if ($isWindows) {
                exec("netstat -ano | findstr \":{$this->serverPort}\"", $output);
            } else {
                exec("netstat -tuln | grep :{$this->serverPort}", $output);
            }
            
            $stillRunning = false;
            foreach ($output as $line) {
                if (strpos($line, 'LISTENING') !== false || strpos($line, 'LISTEN') !== false) {
                    $stillRunning = true;
                    break;
                }
            }
            
            if (!$stillRunning) {
                echo "   ✅ Server completely stopped (verified in attempt $attempt)\n";
                return;
            }
            
            if ($attempt < $maxAttempts) {
                echo "   ⏳ Server still running, waiting... (attempt $attempt/$maxAttempts)\n";
                sleep(2);
            }
        }
        
        echo "   ⚠️  Server may still be running after $maxAttempts attempts, continuing anyway\n";
    }

    /**
     * Initialize fresh database with complete reset
     */
    private function initializeFreshDatabase(): void
    {
        // Remove existing database file
        $dbPath = database_path('testing.sqlite');
        if (file_exists($dbPath)) {
            unlink($dbPath);
            echo "   🗑️  Removed old database file\n";
        }
        
        // Create new empty database file
        touch($dbPath);
        echo "   📄 Created new database file\n";
        
        // Run fresh migrations with seeding
        exec("php artisan migrate:fresh --seed --force --env=testing 2>&1", $output, $exitCode);
        
        if ($exitCode === 0) {
            echo "   ✅ Fresh database migration and seeding completed\n";
        } else {
            echo "   ⚠️  Database initialization had issues:\n";
            foreach (array_slice($output, -3) as $line) {
                echo "      $line\n";
            }
        }
        
        // Verify database has data
        try {
            $userCount = \Illuminate\Support\Facades\DB::connection('sqlite')->table('users')->count();
            $permissionCount = \Illuminate\Support\Facades\DB::connection('sqlite')->table('permissions')->count();
            echo "   📊 Database verification: $userCount users, $permissionCount permissions\n";
        } catch (Exception $e) {
            echo "   ⚠️  Database verification failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Start new Laravel server
     */
    private function startNewServer(): void
    {
        echo "🚀 Starting Laravel server on port {$this->serverPort}...\n";
        
        // Use proc_open for better cross-platform background process handling
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout  
            2 => ['pipe', 'w']   // stderr
        ];
        
        $command = "php artisan serve --port={$this->serverPort} --env=testing";
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            // Close pipes to detach process
            fclose($pipes[0]);
            fclose($pipes[1]); 
            fclose($pipes[2]);
            
            echo "✅ Server process started in background\n";
            
            // Wait a moment for server to start
            sleep(3);
        } else {
            $this->fail("❌ Failed to start server process");
        }
    }

    /**
     * Check if we're in CI environment
     */
    protected function isCIEnvironment(): bool
    {
        return getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true';
    }
}