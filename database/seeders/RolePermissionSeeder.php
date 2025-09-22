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
        // Create permissions for users resource
        $permissions = [
            ['name' => 'users.view', 'display_name' => 'View Users', 'resource' => 'users', 'action' => 'view'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'resource' => 'users', 'action' => 'create'],
            ['name' => 'users.update', 'display_name' => 'Update Users', 'resource' => 'users', 'action' => 'update'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'resource' => 'users', 'action' => 'delete'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

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

        // Assign permissions to roles
        
        // Super Admin gets all permissions (handled in controller)
        
        // Admin gets full CRUD
        $admin->givePermissionTo('users.view');
        $admin->givePermissionTo('users.create');
        $admin->givePermissionTo('users.update');
        $admin->givePermissionTo('users.delete');

        // Editor gets view and update only
        $editor->givePermissionTo('users.view');
        $editor->givePermissionTo('users.update');

        // Viewer gets view only
        $viewer->givePermissionTo('users.view');

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
