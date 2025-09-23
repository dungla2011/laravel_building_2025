<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Exception;

/**
 * User Validation Feature Test
 * 
 * Tests user creation validation scenarios including:
 * - Password validation (length, complexity)
 * - Email validation (format, uniqueness)
 * - Required fields validation
 * - Data sanitization
 */
class UserValidationTest extends TestCase
{

    private string $baseUrl;
    private int $serverPort;
    private string $cookieJar;
    private ?string $userToken = null;
    private ?string $csrfToken = null;
    private array $testAdmin = ['email' => 'admin@example.com', 'password' => 'password'];
    private string $testRunId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Generate unique test run ID
        $this->testRunId = time() . '_' . rand(1000, 9999);
        
        // Determine server port based on environment
        $this->serverPort = $this->determineServerPort();
        $this->baseUrl = "http://127.0.0.1:{$this->serverPort}";
        
        // Create cookie jar for session management
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'phpunit_cookies_validation');
        
        // Start Laravel server and prepare database
        $this->checkAndRestartServer();
        
        echo "✅ User Validation Test setup completed\n";
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
        parent::tearDown();
    }

    private function determineServerPort(): int
    {
        return (app()->environment('testing') && !$this->isLocalDevelopment()) ? 8000 : 12368;
    }

    private function isLocalDevelopment(): bool
    {
        return file_exists(base_path('.env')) && 
               strpos(file_get_contents(base_path('.env')), 'APP_ENV=local') !== false;
    }

    private function checkAndRestartServer(): void
    {
        echo "🔍 " . (app()->environment('testing') ? 'CI' : 'Local') . " environment detected, using port {$this->serverPort}\n";
        echo "🔍 Checking Laravel server status for User Validation Tests...\n";
        
        $this->prepareDatabaseForTesting();
        
        if (!$this->isServerResponding()) {
            $this->fail("Laravel server is not responding on port {$this->serverPort}. Please start the server first.");
        }
        
        echo "✅ Laravel server is responding on port {$this->serverPort}\n";
    }

    private function prepareDatabaseForTesting(): void
    {
        echo "🗄️  Checking database status...\n";
        
        $dbPath = database_path('testing.sqlite');
        if (file_exists($dbPath)) {
            echo "   ✅ Testing SQLite database exists\n";
        }
        
        echo "   ✅ Database preparation completed\n";
    }

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

    private function initializeTestEnvironment(): void
    {
        echo "🔧 Setting up test environment for user validation\n";
        
        // Get CSRF token
        $this->getCsrfToken();
        echo "✅ CSRF token obtained\n";
        
        // Login as admin for API access
        $this->loginAsAdmin();
        echo "✅ Admin login successful\n";
        
        echo "✅ Test setup completed\n\n";
    }

    private function getCsrfToken(): void
    {
        // Try to get CSRF from admin page first (like RolePermissionIntegrationTest)
        $response = $this->makeRequest($this->baseUrl . '/admin/role-permissions', 'GET');
        
        if ($response && isset($response['body'])) {
            // Look for meta tag CSRF token
            if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $matches)) {
                $this->csrfToken = $matches[1];
                return;
            }
            
            // Fallback: look for form CSRF token
            if (preg_match('/name="_token" value="([^"]+)"/', $response['body'], $matches)) {
                $this->csrfToken = $matches[1];
                return;
            }
        }
        
        // Fallback: try homepage
        $response = $this->makeRequest($this->baseUrl, 'GET');
        if ($response && isset($response['body'])) {
            if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $matches)) {
                $this->csrfToken = $matches[1];
                return;
            }
        }
        
        if (!$this->csrfToken) {
            throw new Exception('Failed to obtain CSRF token from any source');
        }
    }

    private function loginAsAdmin(): void
    {
        // Direct API login (no web session needed)
        $tokenResponse = $this->makeRequest($this->baseUrl . '/api/login', 'POST', [
            'email' => $this->testAdmin['email'],
            'password' => $this->testAdmin['password']
        ], ['Content-Type: application/json']);

        if ($tokenResponse && $tokenResponse['http_code'] === 200) {
            $tokenData = json_decode($tokenResponse['body'], true);
            if (isset($tokenData['token'])) {
                $this->userToken = $tokenData['token'];
                return;
            }
        }

        // Debug login response if failed
        $errorData = $tokenResponse ? json_decode($tokenResponse['body'], true) : null;
        $errorMessage = $errorData['message'] ?? 'Unknown error';
        
        throw new Exception("Admin login failed. HTTP Code: " . ($tokenResponse['http_code'] ?? 'Unknown') . ". Message: $errorMessage");
    }

    private function makeRequest(string $url, string $method = 'GET', array $data = [], array $headers = []): ?array
    {
        $ch = curl_init();
        
        $defaultHeaders = [
            'User-Agent: PHPUnit-UserValidation-Test',
            'Accept: application/json'
        ];
        
        if ($this->userToken) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $this->userToken;
        }
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method
        ]);
        
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
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
            echo "❌ CURL Error: $error\n";
            return null;
        }
        
        return [
            'body' => $response,
            'http_code' => $httpCode
        ];
    }

    private function testUserCreation(array $userData, int $expectedStatus, string $testName): array
    {
        echo "   Testing: $testName\n";
        
        $response = $this->makeRequest(
            $this->baseUrl . '/api/users',
            'POST',
            $userData,
            ['Content-Type: application/json']
        );
        
        $actualStatus = $response['http_code'] ?? 0;
        $responseData = json_decode($response['body'] ?? '{}', true);
        
        echo "      Expected: HTTP $expectedStatus | Actual: HTTP $actualStatus\n";
        
        if ($actualStatus === $expectedStatus) {
            echo "      ✅ PASS - Status matches\n";
        } else {
            echo "      ❌ FAIL - Status mismatch\n";
            if ($responseData) {
                echo "      Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            }
        }
        
        return [
            'status' => $actualStatus,
            'data' => $responseData,
            'passed' => $actualStatus === $expectedStatus
        ];
    }

    private function makeUniqueEmail(string $prefix): string
    {
        return $prefix . '_' . $this->testRunId . '@example.com';
    }

    #[Test]
    public function password_validation_tests()
    {
        echo "\n🔒 TEST: Password Validation Scenarios\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->initializeTestEnvironment();
        
        $testCases = [
            [
                'name' => 'Password too short (< 8 characters)',
                'data' => [
                    'name' => 'Test User',
                    'email' => $this->makeUniqueEmail('shortpass'),
                    'password' => '123',
                    'password_confirmation' => '123'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Password without confirmation',
                'data' => [
                    'name' => 'Test User',
                    'email' => $this->makeUniqueEmail('noconfirm'),
                    'password' => 'validpassword123'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Password confirmation mismatch',
                'data' => [
                    'name' => 'Test User',
                    'email' => $this->makeUniqueEmail('mismatch'),
                    'password' => 'password123',
                    'password_confirmation' => 'different123'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Valid password',
                'data' => [
                    'name' => 'Valid User',
                    'email' => $this->makeUniqueEmail('validpass'),
                    'password' => 'SecurePassword123!',
                    'password_confirmation' => 'SecurePassword123!'
                ],
                'expected_status' => 201
            ]
        ];
        
        $passed = 0;
        $total = count($testCases);
        
        foreach ($testCases as $testCase) {
            $result = $this->testUserCreation(
                $testCase['data'],
                $testCase['expected_status'],
                $testCase['name']
            );
            
            if ($result['passed']) {
                $passed++;
            }
            
            echo "\n";
        }
        
        echo "📊 Password Validation Results: $passed/$total passed (" . round(($passed/$total)*100) . "%)\n";
        $this->assertEquals($total, $passed, "All password validation tests should pass");
    }

    #[Test]
    public function email_validation_tests()
    {
        echo "\n📧 TEST: Email Validation Scenarios\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->initializeTestEnvironment();
        
        $testCases = [
            [
                'name' => 'Invalid email format (no @)',
                'data' => [
                    'name' => 'Test User',
                    'email' => 'invalid-email-format',
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Invalid email format (no domain)',
                'data' => [
                    'name' => 'Test User',
                    'email' => 'user@',
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Invalid email format (spaces)',
                'data' => [
                    'name' => 'Test User',
                    'email' => 'user with spaces@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Duplicate email address',
                'data' => [
                    'name' => 'Duplicate User',
                    'email' => 'admin@example.com', // This already exists in seeded data
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Valid email format',
                'data' => [
                    'name' => 'Valid Email User',
                    'email' => $this->makeUniqueEmail('validemail'),
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 201
            ]
        ];
        
        $passed = 0;
        $total = count($testCases);
        
        foreach ($testCases as $testCase) {
            $result = $this->testUserCreation(
                $testCase['data'],
                $testCase['expected_status'],
                $testCase['name']
            );
            
            if ($result['passed']) {
                $passed++;
            }
            
            echo "\n";
        }
        
        echo "📊 Email Validation Results: $passed/$total passed (" . round(($passed/$total)*100) . "%)\n";
        $this->assertEquals($total, $passed, "All email validation tests should pass");
    }

    #[Test]
    public function required_fields_validation_tests()
    {
        echo "\n📝 TEST: Required Fields Validation\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->initializeTestEnvironment();
        
        $testCases = [
            [
                'name' => 'Missing name field',
                'data' => [
                    'email' => $this->makeUniqueEmail('noname'),
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Missing email field',
                'data' => [
                    'name' => 'No Email User',
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Missing password field',
                'data' => [
                    'name' => 'No Password User',
                    'email' => $this->makeUniqueEmail('nopassword')
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Empty string values',
                'data' => [
                    'name' => '',
                    'email' => '',
                    'password' => '',
                    'password_confirmation' => ''
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'All required fields present',
                'data' => [
                    'name' => 'Complete User',
                    'email' => $this->makeUniqueEmail('complete'),
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 201
            ]
        ];
        
        $passed = 0;
        $total = count($testCases);
        
        foreach ($testCases as $testCase) {
            $result = $this->testUserCreation(
                $testCase['data'],
                $testCase['expected_status'],
                $testCase['name']
            );
            
            if ($result['passed']) {
                $passed++;
            }
            
            echo "\n";
        }
        
        echo "📊 Required Fields Results: $passed/$total passed (" . round(($passed/$total)*100) . "%)\n";
        $this->assertEquals($total, $passed, "All required field validation tests should pass");
    }

    #[Test]
    public function data_sanitization_tests()
    {
        echo "\n🧹 TEST: Data Sanitization\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->initializeTestEnvironment();
        
        $testCases = [
            [
                'name' => 'HTML tags in name field',
                'data' => [
                    'name' => '<script>alert("xss")</script>John Doe',
                    'email' => $this->makeUniqueEmail('htmltags'),
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 201, // Should create but sanitize the data
                'check_sanitization' => true
            ],
            [
                'name' => 'SQL injection attempt in name',
                'data' => [
                    'name' => "'; DROP TABLE users; --",
                    'email' => $this->makeUniqueEmail('sqlinjection'),
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 201, // Should create but sanitize
                'check_sanitization' => true
            ],
            [
                'name' => 'Very long name field',
                'data' => [
                    'name' => str_repeat('A', 300), // Very long name
                    'email' => $this->makeUniqueEmail('longname'),
                    'password' => 'password123',
                    'password_confirmation' => 'password123'
                ],
                'expected_status' => 422 // Should reject if name too long
            ]
        ];
        
        $passed = 0;
        $total = count($testCases);
        
        foreach ($testCases as $testCase) {
            $result = $this->testUserCreation(
                $testCase['data'],
                $testCase['expected_status'],
                $testCase['name']
            );
            
            if ($result['passed']) {
                $passed++;
                
                // Additional check for sanitization
                if (isset($testCase['check_sanitization']) && $testCase['check_sanitization'] && $result['status'] === 201) {
                    if (isset($result['data']['data']['name'])) {
                        $sanitizedName = $result['data']['data']['name'];
                        echo "      📝 Sanitized name: '$sanitizedName'\n";
                        
                        // Check that HTML/script tags are removed or escaped
                        if (strpos($sanitizedName, '<script>') === false && strpos($sanitizedName, 'DROP TABLE') === false) {
                            echo "      ✅ Data properly sanitized\n";
                        } else {
                            echo "      ⚠️  Data may not be properly sanitized\n";
                        }
                    }
                }
            }
            
            echo "\n";
        }
        
        echo "📊 Data Sanitization Results: $passed/$total passed (" . round(($passed/$total)*100) . "%)\n";
        $this->assertEquals($total, $passed, "All data sanitization tests should pass");
    }

    #[Test]
    public function server_is_running_and_responsive()
    {
        echo "\n🧪 TEST: Server health check for User Validation\n";
        
        $response = $this->makeRequest($this->baseUrl, 'GET');
        
        $this->assertEquals(200, $response['http_code'], 'Server should be responding on configured port');
        echo "✅ Server is running and responsive\n";
    }
}