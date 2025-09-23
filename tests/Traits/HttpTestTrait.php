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
        // Determine server port based on environment
        $this->serverPort = $this->determineServerPort();
        $this->baseUrl = "http://127.0.0.1:{$this->serverPort}";
        
        // Create cookie jar for session management
        $this->cookieJar = tempnam(sys_get_temp_dir(), $cookiePrefix);
        
        // Start Laravel server and prepare database
        $this->checkAndRestartServer();
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
     * Check server status and prepare database
     */
    private function checkAndRestartServer(): void
    {
        echo "🔍 " . (app()->environment('testing') ? 'CI' : 'Local') . " environment detected, using port {$this->serverPort}\n";
        echo "🔍 Checking Laravel server status...\n";
        
        $this->prepareDatabaseForTesting();
        
        if (!$this->isServerResponding()) {
            $this->fail("Laravel server is not responding on port {$this->serverPort}. Please start the server first.");
        }
        
        echo "✅ Laravel server is responding on port {$this->serverPort}\n";
    }

    /**
     * Prepare database for testing with fresh migration and seeding
     */
    private function prepareDatabaseForTesting(): void
    {
        echo "🗄️  Preparing database (fresh migration + seeding)...\n";
        
        $dbPath = database_path('testing.sqlite');
        if (file_exists($dbPath)) {
            echo "   ✅ Testing SQLite database exists\n";
        }
        
        // Run fresh migrations with seeding to ensure clean database state
        exec("php artisan migrate:fresh --seed --force --env=testing 2>&1", $output, $exitCode);
        
        if ($exitCode !== 0) {
            echo "   ⚠️  Migration/seeding had issues, but continuing...\n";
            if (count($output) > 0) {
                echo "   Output: " . implode("\n   ", array_slice($output, -3)) . "\n";
            }
        } else {
            echo "   ✅ Fresh database migration and seeding completed\n";
        }
    }

    /**
     * Check if Laravel server is responding
     */
    private function isServerResponding(): bool
    {
        $checkUrl = $this->baseUrl;
        $response = $this->makeRequest($checkUrl, 'GET');
        
        if ($response && $response['http_code'] === 200) {
            return true;
        }
        
        echo "⚠️  Server check failed. Response: " . ($response['http_code'] ?? 'No response') . "\n";
        return false;
    }

    /**
     * Make HTTP request using cURL
     */
    protected function makeRequest(string $url, string $method = 'GET', array $data = [], array $headers = []): ?array
    {
        $ch = curl_init();
        
        // Basic cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
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
        
        if ($error) {
            echo "⚠️  cURL Error: $error\n";
            return null;
        }
        
        return [
            'body' => $response,
            'http_code' => $httpCode
        ];
    }

    /**
     * Get CSRF token from various sources
     */
    protected function getCsrfToken(): void
    {
        $routes_to_try = [
            '/admin/role-permissions' => 'admin role permissions',
            '/admin/field-permissions' => 'admin field permissions', 
            '/admin/users' => 'admin users',
            '/' => 'homepage'
        ];
        
        foreach ($routes_to_try as $route => $description) {
            echo "🔍 Trying to get CSRF token from {$description} ({$route})...\n";
            
            $response = $this->makeRequest($this->baseUrl . $route, 'GET');
            
            if ($response && isset($response['body']) && $response['http_code'] == 200) {
                echo "   ✅ Got response (HTTP {$response['http_code']}, " . strlen($response['body']) . " bytes)\n";
                
                // Look for meta tag CSRF token first (most reliable)
                if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $matches)) {
                    $this->csrfToken = $matches[1];
                    echo "   ✅ CSRF token found in meta tag: " . substr($this->csrfToken, 0, 10) . "...\n";
                    return;
                }
                
                // Fallback: look for form CSRF token
                if (preg_match('/name="_token" value="([^"]+)"/', $response['body'], $matches)) {
                    $this->csrfToken = $matches[1];
                    echo "   ✅ CSRF token found in form input: " . substr($this->csrfToken, 0, 10) . "...\n";
                    return;
                }
                
                echo "   ⚠️ No CSRF token found in response body\n";
                
                // Debug: show first 500 chars of response in CI to help troubleshooting
                if (isset($_ENV['CI']) || isset($_ENV['GITHUB_ACTIONS']) || isset($_ENV['PHPUNIT_TESTING'])) {
                    echo "   🔍 Response preview: " . substr($response['body'], 0, 500) . "...\n";
                    
                    // Check if response looks like HTML at all
                    if (stripos($response['body'], '<html') === false && stripos($response['body'], '<!DOCTYPE') === false) {
                        echo "   ⚠️ Response doesn't appear to be HTML\n";
                    }
                }
            } else {
                echo "   ❌ Failed request - HTTP " . ($response['http_code'] ?? 'unknown') . "\n";
                if ($response && isset($response['body']) && strlen($response['body']) < 200) {
                    echo "      Response: " . trim($response['body']) . "\n";
                }
            }
        }
        
        // Final fallback: try to generate a token via Laravel's built-in method
        echo "🔍 Attempting direct CSRF token generation via Laravel...\n";
        try {
            // Make a simple POST request to trigger CSRF token in session
            $tokenResponse = $this->makeRequest($this->baseUrl . '/api/login', 'POST', 
                ['email' => 'nonexistent@example.com', 'password' => 'wrong'],
                ['Content-Type: application/json', 'Accept: application/json']
            );
            
            if ($tokenResponse && isset($tokenResponse['body'])) {
                $responseData = json_decode($tokenResponse['body'], true);
                if (isset($responseData['errors']) && isset($responseData['message'])) {
                    echo "   ✅ API responded with validation errors (good sign)\n";
                    // At this point, session should be established, try getting CSRF again
                    $response = $this->makeRequest($this->baseUrl . '/admin/role-permissions', 'GET');
                    if ($response && preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $matches)) {
                        $this->csrfToken = $matches[1];
                        echo "   ✅ CSRF token obtained after session establishment: " . substr($this->csrfToken, 0, 10) . "...\n";
                        return;
                    }
                }
            }
        } catch (Exception $e) {
            echo "   ⚠️ Direct token generation failed: " . $e->getMessage() . "\n";
        }
        
        if (!$this->csrfToken) {
            throw new Exception('Failed to obtain CSRF token from any source. Routes tried: ' . implode(', ', array_keys($routes_to_try)));
        }
    }

    /**
     * Login user and get authentication token
     */
    protected function loginUser(string $email, string $password): bool
    {
        // Direct API login
        $tokenResponse = $this->makeRequest($this->baseUrl . '/api/login', 'POST', [
            'email' => $email,
            'password' => $password
        ], ['Content-Type: application/json']);

        if ($tokenResponse && $tokenResponse['http_code'] === 200) {
            $tokenData = json_decode($tokenResponse['body'], true);
            if (isset($tokenData['token'])) {
                $this->userToken = $tokenData['token'];
                return true;
            }
        }

        // Debug login response if failed
        $errorData = $tokenResponse ? json_decode($tokenResponse['body'], true) : null;
        $errorMessage = $errorData['message'] ?? 'Unknown error';
        
        echo "⚠️  Login failed for $email. HTTP Code: " . ($tokenResponse['http_code'] ?? 'Unknown') . ". Message: $errorMessage\n";
        return false;
    }

    /**
     * Check if we're in CI environment
     */
    protected function isCIEnvironment(): bool
    {
        return getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true';
    }
}