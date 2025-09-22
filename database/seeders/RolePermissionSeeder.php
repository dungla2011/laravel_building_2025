<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Note: Permissions are now auto-synced from API routes
        // Run 'php artisan permissions:sync' first to populate permissions

        // Create roles
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'Full access to all resources'
            ]
        );

        $admin = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Full CRUD access to users'
            ]
        );

        $viewer = Role::firstOrCreate(
            ['name' => 'viewer'],
            [
                'display_name' => 'Viewer',
                'description' => 'Read-only access to users'
            ]
        );

        $editor = Role::firstOrCreate(
            ['name' => 'editor'],
            [
                'display_name' => 'Editor',
                'description' => 'Can view and update users, but not create or delete'
            ]
        );

        // Assign permissions to roles using API route permissions
        // Get existing permissions from database
        $userPermissions = Permission::where('resource', 'users')->pluck('name')->toArray();
        
        // Super Admin gets all API permissions
        if (!empty($userPermissions)) {
            $superAdmin->permissions()->sync(Permission::whereIn('name', $userPermissions)->pluck('id'));
        }

        // Admin gets most permissions (no delete/destroy)
        $adminPermissions = array_filter($userPermissions, function($perm) {
            return !str_contains($perm, 'destroy'); // Exclude delete operations
        });
        if (!empty($adminPermissions)) {
            $admin->permissions()->sync(Permission::whereIn('name', $adminPermissions)->pluck('id'));
        }

        // Editor gets view and update only
        $editorPermissions = array_filter($userPermissions, function($perm) {
            return str_contains($perm, 'index') || str_contains($perm, 'show') || str_contains($perm, 'update');
        });
        if (!empty($editorPermissions)) {
            $editor->permissions()->sync(Permission::whereIn('name', $editorPermissions)->pluck('id'));
        }

        // Viewer gets view only
        $viewerPermissions = array_filter($userPermissions, function($perm) {
            return str_contains($perm, 'index') || str_contains($perm, 'show');
        });
        if (!empty($viewerPermissions)) {
            $viewer->permissions()->sync(Permission::whereIn('name', $viewerPermissions)->pluck('id'));
        }

        // Create sample users with different roles
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdminUser->assignRole($superAdmin);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->assignRole($admin);

        $editorUser = User::firstOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => 'Editor User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $editorUser->assignRole($editor);

        $viewerUser = User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $viewerUser->assignRole($viewer);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Sample users:');
        $this->command->info('- Super Admin: superadmin@example.com / password');
        $this->command->info('- Admin: admin@example.com / password');
        $this->command->info('- Editor: editor@example.com / password');
        $this->command->info('- Viewer: viewer@example.com / password');
    }
}
