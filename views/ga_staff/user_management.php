<?php
$pageTitle = 'User Management';
$requiredRole = 'ga_staff';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/topnav.php';

$flash = null;
$flashType = 'success';

$currentUser = getUser();
$currentUserId = (int)($currentUser['id'] ?? 0);

$departmentsDb = fetch_departments();

function has_user_audit_columns(): bool {
    try {
        $a = db_fetch_one("SHOW COLUMNS FROM users LIKE 'created_by_role'");
        $b = db_fetch_one("SHOW COLUMNS FROM users LIKE 'created_by_user_id'");
        return (bool)$a && (bool)$b;
    } catch (Throwable $e) {
        return false;
    }
}

$auditOk = has_user_audit_columns();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!csrf_validate($token)) {
        $flash = 'Security check failed. Please refresh and try again.';
        $flashType = 'error';
    } elseif (!$auditOk) {
        $flash = 'Database is missing required audit columns. Run the latest migration/schema update first.';
        $flashType = 'error';
    } else {
        try {
            if ($action === 'add') {
                $name = trim($_POST['name'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $password = (string)($_POST['password'] ?? '');
                $role = (string)($_POST['role'] ?? '');
                $departmentId = (int)($_POST['department_id'] ?? 0);
                $securityType = (string)($_POST['security_type'] ?? '');
                $building = (string)($_POST['building'] ?? '');
                $accountStatus = 'active';

                if ($name === '' || $username === '' || $password === '') {
                    throw new RuntimeException('Please fill in all required fields.');
                }

                // GA Staff role restriction
                if (!in_array($role, ['security', 'department'], true)) {
                    throw new RuntimeException('Unauthorized Role Creation');
                }

                if ($role !== 'department') {
                    $departmentId = 0;
                }

                if ($role !== 'security') {
                    $securityType = '';
                    $building = '';
                } else {
                    if (!in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a valid Security Type.');
                    }
                    if (!in_array($building, ['NCFL', 'NPFL'], true)) {
                        throw new RuntimeException('Please select an assigned building (NCFL/NPFL).');
                    }
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);
                db_execute(
                    "INSERT INTO users (name, username, password_hash, role, department_id, security_type, building, account_status, created_by_role, created_by_user_id)
                     VALUES (?, ?, ?, ?, NULLIF(?,0), NULLIF(?,''), NULLIF(?,''), ?, 'ga_staff', ?)",
                    'ssssisssi',
                    [$name, $username, $hash, $role, $departmentId, $securityType, $building, $accountStatus, $currentUserId]
                );

                $flash = 'User added successfully.';
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $password = (string)($_POST['password'] ?? '');
                $role = (string)($_POST['role'] ?? '');
                $departmentId = (int)($_POST['department_id'] ?? 0);
                $securityType = (string)($_POST['security_type'] ?? '');
                $building = (string)($_POST['building'] ?? '');
                $accountStatus = (string)($_POST['account_status'] ?? 'active');

                if ($id <= 0 || $name === '' || $username === '') {
                    throw new RuntimeException('Invalid update request.');
                }

                if (!in_array($accountStatus, ['active', 'inactive'], true)) {
                    throw new RuntimeException('Invalid account status.');
                }

                // Only allow editing of Security/Department users
                $existing = db_fetch_one('SELECT id, role FROM users WHERE id = ? LIMIT 1', 'i', [$id]);
                if (!$existing || !in_array(($existing['role'] ?? ''), ['security', 'department'], true)) {
                    throw new RuntimeException('Access denied.');
                }

                // GA Staff role restriction (server-side)
                if (!in_array($role, ['security', 'department'], true)) {
                    throw new RuntimeException('Unauthorized Role Creation');
                }

                if ($role !== 'department') {
                    $departmentId = 0;
                }

                if ($role !== 'security') {
                    $securityType = '';
                    $building = '';
                } else {
                    if (!in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a valid Security Type.');
                    }
                    if (!in_array($building, ['NCFL', 'NPFL'], true)) {
                        throw new RuntimeException('Please select an assigned building (NCFL/NPFL).');
                    }
                }

                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    db_execute(
                        'UPDATE users SET name=?, username=?, password_hash=?, role=?, department_id=NULLIF(?,0), security_type=NULLIF(?,\'\'), building=NULLIF(?,\'\'), account_status=? WHERE id=?',
                        'ssssisssi',
                        [$name, $username, $hash, $role, $departmentId, $securityType, $building, $accountStatus, $id]
                    );
                } else {
                    db_execute(
                        'UPDATE users SET name=?, username=?, role=?, department_id=NULLIF(?,0), security_type=NULLIF(?,\'\'), building=NULLIF(?,\'\'), account_status=? WHERE id=?',
                        'sssisssi',
                        [$name, $username, $role, $departmentId, $securityType, $building, $accountStatus, $id]
                    );
                }

                $flash = 'User updated successfully.';
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new RuntimeException('Invalid request.');
                if ($id === $currentUserId) throw new RuntimeException('You cannot delete your own account.');

                $row = db_fetch_one('SELECT id, role FROM users WHERE id = ? LIMIT 1', 'i', [$id]);
                if (!$row || !in_array(($row['role'] ?? ''), ['security', 'department'], true)) {
                    throw new RuntimeException('Access denied.');
                }

                db_execute('DELETE FROM users WHERE id = ? LIMIT 1', 'i', [$id]);
                $flash = 'User deleted successfully.';
            } else {
                throw new RuntimeException('Unknown action.');
            }
        } catch (Throwable $e) {
            $flash = $e->getMessage();
            $flashType = 'error';
        }
    }
}

