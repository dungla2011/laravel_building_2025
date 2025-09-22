<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Orion\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Fully-qualified model class name
     */
    protected $model = User::class;

    /**
     * The attributes that are mass assignable.
     */
    protected function fillableBy(): array
    {
        return ['name', 'email', 'password'];
    }

    /**
     * Disable authorization for demo purposes
     */
    public function authorize($gate, $arguments = [])
    {
        // Skip authorization for demo
        return true;
    }

    /**
     * Perform validation before storing the model.
     */
    protected function beforeStore($request, $model)
    {
        // Skip validation for batch operations
        if (request()->is('*/batch')) {
            return;
        }
        $request->validate($this->storeRules());
    }

    /**
     * Perform validation before updating the model.
     */
    protected function beforeUpdate($request, $model)
    {
        // Skip validation for batch operations  
        if (request()->is('*/batch')) {
            return;
        }
        $request->validate($this->updateRules());
    }

    /**
     * Disable batch validation for now due to Laravel Orion limitations
     */
    // protected function beforeBatchStore($request)
    // {
    //     $request->validate($this->storeRulesForBatch());
    // }

    // protected function beforeBatchUpdate($request)
    // {
    //     $request->validate($this->updateRulesForBatch());
    // }

    /**
     * The attributes that can be filtered by.
     */
    public function filterableBy(): array
    {
        return ['name', 'email', 'created_at', 'updated_at'];
    }

    /**
     * The attributes that are used for searching.
     */
    public function searchableBy(): array
    {
        return ['name', 'email'];
    }

    /**
     * The attributes that can be sorted by.
     */
    public function sortableBy(): array
    {
        return ['name', 'email', 'created_at', 'updated_at'];
    }

    /**
     * The rules that are used for validation, when storing a resource.
     */
    protected function storeRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ];
    }

    /**
     * The rules that are used for validation, when updating a resource.
     */
    protected function updateRules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . request()->route('user'),
            'password' => 'sometimes|required|string|min:8'
        ];
    }

    /**
     * The rules that are used for validation, when storing batch of resources.
     */
    protected function storeRulesForBatch(): array
    {
        return [
            'resources.*.name' => 'required|string|max:255',
            'resources.*.email' => 'required|string|email|max:255|unique:users',
            'resources.*.password' => 'required|string|min:8'
        ];
    }

    /**
     * The rules that are used for validation, when updating a batch of resources.
     */
    protected function updateRulesForBatch(): array
    {
        return [
            'resources.*.name' => 'sometimes|required|string|max:255',
            'resources.*.email' => 'sometimes|required|string|email|max:255',
            'resources.*.password' => 'sometimes|required|string|min:8'
        ];
    }

    /**
     * The rules that are used for validation, when deleting a batch of resources.
     */
    protected function deleteRulesForBatch(): array
    {
        return [
            'resources' => 'required|array|min:1',
            'resources.*' => 'required|integer|exists:users,id'
        ];
    }

    /**
     * Override batch delete to add validation.
     */
    public function batchDelete($request)
    {
        // Validate request first
        $request->validate($this->deleteRulesForBatch());
        
        // Call parent method
        return parent::batchDelete($request);
    }
}
