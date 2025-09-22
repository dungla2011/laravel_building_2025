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
     * Show JSON API documentation guide (general overview)
     */
    public function jsonGuide()
    {
        return view('guide.api.json');
    }

    /**
     * Show JSON API documentation guide for Users resource specifically
     */
    public function usersJsonGuide()
    {
        return view('guide.api.users-json');
    }

    /**
     * Get roles and permissions information as JSON for users resource
     */
    public function rolesPermissions()
    {
        $resource = 'users'; // This method is now specific to users
        $rolesWithPermissions = $this->getRolesWithPermissions($resource);
        $sampleUsers = $this->getSampleUsers();

        return response()->json([
            'resource' => $resource,
            'resource_endpoints' => config('app.url') . '/api/' . $resource,
            'roles' => $rolesWithPermissions,
            'sample_users' => $sampleUsers,
            'authentication' => [
                'type' => 'Laravel Sanctum',
                'login_endpoint' => config('app.url') . '/api/login',
                'token_header' => 'Authorization: Bearer {token}',
                'instructions' => 'Login with email/password to get access token, then include token in Authorization header'
            ],
            'summary' => [
                'resource' => ucfirst($resource) . ' API',
                'total_roles' => count($rolesWithPermissions),
                'total_sample_users' => count($sampleUsers),
                'available_actions' => ['view', 'create', 'update', 'delete'],
                'role_hierarchy' => [
                    'super-admin' => 'Full access to all resources and actions',
                    'admin' => 'Full CRUD access to ' . $resource . ' resource',
                    'editor' => 'Can view and update ' . $resource . ', but not create or delete',
                    'viewer' => 'Read-only access to ' . $resource . ' resource'
                ]
            ]
        ], 200, [], JSON_PRETTY_PRINT);
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
        
        // Get roles and their permissions from database
        $rolesWithPermissions = $this->getRolesWithPermissions($resource);
        
        // Generate sample data
        $sampleData = $this->generateSampleData($fields);
        
        // Auto-generate endpoints based on Laravel Orion patterns
        $endpoints = $this->generateEndpoints($resource, $fields, $permissions, $sampleData);
        
        // Create response with endpoints and authorization info
        $response = [
            'endpoints' => $endpoints,
            'authorization' => [
                'roles' => $rolesWithPermissions,
                'sample_users' => $this->getSampleUsers()
            ]
        ];

        return response()->json($response);
    }

    /**
     * Get OpenAPI 3.0 JSON specification for automated system integration
     */
    public function openApiJson()
    {
        $resource = 'users';
        $model = User::class;
        
        // Get model fields from database schema
        $fields = $this->getModelFields($model);
        
        // Get permissions for this resource
        $permissions = $this->getPermissions($resource);
        
        // Generate OpenAPI 3.0 specification
        $openApiSpec = $this->generateOpenApiSpec($resource, $model, $fields, $permissions);

        return response()->json($openApiSpec, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Get full OpenAPI 3.0 JSON specification for all resources
     */
    public function fullOpenApiJson()
    {
        // Define all available resources and their models
        $resources = [
            'users' => User::class,
            // Add more resources here as needed
            // 'posts' => Post::class,
            // 'categories' => Category::class,
        ];

        $baseUrl = config('app.url') . '/api';
        
        $fullSpec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => config('app.name') . ' API',
                'description' => 'Complete API documentation for all resources with Laravel Orion. This specification can be used for automated system integration, code generation, and testing.',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'API Support',
                    'url' => config('app.url'),
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT'
                ]
            ],
            'servers' => [
                [
                    'url' => $baseUrl,
                    'description' => 'Production API Server'
                ],
                [
                    'url' => str_replace('http://', 'https://', $baseUrl),
                    'description' => 'Secure API Server'
                ]
            ],
            'security' => [
                [
                    'bearerAuth' => []
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'Laravel Sanctum token authentication'
                    ]
                ],
                'schemas' => [],
                'responses' => [
                    'UnauthorizedError' => [
                        'description' => 'Authentication token is missing or invalid',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string', 'example' => 'Unauthenticated.']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'ForbiddenError' => [
                        'description' => 'Insufficient permissions for this resource',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string', 'example' => 'This action is unauthorized.']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'ValidationError' => [
                        'description' => 'Validation failed',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string'],
                                        'errors' => ['type' => 'object']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'NotFoundError' => [
                        'description' => 'Resource not found',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => ['type' => 'string', 'example' => 'No query results for model.']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'paths' => [],
            'tags' => []
        ];

        // Generate specifications for each resource
        foreach ($resources as $resource => $modelClass) {
            $fields = $this->getModelFields($modelClass);
            $permissions = $this->getPermissions($resource);
            
            // Add schemas for this resource
            $fullSpec['components']['schemas'][ucfirst($resource)] = $this->generateSchema($fields);
            $fullSpec['components']['schemas'][ucfirst($resource) . 'Collection'] = [
                'type' => 'object',
                'properties' => [
                    'data' => [
                        'type' => 'array',
                        'items' => [
                            '$ref' => '#/components/schemas/' . ucfirst($resource)
                        ]
                    ],
                    'meta' => [
                        'type' => 'object',
                        'properties' => [
                            'current_page' => ['type' => 'integer', 'example' => 1],
                            'per_page' => ['type' => 'integer', 'example' => 15],
                            'total' => ['type' => 'integer', 'example' => 100],
                            'last_page' => ['type' => 'integer', 'example' => 7],
                            'from' => ['type' => 'integer', 'example' => 1],
                            'to' => ['type' => 'integer', 'example' => 15]
                        ]
                    ],
                    'links' => [
                        'type' => 'object',
                        'properties' => [
                            'first' => ['type' => 'string'],
                            'last' => ['type' => 'string'],
                            'prev' => ['type' => 'string', 'nullable' => true],
                            'next' => ['type' => 'string', 'nullable' => true]
                        ]
                    ]
                ]
            ];
            
            // Add paths for this resource
            $resourcePaths = $this->generateOpenApiPaths($resource, $fields, $permissions);
            $fullSpec['paths'] = array_merge($fullSpec['paths'], $resourcePaths);
            
            // Add tag for this resource
            $fullSpec['tags'][] = [
                'name' => ucfirst($resource),
                'description' => ucfirst($resource) . ' management operations'
            ];
        }

        // Add authorization information
        $fullSpec['x-authorization'] = [
            'description' => 'Role-based access control information',
            'roles' => $this->getRolesWithPermissions('users'),
            'sample_users' => $this->getSampleUsers(),
            'authentication' => [
                'type' => 'Laravel Sanctum',
                'login_endpoint' => config('app.url') . '/api/login',
                'token_header' => 'Authorization: Bearer {token}',
                'instructions' => 'Login with email/password to get access token, then include token in Authorization header'
            ]
        ];

        return response()->json($fullSpec, 200, [], JSON_PRETTY_PRINT);
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

    /**
     * Generate OpenAPI 3.0 specification for automated system integration
     */
    private function generateOpenApiSpec($resource, $modelClass, $fields, $permissions)
    {
        $baseUrl = config('app.url') . '/api';
        
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => config('app.name') . ' API',
                'description' => 'Auto-generated API documentation with Laravel Orion',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'API Support',
                    'url' => config('app.url'),
                ]
            ],
            'servers' => [
                [
                    'url' => $baseUrl,
                    'description' => 'Main API Server'
                ]
            ],
            'security' => [
                [
                    'bearerAuth' => []
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ],
                'schemas' => [
                    ucfirst($resource) => $this->generateSchema($fields),
                    ucfirst($resource) . 'Collection' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    '$ref' => '#/components/schemas/' . ucfirst($resource)
                                ]
                            ],
                            'meta' => [
                                'type' => 'object',
                                'properties' => [
                                    'current_page' => ['type' => 'integer'],
                                    'per_page' => ['type' => 'integer'],
                                    'total' => ['type' => 'integer'],
                                    'last_page' => ['type' => 'integer']
                                ]
                            ]
                        ]
                    ],
                    'Error' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'errors' => ['type' => 'object']
                        ]
                    ]
                ]
            ],
            'paths' => $this->generateOpenApiPaths($resource, $fields, $permissions)
        ];
    }

    /**
     * Generate OpenAPI schema from model fields
     */
    private function generateSchema($fields)
    {
        $properties = [];
        $required = [];

        foreach ($fields as $fieldName => $fieldInfo) {
            if ($fieldInfo['hidden']) {
                continue; // Skip hidden fields
            }

            $properties[$fieldName] = [
                'type' => $this->mapToOpenApiType($fieldInfo['type']),
                'description' => $fieldInfo['description']
            ];

            if ($fieldInfo['required'] && !in_array($fieldName, ['id', 'created_at', 'updated_at'])) {
                $required[] = $fieldName;
            }
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required
        ];
    }

    /**
     * Generate OpenAPI paths for all endpoints
     */
    private function generateOpenApiPaths($resource, $fields, $permissions)
    {
        $paths = [];
        
        // Collection endpoint (GET /users)
        if (isset($permissions['view-any'])) {
            $paths["/{$resource}"] = [
                'get' => [
                    'tags' => [ucfirst($resource)],
                    'summary' => "List all {$resource}",
                    'description' => "Retrieve paginated list of {$resource} with filtering and sorting capabilities",
                    'parameters' => [
                        [
                            'name' => 'page',
                            'in' => 'query',
                            'description' => 'Page number for pagination',
                            'schema' => ['type' => 'integer', 'default' => 1]
                        ],
                        [
                            'name' => 'per_page',
                            'in' => 'query', 
                            'description' => 'Number of items per page',
                            'schema' => ['type' => 'integer', 'default' => 15]
                        ],
                        [
                            'name' => 'sort',
                            'in' => 'query',
                            'description' => 'Sort field (prefix with - for descending)',
                            'schema' => ['type' => 'string', 'example' => '-created_at']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/' . ucfirst($resource) . 'Collection'
                                    ]
                                ]
                            ]
                        ],
                        '401' => ['description' => 'Unauthorized'],
                        '403' => ['description' => 'Forbidden']
                    ]
                ]
            ];
        }

        // Create endpoint (POST /users)
        if (isset($permissions['create'])) {
            if (!isset($paths["/{$resource}"])) {
                $paths["/{$resource}"] = [];
            }
            
            $paths["/{$resource}"]['post'] = [
                'tags' => [ucfirst($resource)],
                'summary' => "Create new {$resource}",
                'description' => "Create a new {$resource} record",
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/' . ucfirst($resource)
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '201' => [
                        'description' => 'Created successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            '$ref' => '#/components/schemas/' . ucfirst($resource)
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '422' => ['description' => 'Validation errors'],
                    '401' => ['description' => 'Unauthorized'],
                    '403' => ['description' => 'Forbidden']
                ]
            ];
        }

        // Individual resource endpoints
        $resourcePath = "/{$resource}/{id}";
        
        // Show endpoint (GET /users/{id})
        if (isset($permissions['view'])) {
            $paths[$resourcePath]['get'] = [
                'tags' => [ucfirst($resource)],
                'summary' => "Get specific {$resource}",
                'description' => "Retrieve a single {$resource} by ID",
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'description' => ucfirst($resource) . ' ID',
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Success',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            '$ref' => '#/components/schemas/' . ucfirst($resource)
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '404' => ['description' => 'Not found'],
                    '401' => ['description' => 'Unauthorized'],
                    '403' => ['description' => 'Forbidden']
                ]
            ];
        }

        // Update endpoint (PUT/PATCH /users/{id})
        if (isset($permissions['update'])) {
            $paths[$resourcePath]['put'] = [
                'tags' => [ucfirst($resource)],
                'summary' => "Update {$resource}",
                'description' => "Update an existing {$resource}",
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'description' => ucfirst($resource) . ' ID',
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/' . ucfirst($resource)
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Updated successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => [
                                            '$ref' => '#/components/schemas/' . ucfirst($resource)
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '422' => ['description' => 'Validation errors'],
                    '404' => ['description' => 'Not found'],
                    '401' => ['description' => 'Unauthorized'],
                    '403' => ['description' => 'Forbidden']
                ]
            ];
        }

        // Delete endpoint (DELETE /users/{id})
        if (isset($permissions['delete'])) {
            $paths[$resourcePath]['delete'] = [
                'tags' => [ucfirst($resource)],
                'summary' => "Delete {$resource}",
                'description' => "Delete a {$resource} record",
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'description' => ucfirst($resource) . ' ID',
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'responses' => [
                    '200' => ['description' => 'Deleted successfully'],
                    '404' => ['description' => 'Not found'],
                    '401' => ['description' => 'Unauthorized'],
                    '403' => ['description' => 'Forbidden']
                ]
            ];
        }

        return $paths;
    }

    /**
     * Map database types to OpenAPI types
     */
    private function mapToOpenApiType($type)
    {
        switch ($type) {
            case 'integer':
            case 'bigint':
                return 'integer';
            case 'decimal':
            case 'float':
            case 'double':
                return 'number';
            case 'boolean':
                return 'boolean';
            case 'datetime':
            case 'timestamp':
                return 'string';
            default:
                return 'string';
        }
    }

    /**
     * Get roles with their permissions from database
     */
    private function getRolesWithPermissions($resource)
    {
        return Role::with(['permissions' => function($query) use ($resource) {
            $query->where('resource', $resource);
        }])->get()->map(function($role) {
            return [
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'permissions' => $role->permissions->map(function($permission) {
                    return [
                        'action' => $permission->action,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'endpoints' => $this->getEndpointsForPermission($permission)
                    ];
                })->toArray()
            ];
        })->toArray();
    }

    /**
     * Get sample users with their roles from database
     */
    private function getSampleUsers()
    {
        return User::with('roles')->whereIn('email', [
            'superadmin@example.com',
            'admin@example.com', 
            'editor@example.com',
            'viewer@example.com'
        ])->get()->map(function($user) {
            return [
                'email' => $user->email,
                'name' => $user->name,
                'roles' => $user->roles->pluck('name')->toArray(),
                'role_descriptions' => $user->roles->map(function($role) {
                    return [
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                        'description' => $role->description
                    ];
                })->toArray(),
                'password' => 'password', // For documentation purposes
                'login_instructions' => 'Use email and password to get access token via /api/login'
            ];
        })->toArray();
    }

    /**
     * Get endpoints that a role can access
     */
    private function getEndpointsForRole($role)
    {
        $endpoints = [];
        
        // Super admin follows same permission logic (should have all permissions anyway)

        // Check permissions for other roles
        $permissions = $role->permissions->pluck('action')->toArray();
        
        // Precise 1:1 permission to endpoint mapping
        if (in_array('index', $permissions)) {
            $endpoints['GET /api/users'] = 'List all users with pagination, filtering, sorting';
        }
        
        if (in_array('show', $permissions)) {
            $endpoints['GET /api/users/{id}'] = 'Get specific user details';
        }
        
        if (in_array('store', $permissions)) {
            $endpoints['POST /api/users'] = 'Create new user';
        }
        
        if (in_array('update', $permissions)) {
            $endpoints['PUT /api/users/{id}'] = 'Update existing user';
        }
        
        if (in_array('destroy', $permissions)) {
            $endpoints['DELETE /api/users/{id}'] = 'Delete user';
        }
        
        if (in_array('search', $permissions)) {
            $endpoints['POST /api/users/search'] = 'Advanced search users';
        }
        
        if (in_array('batch', $permissions)) {
            $endpoints['POST /api/users/batch'] = 'Create multiple users';
            $endpoints['PATCH /api/users/batch'] = 'Update multiple users';
            $endpoints['DELETE /api/users/batch'] = 'Delete multiple users';
        }

        return $endpoints;
    }

    /**
     * Get accessible endpoints for a specific permission
     */
    private function getEndpointsForPermission($permission)
    {
        $endpoints = [];
        
        // Map permission actions to endpoints
        switch($permission->action) {
            case 'index':
                $endpoints[] = 'GET /api/users';
                break;
            case 'show':
                $endpoints[] = 'GET /api/users/{id}';
                break;
            case 'store':
                $endpoints[] = 'POST /api/users';
                break;
            case 'update':
                $endpoints[] = 'PUT /api/users/{id}';
                break;
            case 'destroy':
                $endpoints[] = 'DELETE /api/users/{id}';
                break;
            case 'search':
                $endpoints[] = 'POST /api/users/search';
                break;
            case 'batch':
                // Batch permission covers multiple endpoints
                $endpoints[] = 'POST /api/users/batch';
                $endpoints[] = 'PATCH /api/users/batch';  
                $endpoints[] = 'DELETE /api/users/batch';
                break;
        }
        
        return $endpoints;
    }
}
