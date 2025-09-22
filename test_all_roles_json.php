<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Http\Controllers\ApiDocController;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing All Roles with Endpoints ===\n";

$controller = new ApiDocController();
$response = $controller->rolesPermissions();
$data = $response->getData();

foreach ($data->roles as $role) {
    echo "\n=== " . strtoupper($role->name) . " ROLE ===\n";
    echo "Display Name: {$role->display_name}\n";
    echo "Description: {$role->description}\n";
    
    if (count($role->permissions) > 0) {
        echo "\nPermissions with Endpoints (" . count($role->permissions) . "):\n";
        $totalEndpoints = 0;
        foreach ($role->permissions as $perm) {
            $endpointCount = is_array($perm->endpoints) ? count($perm->endpoints) : 0;
            $totalEndpoints += $endpointCount;
            
            echo "  - {$perm->action}: {$perm->name} ({$perm->display_name})\n";
            if ($endpointCount > 0) {
                foreach ($perm->endpoints as $endpoint) {
                    echo "    * {$endpoint}\n";
                }
            }
        }
        echo "Total Endpoints: {$totalEndpoints}\n";
    } else {
        echo "\n❌ No permissions assigned\n";
    }
}