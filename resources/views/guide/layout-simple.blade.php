<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'API Documentation')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Prism.js for syntax highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container-fluid {
            max-width: 1200px;
            margin: 2rem auto;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0;
        }

        .sidebar {
            background: #f8f9fa;
            padding: 1.5rem;
            border-right: 1px solid #dee2e6;
            height: calc(100vh - 4rem);
            overflow-y: auto;
            position: sticky;
            top: 1rem;
        }

        .api-card {
            padding: 15px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .method-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            min-width: 50px;
            text-align: center;
            border: 1px solid;
        }

        .method-get { background: #fff; color: #28a745; border-color: #28a745; }
        .method-post { background: #fff; color: #007bff; border-color: #007bff; }
        .method-put { background: #fff; color: #fd7e14; border-color: #fd7e14; }
        .method-patch { background: #fff; color: #6f42c1; border-color: #6f42c1; }
        .method-delete { background: #fff; color: #dc3545; border-color: #dc3545; }

        .endpoint-url {
            font-family: 'Monaco', 'Menlo', monospace;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            font-size: 0.9rem;
        }

        .permission-badge {
            background: #f8f9fa;
            color: #6c757d;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            border: 1px solid #dee2e6;
        }

        .test-section {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid #dee2e6;
        }

        .json-editor {
            font-family: 'Monaco', 'Menlo', monospace;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.75rem;
            font-size: 0.85rem;
            resize: vertical;
        }

        .response-area {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.75rem;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.85rem;
            max-height: 300px;
            overflow-y: auto;
            color: #333;
        }

        .btn-test {
            background: #007bff;
            border: none;
            color: white;
        }

        .btn-test:hover {
            background: #0056b3;
            color: white;
        }

        .auth-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .parameter-table {
            font-size: 0.9rem;
        }

        .parameter-table th {
            background: #f8f9fa;
            font-weight: 600;
            border-top: none;
        }

        .required-badge {
            background: #dc3545;
            color: white;
            padding: 0.1rem 0.4rem;
            border-radius: 3px;
            font-size: 0.7rem;
        }

        .optional-badge {
            background: #6c757d;
            color: white;
            padding: 0.1rem 0.4rem;
            border-radius: 3px;
            font-size: 0.7rem;
        }

        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .status-200, .status-201 { background: #28a745; }
        .status-400, .status-401, .status-404, .status-422, .status-500 { background: #dc3545; }

        .nav-link {
            color: #495057;
            padding: 0.5rem 0;
            border: none;
            text-decoration: none;
        }

        .nav-link:hover {
            color: #007bff;
        }

        .nav-link.active {
            color: #007bff;
            font-weight: 600;
        }

        .loading-spinner {
            display: none;
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        pre {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.75rem;
            font-size: 0.85rem;
        }

        code {
            background: #f8f9fa;
            color: #e83e8c;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-size: 0.875em;
        }

        pre code {
            background: none;
            color: inherit;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row g-0">
            <main class="col-12 p-4">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Toast container -->
    <div class="toast-container"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios@1.4.0/dist/axios.min.js"></script>
    <!-- Prism.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>

    <script>
        // Global API base URL
        const API_BASE_URL = '{{ url("/") }}';

        // Format JSON for display
        function formatJson(obj) {
            return JSON.stringify(obj, null, 2);
        }

        // Show toast notification
        function showToast(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container');
            const toastElement = document.createElement('div');
            toastElement.className = `toast align-items-center text-white bg-${type} border-0`;
            toastElement.setAttribute('role', 'alert');
            toastElement.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toastElement);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Remove element after toast is hidden
            toastElement.addEventListener('hidden.bs.toast', function () {
                toastElement.remove();
            });
        }
    </script>

    @stack('scripts')
</body>
</html>