<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiDocController;

Route::get('/', function () {
    return view('welcome');
});

// Simple Authentication Routes (for demo purposes)
Route::get('/login', function () {
    return redirect('/admin/role-permissions')->with('info', 'Demo mode - authentication bypassed');
})->name('login');

Route::post('/logout', function () {
    return redirect('/')->with('success', 'Logged out successfully');
})->name('logout');

// Removed - merged with main admin routes below

// API Documentation routes
Route::prefix('guide')->group(function () {
    Route::prefix('api')->group(function () {
        
        // Users resource documentation
        Route::prefix('users')->group(function () {
            Route::get('/', [ApiDocController::class, 'users'])->name('guide.api.users');
            Route::get('/json', [ApiDocController::class, 'usersJsonGuide'])->name('guide.api.users.json');
            Route::get('/data.json', [ApiDocController::class, 'apiData'])->name('guide.api.users.data');
            Route::get('/openapi.json', [ApiDocController::class, 'openApiJson'])->name('guide.api.users.openapi');
            Route::get('/roles-permissions.json', [ApiDocController::class, 'rolesPermissions'])->name('guide.api.users.roles-permissions');
        });
        
        // General API documentation endpoints (for all resources combined)
        Route::get('/openapi.json', [ApiDocController::class, 'fullOpenApiJson'])->name('guide.api.openapi');
        Route::get('/swagger.json', [ApiDocController::class, 'fullOpenApiJson'])->name('guide.api.swagger');
        Route::get('/json', [ApiDocController::class, 'jsonGuide'])->name('guide.api.json');
        
        // Future: Products resource documentation
        // Route::prefix('products')->group(function () {
        //     Route::get('/', [ApiDocController::class, 'products'])->name('guide.api.products');
        //     Route::get('/data.json', [ApiDocController::class, 'productsData'])->name('guide.api.products.data');
        //     Route::get('/openapi.json', [ApiDocController::class, 'productsOpenApiJson'])->name('guide.api.products.openapi');
        // });
    });
});

// Admin Routes (Authentication Required)
// Route::middleware(['auth'])->prefix('admin')->group(function () {
Route::prefix('admin')->group(function () {
    // Admin Dashboard
    Route::get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    
    // Field Permissions
    Route::prefix('field-permissions')->name('admin.field-permissions.')->group(function () {
        Route::get('/', [App\Http\Controllers\FieldPermissionController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\FieldPermissionController::class, 'store'])->name('store');
        Route::put('/{fieldPermission}', [App\Http\Controllers\FieldPermissionController::class, 'update'])->name('update');
        Route::delete('/{fieldPermission}', [App\Http\Controllers\FieldPermissionController::class, 'destroy'])->name('destroy');
        Route::post('/reset', [App\Http\Controllers\FieldPermissionController::class, 'reset'])->name('reset');
        Route::post('/template', [App\Http\Controllers\FieldPermissionController::class, 'template'])->name('template');
        Route::post('/refresh', [App\Http\Controllers\FieldPermissionController::class, 'reset'])->name('refresh');
        Route::get('/export', function() { return response()->json(['message' => 'Export not implemented yet']); })->name('export');
    });
    
    // Role-Permission Management Routes
    Route::prefix('role-permissions')->name('admin.role-permissions.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\RolePermissionController::class, 'index'])->name('index');
        Route::post('/update', [App\Http\Controllers\Admin\RolePermissionController::class, 'updatePermission'])->name('update');
        Route::post('/bulk-update-role', [App\Http\Controllers\Admin\RolePermissionController::class, 'bulkUpdateRole'])->name('bulk-update-role');
        Route::post('/bulk-update-resource', [App\Http\Controllers\Admin\RolePermissionController::class, 'bulkUpdateResource'])->name('bulk-update-resource');
        Route::post('/sync', [App\Http\Controllers\Admin\RolePermissionController::class, 'syncPermissions'])->name('sync');
        Route::get('/export', [App\Http\Controllers\Admin\RolePermissionController::class, 'exportPermissions'])->name('export');
    });
});
