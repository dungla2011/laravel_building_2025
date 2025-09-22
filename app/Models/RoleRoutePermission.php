<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleRoutePermission extends Model
{
    protected $fillable = [
        'role_id',
        'api_route_id',
        'can_access',
    ];

    protected $casts = [
        'can_access' => 'boolean',
    ];

    /**
     * Get the role that owns this permission
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the API route for this permission
     */
    public function apiRoute(): BelongsTo
    {
        return $this->belongsTo(ApiRoute::class);
    }

    /**
     * Scope to get permissions by role
     */
    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope to get allowed permissions only
     */
    public function scopeAllowed($query)
    {
        return $query->where('can_access', true);
    }
}
