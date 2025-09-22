<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Base API Test Class using CURL
 * 
 * Provides helper methods for API testing with CURL
 * Based on simple_api_test.php and test_permission_toggle_session.php
 */
abstract class BaseApiTest extends TestCase
{
    protected string $baseUrl = 'http://127.0.0.1:8000';
    protected string $cookieJar;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'curl_cookies_test');
    }
    
    protected function tearDown(): void
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
        parent::tearDown();
    }
    
    /**
     * Make CURL request with optional cookies and JSON support
     */
    protected function makeRequest(string $url, string $method = 'GET', $data = null, array $headers = [], bool $useCookies = false): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Handle cookies
        if ($useCookies) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieJar);
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
            return ['error' => $error, 'http_code' => 0, 'success' => false];
        }
        
        return [
            'success' => true,
            'http_code' => $httpCode,
            'body' => $response,
            'data' => json_decode($response, true)
        ];
    }
    
    /**
     * Login user and get Bearer token
     */
    protected function loginUser(string $email, string $password): ?string
    {
        $loginData = [
            'email' => $email,
            'password' => $password
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $response = $this->makeRequest("{$this->baseUrl}/api/login", 'POST', $loginData, $headers);
        
        if ($response['http_code'] === 200 && isset($response['data']['token'])) {
            return $response['data']['token'];
        }
        
        return null;
    }
    
    /**
     * Initialize session by visiting admin page
     */
    protected function initializeSession(): bool
    {
        $response = $this->makeRequest("{$this->baseUrl}/admin/role-permissions", 'GET', null, [], true);
        return $response['http_code'] === 200;
    }
    
    /**
     * Get CSRF token from admin page
     */
    protected function getCSRFToken(): ?string
    {
        $response = $this->makeRequest("{$this->baseUrl}/admin/role-permissions", 'GET', null, [], true);
        
        if ($response['http_code'] === 200) {
            if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Get role and permission IDs from admin page
     */
    protected function getRoleAndPermissionIds(): ?array
    {
        $response = $this->makeRequest("{$this->baseUrl}/admin/role-permissions", 'GET', null, [], true);
        
        if ($response['http_code'] !== 200) {
            return null;
        }
        
        $html = $response['body'];
        $roles = [];
        $permissions = [];
        
        // Extract all roles
        if (preg_match_all('/data-role-name="([^"]+)"[^>]*data-role-id="(\d+)"/', $html, $roleMatches, PREG_SET_ORDER)) {
            foreach ($roleMatches as $match) {
                $roles[strtolower($match[1])] = (int) $match[2];
            }
        }
        
        // Extract all permissions  
        if (preg_match_all('/data-permission-name="([^"]+)"[^>]*data-permission-id="(\d+)"/', $html, $permMatches, PREG_SET_ORDER)) {
            foreach ($permMatches as $match) {
                $permissions[$match[1]] = (int) $match[2];
            }
        }
        
        return [
            'roles' => $roles,
            'permissions' => $permissions
        ];
    }
    
    /**
     * Toggle permission for a role
     */
    protected function togglePermission(int $roleId, int $permissionId, bool $granted, string $csrfToken): bool
    {
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
        
        $response = $this->makeRequest("{$this->baseUrl}/admin/role-permissions/update", 'POST', $data, $headers, true);
        
        return $response['http_code'] === 200 && 
               isset($response['data']['success']) && 
               $response['data']['success'];
    }
    
    /**
     * Test API endpoint with bearer token
     */
    protected function testApiEndpoint(string $token, string $endpoint, string $method = 'GET', $data = null): array
    {
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        
        return $this->makeRequest("{$this->baseUrl}/api/$endpoint", $method, $data, $headers);
    }
    
    /**
     * Get all users permissions related to API endpoints
     */
    protected function getUsersPermissions(array $allPermissions): array
    {
        $usersPermissions = [];
        
        foreach ($allPermissions as $name => $id) {
            // Find permissions that contain "Users" or "User" 
            if (stripos($name, 'user') !== false) {
                $usersPermissions[$name] = $id;
            }
        }
        
        return $usersPermissions;
    }
    
    /**
     * Enable all permissions for a role
     */
    protected function enableAllPermissions(int $roleId, array $permissions, string $csrfToken): array
    {
        $results = [];
        
        foreach ($permissions as $name => $id) {
            $success = $this->togglePermission($roleId, $id, true, $csrfToken);
            $results[$name] = $success;
            
            if (!$success) {
                $this->fail("Failed to enable permission: $name (ID: $id) for role ID: $roleId");
            }
        }
        
        return $results;
    }
    
    /**
     * Disable all permissions for a role
     */
    protected function disableAllPermissions(int $roleId, array $permissions, string $csrfToken): array
    {
        $results = [];
        
        foreach ($permissions as $name => $id) {
            $success = $this->togglePermission($roleId, $id, false, $csrfToken);
            $results[$name] = $success;
            
            if (!$success) {
                $this->fail("Failed to disable permission: $name (ID: $id) for role ID: $roleId");
            }
        }
        
        return $results;
    }
}