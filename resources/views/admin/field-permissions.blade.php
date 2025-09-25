@extends('layouts.app')

@section('title', 'Field Permissions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Field Permissions Management</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    @foreach($roles as $role)
                                        <th class="text-center">{{ $role->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tables as $tableName => $fields)
                                    <tr>
                                        <td colspan="{{ count($roles) + 1 }}" class="table-active">
                                            <strong>{{ ucfirst($tableName) }} Table</strong>
                                        </td>
                                    </tr>
                                    @foreach($fields as $field)
                                        <tr>
                                            <td>{{ $tableName }}.{{ $field }}</td>
                                            @foreach($roles as $role)
                                                <td class="text-center">
                                                    @php
                                                        $permission = $existingPermissions[$role->id][$tableName] ?? collect();
                                                        $fieldPerm = $permission->where('field_name', $field)->first();
                                                        $canRead = $fieldPerm ? $fieldPerm->can_read : false;
                                                        $canWrite = $fieldPerm ? $fieldPerm->can_write : false;
                                                    @endphp
                                                    
                                                    <div class="permission-controls">
                                                        <div class="form-check form-check-inline">
                                                            <i class="fas {{ $canRead ? 'fa-toggle-on text-success' : 'fa-toggle-off text-secondary' }} permission-toggle" 
                                                               data-role="{{ $role->id }}" 
                                                               data-table="{{ $tableName }}" 
                                                               data-field="{{ $field }}" 
                                                               data-type="read"
                                                               data-status="{{ $canRead ? '1' : '0' }}"
                                                               style="cursor: pointer; font-size: 1.2em;"
                                                               title="Read Permission"></i>
                                                            <small class="text-muted">R</small>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <i class="fas {{ $canWrite ? 'fa-toggle-on text-success' : 'fa-toggle-off text-secondary' }} permission-toggle" 
                                                               data-role="{{ $role->id }}" 
                                                               data-table="{{ $tableName }}" 
                                                               data-field="{{ $field }}" 
                                                               data-type="write"
                                                               data-status="{{ $canWrite ? '1' : '0' }}"
                                                               style="cursor: pointer; font-size: 1.2em;"
                                                               title="Write Permission"></i>
                                                            <small class="text-muted">W</small>
                                                        </div>
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.permission-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const roleId = this.dataset.role;
            const tableName = this.dataset.table;
            const fieldName = this.dataset.field;
            const type = this.dataset.type;
            const currentStatus = this.dataset.status;
            const newStatus = currentStatus === '1' ? '0' : '1';
            
            // Update UI immediately
            if (newStatus === '1') {
                this.classList.remove('fa-toggle-off', 'text-secondary');
                this.classList.add('fa-toggle-on', 'text-success');
            } else {
                this.classList.remove('fa-toggle-on', 'text-success');
                this.classList.add('fa-toggle-off', 'text-secondary');
            }
            this.dataset.status = newStatus;
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('role_id', roleId);
            formData.append('table_name', tableName);
            formData.append('field_name', fieldName);
            
            if (type === 'read') {
                formData.append('can_read', newStatus);
                // Keep current write status
                const writeToggle = document.querySelector(`[data-role="${roleId}"][data-table="${tableName}"][data-field="${fieldName}"][data-type="write"]`);
                formData.append('can_write', writeToggle ? writeToggle.dataset.status : '0');
            } else {
                formData.append('can_write', newStatus);
                // Keep current read status
                const readToggle = document.querySelector(`[data-role="${roleId}"][data-table="${tableName}"][data-field="${fieldName}"][data-type="read"]`);
                formData.append('can_read', readToggle ? readToggle.dataset.status : '0');
            }
            
            fetch('/admin/field-permissions', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    console.log('Permission updated successfully');
                } else {
                    console.error('Failed to update permission');
                    // Revert UI changes
                    if (newStatus === '1') {
                        this.classList.remove('fa-toggle-on', 'text-success');
                        this.classList.add('fa-toggle-off', 'text-secondary');
                    } else {
                        this.classList.remove('fa-toggle-off', 'text-secondary');
                        this.classList.add('fa-toggle-on', 'text-success');
                    }
                    this.dataset.status = currentStatus;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                console.log('Trying alternative method...');
                
                // Alternative: Use traditional form submission for DevTools compatibility
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/field-permissions';
                form.style.display = 'none';
                
                const inputs = [
                    { name: '_token', value: document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                    { name: 'role_id', value: roleId },
                    { name: 'table_name', value: tableName },
                    { name: 'field_name', value: fieldName }
                ];
                
                if (type === 'read') {
                    inputs.push({ name: 'can_read', value: newStatus });
                    const writeToggle = document.querySelector(`[data-role="${roleId}"][data-table="${tableName}"][data-field="${fieldName}"][data-type="write"]`);
                    inputs.push({ name: 'can_write', value: writeToggle ? writeToggle.dataset.status : '0' });
                } else {
                    inputs.push({ name: 'can_write', value: newStatus });
                    const readToggle = document.querySelector(`[data-role="${roleId}"][data-table="${tableName}"][data-field="${fieldName}"][data-type="read"]`);
                    inputs.push({ name: 'can_read', value: readToggle ? readToggle.dataset.status : '0' });
                }
                
                inputs.forEach(input => {
                    const inputElement = document.createElement('input');
                    inputElement.type = 'hidden';
                    inputElement.name = input.name;
                    inputElement.value = input.value;
                    form.appendChild(inputElement);
                });
                
                document.body.appendChild(form);
                form.submit();
            });
        });
    });
});
</script>
@endsection