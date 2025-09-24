<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FieldPermission;
use Spatie\Permission\Models\Role;

class FieldPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing field permissions
        FieldPermission::truncate();

        // Get roles
        $adminRole = Role::where('name', 'admin')->first();
        $editorRole = Role::where('name', 'editor')->first();
        $viewerRole = Role::where('name', 'viewer')->first();

        if (!$adminRole || !$editorRole || !$viewerRole) {
            $this->command->warn('Some roles are missing. Creating sample roles...');
            
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $editorRole = Role::firstOrCreate(['name' => 'editor']); 
            $viewerRole = Role::firstOrCreate(['name' => 'viewer']);
        }

        // Sample field permissions for users table
        $userFields = ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'];
        
        foreach ($userFields as $field) {
            // Admin: full access
            FieldPermission::create([
                'role_id' => $adminRole->id,
                'table_name' => 'users',
                'field_name' => $field,
                'can_read' => true,
                'can_write' => $field !== 'id' && $field !== 'created_at' && $field !== 'updated_at' // Can't write to system fields
            ]);

            // Editor: read all, write some
            FieldPermission::create([
                'role_id' => $editorRole->id,
                'table_name' => 'users',
                'field_name' => $field,
                'can_read' => true,
                'can_write' => in_array($field, ['name', 'email']) // Only name and email
            ]);

            // Viewer: read only
            FieldPermission::create([
                'role_id' => $viewerRole->id,
                'table_name' => 'users',
                'field_name' => $field,
                'can_read' => $field !== 'email', // Can't read email for privacy
                'can_write' => false
            ]);
        }

        // Sample field permissions for roles table (if exists)
        $roleFields = ['id', 'name', 'guard_name', 'created_at', 'updated_at'];
        
        foreach (['admin', 'editor', 'viewer'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            
            foreach ($roleFields as $field) {
                $canRead = true;
                $canWrite = false;
                
                if ($roleName === 'admin') {
                    $canWrite = $field === 'name'; // Admin can only edit role name
                } elseif ($roleName === 'editor') {
                    $canRead = $field !== 'guard_name'; // Editor can't see guard_name
                } else { // viewer
                    $canRead = in_array($field, ['id', 'name']); // Viewer can only see id and name
                }

                FieldPermission::create([
                    'role_id' => $role->id,
                    'table_name' => 'roles',
                    'field_name' => $field,
                    'can_read' => $canRead,
                    'can_write' => $canWrite
                ]);
            }
        }

        $this->command->info('Field permissions seeded successfully!');
    }
}
