<?php

// STRICT Field Permissions Test
use App\Models\User;
use App\Models\Role;
use App\Models\FieldPermission;

echo "=== STRICT Field Permissions Test ===\n";
echo "FIELD_PERMISSIONS_ENABLED: " . (config('app.field_permissions_enabled') ? 'TRUE' : 'FALSE') . "\n\n";

// Clear all field permissions
FieldPermission::where('table_name', 'users')->delete();
echo "✅ Cleared all field permissions for 'users' table\n";

// Test with different users
$users = [
    'editor' => User::whereHas('roles', function($q) { $q->where('name', 'editor'); })->first(),
    'admin' => User::whereHas('roles', function($q) { $q->where('name', 'admin'); })->first(),
];

foreach($users as $roleType => $user) {
    if ($user) {
        $token = $user->createToken("strict-{$roleType}-test")->plainTextToken;
        echo "\n--- {$roleType} User Test ---\n";
        echo "User: {$user->name}\n";
        echo "Token: {$token}\n";
        echo "Has field permissions: NO (should be BLOCKED)\n";
        echo "Test: curl -H 'Authorization: Bearer {$token}' http://localhost:8000/api/users\n";
    }
}

echo "\n=== Expected STRICT Behavior ===\n";
echo "🔒 When FIELD_PERMISSIONS_ENABLED=true:\n";
echo "→ No permissions = BLOCK ALL (only show id, timestamps)\n";
echo "→ With permissions = Show only allowed fields\n";

echo "\n🔓 When FIELD_PERMISSIONS_ENABLED=false:\n";  
echo "→ Show all fields (no restrictions)\n";

// Create one permission for testing
$adminRole = Role::where('name', 'admin')->first();
if ($adminRole) {
    FieldPermission::create([
        'role_id' => $adminRole->id,
        'table_name' => 'users',
        'field_name' => 'name',
        'can_read' => true,
        'can_write' => false
    ]);
    echo "\n✅ Created permission: Admin can read 'name' field\n";
    echo "→ Admin should see: id, name, created_at, updated_at\n";
    echo "→ Editor should see: id, created_at, updated_at (blocked)\n";
}