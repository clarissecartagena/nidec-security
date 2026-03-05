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
            <button onclick="UsersPage.toggleModal('add-user-modal')" class="btn btn-primary d-inline-flex align-items-center gap-2">
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
                        <th>Username</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" class="text-center text-muted-foreground">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="font-medium"><?php echo htmlspecialchars($u['name']); ?></td>
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
                                                'name' => (string)$u['name'],
                                                'username' => (string)$u['username'],
                                                'role' => (string)$u['role'],
                                                'department_id' => (int)($u['department_id'] ?? 0),
                                                'security_type' => (string)($u['security_type'] ?? ''),
                                                'building' => (string)($u['building'] ?? ''),
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

        <!-- Add User Modal -->
        <div id="add-user-modal" class="modal-overlay hidden">
            <div class="modal modal--accent">
                <div class="modal-accent-header">
                    <div>
                        <h2 class="modal-accent-title">Add New User</h2>
                        <p class="modal-accent-subtitle">Create a new account (defaults to Active)</p>
                    </div>
                    <button type="button" class="modal-accent-close" aria-label="Close" onclick="UsersPage.toggleModal('add-user-modal')">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-accent-body">
                    <form method="POST" class="row g-3" id="add-user-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                        <input type="hidden" name="action" value="add" />

                        <div class="col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Name</label>
                            <input type="text" class="form-control form-control-sm" name="name" required placeholder="Enter full name" />
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Username</label>
                            <input type="text" class="form-control form-control-sm" name="username" required placeholder="Enter username" />
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Password</label>
                            <input type="password" class="form-control form-control-sm" name="password" required placeholder="Enter password" />
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

                        <div id="add-building-wrap" class="hidden col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Assigned Building</label>
                            <select name="building" class="form-select form-select-sm" id="add-building">
                                <option value="" selected disabled>Select building</option>
                                <option value="NCFL">NCFL</option>
                                <option value="NPFL">NPFL</option>
                            </select>
                        </div>

                        <div class="modal-footer col-12 d-flex justify-content-end gap-2 flex-wrap">
                            <button type="button" onclick="UsersPage.toggleModal('add-user-modal')" class="btn btn-outline">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add User</button>
                        </div>
                    </form>
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

                        <div id="edit-building-wrap" class="hidden col-12 col-md-6">
                            <label class="form-label text-sm font-medium text-foreground mb-1">Assigned Building</label>
                            <select name="building" class="form-select form-select-sm" id="edit-building">
                                <option value="" selected disabled>Select building</option>
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

      const addModal = document.getElementById('add-user-modal');
      if (addModal) {
        addModal.addEventListener('click', (e) => {
          if (e.target === addModal) this.toggleModal('add-user-modal');
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
    },

    syncConditionalFields(prefix) {
      const roleEl = document.getElementById(prefix + '-role');
      if (!roleEl) return;
      const role = roleEl.value;

      const deptWrap = document.getElementById(prefix + '-department-wrap');
      const deptSelect = document.getElementById(prefix + '-department-id');
      const secWrap = document.getElementById(prefix + '-security-type-wrap');
      const secSelect = document.getElementById(prefix + '-security-type');
    const buildingWrap = document.getElementById(prefix + '-building-wrap');
    const buildingSelect = document.getElementById(prefix + '-building');

      const isDepartment = role === 'department';
      const isSecurity = role === 'security';

      if (deptWrap) deptWrap.classList.toggle('hidden', !isDepartment);
      if (deptSelect) deptSelect.required = isDepartment;
      if (!isDepartment && deptSelect) deptSelect.value = '0';

      if (secWrap) secWrap.classList.toggle('hidden', !isSecurity);
      if (secSelect) secSelect.required = isSecurity;
      if (!isSecurity && secSelect) secSelect.value = '';

            if (buildingWrap) buildingWrap.classList.toggle('hidden', !isSecurity);
            if (buildingSelect) buildingSelect.required = isSecurity;
            if (!isSecurity && buildingSelect) buildingSelect.value = '';
    },

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
        presidentOpt.hidden = !isPresident;
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

            const buildingSelect = document.getElementById('edit-building');
            if (buildingSelect) buildingSelect.value = user.building || '';

      this.syncConditionalFields('edit');
      this.toggleModal('edit-user-modal');
    },

    closeEditModal() {
      const modal = document.getElementById('edit-user-modal');
      if (modal && !modal.classList.contains('hidden')) {
        this.toggleModal('edit-user-modal');
      }
        },

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

            const isSelf = id > 0 && this.currentUserId === id;
            const warn = document.getElementById('delete-self-warning');
            const submitBtn = document.getElementById('delete-user-submit');
            if (warn) warn.classList.toggle('hidden', !isSelf);
            if (submitBtn) submitBtn.disabled = !!isSelf;

            this.toggleModal('delete-user-modal');
        },

        closeDeleteModal() {
            const modal = document.getElementById('delete-user-modal');
            if (modal && !modal.classList.contains('hidden')) {
                this.toggleModal('delete-user-modal');
            }
    }
  };

    document.addEventListener('DOMContentLoaded', () => UsersPage.init());
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
