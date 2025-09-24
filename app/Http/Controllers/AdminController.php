<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FieldPermission;
use App\Models\Role;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index()
    {
        // Get statistics for dashboard
        $totalRoles = Role::count();
        $totalTables = FieldPermission::distinct('table_name')->count('table_name');
        $totalPermissions = FieldPermission::count();
        
        // Get role statistics
        $roleStats = Role::with('fieldPermissions')
            ->get()
            ->map(function ($role) {
                $permissions = $role->fieldPermissions;
                return [
                    'role_name' => $role->name,
                    'read_count' => $permissions->where('can_read', true)->count(),
                    'write_count' => $permissions->where('can_write', true)->count(),
                    'total_fields' => $permissions->count()
                ];
            });

        // Get table statistics
        $tableStats = FieldPermission::select('table_name')
            ->selectRaw('COUNT(DISTINCT field_name) as field_count')
            ->selectRaw('COUNT(*) as permission_count')
            ->groupBy('table_name')
            ->get()
            ->map(function ($stat) {
                return [
                    'table_name' => $stat->table_name,
                    'field_count' => $stat->field_count,
                    'permission_count' => $stat->permission_count
                ];
            });

        return view('admin.dashboard', compact(
            'totalRoles',
            'totalTables', 
            'totalPermissions',
            'roleStats',
            'tableStats'
        ));
    }
    
    /**
     * Display the admin users page
     */
    public function users()
    {
        return view('admin.users');
    }
}