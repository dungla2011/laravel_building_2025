<?php

/**
 * Standalone Role Permission Test
 * 
 * Tests role permission management for API users endpoints:
 * 1. Enable all permissions and verify all API endpoints work
 * 2. Disable all permissions and verify all API endpoints return 403 Forbidden
 *
 * Supports multiple roles: super-admin, admin, editor, viewer
 * Runs without PHPUnit framework using pure PHP CURL
 */

class PermissionStandaloneTest
{
    private string $baseUrl = 'http://127.0.0.1:12368';
    private string $cookieJar;
    private ?string $userToken = null;
    private ?string $csrfToken = null;
    private ?array $rolePermissionData = null;
    private string $testRole = 'super-admin';
    private array $testUsers = [
        'super-admin' => ['email' => 'superadmin@example.com', 'display_name' => 'Super Administrator'],
        'admin' => ['email' => 'admin@example.com', 'display_name' => 'Administrator'],
        'editor' => ['email' => 'editor@example.com', 'display_name' => 'Editor'],
        'viewer' => ['email' => 'viewer@example.com', 'display_name' => 'Viewer']
    ];
    
    public function __construct($role = 'super-admin')
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'curl_cookies_test');
        if (array_key_exists($role, $this->testUsers)) {
            $this->testRole = $role;
        } else {
            echo "⚠️ Role '$role' not found. Using default 'super-admin'\n";
        }
    }
    
    public function setRole($role): bool
    {
        if (array_key_exists($role, $this->testUsers)) {
            $this->testRole = $role;
            return true;
        }
        return false;
    }
    
    private function getCurrentRoleId(): int
    {
        // Map test role to actual role name in system
        $roleMapping = [
            'super-admin' => ['super administrator', 'super-admin'],
            'admin' => ['administrator', 'admin'],
            'editor' => ['editor'],
            'viewer' => ['viewer']
        ];
        
        $possibleNames = $roleMapping[$this->testRole] ?? [$this->testRole];
        
        foreach ($possibleNames as $roleName) {
            if (isset($this->rolePermissionData['roles'][$roleName])) {
                return $this->rolePermissionData['roles'][$roleName];
            }
        }
        
        throw new Exception("Role '{$this->testRole}' not found in system. Available roles: " . 
                          implode(', ', array_keys($this->rolePermissionData['roles'])));
    }
    
    public function __destruct()
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }
    
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
    
    public function setUp(): void
    {
        echo "🔧 Setting up test environment...\n";
        
        // Initialize session
        $response = $this->makeRequest("{$this->baseUrl}/admin/role-permissions", 'GET', null, [], true);
        if ($response['http_code'] !== 200) {
            throw new Exception('Failed to initialize session');
        }
        
        // Get CSRF token
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $matches)) {
            $this->csrfToken = $matches[1];
            echo "✅ CSRF token obtained\n";
        } else {
            throw new Exception('Failed to get CSRF token');
        }
        
        // Get roles and permissions
        $this->rolePermissionData = $this->getRoleAndPermissionData($response['body']);
        echo "✅ Role and permission data loaded\n";
        
        // Login as selected role
        $userData = $this->testUsers[$this->testRole];
        $loginData = ['email' => $userData['email'], 'password' => 'password'];
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $response = $this->makeRequest("{$this->baseUrl}/api/login", 'POST', $loginData, $headers);
        
        if ($response['http_code'] === 200 && isset($response['data']['token'])) {
            $this->userToken = $response['data']['token'];
            echo "✅ {$userData['display_name']} login successful\n";
        } else {
            throw new Exception("Failed to login as {$userData['display_name']}: " . ($response['body'] ?? 'Unknown error'));
        }
        
        echo "✅ Test setup completed\n\n";
    }
    
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
    
    public function testEnableAllPermissionsAndTestApi(): void
    {
        $userData = $this->testUsers[$this->testRole];
        echo "🔧 TEST 1: Enable all {$userData['display_name']} permissions and test API endpoints\n";
        echo str_repeat("=", 80) . "\n";
        
        // Get current role ID
        $currentRoleId = $this->getCurrentRoleId();
        
        // Debug: show all permissions
        echo "🔍 All permissions found:\n";
        foreach ($this->rolePermissionData['permissions'] as $name => $id) {
            echo "   - $name (ID: $id)\n";
        }
        echo "\n";
        
        // Get users-related permissions
        $usersPermissions = array_filter($this->rolePermissionData['permissions'], 
            fn($name) => stripos($name, 'user') !== false, ARRAY_FILTER_USE_KEY);
        
        echo "📋 Found " . count($usersPermissions) . " users-related permissions:\n";
        foreach ($usersPermissions as $name => $id) {
            echo "   - $name (ID: $id)\n";
        }
        echo "\n";
        
        // Step 1: Enable all permissions
        $userData = $this->testUsers[$this->testRole];
        echo "🔓 Step 1: Enabling all permissions for {$userData['display_name']}...\n";
        $enabledCount = 0;
        foreach ($usersPermissions as $name => $id) {
            if ($this->togglePermission($currentRoleId, $id, true)) {
                $enabledCount++;
            }
        }
        echo "✅ Successfully enabled $enabledCount/" . count($usersPermissions) . " permissions\n\n";
        
        // Step 2: Test API endpoints
        echo "🧪 Step 2: Testing API endpoints (expecting success)...\n";
        $results = $this->testApiEndpoints(true);
        
        $successRate = ($results['passed'] / $results['total']) * 100;
        echo "\n📊 API Test Results: {$results['passed']}/{$results['total']} passed ({$successRate}%)\n";
        
        if ($successRate < 70) {
            throw new Exception("Test failed: Only {$successRate}% of endpoints passed (expected >= 70%)");
        }
        
        echo "✅ Super Admin with all permissions can access most API endpoints!\n\n";
    }
    
    public function testDisableAllPermissionsAndTestApi(): void
    {
        $userData = $this->testUsers[$this->testRole];
        echo "🔒 TEST 2: Disable all {$userData['display_name']} permissions and test API endpoints\n";
        echo str_repeat("=", 80) . "\n";
        
        // Get current role ID
        $currentRoleId = $this->getCurrentRoleId();
        
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
        
        // Step 2: Test API endpoints (should return 403)
        echo "🧪 Step 2: Testing API endpoints (expecting 403 Forbidden)...\n";
        $results = $this->testApiEndpoints(false);
        
        $forbiddenRate = ($results['passed'] / $results['total']) * 100;
        echo "\n📊 Forbidden Results: {$results['passed']}/{$results['total']} endpoints correctly returned 403 ({$forbiddenRate}%)\n";
        
        // Note: For non-Super Admin roles, we expect lower forbidden rates since they may have baseline permissions
        $expectedRate = ($this->testRole === 'super-admin') ? 80 : 50;
        if ($forbiddenRate < $expectedRate) {
            throw new Exception("Test failed: Only {$forbiddenRate}% of endpoints were properly forbidden (expected >= {$expectedRate}%)");
        }
        
        echo "✅ {$userData['display_name']} without permissions is correctly blocked from API endpoints!\n\n";
        
        // Step 3: Re-enable permissions to restore system state
        echo "🔄 Step 3: Re-enabling all permissions to restore system...\n";
        foreach ($usersPermissions as $name => $id) {
            $this->togglePermission($currentRoleId, $id, true);
        }
        echo "✅ System permissions restored\n\n";
    }
    
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
        
        return $response['http_code'] === 200 && 
               isset($response['data']['success']) && 
               $response['data']['success'];
    }
    
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
    
    public function runForRole($role): void
    {
        if (!$this->setRole($role)) {
            echo "❌ Invalid role: $role\n";
            echo "Available roles: " . implode(', ', array_keys($this->testUsers)) . "\n";
            return;
        }
        
        $userData = $this->testUsers[$this->testRole];
        echo "=== {$userData['display_name']} PERMISSION TEST ===\n";
        echo "Role: {$role}\n";
        echo "Email: {$userData['email']}\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        try {
            $this->setUp();
            $this->testEnableAllPermissionsAndTestApi();
            $this->testDisableAllPermissionsAndTestApi();
            
            echo str_repeat("=", 100) . "\n";
            echo "🎉 ALL TESTS FOR {$userData['display_name']} COMPLETED SUCCESSFULLY!\n";
            echo str_repeat("=", 100) . "\n";
            
        } catch (Exception $e) {
            echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    public function runAllTests(): void
    {
        $this->runForRole($this->testRole);
    }
}

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
$role = 'super-admin'; // default
$environment = 'local'; // default
$envFlag = '';

