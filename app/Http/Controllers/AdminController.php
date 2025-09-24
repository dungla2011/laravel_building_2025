<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index()
    {

    }
    
    /**
     * Display the admin users page
     */
    public function users()
    {
        return view('admin.users');
    }
}