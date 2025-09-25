<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

// Get user ID 3
$user = App\Models\User::find(3);
if (!$user) {
    echo "User not found\n";
    exit;
}

echo "=== USER INFO ===\n";
echo "ID: " . $user->id . "\n";
echo "Name: " . $user->name . "\n";
echo "Email: " . $user->email . "\n";

echo "\n=== ROLES ===\n";
$roles = $user->roles;
foreach ($roles as $role) {
    echo "- " . $role->name . " (ID: " . $role->id . ")\n";
}

echo "\n=== FIELD PERMISSIONS for 'users' table ===\n";
foreach ($roles as $role) {
    echo "Role: " . $role->name . "\n";
    $permissions = App\Models\FieldPermission::where('role_id', $role->id)
        ->where('table_name', 'users')
        ->get();
    
    foreach ($permissions as $perm) {
        echo "  - {$perm->field_name}: read=" . ($perm->can_read ? 'YES' : 'NO') . ", write=" . ($perm->can_write ? 'YES' : 'NO') . "\n";
    }
}