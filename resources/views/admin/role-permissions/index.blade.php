@extends('layouts.app')

@section('title', 'Role Permissions Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/toast-system.min.css') }}">
<style>
.permission-switch {
    cursor: pointer;
    transform: scale(1.2);
}

.permission-switch:checked {
    background-color: #198754;
    border-color: #198754;
}

.table th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
}

.table td {
    vertical-align: middle;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid #dee2e6;
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

.resource-header {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    color: white;
}

.loading-overlay {
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(2px);
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.table-responsive {
    /* max-height: 400px; */
    overflow-y: auto;
}

.sticky-header {
    position: sticky;
    top: 0;
    z-index: 999;
    background: white;
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
                        <i class="fas fa-users-cog me-2 text-primary"></i>
                        Role Permissions Management
                    </h1>
                    <p class="text-muted mb-0">Manage API access permissions for each role in your system</p>
                </div>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="syncPermissions()">
                        <i class="fas fa-sync-alt me-1"></i>
                        Sync Routes
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="exportPermissions()">
                        <i class="fas fa-download me-1"></i>
                        Export JSON
                    </button>
                </div>
            </div>

            <!-- Roles Overview -->
            <div class="card mb-4">
                <div class="card-header resource-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
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
                                        <div class="btn-group btn-group-sm w-100" role="group">
                                            <button type="button" class="btn btn-outline-success" onclick="grantAllForRole({{ $role->id }}, '{{ $role->display_name }}')">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Grant All
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" onclick="revokeAllForRole({{ $role->id }}, '{{ $role->display_name }}')">
                                                <i class="fas fa-times-circle me-1"></i>
                                                Revoke All
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No roles found. Please create roles first.
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Permissions Matrix by Resource -->
            @forelse($permissionGroups as $group)
            <div class="card mb-4">
                <div class="card-header resource-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-{{ $group['resource'] === 'users' ? 'users' : ($group['resource'] === 'products' ? 'box' : 'folder') }} me-2"></i>
                            {{ $group['display_name'] }} API Permissions
                            <small class="text-light">({{ count($group['permissions']) }} endpoints)</small>
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-success" onclick="grantAllForResource('{{ $group['resource'] }}', '{{ $group['display_name'] }}')">
                                <i class="fas fa-check-circle me-1"></i>
                                Grant All
                            </button>
                            <button type="button" class="btn btn-danger" onclick="revokeAllForResource('{{ $group['resource'] }}', '{{ $group['display_name'] }}')">
                                <i class="fas fa-times-circle me-1"></i>
                                Revoke All
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-dark sticky-header">
                                <tr>
                                    <th width="200">API Endpoint</th>
                                    <th width="80">Method</th>
                                    <th width="150">Action</th>
                                    <th width="200">Description</th>
                                    @foreach($roles as $role)
                                    <th width="100" class="text-center">
                                        {{ $role->display_name }}
                                        <br>
                                        <small class="text-muted">{{ $role->name }}</small>
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group['permissions'] as $permission)
                                <tr>
                                    <td>
                                        <code class="small">{{ $permission->uri }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ 
                                            $permission->method === 'GET' ? 'primary' : 
                                            ($permission->method === 'POST' ? 'success' : 
                                            ($permission->method === 'PUT' || $permission->method === 'PATCH' ? 'warning' : 'danger'))
                                        }}">
                                            {{ $permission->method }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $permission->display_name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $permission->name }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($permission->description, 60) }}</small>
                                    </td>
                                    @foreach($roles as $role)
                                    <td class="text-center">
                                        @php
                                            $hasPermission = isset($rolePermissions[$role->id][$permission->id]);
                                        @endphp
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input 
                                                class="form-check-input permission-switch" 
                                                type="checkbox" 
                                                id="permission_{{ $role->id }}_{{ $permission->id }}"
                                                data-role-id="{{ $role->id }}"
                                                data-permission-id="{{ $permission->id }}"
                                                data-role-name="{{ $role->display_name }}"
                                                data-permission-name="{{ $permission->display_name }}"
                                                {{ $hasPermission ? 'checked' : '' }}
                                            >
                                        </div>
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @empty
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                    <h5>No API Permissions Found</h5>
                    <p class="text-muted">Click "Sync Routes" to discover and import API routes from your Laravel application.</p>
                    <button type="button" class="btn btn-primary" onclick="syncPermissions()">
                        <i class="fas fa-sync-alt me-1"></i>
                        Sync Routes Now
                    </button>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="d-none position-fixed top-0 start-0 w-100 h-100 loading-overlay" style="z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="text-center text-white">
            <div class="spinner-border mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div id="loading-message">Updating permissions...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/toast-system.js') }}"></script>
<script>
// CSRF token setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Individual permission toggle
$(document).on('change', '.permission-switch', function() {
    const $switch = $(this);
    const roleId = $switch.data('role-id');
    const permissionId = $switch.data('permission-id');
    const roleName = $switch.data('role-name');
    const permissionName = $switch.data('permission-name');
    const granted = $switch.is(':checked');
    
    showLoading('Updating permission...');
    
    $.ajax({
        url: '{{ route("admin.role-permissions.update") }}',
        method: 'POST',
        data: {
            role_id: roleId,
            permission_id: permissionId,
            granted: granted
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                showToast('success', response.message);
            }
        },
        error: function(xhr) {
            hideLoading();
            $switch.prop('checked', !granted); // Revert switch
            const message = xhr.responseJSON?.message || 'Unknown error occurred';
            showToast('error', 'Failed to update permission: ' + message);
        }
    });
});

