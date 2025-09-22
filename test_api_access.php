<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing API Access for Super Admin ===\n";

// Get super-admin user
$superAdmin = User::whereHas('roles', function($query) {
    $query->where('name', 'super-admin');
})->first();

if (!$superAdmin) {
    echo "❌ No super-admin user found!\n";
    exit;
}

echo "✅ Super Admin User: {$superAdmin->email}\n";

// Check roles
$roles = $superAdmin->roles->pluck('name')->toArray();
echo "Roles: " . implode(', ', $roles) . "\n";

// Check permissions
$permissions = $superAdmin->getAllPermissions()->pluck('name')->toArray();
echo "Permissions (" . count($permissions) . "): " . implode(', ', $permissions) . "\n";

// Test specific permission checks
echo "\n=== Permission Checks ===\n";
$testPermissions = ['user.index', 'user.store', 'user.show', 'user.update', 'user.destroy'];

foreach ($testPermissions as $perm) {
    $hasPermissionDefault = $superAdmin->hasPermissionTo($perm);
    $hasPermissionWeb = $superAdmin->hasPermissionTo($perm, 'web');
    echo "- {$perm} (default): " . ($hasPermissionDefault ? "✅ YES" : "❌ NO") . "\n";
    echo "- {$perm} (web): " . ($hasPermissionWeb ? "✅ YES" : "❌ NO") . "\n";
}

// Test Policy authorization
echo "\n=== Policy Authorization Test ===\n";
echo "Testing UserPolicy::create() method...\n";

// Simulate policy check
$userPolicy = new App\Policies\UserPolicy();
$canCreate = $userPolicy->create($superAdmin);
echo "UserPolicy::create(): " . ($canCreate ? "✅ Authorized" : "❌ Denied") . "\n";

// Check if user.store permission exists
$storePermission = Permission::where('name', 'user.store')->first();
if ($storePermission) {
    echo "✅ user.store permission exists in database\n";
    echo "Permission details: {$storePermission->name} - {$storePermission->display_name}\n";
} else {
    echo "❌ user.store permission NOT found in database!\n";
}