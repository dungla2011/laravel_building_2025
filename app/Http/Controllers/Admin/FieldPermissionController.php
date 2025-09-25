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
        
        // Define tables and their fields
        $tables = [
            'users' => ['name', 'email', 'phone', 'address', 'avatar'],
            'roles' => ['name', 'description'],
            'permissions' => ['name', 'description'],
            'posts' => ['title', 'content', 'status', 'featured_image'],
            'categories' => ['name', 'description', 'slug']
        ];
        
        // Get existing permissions
        $existingPermissions = FieldPermission::all()->groupBy(['role_id', 'table_name']);
        
        return view('admin.field-permissions', compact('roles', 'tables', 'existingPermissions'));
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
