<?php

require_once __DIR__ . '/../models/UsersModel.php';
require_once __DIR__ . '/EmployeeService.php';

class UsersService
{
    private UsersModel     $model;
    private EmployeeService $employeeService;

    public function __construct(
        ?UsersModel      $model           = null,
        ?EmployeeService $employeeService = null
    ) {
        $this->model           = $model           ?? new UsersModel();
        $this->employeeService = $employeeService ?? new EmployeeService();
    }

    /**
     * @param array<string, mixed> $post
     * @return array{flash:?string, flashType:string}
     */
    public function handlePost(array $post, int $currentUserId): array
    {
        $flash = null;
        $flashType = 'success';

        $action = (string)($post['action'] ?? '');
        $token = (string)($post['csrf_token'] ?? '');

        if (!csrf_validate($token)) {
            return [
                'flash' => 'Security check failed. Please refresh and try again.',
                'flashType' => 'error',
            ];
        }

        try {
            if ($action === 'add') {
                // ── Step 1: basic field extraction ──────────────────────────────
                $employeeId   = trim((string)($post['employee_id'] ?? ''));
                $username     = trim((string)($post['username'] ?? ''));
                $password     = (string)($post['password'] ?? '');
                $role         = (string)($post['role'] ?? '');
                $departmentId = (int)($post['department_id'] ?? 0);
                $securityType = (string)($post['security_type'] ?? '');
                $building     = (string)($post['building'] ?? '');
                $accountStatus = 'active';

                if ($employeeId === '' || $username === '' || $password === ''
                    || !in_array($role, ['ga_staff', 'security', 'department'], true)
                ) {
                    throw new RuntimeException('Please fill in all required fields.');
                }

                // ── Step 2: verify employee via API (prevents POST spoofing) ────
                // Name, email, department, and position are ALWAYS sourced from
                // the API result — never from the submitted form fields.
                $empResult = $this->employeeService->getEmployee($employeeId);
                if (!$empResult['success']) {
                    throw new RuntimeException(
                        'Employee verification failed: '
                        . ($empResult['error'] ?? 'Employee not found.')
                    );
                }
                $emp = $empResult['employee'];

                // ── Step 3: role-specific validation ────────────────────────────
                if ($role === 'department') {
                    if ($departmentId <= 0) throw new RuntimeException('Please select a department.');
                    $securityType = '';
                    $building     = '';
                } elseif ($role === 'security') {
                    if (!in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a security type (internal/external).');
                    }
                    if (!in_array($building, ['NCFL', 'NPFL'], true)) {
                        throw new RuntimeException('Please select an assigned building (NCFL/NPFL).');
                    }
                    $departmentId = 0;
                } else {
                    $departmentId = 0;
                    $securityType = '';
                    $building     = '';
                }

                // ── Step 4: persist ─────────────────────────────────────────────
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $this->model->insertUser(
                    $emp['employee_id'],
                    $emp['fullname'],
                    $emp['email'],
                    $emp['position'],
                    $username,
                    $hash,
                    $role,
                    $securityType,
                    $building,
                    $departmentId,
                    $accountStatus
                );

                $flash = 'User added successfully.';
            } elseif ($action === 'update') {
                $id = (int)($post['id'] ?? 0);
                $name = trim((string)($post['name'] ?? ''));
                $username = trim((string)($post['username'] ?? ''));
                $password = (string)($post['password'] ?? '');
                $role = (string)($post['role'] ?? '');
                $departmentId = (int)($post['department_id'] ?? 0);
                $accountStatus = (string)($post['account_status'] ?? 'active');
                $securityType = (string)($post['security_type'] ?? '');
                $building = (string)($post['building'] ?? '');

                if ($id <= 0 || $name === '' || $username === '' || !in_array($role, ['ga_president', 'ga_staff', 'security', 'department'], true)) {
                    throw new RuntimeException('Invalid update request.');
                }

                if (!in_array($accountStatus, ['active', 'inactive'], true)) {
                    throw new RuntimeException('Invalid account status.');
                }

                // Prevent assigning GA President to someone else.
                $currentRole = $this->model->getUserRoleById($id);
                if ($currentRole === null || $currentRole === '') throw new RuntimeException('User not found.');
                if ($role === 'ga_president' && $currentRole !== 'ga_president') {
                    throw new RuntimeException('You cannot assign the GA President role.');
                }

                if ($role === 'department') {
                    if ($departmentId <= 0) throw new RuntimeException('Please select a department.');
                    $securityType = '';
                    $building = '';
                } elseif ($role === 'security') {
                    if (!in_array($securityType, ['internal', 'external'], true)) {
                        throw new RuntimeException('Please select a security type (internal/external).');
                    }
                    if (!in_array($building, ['NCFL', 'NPFL'], true)) {
                        throw new RuntimeException('Please select an assigned building (NCFL/NPFL).');
                    }
                    $departmentId = 0;
                } else {
                    $departmentId = 0;
                    $securityType = '';
                    $building = '';
                }

                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $this->model->updateUserWithPassword($id, $name, $username, $hash, $role, $securityType, $building, $departmentId, $accountStatus);
                } else {
                    $this->model->updateUserNoPassword($id, $name, $username, $role, $securityType, $building, $departmentId, $accountStatus);
                }

                $flash = 'User updated successfully.';
            } elseif ($action === 'delete') {
                $id = (int)($post['id'] ?? 0);
                if ($id <= 0) throw new RuntimeException('Invalid delete request.');
                if ($currentUserId === $id) throw new RuntimeException('You cannot delete your own account.');

                $this->model->deleteUserById($id);
                $flash = 'User deleted successfully.';
            } else {
                throw new RuntimeException('Unknown action.');
            }
        } catch (Throwable $e) {
            $flash = $e->getMessage();
            $flashType = 'error';
        }

        return ['flash' => $flash, 'flashType' => $flashType];
    }

    /** @return array<int, array<string, mixed>> */
    public function getAllUsers(): array
    {
        return $this->model->getAllUsers();
    }
}
