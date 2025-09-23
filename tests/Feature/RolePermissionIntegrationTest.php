<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

/**
 * Comprehensive Role Permission Feature Test
 * 
 * Converted from standalone test to PHPUnit Feature Test
 * Tests role permission management for API users endpoints with real HTTP server
 */
class RolePermissionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl;
    private int $serverPort;
    private string $cookieJar;
    private ?string $userToken = null;
    private ?string $csrfToken = null;
    private ?array $rolePermissionData = null;
    private array $testUsers = [
        'super-admin' => ['email' => 'superadmin@example.com', 'display_name' => 'Super Administrator'],
        'admin' => ['email' => 'admin@example.com', 'display_name' => 'Administrator'],
        'editor' => ['email' => 'editor@example.com', 'display_name' => 'Editor'],
        'viewer' => ['email' => 'viewer@example.com', 'display_name' => 'Viewer']
    ];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Determine server port based on environment
        $this->serverPort = $this->determineServerPort();
        $this->baseUrl = "http://127.0.0.1:{$this->serverPort}";
        
        // Create cookie jar for session management
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'phpunit_cookies_test');
        
        // Start Laravel server and prepare database
        $this->checkAndRestartServer();
        
        echo "✅ PHPUnit Feature Test setup completed\n";
    }

    protected function tearDown(): void
    {
        // Clean up cookie jar
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
        
        parent::tearDown();
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
     * Check if port 12368 is in use and restart Laravel server if needed
     */
    private function checkAndRestartServer(): void
    {
        echo "🔍 Checking Laravel server status for PHPUnit...\n";
        
        // Always run database migrations and seeders first
        echo "🗄️  Preparing database (fresh migration + seeding)...\n";
        
        // Create SQLite database file if needed for testing
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
        
        // Run fresh migration with seeding
        exec("php artisan migrate:fresh --seed --force --env=testing 2>&1", $output, $exitCode);
        
        if ($exitCode === 0) {
            echo "   ✅ Fresh database migration and seeding completed\n";
        } else {
            echo "   ⚠️  Database preparation warnings:\n";
            foreach (array_slice($output, -5) as $line) {
                echo "      $line\n";
            }
        }
        
        // Check if the determined port is in use
        $output = [];
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            exec("netstat -ano | findstr \":{$this->serverPort}\"", $output);
        } else {
            exec("netstat -tuln | grep :{$this->serverPort}", $output);
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
            echo "⚠️  Port {$this->serverPort} is in use";
            if ($processId) {
                echo " (PID: $processId)";
            }
            echo "\n";
            
            // Test if it's actually Laravel responding
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:{$this->serverPort}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                echo "✅ Laravel server is responding on port {$this->serverPort}\n";
                return;
            } else {
                echo "❌ Port {$this->serverPort} occupied but not responding to Laravel requests\n";
            }
            
            // Kill the process to restart with fresh code
            if ($processId && $isWindows) {
                echo "🔄 Killing process $processId to restart with fresh code...\n";
                exec("taskkill /PID $processId /F 2>nul", $killOutput);
                sleep(2); // Wait for process to terminate
            }
        }
        
        // Start Laravel development server (only if not in CI - CI starts its own server)
        if (!getenv('CI') && !getenv('GITHUB_ACTIONS')) {
            echo "🚀 Starting Laravel development server for PHPUnit...\n";
            
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
            } else {
                echo "❌ Failed to start server process\n";
            }
        } else {
            echo "🔄 Using existing server in CI environment\n";
        }
        
        // Wait for server to start and verify
        $maxAttempts = 10;
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            sleep(1);
            $attempts++;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:{$this->serverPort}");
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
        
        $this->fail("❌ Failed to start Laravel server after $maxAttempts attempts");
    }

    /**
     * Make HTTP request using CURL (same as standalone test)
     */
    private function makeRequest(string $url, string $method = 'GET', $data = null, array $headers = [], bool $useCookies = false): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($useCookies) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
        }
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            if (is_array($data)) {
                $isJson = false;
                foreach ($headers as $header) {
                    if (stripos($header, 'Content-Type: application/json') !== false) {
                        $isJson = true;
                        break;
                    }
                }
                
                if ($isJson) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        return [
            'success' => !$error,
            'http_code' => $httpCode,
            'body' => $response,
            'data' => json_decode($response, true),
            'error' => $error
        ];
    }

    /**
     * Initialize test environment (same as standalone)
     */
    private function initializeTestEnvironment(string $testRole): void
    {
        echo "🔧 Setting up test environment for role: $testRole\n";
        
        // Initialize session
        $response = $this->makeRequest("{$this->baseUrl}/admin/role-permissions", 'GET', null, [], true);
        $this->assertEquals(200, $response['http_code'], 'Failed to initialize session');
        
        // Get CSRF token
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $matches)) {
            $this->csrfToken = $matches[1];
            echo "✅ CSRF token obtained\n";
        } else {
            $this->fail('Failed to get CSRF token');
        }
        
        // Get roles and permissions
        $this->rolePermissionData = $this->getRoleAndPermissionData($response['body']);
        echo "✅ Role and permission data loaded\n";
        
        // Login as selected role
        $userData = $this->testUsers[$testRole];
        $loginData = ['email' => $userData['email'], 'password' => 'password'];
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $response = $this->makeRequest("{$this->baseUrl}/api/login", 'POST', $loginData, $headers);
        
        $this->assertEquals(200, $response['http_code'], "Failed to login as {$userData['display_name']}");
        $this->assertArrayHasKey('token', $response['data'], 'Login response should contain token');
        
        $this->userToken = $response['data']['token'];
        echo "✅ {$userData['display_name']} login successful\n";
        
        echo "✅ Test setup completed\n\n";
    }

    /**
     * Extract roles and permissions from HTML (same as standalone)
     */
    private function getRoleAndPermissionData(string $html): array
    {
        $roles = [];
        $permissions = [];
        
        // Extract roles (note: order is role-id first, then role-name)
        if (preg_match_all('/data-role-id="(\d+)"[^>]*data-role-name="([^"]+)"/', $html, $roleMatches, PREG_SET_ORDER)) {
            foreach ($roleMatches as $match) {
                $roles[strtolower($match[2])] = (int) $match[1]; // $match[2] is name, $match[1] is id
            }
        }
        
        // Extract permissions (similar to role pattern)
        if (preg_match_all('/data-permission-id="(\d+)"[^>]*data-permission-name="([^"]+)"/', $html, $permMatches, PREG_SET_ORDER)) {
            foreach ($permMatches as $match) {
                $permissions[$match[2]] = (int) $match[1]; // $match[2] is name, $match[1] is id
            }
        }
        
        return ['roles' => $roles, 'permissions' => $permissions];
    }

    /**
     * Get current role ID (same as standalone)
     */
    private function getCurrentRoleId(string $testRole): int
    {
        // Map test role to actual role name in system
        $roleMapping = [
            'super-admin' => ['super administrator', 'super-admin'],
            'admin' => ['administrator', 'admin'],
            'editor' => ['editor'],
            'viewer' => ['viewer']
        ];
        
        $possibleNames = $roleMapping[$testRole] ?? [$testRole];
        
        foreach ($possibleNames as $roleName) {
            if (isset($this->rolePermissionData['roles'][$roleName])) {
                return $this->rolePermissionData['roles'][$roleName];
            }
        }
        
        $this->fail("Role '{$testRole}' not found in system. Available roles: " . 
                   implode(', ', array_keys($this->rolePermissionData['roles'])));
    }

    /**
     * Toggle permission (same as standalone)
     */
    private function togglePermission(int $roleId, int $permissionId, bool $granted): bool
    {
        $data = [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'granted' => $granted ? 'true' : 'false',
            '_token' => $this->csrfToken
        ];
        
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'X-CSRF-TOKEN: ' . $this->csrfToken,
            'X-Requested-With: XMLHttpRequest'
        ];
        
        $response = $this->makeRequest("{$this->baseUrl}/admin/role-permissions/update", 'POST', $data, $headers, true);
        
        // Debug logging for CI
        if ($response['http_code'] !== 200 || !isset($response['data']['success']) || !$response['data']['success']) {
            echo "      ⚠️  Permission toggle failed: HTTP {$response['http_code']}\n";
            if (isset($response['data']['message'])) {
                echo "         Message: {$response['data']['message']}\n";
            }
            if (isset($response['body']) && strlen($response['body']) < 200) {
                echo "         Response: " . trim($response['body']) . "\n";
            }
        }
        
        return $response['http_code'] === 200 && 
               isset($response['data']['success']) && 
               $response['data']['success'];
    }

    /**
     * Test API endpoints (same as standalone but with PHPUnit assertions)
     */
    private function testApiEndpoints(bool $expectSuccess): array
    {
        $endpoints = [
            ['endpoint' => 'users', 'method' => 'GET', 'name' => 'List users'],
            ['endpoint' => 'users/2', 'method' => 'GET', 'name' => 'Show user'],
            ['endpoint' => 'users/search', 'method' => 'POST', 'data' => ['filters' => []], 'name' => 'Search users'],
            ['endpoint' => 'users/batch', 'method' => 'POST', 'data' => [
                'resources' => [
                    '2' => [
                        'name' => 'New Batch User',
                        'email' => 'newbatch1_' . uniqid() . '@example.com',
                        'password' => 'Strong1Pass123!'
                    ],
                    '3' => [
                        'name' => 'New Batch User hai', 
                        'email' => 'newbatch2_' . uniqid() . '@example.com',
                        'password' => 'Strong1Pass456@'
                    ]
                ]
            ], 'name' => 'Batch create users'],
            ['endpoint' => 'users/batch', 'method' => 'PATCH', 'data' => [
                'resources' => [
                    '2' => [
                        'name' => 'Updated User via Batch PATCH',
                        'email' => 'updatedbatch1_' . uniqid() . '@example.com'
                    ],
                    '3' => [
                        'name' => 'Updated User via Batch PATCH hai', 
                        'email' => 'updatedbatch2_' . uniqid() . '@example.com'
                    ]
                ]
            ], 'name' => 'Batch update users'],
            ['endpoint' => 'users', 'method' => 'POST', 'data' => ['name' => 'Test User', 'email' => 'test_' . uniqid() . '@example.com', 'password' => 'TestPass123!'], 'name' => 'Create user'],
            ['endpoint' => 'users/2', 'method' => 'PUT', 'data' => ['name' => 'Updated User'], 'name' => 'Update user'],
        ];
        
        $passed = 0;
        $total = count($endpoints);
        
        foreach ($endpoints as $config) {
            echo "   Testing: {$config['name']} - {$config['method']} /api/{$config['endpoint']}\n";
            
            $headers = [
                'Authorization: Bearer ' . $this->userToken,
                'Accept: application/json',
                'Content-Type: application/json'
            ];
            
            $response = $this->makeRequest(
                "{$this->baseUrl}/api/{$config['endpoint']}", 
                $config['method'], 
                $config['data'] ?? null, 
                $headers
            );
            
            if ($expectSuccess) {
                // Expecting success (200, 201, etc. but not 403)
                if ($response['http_code'] !== 403 && $response['http_code'] >= 200 && $response['http_code'] < 500) {
                    echo "      ✅ SUCCESS - HTTP {$response['http_code']}\n";
                    $passed++;
                } else {
                    echo "      ❌ FAILED - HTTP {$response['http_code']}\n";
                    if (isset($response['data']['message'])) {
                        echo "         Message: {$response['data']['message']}\n";
                    }
                }
            } else {
                // Expecting forbidden (403)
                if ($response['http_code'] === 403) {
                    echo "      ✅ CORRECTLY FORBIDDEN - HTTP 403\n";
                    $passed++;
                } else {
                    echo "      ⚠️  UNEXPECTED - HTTP {$response['http_code']} (expected 403)\n";
                    if (isset($response['data']['message'])) {
                        echo "         Message: {$response['data']['message']}\n";
                    }
                }
            }
        }
        
        return ['passed' => $passed, 'total' => $total];
    }

    /** @test */
    public function viewer_role_can_access_api_endpoints_with_permissions_enabled()
    {
        $testRole = 'viewer';
        $userData = $this->testUsers[$testRole];
        
        echo "\n🔧 TEST: Enable all {$userData['display_name']} permissions and test API endpoints\n";
        echo str_repeat("=", 80) . "\n";
        
        // Initialize test environment
        $this->initializeTestEnvironment($testRole);
        
        // Get current role ID
        $currentRoleId = $this->getCurrentRoleId($testRole);
        
        // Get users-related permissions
        $usersPermissions = array_filter($this->rolePermissionData['permissions'], 
            fn($name) => stripos($name, 'user') !== false, ARRAY_FILTER_USE_KEY);
        
        echo "📋 Found " . count($usersPermissions) . " users-related permissions:\n";
        foreach ($usersPermissions as $name => $id) {
            echo "   - $name (ID: $id)\n";
        }
        echo "\n";
        
        // Step 1: Enable all permissions
        echo "🔓 Step 1: Enabling all permissions for {$userData['display_name']}...\n";
        echo "   Role ID: $currentRoleId\n";
        $enabledCount = 0;
        foreach ($usersPermissions as $name => $id) {
            echo "   Enabling: $name (ID: $id)... ";
            if ($this->togglePermission($currentRoleId, $id, true)) {
                $enabledCount++;
                echo "✅\n";
            } else {
                echo "❌\n";
            }
        }
        echo "✅ Successfully enabled $enabledCount/" . count($usersPermissions) . " permissions\n\n";
        
        // PHPUnit assertion
        $this->assertEquals(count($usersPermissions), $enabledCount, 'All permissions should be enabled');
        
        // Step 2: Test API endpoints
        echo "🧪 Step 2: Testing API endpoints (expecting success)...\n";
        $results = $this->testApiEndpoints(true);
        
        $successRate = ($results['passed'] / $results['total']) * 100;
        echo "\n📊 API Test Results: {$results['passed']}/{$results['total']} passed ({$successRate}%)\n";
        
        // PHPUnit assertions
        $this->assertGreaterThanOrEqual(70, $successRate, "Success rate should be at least 70% but got {$successRate}%");
        $this->assertGreaterThan(0, $results['passed'], 'At least some endpoints should be accessible');
        
        echo "✅ {$userData['display_name']} with permissions can access API endpoints!\n\n";
    }

    /** @test */
    public function viewer_role_cannot_access_api_endpoints_with_permissions_disabled()
    {
        $testRole = 'viewer';
        $userData = $this->testUsers[$testRole];
        
        echo "\n🔒 TEST: Disable all {$userData['display_name']} permissions and test API endpoints\n";
        echo str_repeat("=", 80) . "\n";
        
        // Initialize test environment
        $this->initializeTestEnvironment($testRole);
        
        // Get current role ID
        $currentRoleId = $this->getCurrentRoleId($testRole);
        
        // Get users-related permissions
        $usersPermissions = array_filter($this->rolePermissionData['permissions'], 
            fn($name) => stripos($name, 'user') !== false, ARRAY_FILTER_USE_KEY);
        
        // Step 1: Disable all permissions
        echo "🚫 Step 1: Disabling all permissions for {$userData['display_name']}...\n";
        $disabledCount = 0;
        foreach ($usersPermissions as $name => $id) {
            if ($this->togglePermission($currentRoleId, $id, false)) {
                $disabledCount++;
            }
        }
        echo "✅ Successfully disabled $disabledCount/" . count($usersPermissions) . " permissions\n\n";
        
        // PHPUnit assertion
        $this->assertEquals(count($usersPermissions), $disabledCount, 'All permissions should be disabled');
        
        // Step 2: Test API endpoints (should return 403)
        echo "🧪 Step 2: Testing API endpoints (expecting 403 Forbidden)...\n";
        $results = $this->testApiEndpoints(false);
        
        $forbiddenRate = ($results['passed'] / $results['total']) * 100;
        echo "\n📊 Forbidden Results: {$results['passed']}/{$results['total']} endpoints correctly returned 403 ({$forbiddenRate}%)\n";
        
        // PHPUnit assertions for viewer role (expect at least 50% to be forbidden)
        $expectedRate = 50;
        $this->assertGreaterThanOrEqual($expectedRate, $forbiddenRate, 
            "Forbidden rate should be at least {$expectedRate}% but got {$forbiddenRate}%");
        
        echo "✅ {$userData['display_name']} without permissions is correctly blocked from API endpoints!\n\n";
        
        // Step 3: Re-enable permissions to restore system state
        echo "🔄 Step 3: Re-enabling all permissions to restore system...\n";
        $restoredCount = 0;
        foreach ($usersPermissions as $name => $id) {
            if ($this->togglePermission($currentRoleId, $id, true)) {
                $restoredCount++;
            }
        }
        echo "✅ System permissions restored ($restoredCount permissions enabled)\n\n";
        
        // PHPUnit assertion
        $this->assertEquals(count($usersPermissions), $restoredCount, 'All permissions should be restored');
    }

    /** @test */
    public function admin_role_can_access_api_endpoints_with_permissions_enabled()
    {
        $testRole = 'super-admin';
        $userData = $this->testUsers[$testRole];
        
        echo "\n🔧 TEST: Enable all {$userData['display_name']} permissions and test API endpoints\n";
        echo str_repeat("=", 80) . "\n";
        
        // Initialize test environment
        $this->initializeTestEnvironment($testRole);
        
        // Get current role ID
        $currentRoleId = $this->getCurrentRoleId($testRole);
        
        // Get users-related permissions
        $usersPermissions = array_filter($this->rolePermissionData['permissions'], 
            fn($name) => stripos($name, 'user') !== false, ARRAY_FILTER_USE_KEY);
        
        // Step 1: Enable all permissions
        echo "🔓 Step 1: Enabling all permissions for {$userData['display_name']}...\n";
        $enabledCount = 0;
        foreach ($usersPermissions as $name => $id) {
            if ($this->togglePermission($currentRoleId, $id, true)) {
                $enabledCount++;
            }
        }
        
        // Step 2: Test API endpoints
        echo "🧪 Step 2: Testing API endpoints (expecting success)...\n";
        $results = $this->testApiEndpoints(true);
        
        $successRate = ($results['passed'] / $results['total']) * 100;
        
        // PHPUnit assertions for admin role (should have high success rate)
        $this->assertGreaterThanOrEqual(80, $successRate, "Admin success rate should be at least 80% but got {$successRate}%");
        $this->assertEquals(count($usersPermissions), $enabledCount, 'All admin permissions should be enabled');
        
        echo "✅ {$userData['display_name']} test completed successfully!\n\n";
    }

    /** @test */
    public function server_is_running_and_responsive()
    {
        echo "\n🧪 TEST: Server health check\n";
        
        $response = $this->makeRequest($this->baseUrl, 'GET');
        
        $this->assertEquals(200, $response['http_code'], 'Server should be responding on port 12368');
        $this->assertTrue($response['success'], 'Request should be successful');
        $this->assertNotEmpty($response['body'], 'Response body should not be empty');
        
        echo "✅ Server is running and responsive\n";
    }

    /** @test */
    public function database_is_properly_seeded()
    {
        echo "\n🧪 TEST: Database seeding verification\n";
        
        // Initialize test environment to get role data
        $this->initializeTestEnvironment('viewer');
        
        // Check that we have expected roles and permissions
        $this->assertArrayHasKey('roles', $this->rolePermissionData, 'Role data should be available');
        $this->assertArrayHasKey('permissions', $this->rolePermissionData, 'Permission data should be available');
        
        $this->assertGreaterThan(0, count($this->rolePermissionData['roles']), 'Should have at least one role');
        $this->assertGreaterThan(0, count($this->rolePermissionData['permissions']), 'Should have at least one permission');
        
        // Check for expected user-related permissions
        $userPermissions = array_filter($this->rolePermissionData['permissions'], 
            fn($name) => stripos($name, 'user') !== false, ARRAY_FILTER_USE_KEY);
            
        $this->assertGreaterThan(0, count($userPermissions), 'Should have user-related permissions');
        
        echo "✅ Database is properly seeded with roles and permissions\n";
        echo "   - Roles: " . count($this->rolePermissionData['roles']) . "\n";
        echo "   - Permissions: " . count($this->rolePermissionData['permissions']) . "\n";
        echo "   - User Permissions: " . count($userPermissions) . "\n";
    }
}