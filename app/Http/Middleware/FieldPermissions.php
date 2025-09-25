<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\FieldPermission;
use Illuminate\Support\Facades\Log;

class FieldPermissions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if field permissions are enabled
        if (!config('app.field_permissions_enabled', false)) {
            return $next($request);
        }

        // Handle WRITE operations (POST, PUT, PATCH) - validate input fields
        if ($this->isWriteOperation($request)) {
            $validationResult = $this->validateWritePermissions($request);
            if ($validationResult !== null) {
                return $validationResult;
            }
        }

        $response = $next($request);
        
        // Filter JSON responses for both READ and WRITE operations
        if (!$this->shouldFilter($request, $response)) {
            return $response;
        }

        return $this->filterResponse($request, $response);
    }

    private function shouldFilter(Request $request, Response $response): bool
    {
        // Only apply to API routes and JSON responses
        $contentType = $response->headers->get('content-type');
        $isJson = $contentType && (
            str_contains($contentType, 'application/json') || 
            str_contains($contentType, 'text/json')
        );
        
        return $request->is('api/*') && $isJson && $response->isSuccessful();
    }

    private function filterResponse(Request $request, Response $response): Response
    {
        $user = $request->user();
        if (!$user) {
            return $response;
        }

        $data = json_decode($response->getContent(), true);
        if (!$data) {
            return $response;
        }
        
        // Get table name from route
        $tableName = $this->getTableFromRoute($request);
        if (!$tableName) {
            return $response;
        }

        // For WRITE operations, return minimal response (only updated fields + system fields)
        if ($this->isWriteOperation($request)) {
            $filteredData = $this->filterWriteResponse($data, $request, $user, $tableName);
        } else {
            // For READ operations, apply standard field permissions
            $filteredData = $this->filterFields($data, $user, $tableName);
        }
        
        $response->setContent(json_encode($filteredData));
        return $response;
    }

    private function getTableFromRoute(Request $request): ?string
    {
        $route = $request->route();
        if (!$route) return null;

        // Extract table name from route (e.g., api/users -> users)
        $uri = $route->uri();
        if (preg_match('/api\/([^\/]+)/', $uri, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    private function filterFields(array $data, $user, string $tableName): array
    {
        // Get user's field permissions for this table
        $permissions = $this->getUserFieldPermissions($user, $tableName);
        
        // STRICT MODE: When FIELD_PERMISSIONS_ENABLED=true, block all fields unless explicitly allowed
        if (empty($permissions)) {
            return $this->blockAllFields($data); // No permissions = block everything except system fields
        }

        // User has specific permissions, filter accordingly
        if (isset($data['data'])) {
            if (is_array($data['data']) && $this->isIndexedArray($data['data'])) {
                // Multiple items (pagination)
                $data['data'] = array_map(fn($item) => $this->filterItem($item, $permissions), $data['data']);
            } else {
                // Single item
                $data['data'] = $this->filterItem($data['data'], $permissions);
            }
        } else {
            // Direct data without wrapper
            $data = $this->filterItem($data, $permissions);
        }

        return $data;
    }

    private function filterItem($item, array $permissions): array
    {
        // Handle non-array items
        if (!is_array($item)) {
            return [];
        }
        
        foreach ($item as $field => $value) {
            // Skip system fields
            if (in_array($field, ['id'])) {
                continue;
            }

            // Check if user has read permission for this field
            if (!$this->hasReadPermission($field, $permissions)) {
                unset($item[$field]);
            }
        }

        return $item;
    }

    private function getUserFieldPermissions($user, string $tableName): array
    {
        static $cache = [];
        $cacheKey = $user->id . '_' . $tableName;
        
        if (!isset($cache[$cacheKey])) {
            $permissions = FieldPermission::where('table_name', $tableName)
                ->whereIn('role_id', $user->roles->pluck('id'))
                ->get()
                ->groupBy('field_name')
                ->map(function ($fieldPerms) {
                    // If user has multiple roles with permissions for same field,
                    // grant permission if ANY role allows it
                    return [
                        'can_read' => $fieldPerms->contains('can_read', true),
                        'can_write' => $fieldPerms->contains('can_write', true)
                    ];
                })
                ->toArray();
            
            $cache[$cacheKey] = $permissions;
        }

        return $cache[$cacheKey];
    }

    private function hasReadPermission(string $field, array $permissions): bool
    {
        // STRICT MODE: Only allow if explicitly permitted
        return isset($permissions[$field]) && $permissions[$field]['can_read'];
    }
    
    private function blockAllFields(array $data): array
    {
        // Only keep system fields (id, timestamps)
        $allowedFields = ['id', 'created_at', 'updated_at'];
        
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = array_map(fn($item) => $this->keepOnlySystemFields($item, $allowedFields), $data['data']);
        } else {
            $data = $this->keepOnlySystemFields($data, $allowedFields);
        }
        
        return $data;
    }
    
    private function keepOnlySystemFields($item, array $allowedFields): array
    {
        // Handle non-array items
        if (!is_array($item)) {
            return [];
        }
        
        return array_intersect_key($item, array_flip($allowedFields));
    }
    
    private function isIndexedArray(array $array): bool
    {
        // Check if it's a list of items (indexed array) vs single associative item
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Check if this is a write operation
     */
    private function isWriteOperation(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH']);
    }

    /**
     * Validate write permissions for the request
     */
    private function validateWritePermissions(Request $request): ?Response
    {
        $user = $request->user();
        if (!$user) {
            return null; // Let auth middleware handle this
        }

        // Get the table name from the route
        $tableName = $this->getTableNameFromRoute($request);
        if (!$tableName) {
            return null; // Cannot determine table, skip validation
        }

        // Get submitted fields from request data
        $submittedFields = array_keys($request->all());
        
        // Get user's field permissions for this table
        $userPermissions = $this->getUserFieldPermissions($user, $tableName);
        
        // Check each submitted field
        $deniedFields = [];
        foreach ($submittedFields as $field) {
            // Skip system fields and special request fields
            if ($this->isSystemField($field) || $this->isRequestMetaField($field)) {
                continue;
            }
            
            if (!$this->hasWritePermission($userPermissions, $field)) {
                $deniedFields[] = $field;
            }
        }
        
        // Return error if any fields are denied
        if (!empty($deniedFields)) {
            return response()->json([
                'message' => 'Access denied. You do not have write permission for the following fields: ' . implode(', ', $deniedFields),
                'denied_fields' => $deniedFields
            ], 403);
        }
        
        return null; // All fields are allowed
    }

    /**
     * Get table name from the current route
     */
    private function getTableNameFromRoute(Request $request): ?string
    {
        $routeName = $request->route()?->getName();
        $uri = $request->getPathInfo();
        
        // For Orion routes like /api/users, extract table name
        if (preg_match('/\/api\/(\w+)/', $uri, $matches)) {
            return $matches[1]; // Return 'users' from '/api/users'
        }
        
        return null;
    }

    /**
     * Check if field is a system field that should always be allowed
     */
    private function isSystemField(string $field): bool
    {
        return in_array($field, ['id', 'created_at', 'updated_at']);
    }

    /**
     * Check if field is a request meta field (not actual data field)
     */
    private function isRequestMetaField(string $field): bool
    {
        return in_array($field, ['_token', '_method', 'api_token']);
    }

    /**
     * Check if user has write permission for a specific field
     */
    private function hasWritePermission(array $permissions, string $field): bool
    {
        return isset($permissions[$field]) && $permissions[$field]['can_write'];
    }

    /**
     * Filter response for WRITE operations - minimal response with only updated fields
     */
    private function filterWriteResponse(array $data, Request $request, $user, string $tableName): array
    {
        // Get fields that were submitted in the request
        $submittedFields = array_keys($request->all());
        
        // System fields that are always allowed
        $systemFields = ['id'];
        
        // Fields to include in response
        $allowedFields = array_merge($systemFields, $submittedFields);
        
        return $this->filterDataByFields($data, $allowedFields);
    }

    /**
     * Filter data array by keeping only specified fields
     */
    private function filterDataByFields(array $data, array $allowedFields): array
    {
        if (isset($data['data'])) {
            // Handle single item response
            if (!$this->isIndexedArray($data['data'])) {
                $data['data'] = array_intersect_key($data['data'], array_flip($allowedFields));
            } else {
                // Handle collection response
                $data['data'] = array_map(function ($item) use ($allowedFields) {
                    return array_intersect_key($item, array_flip($allowedFields));
                }, $data['data']);
            }
        }
        
        return $data;
    }
}
