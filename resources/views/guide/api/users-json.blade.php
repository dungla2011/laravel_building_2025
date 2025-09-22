@extends('guide.layout-simple')

@section('title', 'Users API JSON Documentation')

@section('content')
<div class="container-fluid">
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-2">Users API JSON Documentation</h1>
                <p class="text-muted mb-0">Machine-readable documentation for Users API endpoints</p>
            </div>
            <div>
                <a href="{{ route('guide.api.users') }}" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to Users Guide
                </a>
                <a href="{{ route('guide.api.json') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-list me-1"></i> All APIs
                </a>
            </div>
        </div>
        
        <div class="alert alert-info" role="alert">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div>
                    <strong>Users API Integration:</strong> These JSON endpoints provide machine-readable specifications specifically for the Users API endpoints, including detailed roles and permissions information.
                </div>
            </div>
        </div>

        <!-- Available JSON Endpoints for Users -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Available Users API JSON Endpoints</h2>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <!-- Users OpenAPI Specification -->
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h3 class="h6 mb-2">Users OpenAPI 3.0 Specification</h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/users/openapi.json') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">Complete OpenAPI 3.0 specification for Users API endpoints only. Perfect for generating client SDKs or testing tools specific to user management.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ url('/guide/api/users/openapi.json') }}" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    View JSON
                                </a>
                                <button onclick="copyToClipboard(this)" data-url="{{ url('/guide/api/users/openapi.json') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-copy me-1"></i>
                                    Copy URL
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Roles & Permissions Data -->
                    <div class="col-12">
                        <div class="border rounded p-3 border-primary">
                            <h3 class="h6 mb-2 text-primary">
                                <i class="fas fa-users-cog me-1"></i>
                                Users Roles & Permissions
                            </h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/users/roles-permissions.json') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">Complete roles and permissions information for Users API with access matrices, sample credentials, and detailed permission descriptions.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ url('/guide/api/users/roles-permissions.json') }}" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    View JSON
                                </a>
                                <button onclick="copyToClipboard(this)" data-url="{{ url('/guide/api/users/roles-permissions.json') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-copy me-1"></i>
                                    Copy URL
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Custom API Data -->
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h3 class="h6 mb-2">Users API Data (Custom Format)</h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/users/data.json') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">Custom JSON format optimized for JavaScript applications with Users API endpoints, field definitions, sample data, and authorization information.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ url('/guide/api/users/data.json') }}" target="_blank" class="btn btn-info btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    View JSON
                                </a>
                                <button onclick="copyToClipboard(this)" data-url="{{ url('/guide/api/users/data.json') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-copy me-1"></i>
                                    Copy URL
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Examples -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Users API Usage Examples</h2>
            </div>
            <div class="card-body">
                <!-- Postman Collection -->
                <div class="mb-4">
                    <h3 class="h6 mb-2">Import to Postman</h3>
                    <div class="bg-light p-3 rounded">
                        <p class="small mb-1">1. Open Postman</p>
                        <p class="small mb-1">2. Click "Import" → "Link"</p>
                        <p class="small mb-2">3. Paste this URL:</p>
                        <code class="small bg-white p-2 rounded border d-inline-block">{{ url('/guide/api/users/openapi.json') }}</code>
                    </div>
                </div>

                <!-- JavaScript SDK Generation -->
                <div class="mb-4">
                    <h3 class="h6 mb-2">Generate JavaScript Client</h3>
                    <div class="bg-light p-3 rounded">
                        <pre class="small mb-0"><code># Generate JavaScript SDK for Users API
openapi-generator-cli generate \
  -i {{ url('/guide/api/users/openapi.json') }} \
  -g javascript \
  -o ./users-api-client \
  --additional-properties=projectName=UsersAPIClient</code></pre>
                    </div>
                </div>

                <!-- Python SDK Generation -->
                <div class="mb-4">
                    <h3 class="h6 mb-2">Generate Python Client</h3>
                    <div class="bg-light p-3 rounded">
                        <pre class="small mb-0"><code># Generate Python SDK for Users API
openapi-generator-cli generate \
  -i {{ url('/guide/api/users/openapi.json') }} \
  -g python \
  -o ./users-python-client \
  --additional-properties=packageName=users_api_client</code></pre>
                    </div>
                </div>

                <!-- Authentication Testing -->
                <div>
                    <h3 class="h6 mb-2">Test Authentication & Permissions</h3>
                    <div class="bg-light p-3 rounded">
                        <pre class="small mb-0"><code># Get roles and permissions info
curl {{ url('/guide/api/users/roles-permissions.json') }}

# Test with different user roles
curl -H "Authorization: Bearer SUPER_ADMIN_TOKEN" \
     {{ url('/api/users') }}

curl -H "Authorization: Bearer VIEWER_TOKEN" \
     {{ url('/api/users') }}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="card">
            <div class="card-header">
                <h2 class="h5 mb-0">Users API Features</h2>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h3 class="h6 mb-2">Role-Based Access Control</h3>
                        <p class="text-muted small">Complete RBAC system with Super Admin, Admin, Editor, and Viewer roles. Each role has specific permissions for different operations.</p>
                    </div>
                    <div class="col-md-6">
                        <h3 class="h6 mb-2">Laravel Orion Integration</h3>
                        <p class="text-muted small">Built on Laravel Orion for automatic CRUD operations, advanced filtering, sorting, pagination, and batch operations.</p>
                    </div>
                    <div class="col-md-6">
                        <h3 class="h6 mb-2">Sanctum Authentication</h3>
                        <p class="text-muted small">Token-based authentication using Laravel Sanctum with secure token generation and management.</p>
                    </div>
                    <div class="col-md-6">
                        <h3 class="h6 mb-2">OpenAPI 3.0 Compliant</h3>
                        <p class="text-muted small">Full OpenAPI 3.0 specification for maximum compatibility with code generation tools and API testing frameworks.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(button) {
    const text = button.getAttribute('data-url');
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showCopySuccess(button);
        }, function(err) {
            console.error('Could not copy text: ', err);
            fallbackCopyTextToClipboard(text, button);
        });
    } else {
        fallbackCopyTextToClipboard(text, button);
    }
}

function fallbackCopyTextToClipboard(text, button) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        var successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess(button);
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }
    document.body.removeChild(textArea);
}

function showCopySuccess(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');
    
    setTimeout(function() {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}
</script>
@endsection