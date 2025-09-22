<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'API Documentation')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .guide-navbar {
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .guide-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .main-content {
            min-height: calc(100vh - 76px);
            padding: 2rem 0;
        }

        .api-card {
            padding: 15px;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .api-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .method-badge {
            font-weight: 700;
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .method-get { background: linear-gradient(135deg, #10b981, #059669); }
        .method-post { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .method-put { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .method-patch { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .method-delete { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .endpoint-url {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            background: #f1f5f9;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            font-size: 0.95rem;
            font-weight: 600;
        }

        .permission-badge {
            background: linear-gradient(135deg, #ec4899, #db2777);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .test-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .json-editor {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            background: #1a202c;
            color: #e2e8f0;
            border: none;
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 120px;
        }

        .response-area {
            background: #1a202c;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .btn-test {
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .parameter-table {
            font-size: 0.9rem;
        }

        .parameter-table th {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            color: #374151;
            font-weight: 600;
            border: none;
        }

        .parameter-table td {
            border-color: #e5e7eb;
            vertical-align: middle;
        }

        .required-badge {
            background: #fef2f2;
            color: #dc2626;
            padding: 0.125rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .optional-badge {
            background: #f0fdf4;
            color: #16a34a;
            padding: 0.125rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .auth-section {
            background: linear-gradient(135deg, #fef3c7, #fbbf24);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #f59e0b;
        }

        .loading-spinner {
            display: none;
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
        }

        .sidebar {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .sidebar .nav-link {
            color: #64748b;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .sidebar .nav-link:hover {
            background: #f1f5f9;
            color: var(--primary-color);
        }

        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-200 { background: #10b981; }
        .status-201 { background: #3b82f6; }
        .status-400 { background: #f59e0b; }
        .status-401 { background: #ef4444; }
        .status-404 { background: #6b7280; }
        .status-422 { background: #8b5cf6; }
        .status-500 { background: #dc2626; }

        pre {
            background: #1a202c !important;
            border-radius: 8px;
            padding: 1rem;
            overflow-x: auto;
        }

        code {
            background: #e2e8f0;
            color: #374151;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-size: 0.9em;
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg guide-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('guide.api.users') }}">
                <i class="fas fa-book me-2"></i>
                API Documentation
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-light" href="{{ url('/admin/users') }}">
                    <i class="fas fa-cogs me-1"></i>
                    Admin Panel
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.4.0/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    
    <script>
        // Global configurations
        window.API_BASE_URL = '{{ url("/api") }}';
        window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Configure axios defaults
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.headers.common['Content-Type'] = 'application/json';
        
        // Utility functions
        window.formatJson = function(obj) {
            return JSON.stringify(obj, null, 2);
        };
        
        window.showToast = function(message, type = 'success') {
            // Simple toast implementation
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        };
    </script>
    
    @stack('scripts')
</body>
</html>