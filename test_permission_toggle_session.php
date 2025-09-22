<?php

/**
 * Test Enable/Disable Permission via Web Interface (Session-based)
 * 
 * Test POST to /admin/role-permissions/update để enable/disable quyền
 * Sử dụng session và cookies để handle CSRF properly
 */

// API base URL
$base_url = 'http://127.0.0.1:8000';

echo "=== PERMISSION TOGGLE TEST (Session-based) ===\n\n";

// Cookie storage
$cookieJar = tempnam(sys_get_temp_dir(), 'curl_cookies');

function makeRequest($url, $method = 'GET', $data = null, $headers = [], $useCookies = false) {
    global $cookieJar;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Set method
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    // Handle cookies
    if ($useCookies) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    }
    
    // Set data for POST/PUT
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        if (is_array($data)) {
            // Check if Content-Type is JSON
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
    
    // Set headers
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'http_code' => $httpCode,
        'body' => $response,
        'data' => json_decode($response, true)
    ];
}

function initializeSession() {
    global $base_url;
    
    echo "Initializing session by visiting admin page...\n";
    $response = makeRequest("$base_url/admin/role-permissions", 'GET', null, [], true);
    
    if ($response['http_code'] == 200) {
        echo "✅ Session initialized\n";
        return true;
    } else {
        echo "❌ Failed to initialize session\n";
        return false;
    }
}

function getCSRFTokenFromPage() {
    global $base_url;
    
    echo "Getting CSRF token from admin page...\n";
    $response = makeRequest("$base_url/admin/role-permissions", 'GET', null, [], true);
    
    if ($response['http_code'] == 200) {
        // Extract CSRF token from meta tag
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $matches)) {
            echo "✅ CSRF token found: " . substr($matches[1], 0, 20) . "...\n";
            return $matches[1];
        }
    }
    
    echo "❌ Could not get CSRF token\n";
    return false;
}

function getRoleAndPermissionIds() {
    global $base_url;
    
    echo "\nGetting role and permission IDs from admin page...\n";
    $response = makeRequest("$base_url/admin/role-permissions", 'GET', null, [], true);
    
    if ($response['http_code'] != 200) {
        echo "❌ Could not load admin page\n";
        return false;
    }
    
    $html = $response['body'];
    
    // Extract viewer role ID (role name = "Viewer")
    if (preg_match('/data-role-name="Viewer"[^>]*data-role-id="(\d+)"/', $html, $matches) ||
        preg_match('/data-role-id="(\d+)"[^>]*data-role-name="Viewer"/', $html, $matches)) {
        $viewerRoleId = $matches[1];
        echo "✅ Found Viewer role ID: $viewerRoleId\n";
    } else {
        echo "❌ Could not find Viewer role ID\n";
        echo "Debug: Searching for role data...\n";
        if (preg_match_all('/data-role-name="([^"]+)"[^>]*data-role-id="(\d+)"/', $html, $allMatches, PREG_SET_ORDER)) {
            foreach ($allMatches as $match) {
                echo "Found role: {$match[1]} with ID: {$match[2]}\n";
            }
        }
        return false;
    }
    
    // Extract permission ID for "View All Users" (this is user.index)
    if (preg_match('/data-permission-name="View All Users"[^>]*data-permission-id="(\d+)"/', $html, $matches) ||
        preg_match('/data-permission-id="(\d+)"[^>]*data-permission-name="View All Users"/', $html, $matches)) {
        $permissionId = $matches[1];
        echo "✅ Found 'View All Users' permission ID: $permissionId\n";
    } else {
        echo "❌ Could not find 'View All Users' permission ID\n";
        echo "Debug: Searching for permission data...\n";
        if (preg_match_all('/data-permission-name="([^"]+)"[^>]*data-permission-id="(\d+)"/', $html, $allMatches, PREG_SET_ORDER)) {
            foreach ($allMatches as $match) {
                echo "Found permission: {$match[1]} with ID: {$match[2]}\n";
                if (stripos($match[1], 'users') !== false) {
                    echo "  ^ This is a users permission\n";
                }
            }
        }
        return false;
    }
    
    return [
        'viewer_role_id' => $viewerRoleId,
        'permission_id' => $permissionId
    ];
}

