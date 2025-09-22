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
    });
});
