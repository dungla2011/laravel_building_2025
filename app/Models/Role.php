<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * The users that belong to the role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
                    ->withTimestamps();
    }

    /**
     * The permissions that belong to the role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
                    ->withTimestamps();
    }


    /**
     * Check if role has permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
                    ->where('name', $permission)
                    ->exists();
    }

    /**
     * Check if role has permission (Spatie interface)
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (is_string($permission)) {
            return $this->hasPermission($permission);
        }
        
        return $this->permissions->contains($permission);
    }

    /**
     * Give permission to role
     */
    public function givePermissionTo(Permission|string $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        if (!$this->hasPermission($permission->name)) {
            $this->permissions()->attach($permission);
        }

        return $this;
    }

    /**
     * Revoke permission from role
     */
    public function revokePermissionTo(Permission|string $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission);

        return $this;
    }

    /**
     * Find a role by its name.
     */
    public static function findByName(string $name, $guardName = null): self
    {
        return static::where('name', $name)->firstOrFail();
    }

    /**
     * Find a role by its id.
     */
    public static function findById(int|string $id, string $guardName = null): self
    {
        return static::findOrFail($id);
    }

    /**
     * Find or create role by its name.
     */
    public static function findOrCreate(string $name, $guardName = null): self
    {
        return static::firstOrCreate(['name' => $name]);
    }
}
