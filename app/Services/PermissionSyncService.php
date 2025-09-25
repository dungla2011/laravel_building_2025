<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PermissionSyncService
{
    /**
     * Sync API routes from Laravel routes to permissions table
     */
    public function syncApiRoutesToPermissions(): int
    {
        $routes = Route::getRoutes();
        $syncedCount = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($routes as $route) {
                $uri = $route->uri();
                $methods = $route->methods();
                $name = $route->getName();
                
                // Only process API routes
                if (!Str::startsWith($uri, 'api/')) {
                    continue;
                }
                
                // Skip auth routes and other non-resource routes
                if (Str::contains($uri, ['login', 'logout', 'register', 'password', 'email/verify'])) {
                    continue;
                }
                
                // Process each HTTP method
                foreach ($methods as $method) {
                    if (in_array($method, ['HEAD', 'OPTIONS'])) {
                        continue;
                    }
                    
                    $routeInfo = $this->parseApiRoute($uri, $method, $name);
                    
                    if ($routeInfo) {
                        $this->createOrUpdatePermission($routeInfo);
                        $syncedCount++;
                    }
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        
        return $syncedCount;
    }
    
    /**
     * Parse API route information
     */
    private function parseApiRoute(string $uri, string $method, ?string $name): ?array
    {
        // Remove 'api/' prefix
        $cleanUri = Str::after($uri, 'api/');
        
        // Extract resource from URI
        $segments = explode('/', $cleanUri);
        $resource = $segments[0] ?? null;
        
        if (!$resource) {
            return null;
        }
        
        // Determine action based on HTTP method and URI pattern
        $action = $this->determineAction($method, $cleanUri, $name);
        
        // Generate permission name
        $permissionName = $this->generatePermissionName($resource, $action, $method);
        
        return [
            'name' => $permissionName,
            'display_name' => $this->generateDisplayName($resource, $action, $method),
            'description' => $this->generateDescription($resource, $action, $method, $uri),
            'resource' => $resource,
            'action' => $action,
            'uri' => $uri,
            'method' => $method,
            'route_name' => $name,
            'is_api_route' => true,
            'is_active' => true,
        ];
    }
    
    /**
     * Determine action from HTTP method and URI pattern
     */
    private function determineAction(string $method, string $uri, ?string $name): string
    {
        // If route has a name, try to extract action from it
        if ($name && Str::contains($name, '.')) {
            $parts = explode('.', $name);
            $lastPart = end($parts);
            
            if (in_array($lastPart, ['index', 'show', 'store', 'update', 'destroy', 'search', 'batch'])) {
                return $lastPart;
            }
        }
        
        // Determine action from HTTP method and URI pattern
        switch ($method) {
            case 'GET':
                if (Str::contains($uri, 'search')) return 'search';
                return Str::contains($uri, '{') ? 'show' : 'index';
                
            case 'POST':
                if (Str::contains($uri, 'search')) return 'search';
                if (Str::contains($uri, 'batch')) return 'batch';
                return 'store';
                
            case 'PUT':
            case 'PATCH':
                if (Str::contains($uri, 'batch')) return 'batch';
                return 'update';
                
            case 'DELETE':
                if (Str::contains($uri, 'batch')) return 'batch';
                return 'destroy';
                
            default:
                return 'unknown';
        }
    }
    
    /**
     * Generate permission name
     */
    private function generatePermissionName(string $resource, string $action, string $method): string
    {
        // Convert to consistent format but preserve 'media' as is
        if ($resource === 'media') {
            $resource = 'media'; // Keep 'media' instead of converting to 'medium'
        } else {
            $resource = Str::singular(Str::snake($resource));
        }
        $action = Str::snake($action);
        
        return "{$resource}.{$action}";
    }
    
    /**
     * Generate display name
     */
    private function generateDisplayName(string $resource, string $action, string $method): string
    {
        $resourceName = Str::title(str_replace(['-', '_'], ' ', Str::plural($resource)));
        
        $actionMap = [
            'index' => 'View All',
            'show' => 'View',
            'store' => 'Create',
            'update' => 'Update',
            'destroy' => 'Delete',
            'search' => 'Search',
            'batch' => 'Batch Operations',
        ];
        
        $actionName = $actionMap[$action] ?? Str::title(str_replace(['-', '_'], ' ', $action));
        
        return "{$actionName} {$resourceName}";
    }
    
    /**
     * Generate description
     */
    private function generateDescription(string $resource, string $action, string $method, string $uri): string
    {
        $resourceName = Str::title(str_replace(['-', '_'], ' ', Str::plural($resource)));
        
        $descriptions = [
            'index' => "View list of all {$resourceName}",
            'show' => "View details of a specific {$resourceName}",
            'store' => "Create new {$resourceName}",
            'update' => "Update existing {$resourceName}",
            'destroy' => "Delete {$resourceName}",
            'search' => "Search through {$resourceName}",
            'batch' => "Perform batch operations on {$resourceName}",
        ];
        
        $baseDescription = $descriptions[$action] ?? "Perform {$action} action on {$resourceName}";
        
        return "{$baseDescription} via {$method} {$uri}";
    }
    
    /**
     * Create or update permission
     */
    private function createOrUpdatePermission(array $routeInfo): Permission
    {
        return Permission::updateOrCreate(
            [
                'name' => $routeInfo['name'],
            ],
            $routeInfo
        );
    }
    
    /**
     * Get permissions grouped by resource for display
     */
    public function getPermissionsGroupedByResource(): array
    {
        $permissions = Permission::where('is_api_route', true)
                                ->where('is_active', true)
                                ->orderBy('resource')
                                ->orderBy('action')
                                ->get();
        
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $resource = $permission->resource;
            
            if (!isset($grouped[$resource])) {
                $grouped[$resource] = [
                    'resource' => $resource,
                    'display_name' => Str::title(str_replace(['-', '_'], ' ', Str::plural($resource))),
                    'permissions' => [],
                ];
            }
            
            $grouped[$resource]['permissions'][] = $permission;
        }
        
        // Sort permissions within each resource by action priority
        foreach ($grouped as &$group) {
            usort($group['permissions'], function ($a, $b) {
                $order = ['index', 'show', 'store', 'update', 'destroy', 'search', 'batch'];
                $aPos = array_search($a->action, $order);
                $bPos = array_search($b->action, $order);
                
                if ($aPos === false) $aPos = 999;
                if ($bPos === false) $bPos = 999;
                
                return $aPos <=> $bPos;
            });
        }
        
        return array_values($grouped);
    }
    
    /**
     * Clean up inactive or removed routes
     */
    public function cleanupInactivePermissions(): int
    {
        // Mark all API route permissions as inactive first
        Permission::where('is_api_route', true)
                 ->update(['is_active' => false]);
        
        // Then sync current routes (which will reactivate existing ones)
        $this->syncApiRoutesToPermissions();
        
        // Count and optionally delete truly inactive permissions
        $inactiveCount = Permission::where('is_api_route', true)
                                  ->where('is_active', false)
                                  ->count();
        
        return $inactiveCount;
    }
}