function togglePermission($roleId, $permissionId, $granted, $csrfToken) {
    global $base_url;
    
    $action = $granted ? 'Enable' : 'Disable';
    echo "\n--- $action permission for role $roleId, permission $permissionId ---\n";
    
    $data = [
        'role_id' => $roleId,
        'permission_id' => $permissionId,
        'granted' => $granted ? 'true' : 'false',
        '_token' => $csrfToken
    ];
    
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
        'X-CSRF-TOKEN: ' . $csrfToken,
        'X-Requested-With: XMLHttpRequest'
    ];
    
    $response = makeRequest("$base_url/admin/role-permissions/update", 'POST', $data, $headers, true);
    
    echo "HTTP Code: " . $response['http_code'] . "\n";
    
    if ($response['http_code'] == 200 && isset($response['data']['success']) && $response['data']['success']) {
        echo "✅ Permission toggle successful!\n";
        if (isset($response['data']['message'])) {
            echo "Message: " . $response['data']['message'] . "\n";
        }
        return true;
    } else {
        echo "❌ Permission toggle failed!\n";
        echo "Response: " . $response['body'] . "\n";
        return false;
    }
}

function testApiAccess($token, $expectSuccess = true) {
    global $base_url;
    
    echo "\n--- Testing API access with current permissions ---\n";
    
    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'Content-Type: application/json'
    ];
    
    $response = makeRequest("$base_url/api/users", 'GET', null, $headers);
    
    echo "HTTP Code: " . $response['http_code'] . "\n";
    
    $success = $response['http_code'] == 200;
    
    if ($expectSuccess && $success) {
        echo "✅ API access successful as expected!\n";
    } elseif (!$expectSuccess && !$success) {
        echo "✅ API access blocked as expected!\n";
    } elseif ($expectSuccess && !$success) {
        echo "❌ API access failed but was expected to succeed!\n";
        echo "Response: " . $response['body'] . "\n";
    } else {
        echo "❌ API access succeeded but was expected to fail!\n";
    }
    
    return $success;
}

function loginViewer() {
    global $base_url;
    
    echo "--- Logging in as Viewer ---\n";
    
    $loginData = [
        'email' => 'viewer@example.com',
        'password' => 'password'
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    $response = makeRequest("$base_url/api/login", 'POST', $loginData, $headers);
    
    if ($response['http_code'] == 200 && isset($response['data']['token'])) {
        echo "✅ Viewer login successful!\n";
        return $response['data']['token'];
    } else {
        echo "❌ Viewer login failed!\n";
        echo "Response: " . $response['body'] . "\n";
        return false;
    }
}

// Clean up function
function cleanup() {
    global $cookieJar;
    if (file_exists($cookieJar)) {
        unlink($cookieJar);
    }
}

// Register cleanup
register_shutdown_function('cleanup');

// Main test flow
echo "Step 1: Initialize session with admin page\n";
if (!initializeSession()) {
    die("Cannot proceed without session\n");
}

echo "\nStep 2: Get CSRF token\n";
$csrfToken = getCSRFTokenFromPage();
if (!$csrfToken) {
    die("Cannot proceed without CSRF token\n");
}

echo "\nStep 3: Get role and permission IDs\n";
$ids = getRoleAndPermissionIds();
if (!$ids) {
    die("Cannot proceed without role/permission IDs\n");
}

echo "\nStep 4: Login as Viewer to get API token\n";
$viewerToken = loginViewer();
if (!$viewerToken) {
    die("Cannot proceed without viewer token\n");
}

echo "\nStep 5: Test current API access (should work initially)\n";
testApiAccess($viewerToken, true);

echo "\nStep 6: Disable user.index permission for Viewer\n";
$disableSuccess = togglePermission($ids['viewer_role_id'], $ids['permission_id'], false, $csrfToken);

if ($disableSuccess) {
    echo "\nStep 7: Test API access after disabling (should fail)\n";
    testApiAccess($viewerToken, false);
    
    echo "\nStep 8: Re-enable user.index permission for Viewer\n";
    togglePermission($ids['viewer_role_id'], $ids['permission_id'], true, $csrfToken);
    
    echo "\nStep 9: Test API access after re-enabling (should work)\n";
    testApiAccess($viewerToken, true);
} else {
    echo "\nSkipping remaining steps due to disable failure\n";
}

echo "\n=== TEST COMPLETED ===\n";