<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Orion\Http\Controllers\Controller as OrionController;

class MediaController extends OrionController
{
    /**
     * The model the controller corresponds to.
     */
    protected $model = Media::class;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'file_name', 
        'mime_type',
        'extension',
        'size',
        'disk',
        'path',
        'url',
        'alt_text',
        'metadata',
        'user_id',
        'collection_name',
        'is_public'
    ];

    /**
     * The attributes that should be searchable.
     */
    protected $searchable = [
        'name',
        'mime_type',
        'extension',
        'collection_name'
    ];

    /**
     * The attributes that can be used for filtering.
     */
    protected $filterableBy = [
        'mime_type',
        'extension', 
        'user_id',
        'collection_name',
        'is_public'
    ];

    /**
     * The attributes that can be used for sorting.
     */
    protected $sortableBy = [
        'id',
        'name',
        'size',
        'mime_type',
        'created_at',
        'updated_at'
    ];

    /**
     * The relations that can be included.
     */
    protected $includes = [
        'user'
    ];

    /**
     * Perform additional operations before creating a media record.
     */
    protected function beforeStore(Request $request, $model)
    {
        // Set user_id to current authenticated user if not provided
        if (!$request->has('user_id')) {
            $model->user_id = $request->user()->id;
        }
    }

    /**
     * Perform additional operations before updating a media record.
     */
    protected function beforeUpdate(Request $request, $model)
    {
        // Prevent changing user_id unless admin
        $user = $request->user();
        if ($request->has('user_id') && $user && !$user->hasRole('super-admin')) {
            unset($request['user_id']);
        }
    }

    /**
     * Override authorization - allow all actions
     */
    // public function authorize(string $ability, $arguments = [])
    // {
    //     // Skip authorization - allow all authenticated users
    //     return;
    // }


}
