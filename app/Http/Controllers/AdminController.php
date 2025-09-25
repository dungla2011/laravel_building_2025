<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'recent_users' => User::latest()->limit(5)->get(),
        ];
        
        // Get roles with user count for the roles overview section
        $roles = Role::withCount('users')->get();
        
        return view('admin.index', compact('stats', 'roles'));
    }
     
    /**
     * Display the admin users page
     */
    public function users()
    {
        return view('admin.users');
    }
}