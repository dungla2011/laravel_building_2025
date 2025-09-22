<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Debug: Log user permissions
        Log::info('UserPolicy viewAny check', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ]);
        
        // Temporary: Always allow if user has the permission in their list
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        $hasPermission = in_array('user.index', $userPermissions);
        Log::info('Has user.index permission (array check): ' . ($hasPermission ? 'true' : 'false'));
        
        return $hasPermission;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Check if user has permission to view individual user
        if ($user->hasPermissionTo('user.show')) {
            return true;
        }
        
        // Users can view their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Check if user has permission to create users
        return $user->hasPermissionTo('user.store');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Check if user has permission to update users
        if ($user->hasPermissionTo('user.update')) {
            return true;
        }
        
        // Users can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Check if user has permission to delete users
        return $user->hasPermissionTo('user.destroy');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // Only super admin can restore users
        return $user->roles->contains('name', 'super-admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only super admin can force delete users
        return $user->roles->contains('name', 'super-admin');
    }
}
