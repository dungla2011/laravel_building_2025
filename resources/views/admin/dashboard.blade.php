@extends('layouts.app')

@section('title', 'Admin Dashboard - LAD-GALAXY-2025')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
            <p class="text-muted">Manage field-level permissions and system settings</p>
        </div>
    </div>
    
    <div class="row admin-stats">
        <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ $totalRoles ?? 3 }}</h4>
                        <p>Total Roles</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ $totalTables ?? 2 }}</h4>
                        <p>Tables with Permissions</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-table fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ $totalPermissions ?? 33 }}</h4>
                        <p>Total Permissions</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-lock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ $activeUsers ?? '0' }}</h4>
                        <p>Active Users</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-cogs"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Field Permissions</h5>
                                <p class="card-text">Manage read/write permissions for each role on table fields.</p>
                                <a href="{{ route('admin.field-permissions.index') }}" class="btn btn-primary">
                                    <i class="fas fa-cog"></i> Manage Permissions
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-sync fa-3x text-success mb-3"></i>
                                <h5 class="card-title">Refresh System</h5>
                                <p class="card-text">Refresh all permissions and clear caches for optimal performance.</p>
                                <button class="btn btn-success" onclick="refreshPermissions()">
                                    <i class="fas fa-sync"></i> Refresh All
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-download fa-3x text-warning mb-3"></i>
                                <h5 class="card-title">Export Data</h5>
                                <p class="card-text">Export permissions configuration and system settings.</p>
                                <button class="btn btn-warning" onclick="exportPermissions()">
                                    <i class="fas fa-download"></i> Export Config
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Permissions by Role</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Read Permissions</th>
                                <th>Write Permissions</th>
                                <th>Total Fields</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($roleStats))
                                @foreach($roleStats as $stat)
                                <tr>
                                    <td><strong>{{ $stat['role_name'] }}</strong></td>
                                    <td>
                                        <span class="badge bg-success">{{ $stat['read_count'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $stat['write_count'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $stat['total_fields'] }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td><strong>admin</strong></td>
                                    <td><span class="badge bg-success">11</span></td>
                                    <td><span class="badge bg-primary">8</span></td>
                                    <td><span class="badge bg-secondary">11</span></td>
                                </tr>
                                <tr>
                                    <td><strong>editor</strong></td>
                                    <td><span class="badge bg-success">10</span></td>
                                    <td><span class="badge bg-primary">2</span></td>
                                    <td><span class="badge bg-secondary">11</span></td>
                                </tr>
                                <tr>
                                    <td><strong>viewer</strong></td>
                                    <td><span class="badge bg-success">7</span></td>
                                    <td><span class="badge bg-primary">0</span></td>
                                    <td><span class="badge bg-secondary">11</span></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-table"></i> Tables Overview</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Fields</th>
                                <th>Permissions</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($tableStats))
                                @foreach($tableStats as $stat)
                                <tr>
                                    <td><strong>{{ $stat['table_name'] }}</strong></td>
                                    <td>
                                        <span class="badge bg-info">{{ $stat['field_count'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ $stat['permission_count'] }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.field-permissions.index', ['table' => $stat['table_name']]) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td><strong>users</strong></td>
                                    <td><span class="badge bg-info">6</span></td>
                                    <td><span class="badge bg-warning">18</span></td>
                                    <td>
                                        <a href="{{ route('admin.field-permissions.index', ['table' => 'users']) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>roles</strong></td>
                                    <td><span class="badge bg-info">5</span></td>
                                    <td><span class="badge bg-warning">15</span></td>
                                    <td>
                                        <a href="{{ route('admin.field-permissions.index', ['table' => 'roles']) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function refreshPermissions() {
    if (confirm('Are you sure you want to refresh all permissions? This may take a moment.')) {
        // For now, just reload the page
        location.reload();
    }
}

function exportPermissions() {
    alert('Export functionality will be implemented soon!');
}
</script>
@endpush