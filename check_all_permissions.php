<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ALL PERMISSIONS ===" . PHP_EOL;

// Get first permission to see available columns
$firstPermission = \Illuminate\Support\Facades\DB::table('permissions')->first();
if ($firstPermission) {
    echo "Available columns: " . implode(', ', array_keys((array)$firstPermission)) . PHP_EOL;
    echo "---" . PHP_EOL;
}

$permissions = \Illuminate\Support\Facades\DB::table('permissions')->get();

foreach ($permissions as $permission) {
    echo "Name: {$permission->name}" . PHP_EOL;
    echo "  Guard: {$permission->guard_name}" . PHP_EOL;
    if (property_exists($permission, 'api_route')) {
        echo "  API Route: {$permission->api_route}" . PHP_EOL;
    }
    if (property_exists($permission, 'description')) {
        echo "  Description: {$permission->description}" . PHP_EOL;
    }
    echo "---" . PHP_EOL;
}

echo "\n=== VIEWER ROLE CURRENT PERMISSIONS ===" . PHP_EOL;
$viewerRole = Spatie\Permission\Models\Role::where('name', 'viewer')->first();
if ($viewerRole) {
    $permissions = $viewerRole->permissions;
    foreach ($permissions as $permission) {
        echo "✅ {$permission->name}" . PHP_EOL;
    }
} else {
    echo "❌ Viewer role not found" . PHP_EOL;
}