$users = db_fetch_all(
    "SELECT u.id, u.name, u.username, u.role, u.department_id, u.security_type, u.building, u.account_status, u.created_at, d.name AS department_name
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     WHERE u.role IN ('security','department')
     ORDER BY u.created_at DESC"
);

function user_status_badge(string $status): string {
    return $status === 'active'
        ? '<span class="badge badge--success">Active</span>'
        : '<span class="badge badge--muted">Inactive</span>';
}

function user_role_label(string $role): string {
    return $role === 'security' ? 'Security' : 'Department';
}
?>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-people-fill me-2 text-primary"></i>User Management</h1>
                <p class="text-sm text-muted-foreground mb-0">GA Staff can manage Security and Department accounts</p>
            </div>
            <button type="button" onclick="UsersPage.toggleModal('add-user-modal')" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14"/><path d="M12 5v14"/>
                </svg>
                Add User
            </button>
        </div>

        <?php if (!$auditOk): ?>
            <div class="alert alert-error mb-4">
                Database audit columns are missing. Update your schema/migration to add <strong>users.created_by_role</strong> and <strong>users.created_by_user_id</strong>.
            </div>
        <?php endif; ?>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'error' : 'success'; ?> mb-4">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <div class="table-container table-card" style="--table-accent: var(--info)">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-foreground">Users</h3>
                    <p class="text-xs text-muted-foreground">Security and Department accounts only</p>
                </div>
                <div class="text-xs text-muted-foreground">Total: <?php echo (int)count($users); ?></div>
            </div>
            <table>
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
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" class="text-center text-muted-foreground">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="font-medium"><?php echo htmlspecialchars($u['name']); ?></td>
                                <td class="font-mono text-xs"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars(user_role_label($u['role'])); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($u['department_name'] ?? '—'); ?></td>
                                <td><?php echo user_status_badge((string)($u['account_status'] ?? 'inactive')); ?></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="icon-btn" title="Edit"
                                            onclick="UsersPage.openEditModal(this)"
                                            data-user="<?php echo htmlspecialchars(json_encode([
                                                'id' => (int)$u['id'],
                                                'name' => (string)$u['name'],
                                                'username' => (string)$u['username'],
                                                'role' => (string)$u['role'],
                                                'department_id' => (int)($u['department_id'] ?? 0),
                                                'department' => (string)($u['department_name'] ?? ''),
                                                'security_type' => (string)($u['security_type'] ?? ''),
                                                'building' => (string)($u['building'] ?? ''),
                                                'account_status' => (string)($u['account_status'] ?? 'active')
                                            ]), ENT_QUOTES, 'UTF-8'); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="icon-btn" title="Delete"
                                            onclick="UsersPage.openDeleteModal(this)"
                                            data-user="<?php echo htmlspecialchars(json_encode([
                                                'id' => (int)$u['id'],
                                                'name' => (string)$u['name'],
                                                'username' => (string)$u['username'],
                                                'role' => (string)$u['role'],
                                                'department' => (string)($u['department_name'] ?? ''),
                                                'account_status' => (string)($u['account_status'] ?? 'active')
                                            ]), ENT_QUOTES, 'UTF-8'); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-accent-body">
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="add-user-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                        <input type="hidden" name="action" value="add" />

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Name</label>
                            <input type="text" name="name" required placeholder="Enter full name" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Username</label>
                            <input type="text" name="username" required placeholder="Enter username" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Password</label>
                            <input type="password" name="password" required placeholder="Enter password" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Role</label>
                            <select name="role" required id="add-role">
                                <option value="" selected disabled>Select role</option>
                                <option value="security">Security</option>
                                <option value="department">Department</option>
                            </select>
                        </div>

                        <div id="add-department-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Department</label>
                            <select name="department_id" id="add-department-id">
                                <option value="0">—</option>
                                <?php foreach ($departmentsDb as $d): ?>
                                    <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="add-security-type-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Security Type</label>
                            <select name="security_type" id="add-security-type">
                                <option value="" selected disabled>Select type</option>
                                <option value="internal">Internal</option>
                                <option value="external">External</option>
                            </select>
                        </div>

                        <div id="add-building-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Assigned Building</label>
                            <select name="building" id="add-building">
                                <option value="" selected disabled>Select building</option>
                                <option value="NCFL">NCFL</option>
                                <option value="NPFL">NPFL</option>
                            </select>
                        </div>

                        <div class="modal-footer md:col-span-2">
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-accent-body">
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="edit-user-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                        <input type="hidden" name="action" value="update" />
                        <input type="hidden" name="id" id="edit-id" value="" />

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Name</label>
                            <input type="text" name="name" id="edit-name" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Username</label>
                            <input type="text" name="username" id="edit-username" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">New Password (optional)</label>
                            <input type="password" name="password" id="edit-password" placeholder="Leave blank to keep current" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Account Status</label>
                            <select name="account_status" id="edit-account-status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Role</label>
                            <select name="role" id="edit-role" required>
                                <option value="security">Security</option>
                                <option value="department">Department</option>
                            </select>
                        </div>

                        <div id="edit-department-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Department</label>
                            <select name="department_id" id="edit-department-id">
                                <option value="0">—</option>
                                <?php foreach ($departmentsDb as $d): ?>
                                    <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="edit-security-type-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Security Type</label>
                            <select name="security_type" id="edit-security-type">
                                <option value="" selected disabled>Select type</option>
                                <option value="internal">Internal</option>
                                <option value="external">External</option>
                            </select>
                        </div>

                        <div id="edit-building-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Assigned Building</label>
                            <select name="building" id="edit-building">
                                <option value="" selected disabled>Select building</option>
                                <option value="NCFL">NCFL</option>
                                <option value="NPFL">NPFL</option>
                            </select>
                        </div>

                        <div class="modal-footer md:col-span-2">
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-accent-body">
                    <div class="alert alert-error">
                        You are about to permanently delete this account. Historical records may still reference this user.
                    </div>

                    <div class="confirm-details mt-4">
                        <div class="confirm-detail-card flex items-center justify-between">
                            <div>
                                <div class="text-xs text-muted-foreground">Name</div>
                                <div class="font-medium" id="delete-user-name">—</div>
                            </div>
                            <div class="confirm-detail-right">
                                <div class="text-xs text-muted-foreground">Username</div>
                                <div class="font-mono text-xs" id="delete-user-username">—</div>
                            </div>
                        </div>
                        <div class="confirm-detail-card flex items-center justify-between">
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

                        <div class="flex-1 hidden" id="delete-self-warning">
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
        currentUserId: <?php echo (int)$currentUserId; ?>,

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
            try { user = JSON.parse(raw); } catch (e) { return; }

            document.getElementById('edit-id').value = String(user.id || '');
            document.getElementById('edit-name').value = user.name || '';
            document.getElementById('edit-username').value = user.username || '';
            document.getElementById('edit-password').value = '';
            document.getElementById('edit-account-status').value = (user.account_status === 'inactive') ? 'inactive' : 'active';

            const roleEl = document.getElementById('edit-role');
            if (roleEl) roleEl.value = user.role || 'security';

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
            try { user = JSON.parse(raw); } catch (e) { return; }

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
