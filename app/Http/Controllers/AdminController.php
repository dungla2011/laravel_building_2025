<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display the admin users page
     */
    public function users()
    {
        return view('admin.users');
    }
}