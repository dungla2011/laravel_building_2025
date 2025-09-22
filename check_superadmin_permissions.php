<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Role;
use App\Models\Permission;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Super-admin Permissions Analysis ===\n";

$superAdmin = Role::where('name', 'super-admin')->first();
$allPermissions = Permission::where('is_api_route', true)->where('is_active', true)->get();

echo "Super-admin current permissions: " . $superAdmin->permissions->count() . "\n";
foreach ($superAdmin->permissions as $p) {
    echo "- {$p->action}: {$p->name}\n";
}

echo "\nAll available User permissions: " . $allPermissions->count() . "\n";
foreach ($allPermissions as $p) {
    echo "- {$p->action}: {$p->name}\n";
}

// Find missing permissions
$currentActions = $superAdmin->permissions->pluck('action')->toArray();
$allActions = $allPermissions->pluck('action')->toArray();
$missingActions = array_diff($allActions, $currentActions);

echo "\nMissing permissions for super-admin:\n";
foreach ($missingActions as $action) {
    $permission = $allPermissions->where('action', $action)->first();
    echo "- {$action}: {$permission->name}\n";
}