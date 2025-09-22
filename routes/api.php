<?php

use App\Http\Controllers\Api\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Orion\Facades\Orion;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Test endpoint
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

// Login endpoint for API documentation testing
Route::post('/login', function (Request $request) {
    try {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.']
                ]
            ], 401);
        }

        // Delete existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')
            ],
            'expires_in' => null
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Login failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Logout endpoint
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    
    return response()->json([
        'message' => 'Logged out successfully'
    ]);
});

// Test simple auth endpoint
Route::middleware(['auth:sanctum'])->get('/test-auth', function (Request $request) {
    return response()->json([
        'message' => 'Auth working',
        'user' => $request->user()->name,
        // 'roles' => $user->roles->pluck('name'),
        // 'can_view_users' => $canViewUsers
    ]);
});

// Orion API Routes with authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Orion::resource('users', UserController::class);
});