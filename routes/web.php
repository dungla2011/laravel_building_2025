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
        Route::get('/users', [ApiDocController::class, 'users'])->name('guide.api.users');
        Route::get('/users/data', [ApiDocController::class, 'apiData'])->name('guide.api.users.data');
        Route::get('/users/openapi.json', [ApiDocController::class, 'openApiJson'])->name('guide.api.users.openapi');
        
        // General API documentation endpoints
        Route::get('/openapi.json', [ApiDocController::class, 'fullOpenApiJson'])->name('guide.api.openapi');
        Route::get('/swagger.json', [ApiDocController::class, 'fullOpenApiJson'])->name('guide.api.swagger');
        Route::get('/roles-permissions.json', [ApiDocController::class, 'rolesPermissions'])->name('guide.api.roles-permissions');
        Route::get('/json', [ApiDocController::class, 'jsonGuide'])->name('guide.api.json');
    });
});
