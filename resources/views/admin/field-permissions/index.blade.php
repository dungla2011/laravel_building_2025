@extends('layouts.app')

@section('title', 'Field Permissions Management - LAD-GALAXY-2025')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h1><i class="fas fa-shield-alt me-2"></i>Field Permissions Management</h1>
            <p class="text-muted">Configure read/write permissions for each role on individual table fields</p>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="fas fa-shield-alt"></i> Field-Level Permissions Matrix</h5>
                    <p class="text-muted mb-0">Manage read/write permissions for each role on individual table fields</p>
                </div>
                <div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-filter"></i> Filter by Table
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.field-permissions.index') }}">All Tables</a></li>
                            @foreach($tables as $table)
                                <li><a class="dropdown-item" href="{{ route('admin.field-permissions.index', ['table' => $table]) }}">{{ ucfirst($table) }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <button class="btn btn-success ms-2" onclick="saveAllChanges()">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($permissionMatrix as $tableName => $tableData)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-table"></i> {{ ucfirst($tableName) }} Table</h6>
            </div>
            <div class="card-body">
                <div class="permission-matrix">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 150px;">Role / Field</th>
                                    @foreach($tableData['fields'] as $field)
                                        <th class="text-center field-header">
                                            <div class="field-name">{{ $field }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                <tr>
                                    <td class="fw-bold bg-light">
                                        <i class="fas fa-user-tag"></i> {{ ucfirst($role->name) }}
                                        <br><small class="text-muted">Role ID: {{ $role->id }}</small>
                                    </td>
                                    @foreach($tableData['fields'] as $field)
                                        @php
                                            $permission = $tableData['permissions'][$role->id][$field] ?? null;
                                            $canRead = $permission ? $permission->can_read : false;
                                            $canWrite = $permission ? $permission->can_write : false;
                                            $permissionId = $permission ? $permission->id : null;
                                        @endphp
                                        <td class="text-center p-2">
                                            <div class="permission-cell">
                                                <!-- Read Permission -->
                                                <div class="permission-row">
                                                    <div class="permission-label">
                                                        <i class="fas fa-eye text-success"></i> Read
                                                    </div>
                                                    <button class="permission-toggle read {{ $canRead ? 'active' : 'inactive' }}"
                                                            data-permission-id="{{ $permissionId }}"
                                                            data-role-id="{{ $role->id }}"
                                                            data-table="{{ $tableName }}"
                                                            data-field="{{ $field }}"
                                                            data-type="read"
                                                            data-current="{{ $canRead ? 'true' : 'false' }}"
                                                            onclick="togglePermission(this)">
                                                        <i class="fas {{ $canRead ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                    </button>
                                                </div>
                                                <!-- Write Permission -->
                                                <div class="permission-row">
                                                    <div class="permission-label">
                                                        <i class="fas fa-edit text-primary"></i> Write
                                                    </div>
                                                    <button class="permission-toggle write {{ $canWrite ? 'active' : 'inactive' }}"
                                                            data-permission-id="{{ $permissionId }}"
                                                            data-role-id="{{ $role->id }}"
                                                            data-table="{{ $tableName }}"
                                                            data-field="{{ $field }}"
                                                            data-type="write"
                                                            data-current="{{ $canWrite ? 'true' : 'false' }}"
                                                            onclick="togglePermission(this)">
                                                        <i class="fas {{ $canWrite ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                    </button>
                                                </div>
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
        </div>
    </div>
</div>
@endforeach

<!-- Legend -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h6><i class="fas fa-info-circle"></i> Legend</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="permission-row">
                            <div class="permission-label">
                                <i class="fas fa-eye text-success"></i> Read Permission
                            </div>
                            <div class="d-flex align-items-center">
                                <button class="permission-toggle read active me-2">
                                    <i class="fas fa-toggle-on"></i>
                                </button>
                                <span class="text-success small">ON</span>
                                <span class="mx-2">/</span>
                                <button class="permission-toggle read inactive me-2">
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                                <span class="text-danger small">OFF</span>
                            </div>
                        </div>
                        <small class="text-muted">User can view this field</small>
                    </div>
                    <div class="col-md-6">
                        <div class="permission-row">
                            <div class="permission-label">
                                <i class="fas fa-edit text-primary"></i> Write Permission
                            </div>
                            <div class="d-flex align-items-center">
                                <button class="permission-toggle write active me-2">
                                    <i class="fas fa-toggle-on"></i>
                                </button>
                                <span class="text-success small">ON</span>
                                <span class="mx-2">/</span>
                                <button class="permission-toggle write inactive me-2">
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                                <span class="text-danger small">OFF</span>
                            </div>
                        </div>
                        <small class="text-muted">User can modify this field</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
let pendingChanges = [];

function togglePermission(button) {
    console.log('Button clicked:', button);
    const permissionId = $(button).data('permission-id');
    const roleId = parseInt($(button).data('role-id'));
    const table = $(button).data('table');
    const field = $(button).data('field');
    const type = $(button).data('type'); // 'read' or 'write'
    const current = $(button).data('current') === 'true' || $(button).data('current') === true;
    const newValue = !current;

    // Update button appearance immediately
    $(button).data('current', newValue.toString());
    
    if (newValue) {
        $(button).removeClass('inactive').addClass('active');
        $(button).find('i').removeClass('fa-toggle-off').addClass('fa-toggle-on');
    } else {
        $(button).removeClass('active').addClass('inactive');
        $(button).find('i').removeClass('fa-toggle-on').addClass('fa-toggle-off');
    }

    // Track the change
    const changeIndex = pendingChanges.findIndex(change => 
        change.roleId === roleId && 
        change.table === table && 
        change.field === field &&
        change.type === type
    );

    if (changeIndex >= 0) {
        pendingChanges[changeIndex].value = newValue;
    } else {
        pendingChanges.push({
            permissionId: permissionId,
            roleId: roleId,
            table: table,
            field: field,
            type: type,
            value: newValue
        });
    }

    // Update save button to show pending changes
    updateSaveButton();
    
    // Save immediately
    savePermissionNow(permissionId, roleId, table, field, type, newValue);
}

function updateSaveButton() {
    const saveButton = document.querySelector('[onclick="saveAllChanges()"]');
    if (pendingChanges.length > 0) {
        saveButton.innerHTML = `<i class="fas fa-save"></i> Save Changes (${pendingChanges.length})`;
        saveButton.classList.remove('btn-success');
        saveButton.classList.add('btn-warning');
    } else {
        saveButton.innerHTML = '<i class="fas fa-save"></i> Save All Changes';
        saveButton.classList.remove('btn-warning');
        saveButton.classList.add('btn-success');
    }
}

function savePermissionNow(permissionId, roleId, table, field, type, value) {
    const url = permissionId 
        ? `{{ route('admin.field-permissions.update', ':id') }}`.replace(':id', permissionId)
        : `{{ route('admin.field-permissions.store') }}`;
    
    const method = permissionId ? 'PUT' : 'POST';
    
    // Get current values from both buttons for this role/table/field
    const readButton = $(`[data-role-id="${roleId}"][data-table="${table}"][data-field="${field}"][data-type="read"]`);
    const writeButton = $(`[data-role-id="${roleId}"][data-table="${table}"][data-field="${field}"][data-type="write"]`);
    
    const currentReadValue = readButton.data('current') === 'true' || readButton.data('current') === true;
    const currentWriteValue = writeButton.data('current') === 'true' || writeButton.data('current') === true;
    
    const data = {
        role_id: parseInt(roleId),
        table_name: table,
        field_name: field,
        can_read: type === 'read' ? Boolean(value) : Boolean(currentReadValue),
        can_write: type === 'write' ? Boolean(value) : Boolean(currentWriteValue),
        _method: method,
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    $.ajax({
        url: url,
        method: 'POST',
        data: data,
        success: function(response) {
            console.log('Permission saved successfully');
            // Remove from pending changes if it exists
            const changeIndex = pendingChanges.findIndex(change => 
                change.roleId === roleId && 
                change.table === table && 
                change.field === field &&
                change.type === type
            );
            if (changeIndex >= 0) {
                pendingChanges.splice(changeIndex, 1);
                updateSaveButton();
            }
        },
        error: function(error) {
            console.error('Error saving permission:', error);
            alert('Error saving permission. Please try again.');
            // Revert the button state
            const button = $(`[data-role-id="${roleId}"][data-table="${table}"][data-field="${field}"][data-type="${type}"]`);
            const currentValue = button.data('current') === 'true';
            const revertValue = !currentValue;
            
            button.data('current', revertValue.toString());
            if (revertValue) {
                button.removeClass('inactive').addClass('active');
                button.find('i').removeClass('fa-toggle-off').addClass('fa-toggle-on');
            } else {
                button.removeClass('active').addClass('inactive');
                button.find('i').removeClass('fa-toggle-on').addClass('fa-toggle-off');
            }
        }
    });
}

function saveAllChanges() {
    if (pendingChanges.length === 0) {
        alert('No changes to save.');
        return;
    }

    const saveButton = document.querySelector('[onclick="saveAllChanges()"]');
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveButton.disabled = true;

    // Group changes by permission record
    const groupedChanges = {};
    
    pendingChanges.forEach(change => {
        const key = `${change.roleId}-${change.table}-${change.field}`;
        if (!groupedChanges[key]) {
            groupedChanges[key] = {
                permissionId: change.permissionId,
                roleId: change.roleId,
                table: change.table,
                field: change.field,
                canRead: null,
                canWrite: null
            };
        }
        
        if (change.type === 'read') {
            groupedChanges[key].canRead = change.value;
        } else {
            groupedChanges[key].canWrite = change.value;
        }
    });

    // Send AJAX requests
    const requests = Object.values(groupedChanges).map(change => {
        const url = change.permissionId 
            ? `{{ route('admin.field-permissions.update', ':id') }}`.replace(':id', change.permissionId)
            : `{{ route('admin.field-permissions.store') }}`;
        
        const method = change.permissionId ? 'PUT' : 'POST';
        
        const data = {
            role_id: change.roleId,
            table_name: change.table,
            field_name: change.field,
            can_read: change.canRead !== null ? change.canRead : undefined,
            can_write: change.canWrite !== null ? change.canWrite : undefined,
            _method: method,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        return $.ajax({
            url: url,
            method: 'POST',
            data: data
        });
    });

    Promise.all(requests)
        .then(results => {
            pendingChanges = [];
            updateSaveButton();
            saveButton.disabled = false;
            alert('All changes saved successfully!');
        })
        .catch(error => {
            console.error('Error saving changes:', error);
            saveButton.innerHTML = '<i class="fas fa-save"></i> Save All Changes';
            saveButton.disabled = false;
            alert('Error saving changes. Please try again.');
        });
}
</script>
@endpush