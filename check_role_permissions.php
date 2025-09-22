<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Role Permissions in Database ===\n";

$roles = Role::with('permissions')->get();

foreach ($roles as $role) {
    echo "\nRole: {$role->name} ({$role->display_name})\n";
    echo "Permissions:\n";
    
    if ($role->permissions->count() > 0) {
        foreach ($role->permissions as $permission) {
            echo "  - {$permission->name} ({$permission->display_name})\n";
        }
    } else {
        echo "  - No permissions assigned\n";
    }
}

echo "\n=== Checking role_has_permissions table directly ===\n";
$rolePermissions = DB::table('role_has_permissions')
    ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
    ->select('roles.name as role_name', 'permissions.name as permission_name')
    ->orderBy('roles.name')
    ->get();

$groupedPermissions = [];
foreach ($rolePermissions as $rp) {
    $groupedPermissions[$rp->role_name][] = $rp->permission_name;
}

foreach ($groupedPermissions as $roleName => $permissions) {
    echo "\nRole: {$roleName}\n";
    echo "Permissions: " . implode(', ', $permissions) . "\n";
}