<?php
$pageTitle = 'User Management';
$requiredRole = 'ga_staff';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/topnav.php';
require_once __DIR__ . '/../../app/services/EmployeeService.php';

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
                $employeeNo   = trim($_POST['employee_id'] ?? '');
                $username     = trim($_POST['username'] ?? '');
                $password     = (string)($_POST['password'] ?? '');
                $departmentId = (int)($_POST['department_id'] ?? 0);
                $securityType = (string)($_POST['security_type'] ?? '');
                $accountStatus = 'active';

                if ($employeeNo === '' || $username === '' || $password === '') {
                    throw new RuntimeException('Please fill in all required fields.');
                }

                // Verify employee via company API and auto-detect role + entity.
                $empSvc    = new EmployeeService();
                $empResult = $empSvc->getEmployee($employeeNo);
                if (!$empResult['success']) {
                    throw new RuntimeException(
                        'Employee verification failed: '
                        . ($empResult['error'] ?? 'Employee not found.')
                    );
                }
                $emp = $empResult['employee'];

                // Auto-detect role and entity from Employee API data.
                $detected = EmployeeService::detectRoleFromEmployee($emp);
                if ($detected === null) {
                    throw new RuntimeException(
                        'This employee cannot be added to the system. '
                        . 'Only GA Staff (HUMAN RESOURCE, GA AND COMPLIANCE section), '
                        . 'Security Guards (job level: Security / Security Guard), '
                        . 'and Department PICs (job level: SUPPORT/PIC) may be registered.'
                    );
                }

                $role   = $detected['role'];
                $entity = $detected['entity'];

                if ($role === 'ga_president') {
                    throw new RuntimeException('The GA President account cannot be added through this form.');
                }

                // GA Staff restriction — only security and department roles allowed.
                if (!in_array($role, ['security', 'department', 'ga_staff'], true)) {
                    throw new RuntimeException('Unauthorized Role Creation');
                }

                if ($role === 'department') {
                    if ($departmentId <= 0) {
                        // Try auto-resolving department from API data.
                        $deptRow = db_fetch_one(
                            'SELECT id FROM departments WHERE LOWER(name) = ? LIMIT 1',
                            's',
                            [strtolower($emp['department'] ?? '')]
                        );
                        $departmentId = $deptRow ? (int)$deptRow['id'] : 0;
                    }
                    $securityType = '';
                    $entity       = '';
                } elseif ($role === 'security') {
                    if (!in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a valid Security Type.');
                    }
                    $departmentId = 0;
                } else {
                    // ga_staff
                    $departmentId = 0;
                    $securityType = '';
                    $entity       = '';
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);
                db_execute(
                    "INSERT INTO users
                         (employee_no, name, email, position, department, username, password_hash,
                          role, department_id, security_type, entity, account_status,
                          created_by_role, created_by_user_id)
                     VALUES
                         (NULLIF(?,''), ?, NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), ?, ?,
                          ?, NULLIF(?,0), NULLIF(?,''), NULLIF(?,''), ?,
                          'ga_staff', ?)",
                    '',
                    [
                        $emp['employee_id'], $emp['fullname'],
                        $emp['email'],       $emp['position'],
                        $emp['department'],  $username,  $hash,
                        $role, $departmentId, $securityType, $entity, $accountStatus,
                        $currentUserId,
                    ]
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
                $entity = (string)($_POST['entity'] ?? '');
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
                    $entity = '';
                } else {
                    if (!in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a valid Security Type.');
                    }
                    if (!in_array($entity, ['NCFL', 'NPFL'], true)) {
                        throw new RuntimeException('Please select an assigned entity (NCFL/NPFL).');
                    }
                }

                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    db_execute(
                        'UPDATE users SET name=?, username=?, password_hash=?, role=?, department_id=NULLIF(?,0), security_type=NULLIF(?,\'\'), entity=NULLIF(?,\'\'), account_status=? WHERE id=?',
                        'ssssisssi',
                        [$name, $username, $hash, $role, $departmentId, $securityType, $entity, $accountStatus, $id]
                    );
                } else {
                    db_execute(
                        'UPDATE users SET name=?, username=?, role=?, department_id=NULLIF(?,0), security_type=NULLIF(?,\'\'), entity=NULLIF(?,\'\'), account_status=? WHERE id=?',
                        'sssisssi',
                        [$name, $username, $role, $departmentId, $securityType, $entity, $accountStatus, $id]
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
    "SELECT u.id, u.employee_no, u.name, u.username, u.role, u.department_id, u.security_type, u.entity, u.account_status, u.created_at, d.name AS department_name
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
            <button type="button" onclick="UsersPage.openAddModal()" class="btn btn-primary d-inline-flex align-items-center gap-2">
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
                        <th>Emp ID</th>
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
                        <tr><td colspan="7" class="text-center text-muted-foreground">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="font-mono text-xs"><?php echo htmlspecialchars($u['employee_no'] ?? '—'); ?></td>
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
                                                'entity' => (string)($u['entity'] ?? ''),
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

        <!-- Add User Modal — two-step: search employee → set credentials/role -->
        <div id="add-user-modal" class="modal-overlay hidden">
            <div class="modal modal--accent">
                <div class="modal-accent-header">
                    <div>
                        <h2 class="modal-accent-title">Add New User</h2>
                        <p class="modal-accent-subtitle">Search the company employee directory first</p>
                    </div>
                    <button type="button" class="modal-accent-close" aria-label="Close" onclick="UsersPage.closeAddModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-accent-body">

                    <!-- Step 1: Employee search -->
                    <div id="add-step-search">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-foreground mb-1" for="emp-search-input">
                                Search Employee
                            </label>
                            <div class="flex gap-2">
                                <input type="text" id="emp-search-input"
                                    class="flex-1"
                                    placeholder="Employee ID or Full Name (min 2 characters)…"
                                    autocomplete="off" />
                                <button type="button" id="emp-search-btn" class="btn btn-primary">
                                    Search
                                </button>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Data is fetched from the company employee directory.
                            </p>
                        </div>

                        <div id="emp-search-loader" class="text-center py-3 hidden" aria-live="polite">
                            <span class="text-sm text-muted-foreground">Searching…</span>
                        </div>

                        <div id="emp-search-alert" class="alert alert-error text-sm py-2 mb-0 hidden" role="alert"></div>

                        <div id="emp-search-results" class="hidden">
                            <p class="text-xs text-muted-foreground mb-2">Select an employee to continue:</p>
                            <div id="emp-results-list" class="flex flex-col gap-2"></div>
                        </div>
                    </div>

                    <!-- Step 2: Confirm employee + set credentials / role -->
                    <div id="add-step-form" class="hidden">

                        <div class="p-3 mb-4 rounded border" style="background: var(--surface-2, #f8f9fa)">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-muted-foreground font-semibold uppercase">
                                    Selected Employee
                                </span>
                                <button type="button" class="btn btn-link text-xs p-0"
                                    onclick="UsersPage.resetAddModal()">
                                    ← Change
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <div class="text-xs text-muted-foreground">Employee ID</div>
                                    <div class="text-sm font-medium font-mono" id="emp-card-id">—</div>
                                </div>
                                <div>
                                    <div class="text-xs text-muted-foreground">Full Name</div>
                                    <div class="text-sm font-medium" id="emp-card-name">—</div>
                                </div>
                                <div>
                                    <div class="text-xs text-muted-foreground">Department</div>
                                    <div class="text-sm" id="emp-card-dept">—</div>
                                </div>
                                <div>
                                    <div class="text-xs text-muted-foreground">Position</div>
                                    <div class="text-sm" id="emp-card-pos">—</div>
                                </div>
                                <div class="col-span-2">
                                    <div class="text-xs text-muted-foreground">Email</div>
                                    <div class="text-sm" id="emp-card-email">—</div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="add-user-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                            <input type="hidden" name="action" value="add" />
                            <input type="hidden" name="employee_id" id="add-employee-id" />

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1" for="add-username">
                                    Username
                                </label>
                                <input type="text" name="username" id="add-username"
                                    required placeholder="Login username"
                                    autocomplete="off" />
                                <p class="text-xs text-muted-foreground mt-1">Used to log in to the system.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">Password</label>
                                <input type="password" name="password"
                                    required placeholder="Set initial password"
                                    autocomplete="new-password" />
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

                            <div class="modal-footer md:col-span-2">
                                <button type="button" onclick="UsersPage.closeAddModal()" class="btn btn-outline">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add User</button>
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

                        <div id="edit-entity-wrap" class="hidden">
                            <label class="block text-sm font-medium text-foreground mb-1">Assigned Entity</label>
                            <select name="entity" id="edit-entity">
                                <option value="" selected disabled>Select entity</option>
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
        _empApiUrl: '<?php echo htmlspecialchars(app_url('api/employee_search.php'), ENT_QUOTES, 'UTF-8'); ?>',

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
        },

        // ── Add User (employee-search flow) ──────────────────────────────
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

            const list = document.getElementById('emp-results-list');
            if (list) list.innerHTML = '';

            const results = document.getElementById('emp-search-results');
            if (results) results.classList.add('hidden');

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

            const results = document.getElementById('emp-search-results');
            if (results) results.classList.add('hidden');

            // Use exact employee_id lookup for all-digit input; free-text search otherwise.
            const isNumericId = /^\d+$/.test(query);
            const url = isNumericId
                ? this._empApiUrl + '?employee_id=' + encodeURIComponent(query)
                : this._empApiUrl + '?q=' + encodeURIComponent(query);

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
                row.className = 'flex items-center justify-between gap-3 p-2 rounded border cursor-pointer';
                row.style.cursor = 'pointer';

                const info = document.createElement('div');

                const nameLine = document.createElement('div');
                nameLine.className = 'text-sm font-medium';
                nameLine.textContent = emp.fullname || '—';

                const subLine = document.createElement('div');
                subLine.className = 'text-xs text-muted-foreground';
                subLine.textContent = [emp.employee_id, emp.department, emp.position]
                    .filter(v => v && String(v).trim())
                    .join(' · ');

                info.appendChild(nameLine);
                info.appendChild(subLine);

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline';
                btn.textContent = 'Select';

                row.appendChild(info);
                row.appendChild(btn);

                row.addEventListener('click', () => this.selectEmployee(emp));
                list.appendChild(row);
            });

            if (usingMock) {
                const notice = document.createElement('div');
                notice.className = 'alert alert-warning text-xs py-1 mt-2 mb-0';
                notice.textContent = '⚠ Development mode: data from local mock API.';
                list.appendChild(notice);
            }

            container.classList.remove('hidden');
        },

        // ── Role-conditional fields ───────────────────────────────────────
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

        // ── Edit modal ────────────────────────────────────────────────────
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

            const entitySelect = document.getElementById('edit-entity');
            if (entitySelect) entitySelect.value = user.entity || '';

            this.syncConditionalFields('edit');
            this.toggleModal('edit-user-modal');
        },

        closeEditModal() {
            const modal = document.getElementById('edit-user-modal');
            if (modal && !modal.classList.contains('hidden')) {
                this.toggleModal('edit-user-modal');
            }
        },

        // ── Delete modal ──────────────────────────────────────────────────
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

            const isSelf    = id > 0 && this.currentUserId === id;
            const warn      = document.getElementById('delete-self-warning');
            const submitBtn = document.getElementById('delete-user-submit');
            if (warn)      warn.classList.toggle('hidden', !isSelf);
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
