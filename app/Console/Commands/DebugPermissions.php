<?php

namespace App\Console\Commands;

use App\Models\Permission;
use Illuminate\Console\Command;

class DebugPermissions extends Command
{
    protected $signature = 'debug:permissions';
    protected $description = 'Debug permissions data';

    public function handle()
    {
        $this->info('Checking permissions...');
        
        $allPerms = Permission::all();
        $this->info("Total permissions: " . $allPerms->count());
        
        $apiPerms = Permission::where('is_api_route', true)->get();
        $this->info("API route permissions: " . $apiPerms->count());
        
        $this->line("\nAll permissions:");
        foreach ($allPerms as $perm) {
            $isApi = $perm->is_api_route ? '[API]' : '[Regular]';
            $this->line("  {$isApi} {$perm->name} - {$perm->display_name}");
        }
        
        return Command::SUCCESS;
    }
}
