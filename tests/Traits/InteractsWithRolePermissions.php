<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Trait for testing role and permission functionality
 */
trait InteractsWithRolePermissions
{
    /**
     * Set up test data for role permission tests
     */
    protected function setUpRolePermissionTestData(): void
    {
        // Ensure fresh database
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    }

    /**
     * Create a test user with specific role
     */
    protected function createTestUserWithRole(string $roleName, array $userData = []): User
    {
        $defaultData = [
            'name' => ucfirst($roleName) . ' User',
            'email' => strtolower($roleName) . '@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ];

        $user = User::create(array_merge($defaultData, $userData));
        
        // Assign role if it exists
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * Get or create a permission
     */
    protected function getOrCreatePermission(string $name): Permission
    {
        return Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'api'
        ], [
            'display_name' => ucfirst(str_replace('.', ' ', $name))
        ]);
    }

    /**
     * Get or create a role
     */
    protected function getOrCreateRole(string $name): Role
    {
        return Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'api'
        ], [
            'display_name' => ucfirst($name)
        ]);
    }

    /**
     * Assert that user has specific permissions
     */
    protected function assertUserHasPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->assertTrue($user->can($permission), 
                "User should have '{$permission}' permission");
        }
    }

    /**
     * Assert that user does not have specific permissions
     */
    protected function assertUserDoesNotHavePermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            $this->assertFalse($user->can($permission), 
                "User should not have '{$permission}' permission");
        }
    }

    /**
     * Login as user and get API token
     */
    protected function loginAsUserAndGetToken(User $user): string
    {
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(200);
        return $response->json('token');
    }

    /**
     * Make authenticated API request
     */
    protected function makeAuthenticatedApiRequest(string $method, string $url, string $token, array $data = [])
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->json($method, $url, $data);
    }

    /**
     * Assert API response is successful
     */
    protected function assertApiResponseSuccessful($response, string $message = 'API response should be successful'): void
    {
        $statusCode = $response->getStatusCode();
        $this->assertTrue($statusCode >= 200 && $statusCode < 400 && $statusCode !== 403, 
            $message . " (got HTTP {$statusCode})");
    }

    /**
     * Assert API response is forbidden
     */
    protected function assertApiResponseForbidden($response, string $message = 'API response should be forbidden'): void
    {
        $response->assertStatus(403, $message);
    }
}