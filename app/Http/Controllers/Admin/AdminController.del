<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index(): View
    {
        // Get basic statistics
        $stats = [
            'total_users' => User::count(),
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'recent_users' => User::latest()->take(5)->get(),
        ];

        // Get roles with user counts
        $roles = Role::withCount('users')->get();

        return view('admin.index', compact('stats', 'roles'));
    }
}