<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Sync permissions first
        $this->command->info('Syncing permissions...');
        Artisan::call('permissions:sync');
        
        // Seed roles and permissions
        $this->call(RolePermissionSeeder::class);
    }
}
