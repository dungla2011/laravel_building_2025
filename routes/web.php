<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiDocController;

Route::get('/', function () {
    return view('welcome');
});

// Admin routes
Route::prefix('admin')->group(function () {
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
});

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
