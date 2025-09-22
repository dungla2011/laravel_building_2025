<?php

namespace App\Console\Commands;

use App\Services\PermissionSyncService;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync {--cleanup : Clean up inactive permissions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync API routes to permissions table for role-based access control';

    /**
     * Execute the console command.
     */
    public function handle(PermissionSyncService $syncService)
    {
        $this->info('Starting permissions sync...');
        
        try {
            if ($this->option('cleanup')) {
                $this->info('Cleaning up inactive permissions...');
                $inactiveCount = $syncService->cleanupInactivePermissions();
                $this->warn("Found {$inactiveCount} inactive permissions.");
            }
            
            $syncedCount = $syncService->syncApiRoutesToPermissions();
            
            $this->info("Successfully synced {$syncedCount} API route permissions.");
            
            // Show grouped permissions
            $groupedPermissions = $syncService->getPermissionsGroupedByResource();
            
            if (empty($groupedPermissions)) {
                $this->warn('No API route permissions found.');
                return Command::SUCCESS;
            }
            
            $this->info("\nAPI Route Permissions grouped by resource:");
            
            foreach ($groupedPermissions as $group) {
                $this->line("\n<comment>{$group['display_name']} ({$group['resource']}):</comment>");
                
                foreach ($group['permissions'] as $permission) {
                    $method = $permission->method ? strtoupper($permission->method) : 'N/A';
                    $this->line("  <info>{$method}</info> {$permission->uri} -> {$permission->display_name} ({$permission->name})");
                }
            }
            
            $this->info("\nSync completed successfully!");
            
        } catch (\Exception $e) {
            $this->error("Failed to sync permissions: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
