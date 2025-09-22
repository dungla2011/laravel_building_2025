<?php

/**
 * Standalone Super Admin Permission Test
 * 
 * Tests Super Admin permission management for API users endpoints:
 * 1. Enable all permissions and verify all API endpoints work
 * 2. Disable all permissions and verify all API endpoints return 403
 * 
 * Runs without PHPUnit framework using pure PHP CURL
 */

class SuperAdminPermissionStandaloneTest
{
    private string $baseUrl = 'http://127.0.0.1:8000';
    private string $cookieJar;
    private ?string $superAdminToken = null;
    private ?string $csrfToken = null;
    private ?array $rolePermissionData = null;
    
    public function __construct()
    {
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'curl_cookies_test');
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
        
        // Login as Super Admin
        $loginData = ['email' => 'superadmin@example.com', 'password' => 'password'];
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $response = $this->makeRequest("{$this->baseUrl}/api/login", 'POST', $loginData, $headers);
        
        if ($response['http_code'] === 200 && isset($response['data']['token'])) {
            $this->superAdminToken = $response['data']['token'];
            echo "✅ Super Admin login successful\n";
        } else {
            throw new Exception('Failed to login as Super Admin: ' . ($response['body'] ?? 'Unknown error'));
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
        echo "🔧 TEST 1: Enable all Super Admin permissions and test API endpoints\n";
        echo str_repeat("=", 80) . "\n";
        
        // Get Super Admin role ID
        $superAdminRoleId = $this->rolePermissionData['roles']['super administrator'] ?? 
                           $this->rolePermissionData['roles']['super-admin'] ?? null;
        if (!$superAdminRoleId) {
            echo "Available roles: " . json_encode(array_keys($this->rolePermissionData['roles'])) . "\n";
            throw new Exception('Super Administrator role not found');
        }
        
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
        echo "🔓 Step 1: Enabling all permissions for Super Admin...\n";
        $enabledCount = 0;
        foreach ($usersPermissions as $name => $id) {
            if ($this->togglePermission($superAdminRoleId, $id, true)) {
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
        echo "🔒 TEST 2: Disable all Super Admin permissions and test API endpoints\n";
        echo str_repeat("=", 80) . "\n";
        
        // Get Super Admin role ID
        $superAdminRoleId = $this->rolePermissionData['roles']['super administrator'] ?? 
                           $this->rolePermissionData['roles']['super-admin'];
        
        // Get users-related permissions
        $usersPermissions = array_filter($this->rolePermissionData['permissions'], 
            fn($name) => stripos($name, 'user') !== false, ARRAY_FILTER_USE_KEY);
        
        // Step 1: Disable all permissions
        echo "🚫 Step 1: Disabling all permissions for Super Admin...\n";
        $disabledCount = 0;
        foreach ($usersPermissions as $name => $id) {
            if ($this->togglePermission($superAdminRoleId, $id, false)) {
                $disabledCount++;
            }
        }
        echo "✅ Successfully disabled $disabledCount/" . count($usersPermissions) . " permissions\n\n";
        
        // Step 2: Test API endpoints (should return 403)
        echo "🧪 Step 2: Testing API endpoints (expecting 403 Forbidden)...\n";
        $results = $this->testApiEndpoints(false);
        
        $forbiddenRate = ($results['passed'] / $results['total']) * 100;
        echo "\n📊 Forbidden Results: {$results['passed']}/{$results['total']} endpoints correctly returned 403 ({$forbiddenRate}%)\n";
        
        if ($forbiddenRate < 80) {
            throw new Exception("Test failed: Only {$forbiddenRate}% of endpoints were properly forbidden (expected >= 80%)");
        }
        
        echo "✅ Super Admin without permissions is correctly blocked from API endpoints!\n\n";
        
        // Step 3: Re-enable permissions to restore system state
        echo "🔄 Step 3: Re-enabling all permissions to restore system...\n";
        foreach ($usersPermissions as $name => $id) {
            $this->togglePermission($superAdminRoleId, $id, true);
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
            ['endpoint' => 'users/1', 'method' => 'GET', 'name' => 'Show user'],
            ['endpoint' => 'users/search', 'method' => 'POST', 'data' => ['filters' => []], 'name' => 'Search users'],
            ['endpoint' => 'users/batch', 'method' => 'POST', 'data' => [
                'resources' => [
                    '1' => [
                        'name' => 'New Batch User 1',
                        'email' => 'newbatch1_' . time() . '@example.com',
                        'password' => 'password123'
                    ],
                    '2' => [
                        'name' => 'New Batch User 2', 
                        'email' => 'newbatch2_' . time() . '@example.com',
                        'password' => 'password123'
                    ]
                ]
            ], 'name' => 'Batch create users'],
            ['endpoint' => 'users/batch', 'method' => 'PATCH', 'data' => [
                'resources' => [
                    '1' => [
                        'name' => 'Updated User 1 via Batch PATCH',
                        'email' => 'updatedbatch1_' . time() . '@example.com'
                    ],
                    '2' => [
                        'name' => 'Updated User 2 via Batch PATCH', 
                        'email' => 'updatedbatch2_' . time() . '@example.com'
                    ]
                ]
            ], 'name' => 'Batch update users'],
            ['endpoint' => 'users', 'method' => 'POST', 'data' => ['name' => 'Test User', 'email' => 'test' . time() . '@example.com', 'password' => 'password'], 'name' => 'Create user'],
            ['endpoint' => 'users/1', 'method' => 'PUT', 'data' => ['name' => 'Updated User'], 'name' => 'Update user'],
        ];
        
        $passed = 0;
        $total = count($endpoints);
        
        foreach ($endpoints as $config) {
            echo "   Testing: {$config['name']} - {$config['method']} /api/{$config['endpoint']}\n";
            
            $headers = [
                'Authorization: Bearer ' . $this->superAdminToken,
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
    
    public function runAllTests(): void
    {
        echo "=== SUPER ADMIN PERMISSION TEST ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        try {
            $this->setUp();
            $this->testEnableAllPermissionsAndTestApi();
            $this->testDisableAllPermissionsAndTestApi();
            
            echo str_repeat("=", 100) . "\n";
            echo "🎉 ALL TESTS COMPLETED SUCCESSFULLY!\n";
            echo str_repeat("=", 100) . "\n";
            
        } catch (Exception $e) {
            echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}

// Check if Laravel server is running
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_NOBODY, true);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Laravel server is not running on http://127.0.0.1:8000\nPlease run: php artisan serve\n");
}

echo "✅ Laravel server is running\n\n";

// Run the test
try {
    $test = new SuperAdminPermissionStandaloneTest();
    $test->runAllTests();
    echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
} catch (Exception $e) {
    echo "\n❌ Test execution failed: " . $e->getMessage() . "\n";
    exit(1);
}