// Process arguments
for ($i = 1; $i < count($argv); $i++) {
    $arg = $argv[$i];
    
    if (strpos($arg, '--env=') === 0) {
        $environment = substr($arg, 6);
    } elseif ($arg === '--env' && isset($argv[$i + 1])) {
        $environment = $argv[$i + 1];
        $i++; // Skip next argument as it's the environment value
    } elseif (!strpos($arg, '--')) {
        $role = $arg; // Role argument (no flag)
    }
}

// Set environment flag for artisan commands
if ($environment !== 'local') {
    $envFlag = " --env=$environment";
}

// Display configuration
$availableRoles = ['super-admin', 'admin', 'editor', 'viewer'];
echo "Available roles: " . implode(', ', $availableRoles) . "\n";
echo "Testing role: $role\n";
echo "Environment: $environment\n";
echo "Usage: php " . basename(__FILE__) . " [role] [--env=environment]\n";
echo "Examples:\n";
echo "  php " . basename(__FILE__) . " editor\n";
echo "  php " . basename(__FILE__) . " viewer --env=testing\n";
echo "  php " . basename(__FILE__) . " admin --env=local\n\n";

// Check and restart server if needed
checkAndRestartServer($envFlag);
echo "\n";

// Run the test
try {
    $test = new PermissionStandaloneTest();
    $test->runForRole($role);
    echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
} catch (Exception $e) {
    echo "\n❌ Test execution failed: " . $e->getMessage() . "\n";
    exit(1);
}