<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Migrating Data from Old System to Spatie ===\n";

try {
    DB::beginTransaction();

    // 1. Migrate role_has_permissions (permission_role -> role_has_permissions)
    echo "1. Migrating role permissions...\n";
    $rolePermissions = DB::table('permission_role')->get();
    foreach ($rolePermissions as $rp) {
        DB::table('role_has_permissions')->insertOrIgnore([
            'role_id' => $rp->role_id,
            'permission_id' => $rp->permission_id
        ]);
    }
    echo "   Migrated " . count($rolePermissions) . " role permissions\n";

    // 2. Migrate model_has_roles (role_user -> model_has_roles)  
    echo "2. Migrating user roles...\n";
    $userRoles = DB::table('role_user')->get();
    foreach ($userRoles as $ur) {
        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => $ur->role_id,
            'model_type' => 'App\\Models\\User',
            'model_id' => $ur->user_id
        ]);
    }
    echo "   Migrated " . count($userRoles) . " user roles\n";

    // 3. Migrate model_has_permissions (user_permissions -> model_has_permissions)
    echo "3. Migrating user permissions...\n";
    $userPermissions = DB::table('user_permissions')->get();
    foreach ($userPermissions as $up) {
        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $up->permission_id,
            'model_type' => 'App\\Models\\User', 
            'model_id' => $up->user_id
        ]);
    }
    echo "   Migrated " . count($userPermissions) . " user permissions\n";

    // 4. Update permissions to have guard_name
    echo "4. Updating permissions guard_name...\n";
    DB::table('permissions')->whereNull('guard_name')->update(['guard_name' => 'web']);
    
    // 5. Update roles to have guard_name
    echo "5. Updating roles guard_name...\n";
    DB::table('roles')->whereNull('guard_name')->update(['guard_name' => 'web']);

    DB::commit();
    
    echo "\n✅ Migration completed successfully!\n";
    
    // Show summary
    echo "\n=== Summary ===\n";
    echo "role_has_permissions: " . DB::table('role_has_permissions')->count() . " records\n";
    echo "model_has_roles: " . DB::table('model_has_roles')->count() . " records\n";
    echo "model_has_permissions: " . DB::table('model_has_permissions')->count() . " records\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}