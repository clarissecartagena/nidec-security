<?php
function getUserStatusBadge($status) {
    return ($status === 'active')
        ? '<span class="badge badge--success">Active</span>'
        : '<span class="badge badge--muted">Inactive</span>';
}
?>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-people-fill me-2 text-primary"></i>User Management</h1>
                <p class="text-sm text-muted-foreground mb-0">Manage system users and their roles</p>
            </div>
            <button onclick="UsersPage.openAddModal()" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Add User
            </button>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'danger' : 'success'; ?> mb-4" role="alert">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-12 col-md-4">
                <div class="metric-card metric-card-split metric-accent-info metric-card--static h-100">
                    <div class="metric-card-left">
                        <div class="metric-card-icon" aria-hidden="true">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="metric-card-text">
                            <p class="text-sm fw-semibold text-foreground">Total Users</p>
                            <p class="text-xs text-muted-foreground">All accounts in the system</p>
                        </div>
                    </div>
                    <div class="metric-card-right">
                        <div class="metric-card-value fs-2 fw-bold text-foreground" id="stat-total-users"><?php echo $totalUsers; ?></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="metric-card metric-card-split metric-accent-success metric-card--static h-100">
                    <div class="metric-card-left">
                        <div class="metric-card-icon" aria-hidden="true">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div class="metric-card-text">
                            <p class="text-sm fw-semibold text-foreground">Active Users</p>
                            <p class="text-xs text-muted-foreground">Currently enabled accounts</p>
                        </div>
                    </div>
                    <div class="metric-card-right">
                        <div class="metric-card-value fs-2 fw-bold text-foreground" id="stat-active-users"><?php echo $activeUsers; ?></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="metric-card metric-card-split metric-accent-warning metric-card--static h-100">
                    <div class="metric-card-left">
                        <div class="metric-card-icon" aria-hidden="true">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="metric-card-text">
                            <p class="text-sm fw-semibold text-foreground">Security Personnel</p>
                            <p class="text-xs text-muted-foreground">Users with security role</p>
                        </div>
                    </div>
                    <div class="metric-card-right">
                        <div class="metric-card-value fs-2 fw-bold text-foreground" id="stat-security-users"><?php echo $securityUsers; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-container table-card" style="--table-accent: var(--info)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">All Users</h3>
                    <p class="text-xs text-muted-foreground">Edit roles, departments, and account status</p>
                </div>
                <div class="text-xs text-muted-foreground">Total: <?php echo (int)count($users); ?></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Emp ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7" class="text-center text-muted-foreground">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="font-medium"><?php echo htmlspecialchars($u['name']); ?></td>
                                <td class="font-mono text-xs text-muted-foreground"><?php echo htmlspecialchars($u['employee_no'] ?? '—'); ?></td>
                                <td class="font-mono text-xs"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(str_replace('_', ' ', $u['role'])); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($u['department_name'] ?? '—'); ?></td>
                                <td><?php echo getUserStatusBadge($u['account_status']); ?></td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <button class="icon-btn" type="button" title="Edit"
                                            onclick="UsersPage.openEditModal(this)"
                                            data-user="<?php echo htmlspecialchars(json_encode([
                                                'id' => (int)$u['id'],
                                                'employee_no' => (string)($u['employee_no'] ?? ''),
                                                'name' => (string)$u['name'],
                                                'username' => (string)$u['username'],
                                                'role' => (string)$u['role'],
                                                'department_id' => (int)($u['department_id'] ?? 0),
                                                'security_type' => (string)($u['security_type'] ?? ''),
                                                'entity' => (string)($u['entity'] ?? ''),
                                                'account_status' => (string)($u['account_status'] ?? 'active')
                                            ]), ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        </button>
                                        <button class="icon-btn danger" type="button" title="Delete"
                                            onclick="UsersPage.openDeleteModal(this)"
                                            data-user="<?php echo htmlspecialchars(json_encode([
                                                'id' => (int)$u['id'],
                                                'name' => (string)$u['name'],
                                                'username' => (string)$u['username'],
                                                'role' => (string)$u['role'],
                                                'department' => (string)($u['department_name'] ?? ''),
                                                'account_status' => (string)($u['account_status'] ?? 'active')
                                            ]), ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                </table>
            </div>
        </div>

        <!-- Add User Modal — two-step: search employee → set credentials/role -->
        <div id="add-user-modal" class="modal-overlay hidden">
            <div class="modal modal--accent">
                <div class="modal-accent-header">
                    <div>
                        <h2 class="modal-accent-title">Add New User</h2>
                        <p class="modal-accent-subtitle">Search the company employee directory first</p>
                    </div>
                    <button type="button" class="modal-accent-close" aria-label="Close" onclick="UsersPage.closeAddModal()">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-accent-body">

                    <!-- ── Step 1: Employee search ─────────────────────────────── -->
                    <div id="add-step-search">
                        <div class="mb-3">
                            <label class="form-label text-sm font-medium text-foreground mb-1" for="emp-search-input">
                                Search Employee
                            </label>
                            <div class="input-group">
                                <input type="text" id="emp-search-input"
                                    class="form-control form-control-sm"
                                    placeholder="Employee ID or Full Name (min 2 characters)…"
                                    autocomplete="off" />
                                <button type="button" class="btn btn-primary btn-sm" id="emp-search-btn">
                                    <i class="bi bi-search me-1" aria-hidden="true"></i>Search
                                </button>
                            </div>
                            <div class="form-text text-xs">
                                Data is fetched from the company employee directory.
                            </div>
                        </div>

                        <div id="emp-search-loader" class="text-center py-3 hidden" aria-live="polite">
                            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                            <span class="ms-2 text-sm text-muted-foreground">Searching…</span>
                        </div>

                        <div id="emp-search-alert" class="alert alert-danger text-sm py-2 mb-0 hidden" role="alert"></div>

                        <div id="emp-search-results" class="hidden">
                            <p class="text-xs text-muted-foreground mb-2">Select an employee to continue:</p>
                            <div id="emp-results-list" class="d-flex flex-column gap-2"></div>
                        </div>
                    </div>

                    <!-- ── Step 2: Confirm employee + set credentials / role ───── -->
                    <div id="add-step-form" class="hidden">

                        <!-- Read-only employee info card (source: API) -->
                        <div class="p-3 mb-4 rounded border" style="background: var(--surface-2, #f8f9fa)">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-xs text-muted-foreground fw-semibold text-uppercase letter-spacing-wide">
                                    Selected Employee
                                </span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-xs"
                                    onclick="UsersPage.resetAddModal()">
                                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Change
                                </button>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Employee ID</div>
                                    <div class="text-sm fw-medium font-mono" id="emp-card-id">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Full Name</div>
                                    <div class="text-sm fw-medium" id="emp-card-name">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Department</div>
                                    <div class="text-sm" id="emp-card-dept">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted-foreground">Position</div>
                                    <div class="text-sm" id="emp-card-pos">—</div>
                                </div>
                                <div class="col-12">
                                    <div class="text-xs text-muted-foreground">Email</div>
                                    <div class="text-sm" id="emp-card-email">—</div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="row g-3" id="add-user-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                            <input type="hidden" name="action" value="add" />
                            <input type="hidden" name="employee_id" id="add-employee-id" />

                            <div class="col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1" for="add-username">
                                    Username
                                </label>
                                <input type="text" class="form-control form-control-sm" name="username"
                                    id="add-username" required placeholder="Login username"
                                    autocomplete="off" />
                                <div class="form-text text-xs">Used to log in to the system.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1">Password</label>
                                <input type="password" class="form-control form-control-sm" name="password"
                                    required placeholder="Set initial password"
                                    autocomplete="new-password" />
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1">Role</label>
                                <select name="role" class="form-select form-select-sm" required id="add-role">
                                    <option value="" selected disabled>Select role</option>
                                    <option value="ga_staff">GA Staff</option>
                                    <option value="security">Security</option>
                                    <option value="department">Department</option>
                                </select>
                            </div>

                            <div id="add-department-wrap" class="hidden col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1">Department</label>
                                <select name="department_id" class="form-select form-select-sm" id="add-department-id">
                                    <option value="0">—</option>
                                    <?php foreach ($departmentsDb as $d): ?>
                                        <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="add-security-type-wrap" class="hidden col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1">Security Type</label>
                                <select name="security_type" class="form-select form-select-sm" id="add-security-type">
                                    <option value="" selected disabled>Select type</option>
                                    <option value="internal">Internal</option>
                                    <option value="external">External</option>
                                </select>
                            </div>

                            <div id="add-entity-wrap" class="hidden col-12 col-md-6">
                                <label class="form-label text-sm font-medium text-foreground mb-1">Assigned Entity</label>
                                <select name="entity" class="form-select form-select-sm" id="add-entity">
                                    <option value="" selected disabled>Select entity</option>
                                    <option value="NCFL">NCFL</option>
                                    <option value="NPFL">NPFL</option>
                                </select>
                            </div>

                            <div class="modal-footer col-12 d-flex justify-content-end gap-2 flex-wrap">
                                <button type="button" onclick="UsersPage.closeAddModal()" class="btn btn-outline">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-check me-1" aria-hidden="true"></i>Add User
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="edit-user-modal" class="modal-overlay hidden">
            <div class="modal modal--accent">
                <div class="modal-accent-header">
                    <div>
                        <h2 class="modal-accent-title">Edit User</h2>
                        <p class="modal-accent-subtitle">Update details, role, and status</p>
                    </div>
                    <button type="button" class="modal-accent-close" aria-label="Close" onclick="UsersPage.closeEditModal()">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-accent-body">
                    <form method="POST" class="row g-3" id="edit-user-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                        <input type="hidden" name="action" value="update" />
                        <input type="hidden" name="id" id="edit-id" value="" />

                        <div class="col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Name</label>
                            <input type="text" class="form-control form-control-sm" name="name" id="edit-name" required />
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Username</label>
                            <input type="text" class="form-control form-control-sm" name="username" id="edit-username" required />
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">New Password (optional)</label>
                            <input type="password" class="form-control form-control-sm" name="password" id="edit-password" placeholder="Leave blank to keep current" />
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Account Status</label>
                            <select name="account_status" class="form-select form-select-sm" id="edit-account-status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Role</label>
                            <select name="role" class="form-select form-select-sm" id="edit-role" required>
                                <option value="ga_president" id="edit-role-president" hidden disabled>GA President</option>
                                <option value="ga_staff">GA Staff</option>
                                <option value="security">Security</option>
                                <option value="department">Department</option>
                            </select>
                        </div>

                        <div id="edit-department-wrap" class="hidden col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Department</label>
                            <select name="department_id" class="form-select form-select-sm" id="edit-department-id">
                                <option value="0">—</option>
                                <?php foreach ($departmentsDb as $d): ?>
                                    <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="edit-security-type-wrap" class="hidden col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Security Type</label>
                            <select name="security_type" class="form-select form-select-sm" id="edit-security-type">
                                <option value="" selected disabled>Select type</option>
                                <option value="internal">Internal</option>
                                <option value="external">External</option>
                            </select>
                        </div>

                        <div id="edit-entity-wrap" class="hidden col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Assigned Entity</label>
                            <select name="entity" class="form-select form-select-sm" id="edit-entity">
                                <option value="" selected disabled>Select entity</option>
                                <option value="NCFL">NCFL</option>
                                <option value="NPFL">NPFL</option>
                            </select>
                        </div>

                        <div class="modal-footer col-12 d-flex justify-content-end gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline" onclick="UsersPage.closeEditModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete User Modal -->
        <div id="delete-user-modal" class="modal-overlay hidden">
            <div class="modal modal--accent modal--accent-destructive">
                <div class="modal-accent-header">
                    <div>
                        <h2 class="modal-accent-title">Delete User</h2>
                        <p class="modal-accent-subtitle">This action cannot be undone</p>
                    </div>
                    <button type="button" class="modal-accent-close" aria-label="Close" onclick="UsersPage.closeDeleteModal()">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-accent-body">
                    <div class="alert alert-danger" role="alert">
                        You are about to permanently delete this account. Reports and historical records may still reference this user.
                    </div>

                    <div class="confirm-details mt-4">
                        <div class="confirm-detail-card d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div>
                                <div class="text-xs text-muted-foreground">Name</div>
                                <div class="font-medium" id="delete-user-name">—</div>
                            </div>
                            <div class="confirm-detail-right">
                                <div class="text-xs text-muted-foreground">Username</div>
                                <div class="font-mono text-xs" id="delete-user-username">—</div>
                            </div>
                        </div>
                        <div class="confirm-detail-card d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div>
                                <div class="text-xs text-muted-foreground">Role</div>
                                <div class="text-sm" id="delete-user-role">—</div>
                            </div>
                            <div class="confirm-detail-right">
                                <div class="text-xs text-muted-foreground">Department</div>
                                <div class="text-sm" id="delete-user-dept">—</div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" id="delete-user-form" class="modal-footer">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                        <input type="hidden" name="action" value="delete" />
                        <input type="hidden" name="id" id="delete-user-id" value="" />

                        <div class="flex-grow-1 hidden" id="delete-self-warning">
                            <span class="confirm-self-warning text-xs">You cannot delete your own account.</span>
                        </div>

                        <button type="button" class="btn btn-outline" onclick="UsersPage.closeDeleteModal()">Cancel</button>
                        <button type="submit" class="btn btn-destructive" id="delete-user-submit">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const UsersPage = window.UsersPage = {
        currentUserId: <?php echo (int)$currentUser['id']; ?>,
        _empApiUrl: '<?php echo htmlspecialchars(app_url('api/employee_search.php'), ENT_QUOTES, 'UTF-8'); ?>',

    // ── Modal helpers ─────────────────────────────────────────────────────
    toggleModal(modalId) {
      const modal = document.getElementById(modalId);
      if (!modal) return;
      const willOpen = modal.classList.contains('hidden');
      modal.classList.toggle('hidden');
      if (willOpen) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      } else {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      }
    },

    // ── Init ──────────────────────────────────────────────────────────────
    init() {
      const addRole = document.getElementById('add-role');
      if (addRole) {
        addRole.addEventListener('change', () => this.syncConditionalFields('add'));
        this.syncConditionalFields('add');
      }

      const editRole = document.getElementById('edit-role');
      if (editRole) {
        editRole.addEventListener('change', () => this.syncConditionalFields('edit'));
      }

      // Employee search bindings
      const empInput = document.getElementById('emp-search-input');
      const empBtn   = document.getElementById('emp-search-btn');
      if (empInput) {
        empInput.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') { e.preventDefault(); this.doEmployeeSearch(); }
        });
      }
      if (empBtn) empBtn.addEventListener('click', () => this.doEmployeeSearch());

      const addModal = document.getElementById('add-user-modal');
      if (addModal) {
        addModal.addEventListener('click', (e) => {
          if (e.target === addModal) this.closeAddModal();
        });
      }

      const editModal = document.getElementById('edit-user-modal');
      if (editModal) {
        editModal.addEventListener('click', (e) => {
          if (e.target === editModal) this.closeEditModal();
        });
      }

      const deleteModal = document.getElementById('delete-user-modal');
      if (deleteModal) {
        deleteModal.addEventListener('click', (e) => {
          if (e.target === deleteModal) this.closeDeleteModal();
        });
      }

      // Loading state: disable submit button while form is submitting
      ['add-user-form', 'edit-user-form', 'delete-user-form'].forEach(id => {
        const form = document.getElementById(id);
        if (form) {
          form.addEventListener('submit', () => {
            const btn = form.querySelector('[type="submit"]');
            if (btn && !btn.disabled) btn.setAttribute('data-loading', 'true');
          });
        }
      });
    },

    // ── Add User (employee-search flow) ──────────────────────────────────
    openAddModal() {
      this.resetAddModal();
      this.toggleModal('add-user-modal');
    },

    closeAddModal() {
      const modal = document.getElementById('add-user-modal');
      if (modal && !modal.classList.contains('hidden')) this.toggleModal('add-user-modal');
      this.resetAddModal();
    },

    resetAddModal() {
      const stepSearch = document.getElementById('add-step-search');
      const stepForm   = document.getElementById('add-step-form');
      if (stepSearch) stepSearch.classList.remove('hidden');
      if (stepForm)   stepForm.classList.add('hidden');

      const empInput = document.getElementById('emp-search-input');
      if (empInput) empInput.value = '';

      const resultsList = document.getElementById('emp-results-list');
      if (resultsList) resultsList.innerHTML = '';

      const resultsBox = document.getElementById('emp-search-results');
      if (resultsBox) resultsBox.classList.add('hidden');

      this._showSearchAlert(null);

      const empIdField = document.getElementById('add-employee-id');
      if (empIdField) empIdField.value = '';

      const form = document.getElementById('add-user-form');
      if (form) form.reset();

      this.syncConditionalFields('add');
    },

    doEmployeeSearch() {
      const input = document.getElementById('emp-search-input');
      const query = input ? input.value.trim() : '';

      if (query.length < 2) {
        this._showSearchAlert('Please enter at least 2 characters.');
        return;
      }

      this._setSearchLoading(true);
      this._showSearchAlert(null);

      const resultsBox = document.getElementById('emp-search-results');
      if (resultsBox) resultsBox.classList.add('hidden');

      const url = this._empApiUrl + '?q=' + encodeURIComponent(query);

      fetch(url, { credentials: 'same-origin' })
        .then(r => {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        })
        .then(data => {
          this._setSearchLoading(false);
          if (!data.success) {
            this._showSearchAlert(data.error || 'Search failed. Please try again.');
            return;
          }
          const employees = Array.isArray(data.employees) ? data.employees : [];
          if (employees.length === 0) {
            this._showSearchAlert('No employees found for "' + query + '".');
            return;
          }
          this._renderResults(employees, !!data.using_mock);
        })
        .catch(() => {
          this._setSearchLoading(false);
          this._showSearchAlert('Network error. Please check your connection and try again.');
        });
    },

    selectEmployee(emp) {
      const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = (val && String(val).trim()) ? String(val) : '—';
      };

      set('emp-card-id',    emp.employee_id);
      set('emp-card-name',  emp.fullname);
      set('emp-card-dept',  emp.department);
      set('emp-card-pos',   emp.position);
      set('emp-card-email', emp.email);

      const empIdField = document.getElementById('add-employee-id');
      if (empIdField) empIdField.value = emp.employee_id || '';

      // Pre-fill username suggestion from employee_id
      const usernameField = document.getElementById('add-username');
      if (usernameField && !usernameField.value && emp.employee_id) {
        usernameField.value = String(emp.employee_id).toLowerCase();
      }

      const stepSearch = document.getElementById('add-step-search');
      const stepForm   = document.getElementById('add-step-form');
      if (stepSearch) stepSearch.classList.add('hidden');
      if (stepForm)   stepForm.classList.remove('hidden');
    },

    _setSearchLoading(loading) {
      const loader = document.getElementById('emp-search-loader');
      const btn    = document.getElementById('emp-search-btn');
      if (loader) loader.classList.toggle('hidden', !loading);
      if (btn)    btn.disabled = loading;
    },

    _showSearchAlert(msg) {
      const el = document.getElementById('emp-search-alert');
      if (!el) return;
      if (msg) {
        el.textContent = msg;
        el.classList.remove('hidden');
      } else {
        el.textContent = '';
        el.classList.add('hidden');
      }
    },

    _renderResults(employees, usingMock) {
      const list      = document.getElementById('emp-results-list');
      const container = document.getElementById('emp-search-results');
      if (!list || !container) return;

      list.innerHTML = '';

      employees.forEach(emp => {
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center justify-content-between gap-3 p-2 rounded border bg-body';
        row.style.cursor = 'pointer';

        const info = document.createElement('div');
        info.className = 'overflow-hidden';

        const nameLine = document.createElement('div');
        nameLine.className = 'text-sm fw-medium text-truncate';
        nameLine.textContent = emp.fullname || '—';

        const subLine = document.createElement('div');
        subLine.className = 'text-xs text-muted-foreground text-truncate';
        subLine.textContent = [emp.employee_id, emp.department, emp.position]
          .filter(v => v && String(v).trim())
          .join(' · ');

        info.appendChild(nameLine);
        info.appendChild(subLine);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline py-1 px-2 flex-shrink-0';
        btn.textContent = 'Select';

        row.appendChild(info);
        row.appendChild(btn);

        row.addEventListener('click', () => this.selectEmployee(emp));
        list.appendChild(row);
      });

      if (usingMock) {
        const notice = document.createElement('div');
        notice.className = 'alert alert-warning text-xs py-1 mt-2 mb-0';
        notice.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>'
          + 'Development mode: data from local mock API.';
        list.appendChild(notice);
      }

      container.classList.remove('hidden');
    },

    // ── Role-conditional fields ───────────────────────────────────────────
    syncConditionalFields(prefix) {
      const roleEl = document.getElementById(prefix + '-role');
      if (!roleEl) return;
      const role = roleEl.value;

      const deptWrap    = document.getElementById(prefix + '-department-wrap');
      const deptSelect  = document.getElementById(prefix + '-department-id');
      const secWrap     = document.getElementById(prefix + '-security-type-wrap');
      const secSelect   = document.getElementById(prefix + '-security-type');
      const entityWrap  = document.getElementById(prefix + '-entity-wrap');
      const entitySelect = document.getElementById(prefix + '-entity');

      const isDepartment = role === 'department';
      const isSecurity   = role === 'security';

      if (deptWrap) deptWrap.classList.toggle('hidden', !isDepartment);
      if (deptSelect) deptSelect.required = isDepartment;
      if (!isDepartment && deptSelect) deptSelect.value = '0';

      if (secWrap) secWrap.classList.toggle('hidden', !isSecurity);
      if (secSelect) secSelect.required = isSecurity;
      if (!isSecurity && secSelect) secSelect.value = '';

      if (entityWrap) entityWrap.classList.toggle('hidden', !isSecurity);
      if (entitySelect) entitySelect.required = isSecurity;
      if (!isSecurity && entitySelect) entitySelect.value = '';
    },

    // ── Edit modal ────────────────────────────────────────────────────────
    openEditModal(btn) {
      const raw = btn && btn.dataset ? btn.dataset.user : '';
      if (!raw) return;
      let user;
      try { user = JSON.parse(raw); } catch { return; }

      document.getElementById('edit-id').value = String(user.id || '');
      document.getElementById('edit-name').value = user.name || '';
      document.getElementById('edit-username').value = user.username || '';
      document.getElementById('edit-password').value = '';
      document.getElementById('edit-account-status').value = (user.account_status === 'inactive') ? 'inactive' : 'active';

      const roleEl = document.getElementById('edit-role');
      const presidentOpt = document.getElementById('edit-role-president');
      const isPresident = user.role === 'ga_president';
      if (presidentOpt) {
        presidentOpt.hidden   = !isPresident;
        presidentOpt.disabled = !isPresident;
      }
      if (roleEl) {
        roleEl.value = user.role || 'ga_staff';
        if (!isPresident && roleEl.value === 'ga_president') roleEl.value = 'ga_staff';
      }

      const deptSelect = document.getElementById('edit-department-id');
      if (deptSelect) deptSelect.value = String(user.department_id || 0);

      const secSelect = document.getElementById('edit-security-type');
      if (secSelect) secSelect.value = user.security_type || '';

      const entitySelect = document.getElementById('edit-entity');
      if (entitySelect) entitySelect.value = user.entity || '';

      this.syncConditionalFields('edit');
      this.toggleModal('edit-user-modal');
    },

    closeEditModal() {
      const modal = document.getElementById('edit-user-modal');
      if (modal && !modal.classList.contains('hidden')) this.toggleModal('edit-user-modal');
    },

    // ── Delete modal ──────────────────────────────────────────────────────
    openDeleteModal(btn) {
      const raw = btn && btn.dataset ? btn.dataset.user : '';
      if (!raw) return;
      let user;
      try { user = JSON.parse(raw); } catch { return; }

      const id = Number(user.id) || 0;
      document.getElementById('delete-user-id').value = String(id);
      document.getElementById('delete-user-name').textContent = user.name || '—';
      document.getElementById('delete-user-username').textContent = user.username || '—';
      document.getElementById('delete-user-role').textContent = (user.role || '—').replace(/_/g, ' ');
      document.getElementById('delete-user-dept').textContent = user.department ? String(user.department) : '—';

      const isSelf    = id > 0 && this.currentUserId === id;
      const warn      = document.getElementById('delete-self-warning');
      const submitBtn = document.getElementById('delete-user-submit');
      if (warn)      warn.classList.toggle('hidden', !isSelf);
      if (submitBtn) submitBtn.disabled = !!isSelf;

      this.toggleModal('delete-user-modal');
    },

    closeDeleteModal() {
      const modal = document.getElementById('delete-user-modal');
      if (modal && !modal.classList.contains('hidden')) this.toggleModal('delete-user-modal');
    }
  };

    document.addEventListener('DOMContentLoaded', () => UsersPage.init());
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
