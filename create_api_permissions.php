<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Creating API Guard Permissions ===\n";

// Define permissions for API guard
$apiPermissions = [
    'user.index' => 'View All Users',
    'user.show' => 'View Users', 
    'user.store' => 'Create Users',
    'user.update' => 'Update Users',
    'user.destroy' => 'Delete Users',
    'user.search' => 'Search Users',
    'user.batch' => 'Batch Operations Users'
];

// Create permissions for API guard
foreach ($apiPermissions as $name => $displayName) {
    $permission = Permission::firstOrCreate([
        'name' => $name,
        'guard_name' => 'api'
    ], [
        'display_name' => $displayName,
        'resource' => 'users',
        'action' => explode('.', $name)[1]
    ]);
    
    echo "✅ Created API permission: {$name}\n";
}

// Create roles for API guard if they don't exist
$apiRoles = [
    'super-admin' => ['Super Administrator', 'Full access to all resources'],
    'admin' => ['Administrator', 'Full CRUD access to users'], 
    'editor' => ['Editor', 'Can view and update users, but not create or delete'],
    'viewer' => ['Viewer', 'Read-only access to users']
];

foreach ($apiRoles as $name => $details) {
    $role = Role::firstOrCreate([
        'name' => $name,
        'guard_name' => 'api'
    ], [
        'display_name' => $details[0],
        'description' => $details[1]
    ]);
    
    echo "✅ Created API role: {$name}\n";
}

// Assign permissions to API roles
echo "\n=== Assigning Permissions to API Roles ===\n";

// Super Admin - all permissions
$superAdminApi = Role::where('name', 'super-admin')->where('guard_name', 'api')->first();
$allApiPermissions = Permission::where('guard_name', 'api')->get();
$superAdminApi->syncPermissions($allApiPermissions);
echo "✅ Super Admin API role: " . $allApiPermissions->count() . " permissions\n";

// Admin - index permission only
$adminApi = Role::where('name', 'admin')->where('guard_name', 'api')->first();
$adminPermissions = Permission::whereIn('name', ['user.index'])->where('guard_name', 'api')->get();
$adminApi->syncPermissions($adminPermissions);
echo "✅ Admin API role: " . $adminPermissions->count() . " permissions\n";

// Editor - update permission only
$editorApi = Role::where('name', 'editor')->where('guard_name', 'api')->first();
$editorPermissions = Permission::whereIn('name', ['user.update'])->where('guard_name', 'api')->get();
$editorApi->syncPermissions($editorPermissions);
echo "✅ Editor API role: " . $editorPermissions->count() . " permissions\n";

// Viewer - show permission only
$viewerApi = Role::where('name', 'viewer')->where('guard_name', 'api')->first();
$viewerPermissions = Permission::whereIn('name', ['user.show'])->where('guard_name', 'api')->get();
$viewerApi->syncPermissions($viewerPermissions);
echo "✅ Viewer API role: " . $viewerPermissions->count() . " permissions\n";

echo "\n=== Summary ===\n";
echo "API Permissions: " . Permission::where('guard_name', 'api')->count() . "\n";
echo "API Roles: " . Role::where('guard_name', 'api')->count() . "\n";