<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Role;
use App\Models\User;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking All Roles ===\n";
$roles = Role::all();
foreach ($roles as $role) {
    echo "Role: {$role->name} (display: {$role->display_name})\n";
}

echo "\n=== Checking Role Permissions ===\n";

// Check Viewer role permissions
$viewer = Role::where('name', 'viewer')->first();
if ($viewer) {
    echo "Viewer role permissions:\n";
    foreach ($viewer->permissions as $p) {
        echo "- {$p->name} ({$p->uri})\n";
    }
} else {
    echo "Viewer role not found\n";
}

echo "\n=== Checking Test User ===\n";

// Check test user
$user = User::where('email', 'viewer@example.com')->first();
if ($user) {
    echo "User: {$user->name} ({$user->email})\n";
    echo "User roles:\n";
    foreach ($user->roles as $role) {
        echo "- {$role->name}\n";
    }
    
    echo "User permissions:\n";
    foreach ($user->getAllPermissions() as $permission) {
        echo "- {$permission->name} ({$permission->uri})\n";
    }
} else {
    echo "Test user not found\n";
}