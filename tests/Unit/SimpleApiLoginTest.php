<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Simple API Login Test với SQLite
 * Test login API và access /api/users endpoint
 */
class SimpleApiLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Confirm SQLite is being used
        $this->assertEquals('sqlite', config('database.default'));
        
        // Run migrations
        $this->artisan('migrate');
        
        // Seed test data - Approach 1: Manual seeding (more control)
        // $this->seedTestData();
        
        // Alternative Approach 2: Use existing seeders (uncomment if preferred)
        $this->seed([
            'RolePermissionSeeder',
            'UserSeeder'
        ]);
        
        echo "✅ SQLite database migrated and seeded\n";
    }

    /**
     * Seed minimal test data for API testing
     * Approach 1: Manual control - preferred for unit tests
     */
    private function seedTestData(): void
    {
        // Create essential permissions for users API
        $permissions = [
            'user.index',
            'user.show', 
            'user.store',
            'user.update',
            'user.destroy'
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => ucfirst(str_replace('.', ' ', $permission)),
                'guard_name' => 'api'
            ]);
        }

        // Create admin role with all permissions
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'guard_name' => 'api'
        ]);
        $adminRole->givePermissionTo($permissions);

        // Create viewer role with limited permissions
        $viewerRole = Role::create([
            'name' => 'viewer',
            'display_name' => 'Viewer',
            'guard_name' => 'api'
        ]);
        $viewerRole->givePermissionTo(['user.index', 'user.show']);

        // Create test user with ID = 1 (admin)
        $adminUser = User::create([
            'id' => 1,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now()
        ]);
        $adminUser->assignRole('admin');

        // Create regular user with ID = 2 (viewer)
        $viewerUser = User::create([
            'id' => 2,
            'name' => 'Test Viewer',
            'email' => 'viewer@test.com', 
            'password' => Hash::make('password123'),
            'email_verified_at' => now()
        ]);
        $viewerUser->assignRole('viewer');

        // Create additional users for list testing
        for ($i = 3; $i <= 5; $i++) {
            User::create([
                'id' => $i,
                'name' => "User $i",
                'email' => "user$i@test.com",
                'password' => Hash::make('password123'),
                'email_verified_at' => now()
            ]);
        }

        echo "✅ Created 5 test users with roles and permissions\n";
    }

    /** @test */
    public function can_login_with_user_id_1_and_get_api_token()
    {
        echo "\n🧪 Test 1: Login API với user ID = 1\n";
        
        // Verify user exists in SQLite
        $user = User::find(1);
        $this->assertNotNull($user, 'User with ID 1 should exist');
        $this->assertEquals('admin@test.com', $user->email);
        $this->assertTrue($user->hasRole('admin'));
        
        echo "✅ User ID 1 found: {$user->name} ({$user->email})\n";
        
        // Test login API
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password123'
        ]);
        
        // Assert login successful
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email']
        ]);
        
        $responseData = $response->json();
        $token = $responseData['token'];
        $userData = $responseData['user'];
        
        $this->assertNotEmpty($token, 'API token should not be empty');
        $this->assertEquals(1, $userData['id']);
        $this->assertEquals('Test Admin', $userData['name']);
        
        echo "✅ Login successful, token: " . substr($token, 0, 20) . "...\n";
        echo "✅ User data: ID={$userData['id']}, Name={$userData['name']}\n";
        
        return $token;
    }

    /** @test */
    public function can_access_users_list_with_valid_token()
    {
        echo "\n🧪 Test 2: Access GET /api/users với Bearer token\n";
        
        // Login first to get token
        $token = $this->can_login_with_user_id_1_and_get_api_token();
        
        // Test /api/users endpoint
        $response = $this->getJson('/api/users', [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ]);
        
        // Assert response structure
        $response->assertStatus(200);
        
        // Check if response has data structure (could be paginated or direct array)
        $responseData = $response->json();
        
        if (isset($responseData['data'])) {
            // Paginated response
            $users = $responseData['data'];
        } else {
            // Direct array response
            $users = $responseData;
        }
        
        $this->assertIsArray($users, 'Users response should be array');
        $this->assertGreaterThanOrEqual(5, count($users), 'Should have at least 5 users');
        
        // Verify structure of first user
        $firstUser = $users[0];
        $this->assertArrayHasKey('id', $firstUser);
        $this->assertArrayHasKey('name', $firstUser);
        $this->assertArrayHasKey('email', $firstUser);
        
        echo "✅ GET /api/users successful (HTTP 200)\n";
        echo "✅ Found " . count($users) . " users in response\n";
        
        // Verify user ID 1 is in the list
        $userIds = array_column($users, 'id');
        $this->assertContains(1, $userIds, 'User ID 1 should be in users list');
        
        echo "✅ User ID 1 found in users list\n";
        
        return $users;
    }

    /** @test */
    public function cannot_access_users_without_token()
    {
        echo "\n🧪 Test 3: Access /api/users without token (should fail)\n";
        
        $response = $this->getJson('/api/users');
        
        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
        
        $message = $response->json('message');
        echo "✅ Correctly blocked access without token (HTTP 401)\n";
        echo "✅ Error message: $message\n";
    }

    /** @test */
    public function cannot_access_users_with_invalid_token()
    {
        echo "\n🧪 Test 4: Access /api/users with invalid token (should fail)\n";
        
        $response = $this->getJson('/api/users', [
            'Authorization' => 'Bearer invalid_token_12345',
            'Accept' => 'application/json'
        ]);
        
        $response->assertStatus(401);
        
        echo "✅ Correctly blocked access with invalid token (HTTP 401)\n";
    }

    /** @test */
    public function user_permissions_work_correctly_with_sqlite()
    {
        echo "\n🧪 Test 5: User permissions với SQLite database\n";
        
        // Test admin user permissions
        $adminUser = User::find(1);
        $this->assertTrue($adminUser->can('user.index'), 'Admin should have user.index permission');
        $this->assertTrue($adminUser->can('user.store'), 'Admin should have user.store permission');
        $this->assertTrue($adminUser->can('user.update'), 'Admin should have user.update permission');
        $this->assertTrue($adminUser->can('user.destroy'), 'Admin should have user.destroy permission');
        
        // Test viewer user permissions
        $viewerUser = User::find(2);
        $this->assertTrue($viewerUser->can('user.index'), 'Viewer should have user.index permission');
        $this->assertTrue($viewerUser->can('user.show'), 'Viewer should have user.show permission');
        $this->assertFalse($viewerUser->can('user.store'), 'Viewer should NOT have user.store permission');
        $this->assertFalse($viewerUser->can('user.destroy'), 'Viewer should NOT have user.destroy permission');
        
        echo "✅ Admin user (ID: 1) has full permissions\n";
        echo "✅ Viewer user (ID: 2) has limited permissions\n";
        echo "✅ Spatie permission system works correctly with SQLite\n";
    }

    /** @test */
    public function complete_api_workflow_test()
    {
        echo "\n🧪 Test 6: Complete API workflow (Login → Get Users → Verify Data)\n";
        
        // Step 1: Login
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password123'
        ]);
        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('token');
        
        // Step 2: Get users list
        $usersResponse = $this->getJson('/api/users', [
            'Authorization' => 'Bearer ' . $token
        ]);
        $usersResponse->assertStatus(200);
        
        // Step 3: Verify data consistency
        $responseData = $usersResponse->json();
        $users = $responseData['data'] ?? $responseData;
        
        // Check that our seeded users are present
        $emails = array_column($users, 'email');
        $this->assertContains('admin@test.com', $emails);
        $this->assertContains('viewer@test.com', $emails);
        $this->assertContains('user3@test.com', $emails);
        
        // Verify user count matches what we seeded
        $this->assertEquals(5, count($users), 'Should have exactly 5 users');
        
        echo "✅ Complete workflow successful:\n";
        echo "   - Login: ✅\n";
        echo "   - Get Users: ✅\n"; 
        echo "   - Data Verification: ✅\n";
        echo "   - Found all 5 seeded users\n";
    }

    /** @test */
    public function viewer_user_can_login_but_has_limited_access()
    {
        echo "\n🧪 Test 7: Viewer user login và permissions\n";
        
        // Login as viewer
        $response = $this->postJson('/api/login', [
            'email' => 'viewer@test.com',
            'password' => 'password123'
        ]);
        
        $response->assertStatus(200);
        $token = $response->json('token');
        $user = $response->json('user');
        
        $this->assertEquals(2, $user['id']);
        $this->assertEquals('Test Viewer', $user['name']);
        
        // Verify viewer can access users list (has user.index permission)
        $usersResponse = $this->getJson('/api/users', [
            'Authorization' => 'Bearer ' . $token
        ]);
        $usersResponse->assertStatus(200);
        
        echo "✅ Viewer user (ID: 2) login successful\n";
        echo "✅ Viewer can access /api/users (has user.index permission)\n";
        
        // Note: To test user.store restriction, we'd need a POST /api/users endpoint
        // which might require additional setup
    }

    /** @test */
    public function database_contains_expected_test_data()
    {
        echo "\n🧪 Test 8: Verify SQLite database contains expected data\n";
        
        // Check users count
        $userCount = User::count();
        $this->assertEquals(5, $userCount, 'Should have 5 users');
        
        // Check roles count
        $roleCount = Role::count();
        $this->assertEquals(2, $roleCount, 'Should have 2 roles');
        
        // Check permissions count
        $permissionCount = Permission::count();
        $this->assertEquals(5, $permissionCount, 'Should have 5 permissions');
        
        // Verify role assignments
        $adminUser = User::find(1);
        $viewerUser = User::find(2);
        
        $this->assertTrue($adminUser->hasRole('admin'));
        $this->assertTrue($viewerUser->hasRole('viewer'));
        
        echo "✅ Database integrity verified:\n";
        echo "   - Users: $userCount\n";
        echo "   - Roles: $roleCount\n";
        echo "   - Permissions: $permissionCount\n";
        echo "   - Role assignments: ✅\n";
    }
}