<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Http\Controllers\ApiDocController;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Role Permissions JSON ===\n";

$controller = new ApiDocController();
$response = $controller->rolesPermissions();
$data = $response->getData();

// Extract super-admin role
foreach ($data->roles as $role) {
    if ($role->name === 'super-admin') {
        echo "=== SUPER-ADMIN ROLE ===\n";
        echo "Name: {$role->name}\n";
        echo "Display Name: {$role->display_name}\n";
        echo "Description: {$role->description}\n";
        
        echo "\nPermissions with Endpoints (" . count($role->permissions) . "):\n";
        $totalEndpoints = 0;
        foreach ($role->permissions as $perm) {
            $endpointCount = is_array($perm->endpoints) ? count($perm->endpoints) : 0;
            $totalEndpoints += $endpointCount;
            
            echo "  - {$perm->action}: {$perm->name} ({$perm->display_name})\n";
            if ($endpointCount > 0) {
                echo "    Endpoints ({$endpointCount}):\n";
                foreach ($perm->endpoints as $endpoint) {
                    echo "      * {$endpoint}\n";
                }
            } else {
                echo "    ❌ No endpoints attached\n";
            }
        }
        
        echo "\nSummary:\n";
        echo "Permissions: " . count($role->permissions) . "\n";
        echo "Total Endpoints: " . $totalEndpoints . "\n";
        echo "Endpoints per Permission: " . ($totalEndpoints / count($role->permissions)) . " avg\n";
        break;
    }
}