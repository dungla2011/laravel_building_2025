<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug User Permissions Direct Query ===\n";

// Get super admin user
$superAdmin = User::where('email', 'superadmin@example.com')->first();

if (!$superAdmin) {
    echo "❌ Super admin user not found!\n";
    exit;
}

echo "✅ User ID: {$superAdmin->id}\n";
echo "✅ User Email: {$superAdmin->email}\n";

// Direct query for user roles
echo "\n=== Direct Roles Query ===\n";
$userRoles = DB::table('model_has_roles')
    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    ->where('model_has_roles.model_id', $superAdmin->id)
    ->where('model_has_roles.model_type', 'App\Models\User')
    ->select('roles.name', 'roles.guard_name')
    ->get();

foreach ($userRoles as $role) {
    echo "- Role: {$role->name} (guard: {$role->guard_name})\n";
}

// Direct query for permissions via roles
echo "\n=== Direct Role Permissions Query ===\n";
$rolePermissions = DB::table('model_has_roles')
    ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
    ->where('model_has_roles.model_id', $superAdmin->id)
    ->where('model_has_roles.model_type', 'App\Models\User')
    ->select('permissions.name', 'permissions.guard_name')
    ->get();

foreach ($rolePermissions as $perm) {
    echo "- Permission: {$perm->name} (guard: {$perm->guard_name})\n";
}

// Test fresh user load
echo "\n=== Fresh User Load Test ===\n";
$freshUser = User::with(['roles', 'permissions'])->find($superAdmin->id);
$freshPermissions = $freshUser->getAllPermissions();

echo "Fresh user permissions count: " . $freshPermissions->count() . "\n";
foreach ($freshPermissions as $perm) {
    echo "- {$perm->name} (guard: {$perm->guard_name})\n";
}

// Test hasPermissionTo with fresh user
echo "\n=== hasPermissionTo Test with Fresh User ===\n";
$testResult = $freshUser->hasPermissionTo('user.store', 'web');
echo "Fresh user hasPermissionTo('user.store', 'web'): " . ($testResult ? "✅ YES" : "❌ NO") . "\n";

// Clear all caches and test again
echo "\n=== Clear Cache and Test ===\n";
\Illuminate\Support\Facades\Cache::flush();
app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

$cleanUser = User::with(['roles', 'permissions'])->find($superAdmin->id);
$cleanResult = $cleanUser->hasPermissionTo('user.store', 'web');
echo "After cache clear hasPermissionTo('user.store', 'web'): " . ($cleanResult ? "✅ YES" : "❌ NO") . "\n";