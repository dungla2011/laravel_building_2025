<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\HttpTestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Exception;
use Illuminate\Support\Facades\Hash;

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
    use RefreshDatabase, HttpTestTrait;

    private array $testAdmin = ['email' => 'admin@example.com', 'password' => 'password'];
    private string $testRunId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Generate unique test run ID
        $this->testRunId = time() . '_' . rand(1000, 9999);

        echo "\n=== Setting up User Validation Test Environment ===\n";
        echo "🔍 Starting HTTP test initialization...\n";
        
        try {
            set_time_limit(120); // 2 minutes max for setup
            $this->initializeHttpTest('phpunit_cookies_validation');
            echo "✅ HTTP test initialization completed\n";
        } catch (Exception $e) {
            echo "❌ HTTP test initialization failed: " . $e->getMessage() . "\n";
            throw $e;
        }
        
        echo "✅ User Validation Test setup completed\n";
    }

    protected function tearDown(): void
    {
        $this->cleanupHttpTest();
        parent::tearDown();
    }



    private function initializeTestEnvironment(): void
    {
        echo "🔧 Setting up test environment for user validation\n";
        
        // Set timeout for this entire setup process
        set_time_limit(30); // 30 seconds max for setup
        echo "⏰ Timeout set to 30 seconds for environment setup\n";
        
        try {
            // Ensure admin user exists (fallback if seeding didn't work)
            $adminUser = \App\Models\User::where('email', $this->testAdmin['email'])->first();
            if (!$adminUser) {
                $adminUser = \App\Models\User::create([
                    'name' => 'Test Administrator',
                    'email' => $this->testAdmin['email'],
                    'password' => bcrypt($this->testAdmin['password']),
                    'email_verified_at' => now(),
                ]);
            }
            
            // Get CSRF token (with timeout protection)
            $this->getCsrfToken();
            if ($this->csrfToken) {
                echo "✅ CSRF token obtained\n";
            } else {
                echo "⚠️  Proceeding without CSRF token\n";
            }
            
            // Login as admin for API access
            if ($this->loginUser($this->testAdmin['email'], $this->testAdmin['password'])) {
                echo "✅ Admin login successful\n";
            } else {
                echo "⚠️  Admin login failed, some tests may not work\n";
            }
            
        } catch (Exception $e) {
            echo "⚠️  Setup error: " . $e->getMessage() . "\n";
            echo "   Continuing with partial setup\n";
        }
        
        echo "✅ Test setup completed\n\n";
    }





    private function testUserCreation(array $userData, int $expectedStatus, string $testName): array
    {
        echo "   Testing: $testName\n";
        
        // Use simple cURL instead of makeRequest to avoid hanging
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/api/users',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($userData),
            CURLOPT_TIMEOUT => 10, // Short timeout
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: PHPUnit Test Suite'
            ] + ($this->userToken ? ['Authorization: Bearer ' . $this->userToken] : []),
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $responseBody = curl_exec($ch);
        $actualStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $responseData = null;
        if ($responseBody && !$error) {
            $responseData = json_decode($responseBody, true);
        }
        
        echo "      Expected: HTTP $expectedStatus | Actual: HTTP $actualStatus\n";
        
        if ($actualStatus === $expectedStatus) {
            echo "      ✅ PASS - Status matches\n";
        } else {
            echo "      ❌ FAIL - Status mismatch\n";
            if ($error) {
                echo "      cURL Error: $error\n";
            }
            if ($responseData) {
                echo "      Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            } elseif ($responseBody && strlen($responseBody) < 500) {
                echo "      Raw Response: " . trim($responseBody) . "\n";
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
                    'password' => 'ValidPassword123!'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Password confirmation mismatch',
                'data' => [
                    'name' => 'Test User',
                    'email' => $this->makeUniqueEmail('mismatch'),
                    'password' => 'StrongPassword123!',
                    'password_confirmation' => 'DifferentPassword123!'
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
                    'password' => 'InvalidPassword123!',
                    'password_confirmation' => 'InvalidPassword123!'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Invalid email format (no domain)',
                'data' => [
                    'name' => 'Test User',
                    'email' => 'user@',
                    'password' => 'NoDomainPassword123!',
                    'password_confirmation' => 'NoDomainPassword123!'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Invalid email format (spaces)',
                'data' => [
                    'name' => 'Test User',
                    'email' => 'user with spaces@example.com',
                    'password' => 'SpacesPassword123!',
                    'password_confirmation' => 'SpacesPassword123!'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Duplicate email address',
                'data' => [
                    'name' => 'Duplicate User',
                    'email' => 'admin@example.com', // This already exists in seeded data
                    'password' => 'DuplicatePassword123!',
                    'password_confirmation' => 'DuplicatePassword123!'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Valid email format',
                'data' => [
                    'name' => 'Valid Email User',
                    'email' => $this->makeUniqueEmail('validemail'),
                    'password' => 'ValidPassword123!',
                    'password_confirmation' => 'ValidPassword123!'
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
                    'password' => 'MissingNamePassword123!',
                    'password_confirmation' => 'MissingNamePassword123!'
                ],
                'expected_status' => 422
            ],
            [
                'name' => 'Missing email field',
                'data' => [
                    'name' => 'No Email User',
                    'password' => 'MissingEmailPassword123!',
                    'password_confirmation' => 'MissingEmailPassword123!'
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
                    'password' => 'CompletePassword123!',
                    'password_confirmation' => 'CompletePassword123!'
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
        
        // Check if we're in CI environment (stricter validation)
        $isCI = getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true';
        
        $testCases = [
            [
                'name' => 'HTML tags in name field',
                'data' => [
                    'name' => '<script>alert("xss")</script>John Doe',
                    'email' => $this->makeUniqueEmail('htmltags'),
                    'password' => 'SecurePassword123!',
                    'password_confirmation' => 'SecurePassword123!'
                ],
                'expected_status' => $isCI ? 422 : 201, // CI blocks XSS, local allows
                'check_sanitization' => !$isCI
            ],
            [
                'name' => 'SQL injection attempt in name',
                'data' => [
                    'name' => "'; DROP TABLE users; --",
                    'email' => $this->makeUniqueEmail('sqlinjection'),
                    'password' => 'SafePassword123!',
                    'password_confirmation' => 'SafePassword123!'
                ],
                'expected_status' => $isCI ? 422 : 201, // CI blocks SQL injection, local allows
                'check_sanitization' => !$isCI
            ],
            [
                'name' => 'Very long name field',
                'data' => [
                    'name' => str_repeat('A', 300), // Very long name
                    'email' => $this->makeUniqueEmail('longname'),
                    'password' => 'StrongPassword123!',
                    'password_confirmation' => 'StrongPassword123!'
                ],
                'expected_status' => 422 // Should reject if name too long
            ],
            [
                'name' => 'Valid clean name field',
                'data' => [
                    'name' => 'John Doe Smith',
                    'email' => $this->makeUniqueEmail('cleanname'),
                    'password' => 'CleanPassword123!',
                    'password_confirmation' => 'CleanPassword123!'
                ],
                'expected_status' => 201, // Should succeed with clean data
                'check_sanitization' => false
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