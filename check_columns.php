<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Table Structures ===\n";

// Check permissions table
echo "Permissions table columns:\n";
$columns = Schema::getColumnListing('permissions');
foreach ($columns as $column) {
    echo "- {$column}\n";
}

echo "\nRoles table columns:\n";
$columns = Schema::getColumnListing('roles');
foreach ($columns as $column) {
    echo "- {$column}\n";
}