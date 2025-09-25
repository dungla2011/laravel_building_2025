@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/toast-system.min.css') }}">
<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid #dee2e6;
}

.stat-card {
    transition: transform 0.2s ease-in-out;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.resource-header {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

.btn-group-sm .btn {
    font-size: 0.775rem;
    padding: 0.25rem 0.5rem;
}

code {
    font-size: 0.8em;
    background: #f8f9fa;
    padding: 0.2em 0.4em;
    border-radius: 0.25rem;
}

.badge {
    font-size: 0.7em;
}

.table th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="fas fa-tachometer-alt me-2 text-primary"></i>
                        Admin Dashboard
                    </h1>
                    <p class="text-muted mb-0">Overview of system management and quick access to admin functions</p>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.role-permissions.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-users-cog me-1"></i>
                        Role Permissions
                    </a>
                    <button type="button" class="btn btn-outline-secondary" onclick="refreshStats()">
                        <i class="fas fa-sync-alt me-1"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <h4 class="text-primary">{{ $stats['total_users'] }}</h4>
                            <p class="text-muted mb-0">Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card border-success">
                        <div class="card-body text-center">
                            <i class="fas fa-user-shield fa-2x text-success mb-2"></i>
                            <h4 class="text-success">{{ $stats['total_roles'] }}</h4>
                            <p class="text-muted mb-0">System Roles</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card border-warning">
                        <div class="card-body text-center">
                            <i class="fas fa-key fa-2x text-warning mb-2"></i>
                            <h4 class="text-warning">{{ $stats['total_permissions'] }}</h4>
                            <p class="text-muted mb-0">Permissions</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stat-card border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                            <h4 class="text-info">{{ $stats['recent_users']->count() }}</h4>
                            <p class="text-muted mb-0">Recent Users</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Roles Overview -->
            <div class="card mb-4">
                <div class="card-header resource-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users-cog me-2"></i>
                        System Roles Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($roles as $role)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="card border-primary h-100">
                                <div class="card-body text-center">
                                    <h6 class="card-title">
                                        <i class="fas fa-user-tag me-1 text-primary"></i>
                                        {{ $role->display_name }}
                                    </h6>
                                    <p class="card-text small text-muted">
                                        {{ $role->description ?? 'No description available' }}
                                    </p>
                                    <small class="text-muted">Role: <code>{{ $role->name }}</code></small>
                                    <div class="mt-3">
                                        <span class="badge bg-primary">{{ $role->users_count }} users</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center">
                            <p class="text-muted">No roles found in the system.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="card">
                <div class="card-header resource-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Recent Users
                    </h5>
                </div>
                <div class="card-body">
                    @if($stats['recent_users']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['recent_users'] as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $user->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=0d6efd&color=fff' }}" 
                                                 alt="{{ $user->name }}" class="user-avatar me-2">
                                            <div>
                                                <div class="fw-bold">{{ $user->name }}</div>
                                                <small class="text-muted">#{{ $user->id }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                    <td>
                                        @if($user->email_verified_at)
                                        <span class="badge bg-success">Verified</span>
                                        @else
                                        <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No users found in the system.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header resource-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>
                                Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="{{ route('admin.role-permissions.index') }}" class="btn btn-primary w-100">
                                        <i class="fas fa-users-cog me-2"></i>
                                        Role Permissions
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="{{ route('admin.field-permissions') }}" class="btn btn-warning w-100">
                                        <i class="fas fa-table me-2"></i>
                                        Field Permissions
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="button" class="btn btn-success w-100" onclick="syncPermissions()">
                                        <i class="fas fa-sync-alt me-2"></i>
                                        Sync API Routes
                                    </button>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="button" class="btn btn-info w-100" onclick="exportData()">
                                        <i class="fas fa-download me-2"></i>
                                        Export System Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" style="background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(2px); z-index: 9999;">
    <div class="text-center text-white">
        <div class="spinner-border mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Processing...</p>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/toast-system.min.js') }}"></script>
<script>
function refreshStats() {
    showLoading();
    window.location.reload();
}

function syncPermissions() {
    showLoading();
    fetch('{{ route("admin.role-permissions.sync") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            ToastSystem.success(data.message || 'Permissions synced successfully');
        } else {
            ToastSystem.error(data.message || 'Failed to sync permissions');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        ToastSystem.error('An error occurred while syncing permissions');
    });
}

function exportData() {
    showLoading();
    // Implement export functionality
    setTimeout(() => {
        hideLoading();
        ToastSystem.info('Export functionality will be implemented soon');
    }, 1000);
}

function showLoading() {
    document.getElementById('loading-overlay').classList.remove('d-none');
    document.getElementById('loading-overlay').classList.add('d-flex');
}

function hideLoading() {
    document.getElementById('loading-overlay').classList.add('d-none');
    document.getElementById('loading-overlay').classList.remove('d-flex');
}
</script>
@endpush