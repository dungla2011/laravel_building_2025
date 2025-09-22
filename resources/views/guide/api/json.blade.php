@extends('guide.layout-simple')

@section('title', 'API JSON Documentation')

@section('content')
<div class="container-fluid">
    <div class="p-4">
        <h1 class="h2 mb-4">API JSON Documentation</h1>
        
        <div class="alert alert-info" role="alert">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div>
                    <strong>Automatic API Integration:</strong> These JSON endpoints provide machine-readable API specifications for automated system integration, code generation, and testing tools.
                </div>
            </div>
        </div>

        <!-- Available JSON Endpoints -->
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Available JSON Endpoints</h2>
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
                            <p class="text-muted mb-3 small">Full OpenAPI 3.0 specification for all API resources. Compatible with Swagger UI, Postman, and code generation tools.</p>
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
                            <h3 class="h6 mb-2">Swagger JSON (Alias)</h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/swagger.json') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">Same as OpenAPI JSON but with traditional Swagger naming for compatibility with legacy tools.</p>
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

                    <!-- Users Specific OpenAPI -->
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h3 class="h6 mb-2">Users Resource OpenAPI</h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/users/openapi.json') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">OpenAPI specification specifically for Users resource endpoints only.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ url('/guide/api/users/openapi.json') }}" target="_blank" class="btn btn-warning btn-sm">
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

                    <!-- Custom API Data -->
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h3 class="h6 mb-2">Custom API Data (JavaScript)</h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/users/data') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">Custom JSON format optimized for JavaScript applications and dynamic documentation.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ url('/guide/api/users/data') }}" target="_blank" class="btn btn-info btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    View JSON
                                </a>
                                <button onclick="copyToClipboard(this)" data-url="{{ url('/guide/api/users/data') }}" class="btn btn-outline-secondary btn-sm">
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
                                Roles & Permissions Data
                            </h3>
                            <div class="bg-light p-2 rounded mb-2">
                                <code class="small">GET {{ url('/guide/api/roles-permissions.json') }}</code>
                            </div>
                            <p class="text-muted mb-3 small">Complete roles and permissions information from database with user access matrix and sample credentials.</p>
                            <div class="d-flex gap-2">
                                <a href="{{ url('/guide/api/roles-permissions.json') }}" target="_blank" class="btn btn-primary btn-sm">
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