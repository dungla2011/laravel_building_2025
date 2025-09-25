<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\FieldPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FieldPermissionController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        
        // Get real tables from database
        $tables = $this->getTablesAndFields();
        
        // Get existing permissions
        $existingPermissions = FieldPermission::all()->groupBy(['role_id', 'table_name']);
        
        return view('admin.field-permissions', compact('roles', 'tables', 'existingPermissions'));
    }
    
    private function getTablesAndFields()
    {
        // Tables to ignore (system tables, migrations, etc.)
        $ignoreTables = [
            'migrations',
            'password_reset_tokens',
            'personal_access_tokens',
            'failed_jobs',
            'jobs',
            'job_batches',
            'cache',
            'cache_locks',
            'sessions',
            'user_permissions', 'roles', 'permissions', 'role_user', 'permission_role', 'api_routes', 'field_permissions'
        ];
        
        $tables = [];
        
        // Get all table names
        $tableNames = DB::select("SHOW TABLES");
        $databaseName = DB::getDatabaseName();
        $tableColumn = "Tables_in_" . $databaseName;
        
        foreach ($tableNames as $table) {
            $tableName = $table->$tableColumn;
            
            // Skip ignored tables
            if (in_array($tableName, $ignoreTables)) {
                continue;
            }
            
            // Get columns for this table
            $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
            $fields = [];
            
            foreach ($columns as $column) {
                // Skip system fields like id, created_at, updated_at, etc.
                if (!in_array($column->Field, ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at', 'remember_token'])) {
                    $fields[] = $column->Field;
                }
            }
            
            if (!empty($fields)) {
                $tables[$tableName] = $fields;
            }
        }
        
        return $tables;
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'table_name' => 'required|string',
            'field_name' => 'required|string',
            'can_read' => 'boolean',
            'can_write' => 'boolean'
        ]);
        
        DB::transaction(function () use ($request) {
            FieldPermission::updateOrCreate([
                'role_id' => $request->role_id,
                'table_name' => $request->table_name,
                'field_name' => $request->field_name
            ], [
                'can_read' => $request->boolean('can_read'),
                'can_write' => $request->boolean('can_write')
            ]);
        });
        
        return response()->json(['success' => true]); 
    }
}
