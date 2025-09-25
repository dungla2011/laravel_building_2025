<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MediaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->getAllPermissions()->contains('name', 'media.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Media $media): bool
    {
        // Check if user has permission to view individual media
        if ($user->getAllPermissions()->contains('name', 'media.show')) {
            return true;
        }
        
        // Users can view their own media
        return $user->id === $media->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->getAllPermissions()->contains('name', 'media.store');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Media $media): bool
    {
        // Check if user has permission to update media
        if ($user->getAllPermissions()->contains('name', 'media.update')) {
            return true;
        }
        
        // Users can update their own media
        return $user->id === $media->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Media $media): bool
    {
        // Check if user has permission to delete media
        if ($user->getAllPermissions()->contains('name', 'media.destroy')) {
            return true;
        }
        
        // Users can delete their own media
        return $user->id === $media->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Media $media): bool
    {
        // Only super admin can restore media
        return $user->roles->contains('name', 'super-admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Media $media): bool
    {
        // Only super admin can force delete media
        return $user->roles->contains('name', 'super-admin');
    }
}
