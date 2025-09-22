@extends('admin.layout')

@section('title', 'User Management - Admin Panel')

@section('content')
<div class="row">
    <!-- Page Header -->
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 text-dark fw-bold">
                    <i class="fas fa-users me-2 text-primary"></i>
                    User Management
                </h1>
                <p class="text-muted mb-0">Manage users using Laravel Orion API</p>
            </div>
            <button class="btn btn-primary btn-lg" onclick="openCreateModal()">
                <i class="fas fa-plus me-2"></i>
                Add New User
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-number" id="totalUsers">0</div>
                    <div class="small">Total Users</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-number" id="verifiedUsers">0</div>
                    <div class="small">Verified Users</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-number" id="newUsers">0</div>
                    <div class="small">New This Month</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
            <div class="d-flex align-items-center">
                <div class="stats-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="ms-3">
                    <div class="stats-number">100%</div>
                    <div class="small">API Health</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="col-12">
        <div class="search-box">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Search Users</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by name or email...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Search Field</label>
                    <select id="searchField" class="form-select">
                        <option value="name">Name</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sort By</label>
                    <select id="sortField" class="form-select">
                        <option value="id">ID</option>
                        <option value="name">Name</option>
                        <option value="email">Email</option>
                        <option value="created_at">Created Date</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Actions</label>
                    <div class="d-grid">
                        <button class="btn btn-outline-primary" onclick="searchUsers()">
                            <i class="fas fa-search me-1"></i>
                            Search
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Batch Actions -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-success btn-sm" onclick="selectAll()">
                            <i class="fas fa-check-square me-1"></i>
                            Select All
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                            <i class="fas fa-square me-1"></i>
                            Clear Selection
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="batchDelete()" id="batchDeleteBtn" disabled>
                            <i class="fas fa-trash me-1"></i>
                            Delete Selected
                        </button>
                        <span class="text-muted small align-self-center ms-2" id="selectedCount">0 users selected</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fas fa-table me-2"></i>
                    Users List
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAllCheckbox" class="form-check-input" onchange="toggleSelectAll()">
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Email Verified</th>
                                <th>Created At</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="mt-2">Loading users...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small" id="paginationInfo">
                        Showing 0 to 0 of 0 results
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="pagination">
                            <!-- Pagination will be generated here -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fas fa-user-plus me-2"></i>
                    Add New User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" onsubmit="saveUser(event)">
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="userName" class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control" id="userName" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="userEmail" class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control" id="userEmail" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="userPassword" class="form-label fw-semibold">Password</label>
                            <input type="password" class="form-control" id="userPassword" name="password">
                            <div class="form-text">Leave blank to keep current password (when editing)</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="userPasswordConfirm" class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" class="form-control" id="userPasswordConfirm" name="password_confirmation">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="fas fa-save me-1"></i>
                        <span class="btn-text">Save User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    This action cannot be undone.
                </div>
                <div id="deleteUserInfo"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>
                    Delete User
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Global variables
    let currentPage = 1;
    let totalPages = 1;
    let selectedUsers = new Set();
    let editingUserId = null;

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        loadUsers();
        
        // Setup search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchUsers();
            }
        });
    });

    // Load users from API
    async function loadUsers(page = 1, search = null) {
        try {
            let url = `${API_BASE_URL}/users?page=${page}`;
            
            // Add search parameters
            if (search) {
                const searchField = document.getElementById('searchField').value;
                url += `&search=${encodeURIComponent(JSON.stringify({
                    filters: [
                        {
                            field: searchField,
                            operator: 'like',
                            value: `%${search}%`
                        }
                    ]
                }))}`;
            }
            
            // Add sorting
            const sortField = document.getElementById('sortField').value;
            url += `&sort=${sortField}`;
            
            const response = await axios.get(url);
            displayUsers(response.data);
            updateStats(response.data);
            
        } catch (error) {
            handleApiError(error);
        }
    }

    // Display users in table
    function displayUsers(data) {
        const tbody = document.getElementById('usersTableBody');
        const users = data.data || [];
        
        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <div class="h5 text-muted">No users found</div>
                        <div class="text-muted">Try adjusting your search criteria</div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>
                    <input type="checkbox" class="form-check-input user-checkbox" 
                           value="${user.id}" onchange="updateSelection()">
                </td>
                <td><span class="badge bg-primary">${user.id}</span></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">${user.name}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-muted small">${user.email}</div>
                </td>
                <td>
                    ${user.email_verified_at 
                        ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Verified</span>'
                        : '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pending</span>'
                    }
                </td>
                <td>
                    <div class="text-muted small">${formatDate(user.created_at)}</div>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="editUser(${user.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="viewUser(${user.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Update pagination
        updatePagination(data);
        updatePaginationInfo(data);
        
        // Clear selections
        selectedUsers.clear();
        updateSelectionUI();
    }

    // Update pagination
    function updatePagination(data) {
        const pagination = document.getElementById('pagination');
        const meta = data.meta || {};
        
        currentPage = meta.current_page || 1;
        totalPages = meta.last_page || 1;
        
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let paginationHTML = '';
        
        // Previous button
        if (currentPage > 1) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadUsers(${currentPage - 1})">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
        }
        
        // Page numbers
        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            paginationHTML += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadUsers(${i})">${i}</a>
                </li>
            `;
        }
        
        // Next button  
        if (currentPage < totalPages) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadUsers(${currentPage + 1})">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
        }
        
        pagination.innerHTML = paginationHTML;
    }

    // Update pagination info
    function updatePaginationInfo(data) {
        const info = document.getElementById('paginationInfo');
        const meta = data.meta || {};
        
        const from = meta.from || 0;
        const to = meta.to || 0;
        const total = meta.total || 0;
        
        info.textContent = `Showing ${from} to ${to} of ${total} results`;
    }

    // Update stats
    function updateStats(data) {
        const meta = data.meta || {};
        const total = meta.total || 0;
        
        document.getElementById('totalUsers').textContent = total;
        
        // For demo purposes, we'll calculate some basic stats
        const users = data.data || [];
        const verified = users.filter(u => u.email_verified_at).length;
        const newThisMonth = users.filter(u => {
            const created = new Date(u.created_at);
            const now = new Date();
            return created.getMonth() === now.getMonth() && created.getFullYear() === now.getFullYear();
        }).length;
        
        document.getElementById('verifiedUsers').textContent = verified;
        document.getElementById('newUsers').textContent = newThisMonth;
    }

    // Search users
    function searchUsers() {
        const searchTerm = document.getElementById('searchInput').value.trim();
        currentPage = 1;
        loadUsers(1, searchTerm || null);
    }

    // Open create modal
    function openCreateModal() {
        editingUserId = null;
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Add New User';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.querySelector('#saveBtn .btn-text').textContent = 'Save User';
        
        // Make password required for new users
        document.getElementById('userPassword').required = true;
        
        // Clear any validation errors
        clearValidationErrors();
        
        new bootstrap.Modal(document.getElementById('userModal')).show();
    }

    // Edit user
    async function editUser(id) {
        try {
            showLoading();
            
            const response = await axios.get(`${API_BASE_URL}/users/${id}`);
            const user = response.data.data;
            
            editingUserId = id;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPassword').value = '';
            document.getElementById('userPasswordConfirm').value = '';
            document.querySelector('#saveBtn .btn-text').textContent = 'Update User';
            
            // Make password optional for editing
            document.getElementById('userPassword').required = false;
            
            // Clear any validation errors
            clearValidationErrors();
            
            new bootstrap.Modal(document.getElementById('userModal')).show();
            
        } catch (error) {
            handleApiError(error);
        } finally {
            hideLoading();
        }
    }

    // View user (for demo, we'll just show an alert)
    async function viewUser(id) {
        try {
            showLoading();
            
            const response = await axios.get(`${API_BASE_URL}/users/${id}`);
            const user = response.data.data;
            
            showToast(`Viewing user: ${user.name} (${user.email})`, 'info');
            
        } catch (error) {
            handleApiError(error);
        } finally {
            hideLoading();
        }
    }

    // Save user (create or update)
    async function saveUser(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const userData = Object.fromEntries(formData);
        
        // Remove empty password fields for updates
        if (!userData.password && editingUserId) {
            delete userData.password;
            delete userData.password_confirmation;
        }
        
        try {
            showLoading();
            clearValidationErrors();
            
            let response;
            if (editingUserId) {
                // Update existing user
                response = await axios.patch(`${API_BASE_URL}/users/${editingUserId}`, userData);
                showToast('User updated successfully!', 'success');
            } else {
                // Create new user
                response = await axios.post(`${API_BASE_URL}/users`, userData);
                showToast('User created successfully!', 'success');
            }
            
            // Close modal and refresh list
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            loadUsers(currentPage);
            
        } catch (error) {
            if (error.response && error.response.status === 422) {
                // Handle validation errors
                const errors = error.response.data.errors || {};
                displayValidationErrors(errors);
            } else {
                handleApiError(error);
            }
        } finally {
            hideLoading();
        }
    }

    // Delete user
    function deleteUser(id) {
        // Find user data for confirmation
        const checkboxes = document.querySelectorAll('.user-checkbox');
        let userName = 'this user';
        
        checkboxes.forEach(checkbox => {
            if (checkbox.value == id) {
                const row = checkbox.closest('tr');
                const nameCell = row.querySelector('td:nth-child(3) .fw-semibold');
                if (nameCell) {
                    userName = nameCell.textContent;
                }
            }
        });
        
        document.getElementById('deleteUserInfo').innerHTML = `
            <div class="alert alert-info">
                <strong>User:</strong> ${userName}
            </div>
        `;
        
        // Store the ID for confirmation
        document.getElementById('confirmDeleteBtn').setAttribute('data-user-id', id);
        
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    // Confirm delete
    async function confirmDelete() {
        const id = document.getElementById('confirmDeleteBtn').getAttribute('data-user-id');
        
        try {
            showLoading();
            
            await axios.delete(`${API_BASE_URL}/users/${id}`);
            showToast('User deleted successfully!', 'success');
            
            // Close modal and refresh list
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            loadUsers(currentPage);
            
        } catch (error) {
            handleApiError(error);
        } finally {
            hideLoading();
        }
    }

    // Selection management
    function updateSelection() {
        selectedUsers.clear();
        document.querySelectorAll('.user-checkbox:checked').forEach(checkbox => {
            selectedUsers.add(parseInt(checkbox.value));
        });
        updateSelectionUI();
    }

    function updateSelectionUI() {
        const count = selectedUsers.size;
        document.getElementById('selectedCount').textContent = `${count} user${count !== 1 ? 's' : ''} selected`;
        document.getElementById('batchDeleteBtn').disabled = count === 0;
        
        // Update select all checkbox
        const checkboxes = document.querySelectorAll('.user-checkbox');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        
        if (checkboxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (selectedUsers.size === checkboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else if (selectedUsers.size > 0) {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        } else {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        }
    }

    function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const checkboxes = document.querySelectorAll('.user-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        
        updateSelection();
    }

    function selectAll() {
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateSelection();
    }

    function clearSelection() {
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('selectAllCheckbox').checked = false;
        updateSelection();
    }

    // Batch delete
    async function batchDelete() {
        if (selectedUsers.size === 0) return;
        
        const count = selectedUsers.size;
        const result = confirm(`Are you sure you want to delete ${count} user${count !== 1 ? 's' : ''}? This action cannot be undone.`);
        
        if (!result) return;
        
        try {
            showLoading();
            
            // Laravel Orion batch delete expects resources keyed by ID
            const resources = {};
            selectedUsers.forEach(id => {
                resources[id] = {}; // Empty object for each ID
            });
            
            await axios.delete(`${API_BASE_URL}/users/batch`, {
                data: { resources }
            });
            
            showToast(`${count} user${count !== 1 ? 's' : ''} deleted successfully!`, 'success');
            loadUsers(currentPage);
            clearSelection();
            
        } catch (error) {
            handleApiError(error);
        } finally {
            hideLoading();
        }
    }

    // Validation helpers
    function displayValidationErrors(errors) {
        Object.keys(errors).forEach(field => {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = errors[field][0];
                }
            }
        });
    }

    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(input => {
            input.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(feedback => {
            feedback.textContent = '';
        });
    }
</script>
@endpush