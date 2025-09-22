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
        // Debug: Log user roles
        Log::info('UserPolicy viewAny check', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'roles' => $user->roles->pluck('name')->toArray()
        ]);
        
        // Only super-admin can view all users
        $hasRole = $user->roles->contains('name', 'super-admin');
        Log::info('Has super-admin role: ' . ($hasRole ? 'true' : 'false'));
        
        return $hasRole;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Super admin can view any user
        if ($user->roles->contains('name', 'super-admin')) {
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
        // Only super admin can create users
        return $user->roles->contains('name', 'super-admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Super admin can update any user
        if ($user->roles->contains('name', 'super-admin')) {
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
        // Only super admin can delete users
        return $user->roles->contains('name', 'super-admin');
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
