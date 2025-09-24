<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FieldPermission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class FieldPermissionController extends Controller
{
    /**
     * Display a listing of field permissions
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $tableFilter = $request->get('table');
        
        // Get all roles
        $roles = Role::all();
        
        // Get unique tables that have permissions
        $tables = FieldPermission::distinct('table_name')
            ->pluck('table_name')
            ->toArray();
        
        // Build permission matrix
        $permissionMatrix = [];
        
        // Get tables to process
        $tablesToProcess = $tableFilter ? [$tableFilter] : $tables;
        
        foreach ($tablesToProcess as $tableName) {
            // Get all fields for this table
            $fields = FieldPermission::where('table_name', $tableName)
                ->distinct('field_name')
                ->pluck('field_name')
                ->toArray();
            
            // Get all permissions for this table
            $permissions = FieldPermission::where('table_name', $tableName)
                ->get()
                ->groupBy(['role_id', 'field_name']);
            
            // Organize permissions by role and field
            $permissionsByRole = [];
            foreach ($roles as $role) {
                $permissionsByRole[$role->id] = [];
                foreach ($fields as $field) {
                    $permission = $permissions->get($role->id)?->get($field)?->first();
                    $permissionsByRole[$role->id][$field] = $permission;
                }
            }
            
            $permissionMatrix[$tableName] = [
                'fields' => $fields,
                'permissions' => $permissionsByRole
            ];
        }
        
        return view('admin.field-permissions.index', compact(
            'permissionMatrix',
            'roles',
            'tables'
        ));
    }

    /**
     * Store a newly created field permission
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'table_name' => 'required|string|max:255',
            'field_name' => 'required|string|max:255',
            'can_read' => 'required|in:true,false,1,0',
            'can_write' => 'required|in:true,false,1,0'
        ]);

        // Convert to boolean
        $validated['can_read'] = in_array($validated['can_read'], ['true', '1', 1, true], true);
        $validated['can_write'] = in_array($validated['can_write'], ['true', '1', 1, true], true);

        // Check if permission already exists
        $permission = FieldPermission::where([
            'role_id' => $validated['role_id'],
            'table_name' => $validated['table_name'],
            'field_name' => $validated['field_name']
        ])->first();

        if ($permission) {
            // Update existing permission
            $permission->update([
                'can_read' => $validated['can_read'] ?? $permission->can_read,
                'can_write' => $validated['can_write'] ?? $permission->can_write
            ]);
        } else {
            // Create new permission
            FieldPermission::create([
                'role_id' => $validated['role_id'],
                'table_name' => $validated['table_name'],
                'field_name' => $validated['field_name'],
                'can_read' => $validated['can_read'] ?? false,
                'can_write' => $validated['can_write'] ?? false
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update the specified field permission
     */
    public function update(Request $request, FieldPermission $fieldPermission)
    {
        $validated = $request->validate([
            'can_read' => 'sometimes|in:true,false,1,0',
            'can_write' => 'sometimes|in:true,false,1,0'
        ]);

        // Convert to boolean
        if (isset($validated['can_read'])) {
            $validated['can_read'] = in_array($validated['can_read'], ['true', '1', 1, true], true);
        }
        if (isset($validated['can_write'])) {
            $validated['can_write'] = in_array($validated['can_write'], ['true', '1', 1, true], true);
        }

        $fieldPermission->update($validated);

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified field permission
     */
    public function destroy(FieldPermission $fieldPermission)
    {
        $fieldPermission->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Reset all permissions
     */
    public function reset()
    {
        FieldPermission::truncate();
        
        return response()->json(['success' => true]);
    }

    /**
     * Apply permission template
     */
    public function template(Request $request)
    {
        $template = $request->get('template');
        
        // Clear existing permissions
        FieldPermission::truncate();
        
        // Apply template based on type
        switch ($template) {
            case 'admin-full':
                $this->applyAdminFullTemplate();
                break;
            case 'read-only':
                $this->applyReadOnlyTemplate();
                break;
            case 'basic':
                $this->applyBasicTemplate();
                break;
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Apply admin full access template
     */
    private function applyAdminFullTemplate()
    {
        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) return;

        $tables = ['users', 'roles'];
        $userFields = ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'];
        $roleFields = ['id', 'name', 'guard_name', 'created_at', 'updated_at'];

        foreach ($userFields as $field) {
            FieldPermission::create([
                'role_id' => $adminRole->id,
                'table_name' => 'users',
                'field_name' => $field,
                'can_read' => true,
                'can_write' => !in_array($field, ['id', 'created_at', 'updated_at'])
            ]);
        }

        foreach ($roleFields as $field) {
            FieldPermission::create([
                'role_id' => $adminRole->id,
                'table_name' => 'roles',
                'field_name' => $field,
                'can_read' => true,
                'can_write' => $field === 'name'
            ]);
        }
    }

    /**
     * Apply read-only template
     */
    private function applyReadOnlyTemplate()
    {
        $roles = Role::all();
        $tables = ['users', 'roles'];
        $userFields = ['id', 'name', 'email'];
        $roleFields = ['id', 'name'];

        foreach ($roles as $role) {
            foreach ($userFields as $field) {
                FieldPermission::create([
                    'role_id' => $role->id,
                    'table_name' => 'users',
                    'field_name' => $field,
                    'can_read' => true,
                    'can_write' => false
                ]);
            }

            foreach ($roleFields as $field) {
                FieldPermission::create([
                    'role_id' => $role->id,
                    'table_name' => 'roles',
                    'field_name' => $field,
                    'can_read' => true,
                    'can_write' => false
                ]);
            }
        }
    }

    /**
     * Apply basic template (same as seeder)
     */
    private function applyBasicTemplate()
    {
        // Run the seeder logic
        Artisan::call('db:seed', ['--class' => 'FieldPermissionSeeder']);
    }
}