<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class FieldPermissionController extends Controller
{
    /**
     * Display the field permissions management page
     */
    public function index(Request $request)
    {
        $selectedTable = $request->get('table', 'users');
        
        // Lấy danh sách tables trong database
        $tables = $this->getAvailableTables();
        
        // Lấy danh sách fields của table được chọn
        $fields = $this->getTableFields($selectedTable);
        
        // Lấy tất cả roles
        $roles = Role::all();
        
        // Lấy permissions hiện tại cho table này
        $permissions = FieldPermission::forTable($selectedTable)
            ->with('role')
            ->get()
            ->keyBy(function ($item) {
                return $item->role_id . '_' . $item->field_name;
            });
        
        return view('admin.field-permissions.index', compact(
            'selectedTable',
            'tables',
            'fields',
            'roles',
            'permissions'
        ));
    }

    /**
     * Update field permissions
     */
    public function update(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'table_name' => 'required|string',
            'field_name' => 'required|string',
            'can_read' => 'boolean',
            'can_write' => 'boolean'
        ]);

        FieldPermission::updateOrCreate(
            [
                'role_id' => $request->role_id,
                'table_name' => $request->table_name,
                'field_name' => $request->field_name,
            ],
            [
                'can_read' => $request->boolean('can_read'),
                'can_write' => $request->boolean('can_write')
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Get available database tables
     */
    private function getAvailableTables()
    {
        // Chỉ lấy một số tables chính, tránh system tables
        $excludeTables = [
            'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs',
            'migrations', 'password_reset_tokens', 'personal_access_tokens',
            'sessions', 'role_has_permissions', 'model_has_permissions',
            'model_has_roles', 'field_permissions'
        ];

        $tables = collect(Schema::getAllTables())
            ->map(function ($table) {
                return current($table); // Get table name from array
            })
            ->reject(function ($table) use ($excludeTables) {
                return in_array($table, $excludeTables);
            })
            ->values()
            ->toArray();

        return $tables;
    }

    /**
     * Get fields of a specific table
     */
    private function getTableFields($tableName)
    {
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        return Schema::getColumnListing($tableName);
    }
}
