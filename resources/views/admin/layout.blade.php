<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --info-color: #0891b2;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-navbar {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .admin-navbar .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .main-content {
            min-height: calc(100vh - 76px);
            padding: 2rem 0;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-bottom: 2px solid var(--primary-color);
            border-radius: 12px 12px 0 0 !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #047857);
            border: none;
            border-radius: 8px;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #b91c1c);
            border: none;
            border-radius: 8px;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #b45309);
            border: none;
            border-radius: 8px;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
            color: white;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f1f5f9;
            transform: scale(1.01);
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .badge {
            border-radius: 6px;
            font-weight: 500;
        }

        .loading {
            display: none !important;
        }

        .loading.show {
            display: flex !important;
        }

        .alert {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .pagination {
            --bs-pagination-border-radius: 8px;
        }

        .pagination .page-link {
            border: none;
            color: var(--primary-color);
            border-radius: 6px;
            margin: 0 2px;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-color), #1d4ed8);
            border: none;
        }

        .search-box {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stats-card .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .stats-card .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cogs me-2"></i>
                Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-light">
                    <i class="fas fa-user me-1"></i>
                    Administrator
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            @yield('content')
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.4.0/axios.min.js"></script>
    
    <script>
        // Global configurations
        window.API_BASE_URL = '{{ url("/api") }}';
        window.CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Configure axios defaults
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.headers.common['Content-Type'] = 'application/json';
        
        // Global utility functions
        window.showLoading = function() {
            document.getElementById('loadingOverlay').classList.add('show');
        };
        
        window.hideLoading = function() {
            document.getElementById('loadingOverlay').classList.remove('show');
        };
        
        window.showToast = function(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHTML = `
                <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-${type} text-white">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} me-2"></i>
                        <strong class="me-auto">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Remove toast after it's hidden
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        };
        
        window.formatDate = function(dateString) {
            return new Date(dateString).toLocaleString();
        };
        
        window.handleApiError = function(error) {
            console.error('API Error:', error);
            let message = 'An error occurred';
            
            if (error.response && error.response.data) {
                if (error.response.data.message) {
                    message = error.response.data.message;
                } else if (error.response.data.errors) {
                    const errors = Object.values(error.response.data.errors).flat();
                    message = errors.join(', ');
                }
            }
            
            showToast(message, 'error');
        };
    </script>
    
    @stack('scripts')
</body>
</html>