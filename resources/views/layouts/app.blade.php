<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'LAD-GALAXY-2025')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom Styles -->
    @stack('styles')
    
    <style>
        body {
            background-color: #f8f9fa;
        }

        td.bg-light {
            padding: 5px 10px;
        }
        
        .navbar-brand {
            font-weight: 600;
        }
        
        .main-content {
            min-height: calc(100vh - 56px);
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        
        .footer {
            background-color: #343a40;
            color: white;
            padding: 1rem 0;
            margin-top: auto;
        }
        
        .btn {
            border-radius: 0.375rem;
        }
        
        .card {
            border-radius: 0.5rem;
        }
        
        /* Admin Panel Styles */
        .permission-matrix {
            overflow-x: auto;
        }
        .permission-cell {
            min-width: 140px;
        }
        .permission-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* margin-bottom: 8px; */
            padding: 2px 5px;
            /* border-radius: 6px; */
            background-color: #f8f9fa;
        }
        .permission-label {
            font-size: 13px;
            font-weight: 500;
            color: #495057;
        }
        .permission-toggle {
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 20px;
            background: none;
            padding: 2px;
        }
        .permission-toggle.read.active {
            color: #28a745;
        }
        .permission-toggle.read.inactive {
            color: #6c757d;
        }
        .permission-toggle.write.active {
            color: #007bff;
        }
        .permission-toggle.write.inactive {
            color: #6c757d;
        }
        .role-header {
            text-align: center;
            padding: 10px 5px;
            min-width: 120px;
        }
        .role-header small {
            font-size: 11px;
            opacity: 0.8;
        }
        .admin-stats .card {
            transition: transform 0.2s;
        }
        .admin-stats .card:hover {
            transform: translateY(-2px);
        }
        
        /* Toast Styles */
        .toast {
            margin-bottom: 10px;
        }
        
        .toast-header {
            border-bottom: 1px solid rgba(0,0,0,.125);
        }
        
        .toast-body {
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <i class="fas fa-rocket me-2"></i>
                    LAD-GALAXY-2025
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a target="_blank" class="nav-link" href="{{ url('/') }}">
                                <i class="fas fa-home me-1"></i>
                                Home
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a target="_blank" class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog me-1"></i>
                                Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a  class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="fas fa-tachometer-alt me-2"></i>
                                        Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item" href="{{ route('admin.users') }}">
                                        <i class="fas fa-users me-2"></i>
                                        Users
                                    </a>
                                </li>
            
                                <li>
                                    <a  class="dropdown-item" href="{{ route('admin.role-permissions.index') }}">
                                        <i class="fas fa-users-cog me-2"></i>
                                        Role Permissions
                                    </a>
                                </li>
                                <li>
                                    <a  class="dropdown-item" href="{{ route('admin.field-permissions') }}">
                                        <i class="fas fa-table me-2"></i>
                                        Field Permissions
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="apiDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-book me-1"></i>
                                API Docs
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" target="_blank" href="{{ route('guide.api.json') }}">
                                        <i class="fas fa-code me-2"></i>
                                        JSON Documentation
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" target="_blank" href="{{ route('guide.api.users') }}">
                                        <i class="fas fa-users me-2"></i>
                                        Users API
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="fas fa-user me-1"></i>
                                <span class="badge bg-success">Demo Mode</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">
                            <i class="fas fa-copyright me-1"></i>
                            LAD-GALAXY-2025. Version {{ app()->version() }}
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="mb-0">
                            <i class="fas fa-code me-1"></i>
                            API-First Development Platform
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- jQuery (required for our JavaScript) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Toast Container -->
    <div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>

    <!-- Custom Scripts -->
    @stack('scripts')
</body>
</html>