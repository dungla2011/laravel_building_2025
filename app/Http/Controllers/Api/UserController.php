<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Validators\UserValidator;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Orion\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Fully-qualified model class name
     */
    protected $model = User::class;

    /**
     * Enable authorization
     */
    protected $authorizesRequests = true;

    /**
     * The attributes that are mass assignable.
     */
    protected function fillableBy(): array
    {
        return ['name', 'email', 'password'];
    }

    



    /**
     * Perform validation before storing the model.
     */
    protected function beforeStore($request, $model)
    {
        // Only validate individual rules for single model operations
        // Batch operations will be handled by beforeBatchStore
        if (!$request->has('resources')) {
            $request->validate($this->storeRules());
        }
    }

    /**
     * Handle store operation with proper error handling
     */
    public function store(\Orion\Http\Requests\Request $request)
    {
        try {
            return parent::store($request);
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => ['The email has already been taken.']
                ]
            ], 422);
        }
    }

    /**
     * Perform validation before updating the model.
     */
    protected function beforeUpdate($request, $model)
    {
        // Only validate individual rules for single model operations
        // Batch operations will be handled by beforeBatchUpdate
        if (!$request->has('resources')) {
            $request->validate($this->updateRules());
        }
    }

    /**
     * Handle update operation with proper error handling
     */
    public function update(\Orion\Http\Requests\Request $request, ...$args)
    {
        try {
            return parent::update($request, ...$args);
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => ['The email has already been taken.']
                ]
            ], 422);
        }
    }

    /**
     * Perform validation before batch store operations
     */
    protected function beforeBatchStore($request)
    {
        $request->validate($this->storeRulesForBatch());
    }

    /**
     * Perform validation before batch update operations
     */
    protected function beforeBatchUpdate($request)
    {
        $request->validate($this->updateRulesForBatch());
    }

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
        return UserValidator::createRules();
    }

    /**
     * The rules that are used for validation, when updating a resource.
     */
    protected function updateRules(): array
    {
        $userId = request()->route('user');
        return UserValidator::updateRules($userId);
    }

    /**
     * The rules that are used for validation, when storing batch of resources.
     */
    protected function storeRulesForBatch(): array
    {
        return UserValidator::batchCreateRules();
    }

    /**
     * The rules that are used for validation, when updating a batch of resources.
     */
    protected function updateRulesForBatch(): array
    {
        return UserValidator::batchUpdateRules();
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
     * Handle batch store with proper error handling
     */
    public function batchStore(\Orion\Http\Requests\Request $request)
    {
        try {
            return parent::batchStore($request);
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => ['One or more email addresses have already been taken.']
                ]
            ], 422);
        }
    }

    /**
     * Handle batch update with proper error handling
     */
    public function batchUpdate(\Orion\Http\Requests\Request $request)
    {
        try {
            return parent::batchUpdate($request);
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => ['One or more email addresses have already been taken.']
                ]
            ], 422);
        }
    }

    /**
     * Override batch delete to add validation.
     */
    public function batchDelete(\Orion\Http\Requests\Request $request)
    {
        // Validate request first
        $request->validate($this->deleteRulesForBatch());
        
        // Call parent method
        return parent::batchDelete($request);
    }
}
