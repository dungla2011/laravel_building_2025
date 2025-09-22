<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use App\Models\Permission;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug Permission Check ===\n";

$user = User::find(4); // Viewer user
$permission = Permission::where('name', 'user.index')->first();

echo "User: {$user->name}\n";
echo "Permission: {$permission->name} (guard: {$permission->guard_name})\n";

echo "\nDirect check:\n";
echo "- hasPermissionTo('user.index'): " . ($user->hasPermissionTo('user.index') ? 'true' : 'false') . "\n";
echo "- hasPermissionTo('user.index', 'web'): " . ($user->hasPermissionTo('user.index', 'web') ? 'true' : 'false') . "\n";

echo "\nPermission details:\n";  
$userPermissions = $user->getAllPermissions();
foreach ($userPermissions as $p) {
    echo "- {$p->name} (guard: {$p->guard_name})\n";
}

echo "\nRole permissions:\n";
foreach ($user->roles as $role) {
    echo "Role: {$role->name} (guard: {$role->guard_name})\n";
    foreach ($role->permissions as $p) {
        echo "  - {$p->name} (guard: {$p->guard_name})\n";
    }
}