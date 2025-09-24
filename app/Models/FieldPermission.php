<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class FieldPermission extends Model
{
    protected $fillable = [
        'role_id',
        'table_name',
        'field_name',
        'can_read',
        'can_write'
    ];

    protected $casts = [
        'can_read' => 'boolean',
        'can_write' => 'boolean'
    ];

    /**
     * Get the role that owns this field permission
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Scope để lấy permissions cho một table
     */
    public function scopeForTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * Scope để lấy permissions cho một role
     */
    public function scopeForRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }
}
