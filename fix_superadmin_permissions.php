<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Role;
use App\Models\Permission;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Adding Missing Permissions to Super-admin ===\n";

try {
    $superAdmin = Role::where('name', 'super-admin')->first();
    
    // Get missing user permissions
    $missingPermissionNames = ['user.index', 'user.store', 'user.show'];
    
    foreach ($missingPermissionNames as $permissionName) {
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            // Check if already assigned
            if (!$superAdmin->permissions()->where('permission_id', $permission->id)->exists()) {
                $superAdmin->permissions()->attach($permission->id);
                echo "✅ Added: {$permission->name} ({$permission->action})\n";
            } else {
                echo "⚠️  Already has: {$permission->name}\n";
            }
        } else {
            echo "❌ Not found: {$permissionName}\n";
        }
    }
    
    echo "\nSuper-admin now has " . $superAdmin->permissions()->count() . " permissions\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}