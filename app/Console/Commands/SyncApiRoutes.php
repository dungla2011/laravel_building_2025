<?php

namespace App\Console\Commands;

use App\Services\ApiRouteDiscoveryService;
use Illuminate\Console\Command;

class SyncApiRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:sync-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync API routes to database for role-based permissions management';

    /**
     * Execute the console command.
     */
    public function handle(ApiRouteDiscoveryService $discoveryService)
    {
        $this->info('Starting API routes sync...');
        
        try {
            $syncedCount = $discoveryService->syncRoutesToDatabase();
            
            $this->info("Successfully synced {$syncedCount} API routes to database.");
            
            // Show discovered routes grouped by resource
            $groupedRoutes = $discoveryService->discoverApiRoutes();
            
            if (empty($groupedRoutes)) {
                $this->warn('No API routes found.');
                return Command::SUCCESS;
            }
            
            $this->info("\nDiscovered API routes grouped by resource:");
            
            foreach ($groupedRoutes as $group) {
                $this->line("\n<comment>{$group['display_name']} ({$group['resource']}):</comment>");
                
                foreach ($group['routes'] as $route) {
                    $this->line("  <info>{$route['method']}</info> {$route['uri']} -> {$route['display_name']}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to sync routes: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
