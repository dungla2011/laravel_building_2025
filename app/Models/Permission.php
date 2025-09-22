<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Contracts\Permission as PermissionContract;

class Permission extends Model implements PermissionContract
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'resource',
        'action',
        'uri',
        'method',
        'route_name',
        'is_api_route',
        'is_active',
    ];

    protected $casts = [
        'is_api_route' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The roles that belong to the permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
                    ->withTimestamps();
    }

    /**
     * The users that have this permission directly.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                    ->withTimestamps();
    }

    /**
     * Create permission for resource action
     */
    public static function createFor(string $resource, string $action, ?string $description = null): self
    {
        $name = "{$resource}.{$action}";
        $displayName = ucfirst($action) . ' ' . ucfirst($resource);

        return self::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description ?? "Permission to {$action} {$resource}",
            'resource' => $resource,
            'action' => $action,
        ]);
    }

    /**
     * Find a permission by its name.
     */
    public static function findByName(string $name, $guardName = null): PermissionContract
    {
        return static::where('name', $name)->firstOrFail();
    }

    /**
     * Find a permission by its id.
     */
    public static function findById(int|string $id, string $guardName = null): PermissionContract
    {
        return static::findOrFail($id);
    }

    /**
     * Find or create permission by its name.
     */
    public static function findOrCreate(string $name, $guardName = null): PermissionContract
    {
        return static::firstOrCreate(['name' => $name]);
    }
}
