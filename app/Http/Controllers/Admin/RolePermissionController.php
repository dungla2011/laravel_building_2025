<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    protected PermissionSyncService $syncService;

    public function __construct(PermissionSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Display role permissions management page
     */
    public function index(): View
    {
        // Sync permissions first to ensure we have latest data
        $this->syncService->syncApiRoutesToPermissions();

        // Get all roles
        $roles = Role::orderBy('name')->get();

        // Get permissions grouped by resource
        $permissionGroups = $this->syncService->getPermissionsGroupedByResource();

        // Get current role-permission matrix
        $rolePermissions = $this->getRolePermissionsMatrix();

        // dump($rolePermissions); // Debug line to inspect the matrix


        return view('admin.role-permissions.index', compact('roles', 'permissionGroups', 'rolePermissions'));
    }

    /**
     * Update single role permission via AJAX
     */
    public function updatePermission(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
            'granted' => 'required|in:true,false,1,0',
        ]);

        // Convert string to boolean
        $granted = filter_var($request->granted, FILTER_VALIDATE_BOOLEAN);

        $role = Role::findOrFail($request->role_id);
        $permission = Permission::findOrFail($request->permission_id);

        if ($granted) {
            // Grant permission
            if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
                $role->permissions()->attach($permission->id);
            }
            $action = 'granted';
        } else {
            // Revoke permission
            $role->permissions()->detach($permission->id);
            $action = 'revoked';
        }

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Permission "%s" %s for role "%s"',
                $permission->display_name,
                $action,
                $role->display_name
            ),
        ]);
    }

    /**
     * Bulk update permissions for a role
     */
    public function bulkUpdateRole(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
            'grant_all' => 'required|in:true,false,1,0',
        ]);

        // Convert string to boolean
        $grantAll = filter_var($request->grant_all, FILTER_VALIDATE_BOOLEAN);
        $role = Role::findOrFail($request->role_id);

        DB::beginTransaction();
        
        try {
            if ($grantAll) {
                // Grant selected permissions
                $role->permissions()->syncWithoutDetaching($request->permission_ids);
                $action = 'granted';
            } else {
                // Revoke selected permissions
                $role->permissions()->detach($request->permission_ids);
                $action = 'revoked';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    '%s %d permissions for role "%s"',
                    ucfirst($action),
                    count($request->permission_ids),
                    $role->display_name
                ),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update permissions for a resource across all roles
     */
    public function bulkUpdateResource(Request $request): JsonResponse
    {
        $request->validate([
            'resource' => 'required|string',
            'grant_all' => 'required|in:true,false,1,0',
        ]);

        // Convert string to boolean
        $grantAll = filter_var($request->grant_all, FILTER_VALIDATE_BOOLEAN);
        $resource = $request->resource;
        $permissions = Permission::where('resource', $resource)
                                ->where('is_api_route', true)
                                ->where('is_active', true)
                                ->get();

        if ($permissions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "No permissions found for resource '{$resource}'",
            ], 404);
        }

        $roles = Role::all();
        $permissionIds = $permissions->pluck('id')->toArray();
        $updated = 0;

        DB::beginTransaction();

        try {
            foreach ($roles as $role) {
                if ($grantAll) {
                    // Grant all permissions for this resource to the role
                    $role->permissions()->syncWithoutDetaching($permissionIds);
                } else {
                    // Revoke all permissions for this resource from the role
                    $role->permissions()->detach($permissionIds);
                }
                $updated++;
            }

            DB::commit();

            $action = $request->grant_all ? 'granted' : 'revoked';

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    '%s all %s permissions for %d roles',
                    ucfirst($action),
                    $resource,
                    $updated
                ),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update resource permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync permissions from Laravel routes
     */
    public function syncPermissions(): JsonResponse
    {
        try {
            $syncedCount = $this->syncService->syncApiRoutesToPermissions();

            return response()->json([
                'success' => true,
                'message' => sprintf('Successfully synced %d API route permissions', $syncedCount),
                'synced_count' => $syncedCount,
                'redirect' => route('admin.role-permissions.index'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export current role permissions as JSON
     */
    public function exportPermissions(): JsonResponse
    {
        $data = [
            'exported_at' => now()->toISOString(),
            'roles' => Role::with(['permissions' => function($query) {
                $query->where('is_api_route', true)->where('is_active', true);
            }])->get(),
            'permissions' => Permission::where('is_api_route', true)
                                     ->where('is_active', true)
                                     ->get(),
            'permission_groups' => $this->syncService->getPermissionsGroupedByResource(),
        ];

        return response()->json($data);
    }

    /**
     * Get role permissions matrix for display
     */
    private function getRolePermissionsMatrix(): array
    {
        $matrix = [];
        
        $roles = Role::with(['permissions' => function($query) {
            $query->where('is_api_route', true)->where('is_active', true);
        }])->get();

        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                $matrix[$role->id][$permission->id] = true;
            }
        }

        return $matrix;
    }
}
