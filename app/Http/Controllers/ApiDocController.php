<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiDocController extends Controller
{
    /**
     * Show API documentation for users
     */
    public function users()
    {
        // Get available permissions for display
        $permissions = Permission::where('resource', 'users')->get();
        $roles = Role::with('permissions')->get();

        return view('guide.api.users', compact('permissions', 'roles'));
    }

    /**
     * Get API endpoints data for JavaScript - Auto-generated from database and permissions
     */
    public function apiData()
    {
        $resource = 'users';
        $model = User::class;
        
        // Get model fields from database schema
        $fields = $this->getModelFields($model);
        
        // Get permissions for this resource
        $permissions = $this->getPermissions($resource);
        
        // Generate sample data
        $sampleData = $this->generateSampleData($fields);
        
        // Auto-generate endpoints based on Laravel Orion patterns
        $endpoints = $this->generateEndpoints($resource, $fields, $permissions, $sampleData);

        return response()->json($endpoints);
    }

    /**
     * Get model fields from database schema
     */
    private function getModelFields($modelClass)
    {
        $model = new $modelClass();
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);
        
        $fields = [];
        foreach ($columns as $column) {
            $columnType = Schema::getColumnType($table, $column);
            
            $fields[$column] = [
                'type' => $this->mapDatabaseTypeToApiType($columnType),
                'required' => $this->isFieldRequired($table, $column),
                'fillable' => in_array($column, $model->getFillable()),
                'hidden' => in_array($column, $model->getHidden()),
                'description' => $this->generateFieldDescription($column, $columnType)
            ];
        }
        
        return $fields;
    }

    /**
     * Get permissions for a resource
     */
    private function getPermissions($resource)
    {
        return Permission::where('resource', $resource)
            ->pluck('name', 'action')
            ->toArray();
    }

    /**
     * Generate sample data based on field types
     */
    private function generateSampleData($fields)
    {
        $sample = [];
        
        foreach ($fields as $fieldName => $fieldInfo) {
            if ($fieldName === 'password' || $fieldInfo['hidden']) {
                continue; // Skip password and hidden fields in response examples
            }
            
            $sample[$fieldName] = $this->generateSampleValue($fieldName, $fieldInfo['type']);
        }
        
        return $sample;
    }

    /**
     * Generate all CRUD endpoints
     */
    private function generateEndpoints($resource, $fields, $permissions, $sampleData)
    {
        $endpoints = [];
        
        // 1. List/Index - GET /api/users
        $endpoints[] = [
            'method' => 'GET',
            'url' => "/api/{$resource}",
            'title' => 'List ' . Str::title($resource),
            'description' => "Get paginated list of {$resource} with optional search and filters",
            'permission' => $permissions['view'] ?? "{$resource}.view",
            'parameters' => $this->getListParameters($fields),
            'response_example' => [
                'data' => [$sampleData],
                'links' => ['first' => '...', 'last' => '...', 'prev' => null, 'next' => '...'],
                'meta' => ['current_page' => 1, 'total' => 50, 'per_page' => 15]
            ]
        ];

        // 2. Create - POST /api/users
        $createFields = $this->getCreateFields($fields);
        $endpoints[] = [
            'method' => 'POST',
            'url' => "/api/{$resource}",
            'title' => 'Create ' . Str::singular(Str::title($resource)),
            'description' => "Create a new " . Str::singular($resource),
            'permission' => $permissions['create'] ?? "{$resource}.create",
            'parameters' => $createFields,
            'request_example' => $this->generateRequestExample($createFields),
            'response_example' => ['data' => $sampleData]
        ];

        // 3. Show - GET /api/users/{id}
        $endpoints[] = [
            'method' => 'GET',
            'url' => "/api/{$resource}/{id}",
            'title' => 'Get ' . Str::singular(Str::title($resource)),
            'description' => "Get specific " . Str::singular($resource) . " by ID",
            'permission' => $permissions['view'] ?? "{$resource}.view",
            'parameters' => [
                ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => Str::singular(Str::title($resource)) . ' ID']
            ],
            'response_example' => ['data' => $sampleData]
        ];

        // 4. Update - PUT /api/users/{id}
        $updateFields = $this->getUpdateFields($fields);
        $endpoints[] = [
            'method' => 'PUT',
            'url' => "/api/{$resource}/{id}",
            'title' => 'Update ' . Str::singular(Str::title($resource)),
            'description' => "Update existing " . Str::singular($resource),
            'permission' => $permissions['update'] ?? "{$resource}.update",
            'parameters' => array_merge(
                [['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => Str::singular(Str::title($resource)) . ' ID']],
                $updateFields
            ),
            'request_example' => $this->generateRequestExample($updateFields, false),
            'response_example' => ['data' => $sampleData]
        ];

        // 5. Delete - DELETE /api/users/{id}
        $endpoints[] = [
            'method' => 'DELETE',
            'url' => "/api/{$resource}/{id}",
            'title' => 'Delete ' . Str::singular(Str::title($resource)),
            'description' => "Delete specific " . Str::singular($resource) . " by ID",
            'permission' => $permissions['delete'] ?? "{$resource}.delete",
            'parameters' => [
                ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => Str::singular(Str::title($resource)) . ' ID']
            ],
            'response_example' => ['message' => Str::singular(Str::title($resource)) . ' deleted successfully']
        ];

        // 6. Batch Create - POST /api/users/batch
        $endpoints[] = [
            'method' => 'POST',
            'url' => "/api/{$resource}/batch",
            'title' => 'Batch Create ' . Str::title($resource),
            'description' => "Create multiple {$resource} at once",
            'permission' => $permissions['create'] ?? "{$resource}.create",
            'parameters' => [
                ['name' => 'resources', 'type' => 'array', 'required' => true, 'description' => "Array of " . Str::singular($resource) . " objects to create"]
            ],
            'request_example' => [
                'resources' => [
                    $this->generateRequestExample($createFields),
                    $this->generateRequestExample($createFields)
                ]
            ]
        ];

        // 7. Batch Update - PATCH /api/users/batch
        $endpoints[] = [
            'method' => 'PATCH',
            'url' => "/api/{$resource}/batch",
            'title' => 'Batch Update ' . Str::title($resource),
            'description' => "Update multiple {$resource} at once",
            'permission' => $permissions['update'] ?? "{$resource}.update",
            'parameters' => [
                ['name' => 'resources', 'type' => 'object', 'required' => true, 'description' => "Object with " . Str::singular($resource) . " IDs as keys and update data as values"]
            ],
            'request_example' => [
                'resources' => [
                    '1' => $this->generateRequestExample($updateFields, false),
                    '2' => $this->generateRequestExample($updateFields, false)
                ]
            ]
        ];

        // 8. Batch Delete - DELETE /api/users/batch
        $endpoints[] = [
            'method' => 'DELETE',
            'url' => "/api/{$resource}/batch",
            'title' => 'Batch Delete ' . Str::title($resource),
            'description' => "Delete multiple {$resource} at once",
            'permission' => $permissions['delete'] ?? "{$resource}.delete",
            'parameters' => [
                ['name' => 'resources', 'type' => 'object', 'required' => true, 'description' => "Object with " . Str::singular($resource) . " IDs as keys"]
            ],
            'request_example' => [
                'resources' => ['1' => [], '2' => []]
            ]
        ];

        // 9. Search - POST /api/users/search
        $endpoints[] = [
            'method' => 'POST',
            'url' => "/api/{$resource}/search",
            'title' => 'Search ' . Str::title($resource),
            'description' => "Advanced search with filters, sorting and aggregations",
            'permission' => $permissions['view'] ?? "{$resource}.view",
            'parameters' => [
                ['name' => 'filters', 'type' => 'array', 'required' => false, 'description' => 'Array of filter conditions'],
                ['name' => 'sort', 'type' => 'array', 'required' => false, 'description' => 'Array of sort conditions'],
                ['name' => 'limit', 'type' => 'integer', 'required' => false, 'description' => 'Number of results to return']
            ],
            'request_example' => [
                'filters' => [
                    ['field' => 'name', 'operator' => 'like', 'value' => '%John%'],
                    ['field' => 'email_verified_at', 'operator' => 'not null']
                ],
                'sort' => [
                    ['field' => 'created_at', 'direction' => 'desc']
                ],
                'limit' => 10
            ]
        ];

        return $endpoints;
    }

    /**
     * Map database column types to API types
     */
    private function mapDatabaseTypeToApiType($dbType)
    {
        $typeMapping = [
            'bigint' => 'integer',
            'integer' => 'integer',
            'smallint' => 'integer',
            'decimal' => 'number',
            'float' => 'number',
            'double' => 'number',
            'string' => 'string',
            'text' => 'string',
            'boolean' => 'boolean',
            'datetime' => 'string',
            'timestamp' => 'string',
            'date' => 'string',
            'time' => 'string',
        ];

        return $typeMapping[$dbType] ?? 'string';
    }

    /**
     * Check if field is required (not nullable and no default)
     */
    private function isFieldRequired($table, $column)
    {
        // Skip auto-increment, timestamps, and nullable fields
        if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at'])) {
            return false;
        }

        try {
            // Use simple SQL query to check column constraints
            $columnInfo = collect(DB::select("DESCRIBE {$table}"))
                ->firstWhere('Field', $column);
            
            if (!$columnInfo) {
                return false;
            }
            
            // Check if column is NOT NULL and has no default value
            $isNotNull = $columnInfo->Null === 'NO';
            $hasDefault = !is_null($columnInfo->Default);
            
            return $isNotNull && !$hasDefault;
        } catch (\Exception $e) {
            // Fallback: assume required for common required fields
            return in_array($column, ['name', 'email', 'password']);
        }
    }

    /**
     * Generate field description based on name and type
     */
    private function generateFieldDescription($fieldName, $type)
    {
        $descriptions = [
            'id' => 'Unique identifier',
            'name' => 'Full name',
            'email' => 'Email address (must be unique)',
            'password' => 'Password (min 8 characters)',
            'email_verified_at' => 'Email verification timestamp',
            'created_at' => 'Creation timestamp',
            'updated_at' => 'Last update timestamp',
            'deleted_at' => 'Soft delete timestamp'
        ];

        if (isset($descriptions[$fieldName])) {
            return $descriptions[$fieldName];
        }

        // Generate based on field name patterns
        if (Str::endsWith($fieldName, '_at')) {
            return Str::title(str_replace('_', ' ', Str::before($fieldName, '_at'))) . ' timestamp';
        }

        if (Str::endsWith($fieldName, '_id')) {
            return Str::title(str_replace('_', ' ', Str::before($fieldName, '_id'))) . ' ID reference';
        }

        return Str::title(str_replace('_', ' ', $fieldName));
    }

    /**
     * Get parameters for list/index endpoint
     */
    private function getListParameters($fields)
    {
        $sortableFields = array_keys(array_filter($fields, function($field) {
            return in_array($field['type'], ['integer', 'string', 'datetime']);
        }));

        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Page number for pagination'],
            ['name' => 'limit', 'type' => 'integer', 'required' => false, 'description' => 'Items per page (max 100)'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Sort field (' . implode(', ', $sortableFields) . ')'],
            ['name' => 'search', 'type' => 'object', 'required' => false, 'description' => 'Search filters JSON object']
        ];
    }

    /**
     * Get fields for create operation
     */
    private function getCreateFields($fields)
    {
        $createFields = [];
        
        foreach ($fields as $fieldName => $fieldInfo) {
            // Skip auto fields and non-fillable fields
            if (in_array($fieldName, ['id', 'created_at', 'updated_at', 'deleted_at']) || !$fieldInfo['fillable']) {
                continue;
            }
            
            $createFields[] = [
                'name' => $fieldName,
                'type' => $fieldInfo['type'],
                'required' => $fieldInfo['required'],
                'description' => $fieldInfo['description']
            ];
        }
        
        return $createFields;
    }

    /**
     * Get fields for update operation
     */
    private function getUpdateFields($fields)
    {
        $updateFields = [];
        
        foreach ($fields as $fieldName => $fieldInfo) {
            // Skip auto fields and non-fillable fields
            if (in_array($fieldName, ['id', 'created_at', 'updated_at', 'deleted_at']) || !$fieldInfo['fillable']) {
                continue;
            }
            
            $updateFields[] = [
                'name' => $fieldName,
                'type' => $fieldInfo['type'],
                'required' => false, // Update fields are usually optional
                'description' => $fieldInfo['description']
            ];
        }
        
        return $updateFields;
    }

    /**
     * Generate sample value based on field name and type
     */
    private function generateSampleValue($fieldName, $type)
    {
        $samples = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => '2025-09-22T10:00:00.000000Z',
            'created_at' => '2025-09-22T10:00:00.000000Z',
            'updated_at' => '2025-09-22T10:00:00.000000Z'
        ];

        if (isset($samples[$fieldName])) {
            return $samples[$fieldName];
        }

        // Generate based on type and patterns
        switch ($type) {
            case 'integer':
                return Str::endsWith($fieldName, '_id') ? 1 : 100;
            case 'string':
                if (Str::contains($fieldName, 'email')) return 'user@example.com';
                if (Str::contains($fieldName, 'name')) return 'Sample Name';
                if (Str::contains($fieldName, 'phone')) return '+1234567890';
                return 'Sample ' . Str::title($fieldName);
            case 'boolean':
                return true;
            case 'datetime':
                return '2025-09-22T10:00:00.000000Z';
            default:
                return 'sample_' . $fieldName;
        }
    }

    /**
     * Generate request example for create/update
     */
    private function generateRequestExample($fields, $includeRequired = true)
    {
        $example = [];
        
        foreach ($fields as $field) {
            if ($includeRequired && !$field['required']) {
                continue; // Only include required fields for create examples
            }
            
            if (!$includeRequired && $field['name'] === 'password') {
                continue; // Skip password in update examples
            }
            
            $example[$field['name']] = $this->generateSampleValue($field['name'], $field['type']);
        }
        
        return $example;
    }
}
