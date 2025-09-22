@extends('guide.layout-simple')

@section('title', 'API JSON Documentation')

@section('content')
<div class="container-fluid">
    <div class="p-4">
        <h1 class="h2 mb-4">API JSON Documentation Overview</h1>
        
        <div class="alert alert-info" role="alert">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div>
                    <strong>Machine-Readable API Documentation:</strong> This page provides an overview of all available JSON endpoints for different API resources. Each resource has its own detailed JSON documentation for automated integration, code generation, and testing tools.
                </div>
            </div>
        </div>

        <!-- Resources Overview -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Available API Resources</h2>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <!-- Users API -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="h6 mb-2">
                                    <i class="fas fa-users me-2"></i>
                                    Users API
                                </h3>
                                <p class="text-muted small mb-3">Complete user management with role-based access control, authentication, and CRUD operations.</p>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('guide.api.users') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-book me-1"></i> HTML Guide
                                    </a>
                                    <a href="{{ route('guide.api.users.json') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-code me-1"></i> JSON Docs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Future: Products API -->
                    <div class="col-md-6">
                        <div class="card h-100 opacity-50">
                            <div class="card-body">
                                <h3 class="h6 mb-2">
                                    <i class="fas fa-box me-2"></i>
                                    Products API <small class="text-muted">(Coming Soon)</small>
                                </h3>
                                <p class="text-muted small mb-3">Product catalog management with categories, inventory, and pricing features.</p>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        <i class="fas fa-book me-1"></i> HTML Guide
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" disabled>
                                        <i class="fas fa-code me-1"></i> JSON Docs
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global JSON Endpoints -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Global JSON Endpoints</h2>
                <small class="text-muted">Combined specifications for all API resources</small>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <!-- Complete OpenAPI Specification -->
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h3 class="h6 mb-2">Complete OpenAPI 3.0 Specification</h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/openapi.json') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">Full OpenAPI 3.0 specification combining all API resources (Users, Products, etc.). Perfect for comprehensive API documentation and client generation.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ url('/guide/api/openapi.json') }}" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    View JSON
                                </a>
                                <button onclick="copyToClipboard(this)" data-url="{{ url('/guide/api/openapi.json') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-copy me-1"></i>
                                    Copy URL
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Swagger JSON (Alias) -->
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h3 class="h6 mb-2">Swagger JSON (Legacy Alias)</h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/swagger.json') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">Same as OpenAPI JSON but with traditional Swagger naming for compatibility with legacy tools and frameworks.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ url('/guide/api/swagger.json') }}" target="_blank" class="btn btn-success btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    View JSON
                                </a>
                                <button onclick="copyToClipboard(this)" data-url="{{ url('/guide/api/swagger.json') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-copy me-1"></i>
                                    Copy URL
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Global Roles & Permissions -->
                    <div class="col-12">
                        <div class="border rounded p-3 border-info">
                            <h3 class="h6 mb-2 text-info">
                                <i class="fas fa-shield-alt me-1"></i>
                                Global Roles & Permissions
                            </h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/roles-permissions.json') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">Complete system-wide roles and permissions data applicable to all API resources with user access matrix.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ url('/guide/api/roles-permissions.json') }}" target="_blank" class="btn btn-info btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    View JSON
                                </a>
                                <button onclick="copyToClipboard(this)" data-url="{{ url('/guide/api/roles-permissions.json') }}" class="btn btn-outline-secondary btn-sm">
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
                <h2 class="h5 mb-0">Usage Examples</h2>
            </div>
            <div class="card-body">
                <!-- Swagger UI -->
                <div class="mb-4">
                    <h3 class="h6 mb-2">Swagger UI Integration</h3>
                    <div class="bg-light p-3 rounded">
                        <pre class="small mb-0"><code>// Import the OpenAPI JSON into Swagger UI
const ui = SwaggerUIBundle({
  url: '{{ url('/guide/api/openapi.json') }}',
  dom_id: '#swagger-ui',
  presets: [
    SwaggerUIBundle.presets.apis,
    SwaggerUIStandalonePreset
  ]
});</code></pre>
                    </div>
                </div>

                <!-- Postman Collection -->
                <div class="mb-4">
                    <h3 class="h6 mb-2">Postman Collection</h3>
                    <div class="bg-light p-3 rounded">
                        <p class="small mb-1">1. Open Postman</p>
                        <p class="small mb-1">2. Click "Import" → "Link"</p>
                        <p class="small mb-2">3. Paste this URL:</p>
                        <code class="small bg-white p-2 rounded border d-inline-block">{{ url('/guide/api/openapi.json') }}</code>
                    </div>
                </div>

                <!-- Code Generation -->
                <div class="mb-4">
                    <h3 class="h6 mb-2">Code Generation</h3>
                    <div class="bg-light p-3 rounded">
                        <pre class="small mb-0"><code># Generate client SDK using OpenAPI Generator
openapi-generator-cli generate \
  -i {{ url('/guide/api/openapi.json') }} \
  -g javascript \
  -o ./api-client

# Generate Python client
openapi-generator-cli generate \
  -i {{ url('/guide/api/openapi.json') }} \
  -g python \
  -o ./python-client</code></pre>
                    </div>
                </div>

                <!-- cURL Examples -->
                <div>
                    <h3 class="h6 mb-2">cURL Examples</h3>
                    <div class="bg-light p-3 rounded">
                        <pre class="small mb-0"><code># Download OpenAPI specification
curl -H "Accept: application/json" \
     {{ url('/guide/api/openapi.json') }} \
     -o api-spec.json

# Test API endpoint with generated specification
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     {{ url('/api/users') }}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="card">
            <div class="card-header">
                <h2 class="h5 mb-0">Features</h2>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h3 class="h6 mb-2">Auto-Generated</h3>
                        <p class="text-muted small">Specifications are automatically generated from your database schema, model relationships, and permission system.</p>
                    </div>
                    <div class="col-md-6">
                        <h3 class="h6 mb-2">Always Up-to-Date</h3>
                        <p class="text-muted small">No manual maintenance required. API documentation stays in sync with your code changes.</p>
                    </div>
                    <div class="col-md-6">
                        <h3 class="h6 mb-2">Permission-Aware</h3>
                        <p class="text-muted small">Only documents endpoints that are actually available based on your authorization system.</p>
                    </div>
                    <div class="col-md-6">
                        <h3 class="h6 mb-2">Standards Compliant</h3>
                        <p class="text-muted small">Full OpenAPI 3.0 compliance for maximum compatibility with tools and frameworks.</p>
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