// Grant all permissions for a role
function grantAllForRole(roleId, roleName) {
    if (!confirm(`Grant ALL API permissions to role "${roleName}"?`)) return;
    
    const permissionIds = [];
    $(`.permission-switch[data-role-id="${roleId}"]`).each(function() {
        const permissionId = $(this).data('permission-id');
        permissionIds.push(permissionId);
        $(this).prop('checked', true);
    });
    
    bulkUpdateRole(roleId, roleName, permissionIds, true);
}

// Revoke all permissions for a role
function revokeAllForRole(roleId, roleName) {
    if (!confirm(`Revoke ALL API permissions from role "${roleName}"?`)) return;
    
    const permissionIds = [];
    $(`.permission-switch[data-role-id="${roleId}"]`).each(function() {
        const permissionId = $(this).data('permission-id');
        permissionIds.push(permissionId);
        $(this).prop('checked', false);
    });
    
    bulkUpdateRole(roleId, roleName, permissionIds, false);
}

// Grant all permissions for a resource
function grantAllForResource(resource, resourceName) {
    if (!confirm(`Grant ALL "${resourceName}" permissions to ALL roles?`)) return;
    
    // Find all switches for this resource and check them
    const $resourceSwitches = $(`.permission-switch`).filter(function() {
        const permissionName = $(this).data('permission-name');
        return permissionName && permissionName.toLowerCase().includes(resource.toLowerCase());
    });
    
    $resourceSwitches.prop('checked', true);
    
    bulkUpdateResource(resource, resourceName, true);
}

// Revoke all permissions for a resource
function revokeAllForResource(resource, resourceName) {
    if (!confirm(`Revoke ALL "${resourceName}" permissions from ALL roles?`)) return;
    
    // Find all switches for this resource and uncheck them
    const $resourceSwitches = $(`.permission-switch`).filter(function() {
        const permissionName = $(this).data('permission-name');
        return permissionName && permissionName.toLowerCase().includes(resource.toLowerCase());
    });
    
    $resourceSwitches.prop('checked', false);
    
    bulkUpdateResource(resource, resourceName, false);
}

// Bulk update role permissions
function bulkUpdateRole(roleId, roleName, permissionIds, grantAll) {
    showLoading(`${grantAll ? 'Granting' : 'Revoking'} permissions for ${roleName}...`);
    
    $.ajax({
        url: '{{ route("admin.role-permissions.bulk-update-role") }}',
        method: 'POST',
        data: {
            role_id: roleId,
            permission_ids: permissionIds,
            grant_all: grantAll
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                showToast('success', response.message);
            }
        },
        error: function(xhr) {
            hideLoading();
            const message = xhr.responseJSON?.message || 'Unknown error occurred';
            showToast('error', 'Failed to bulk update permissions: ' + message);
            location.reload(); // Reload to reset switches
        }
    });
}

// Bulk update resource permissions
function bulkUpdateResource(resource, resourceName, grantAll) {
    showLoading(`${grantAll ? 'Granting' : 'Revoking'} all ${resourceName} permissions...`);
    
    $.ajax({
        url: '{{ route("admin.role-permissions.bulk-update-resource") }}',
        method: 'POST',
        data: {
            resource: resource,
            grant_all: grantAll
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                showToast('success', response.message);
            }
        },
        error: function(xhr) {
            hideLoading();
            const message = xhr.responseJSON?.message || 'Unknown error occurred';
            showToast('error', 'Failed to bulk update permissions: ' + message);
            location.reload(); // Reload to reset switches
        }
    });
}

// Sync permissions from Laravel routes
function syncPermissions() {
    if (!confirm('Sync latest API routes from Laravel to database? This may take a moment.')) return;
    
    showLoading('Syncing API routes...');
    
    $.ajax({
        url: '{{ route("admin.role-permissions.sync") }}',
        method: 'POST',
        success: function(response) {
            hideLoading();
            if (response.success) {
                showToast('success', response.message);
                if (response.redirect) {
                    setTimeout(() => window.location.href = response.redirect, 1500);
                }
            }
        },
        error: function(xhr) {
            hideLoading();
            const message = xhr.responseJSON?.message || 'Unknown error occurred';
            showToast('error', 'Failed to sync permissions: ' + message);
        }
    });
}

// Export permissions as JSON
function exportPermissions() {
    showLoading('Preparing export...');
    
    $.ajax({
        url: '{{ route("admin.role-permissions.export") }}',
        method: 'GET',
        success: function(response) {
            hideLoading();
            
            // Create download link
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response, null, 2));
            const downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", `role-permissions-${new Date().toISOString().split('T')[0]}.json`);
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();
            
            showToast('success', 'Permissions exported successfully');
        },
        error: function(xhr) {
            hideLoading();
            const message = xhr.responseJSON?.message || 'Unknown error occurred';
            showToast('error', 'Failed to export permissions: ' + message);
        }
    });
}

// Utility functions
function showLoading(message = 'Loading...') {
    $('#loading-message').text(message);
    $('#loading-overlay').removeClass('d-none');
}

function hideLoading() {
    $('#loading-overlay').addClass('d-none');
}

// Toast function now uses the external library
// showToast is provided by toast-system.js

// Auto-refresh every 5 minutes to keep data fresh
setInterval(function() {
    console.log('Auto-refreshing permissions data...');
    location.reload();
}, 300000); // 5 minutes
</script>
@endpush