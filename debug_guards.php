<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug Permission Guard Issue ===\n";

// Check permissions table
echo "=== Permissions Table ===\n";
$permissions = DB::table('permissions')->select('name', 'guard_name')->get();
foreach ($permissions as $perm) {
    echo "- {$perm->name} (guard: {$perm->guard_name})\n";
}

echo "\n=== Roles Table ===\n";
$roles = DB::table('roles')->select('name', 'guard_name')->get();
foreach ($roles as $role) {
    echo "- {$role->name} (guard: {$role->guard_name})\n";
}

echo "\n=== Role Has Permissions ===\n";
$rolePermissions = DB::table('role_has_permissions')
    ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
    ->select('roles.name as role_name', 'permissions.name as permission_name', 'roles.guard_name as role_guard', 'permissions.guard_name as perm_guard')
    ->get();

foreach ($rolePermissions as $rp) {
    echo "- Role: {$rp->role_name} ({$rp->role_guard}) -> Permission: {$rp->permission_name} ({$rp->perm_guard})\n";
}

echo "\n=== User Roles ===\n";
$userRoles = DB::table('model_has_roles')
    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    ->join('users', 'users.id', '=', 'model_has_roles.model_id')
    ->select('users.email', 'roles.name as role_name', 'roles.guard_name')
    ->get();

foreach ($userRoles as $ur) {
    echo "- User: {$ur->email} -> Role: {$ur->role_name} (guard: {$ur->guard_name})\n";
}

echo "\n=== Testing with Specific Guard ===\n";
$superAdmin = User::whereHas('roles', function($query) {
    $query->where('name', 'super-admin');
})->first();

if ($superAdmin) {
    echo "Testing hasPermissionTo with different guards:\n";
    echo "- user.store (no guard): " . ($superAdmin->hasPermissionTo('user.store') ? "✅" : "❌") . "\n";
    echo "- user.store (web guard): " . ($superAdmin->hasPermissionTo('user.store', 'web') ? "✅" : "❌") . "\n";
    echo "- user.store (api guard): " . ($superAdmin->hasPermissionTo('user.store', 'api') ? "✅" : "❌") . "\n";
}