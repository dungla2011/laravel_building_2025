<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ApiRoute extends Model
{
    protected $fillable = [
        'uri',
        'method',
        'resource',
        'action',
        'permission_key',
        'display_name',
        'route_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the role route permissions for this route
     */
    public function roleRoutePermissions(): HasMany
    {
        return $this->hasMany(RoleRoutePermission::class);
    }

    /**
     * Get all roles that have access to this route
     */
    public function allowedRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_route_permissions')
                    ->withPivot('can_access')
                    ->wherePivot('can_access', true);
    }

    /**
     * Get all roles with their permission status for this route
     */
    public function rolesWithPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_route_permissions')
                    ->withPivot('can_access', 'created_at', 'updated_at');
    }

    /**
     * Check if a role has access to this route
     */
    public function roleHasAccess(Role $role): bool
    {
        return $this->roleRoutePermissions()
                    ->where('role_id', $role->id)
                    ->where('can_access', true)
                    ->exists();
    }

    /**
     * Scope to get routes by resource
     */
    public function scopeByResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope to get only active routes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
