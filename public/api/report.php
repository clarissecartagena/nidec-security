<?php

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

function safe_public_rel_path(?string $rel): ?string {
    if (!$rel) return null;
    $rel = str_replace('\\', '/', trim((string)$rel));
    $rel = ltrim($rel, '/');
    if ($rel === '') return null;
    if (str_contains($rel, '..')) return null;
    return $rel;
}

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = trim($_GET['id'] ?? '');
if ($id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id']);
    exit;
}

$user = getUser();
$role = (string)($user['role'] ?? '');
$userBuilding = normalize_building($user['building'] ?? null);
$userDepartmentId = (int)($user['department_id'] ?? 0);

$whereExtra = '';
$params = [$id];
if ($role === 'security') {
    if (!$userBuilding) {
        http_response_code(403);
        echo json_encode(['error' => 'Account is missing an assigned building']);
        exit;
    }
    $whereExtra = ' AND r.building = ?';
    $params[] = $userBuilding;
} elseif ($role === 'department') {
    if ($userDepartmentId <= 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Account is missing an assigned department']);
        exit;
    }
    $whereExtra = ' AND r.responsible_department_id = ?';
    $params[] = $userDepartmentId;
}

$sql = "SELECT
        r.id,
        r.report_no,
        r.subject,
        r.category,
        r.location,
        r.severity,
        r.building,
        r.responsible_department_id,
        r.details,
        r.actions_taken,
        r.remarks,
        r.evidence_image_path,
        r.security_remarks,
        r.resolved_at,
        r.returned_at,
        r.status,
        r.fix_due_date,
        r.submitted_at,
        u_submit.name          AS submitted_by_name,
        u_submit.security_type AS submitted_by_security_type,
        d.name AS department_name,
        gasr.reviewed_at,
        gasr.notes AS ga_staff_notes,
        u_staff.name AS ga_staff_reviewer,
        gapa.decided_at,
        gapa.decision AS ga_president_decision,
        gapa.notes AS ga_president_notes,
        u_pres.name AS ga_president_name,
        da.action_type,
        da.timeline_days,
        da.timeline_start,
        da.timeline_due,
        da.remarks AS dept_remarks,
        da.evidence_image_path AS dept_evidence_image_path,
        da.acted_at AS dept_acted_at,
        u_dept.name AS dept_acted_by,
        sfc.decision AS final_decision,
        sfc.remarks AS final_remarks,
        sfc.checked_at AS final_checked_at,
        u_sec.name AS final_checked_by,
        sfc.closed_at
     FROM reports r
     JOIN departments d ON d.id = r.responsible_department_id
    LEFT JOIN users u_submit ON u_submit.employee_no = r.submitted_by
    LEFT JOIN ga_staff_reviews gasr ON gasr.report_id = r.id
    LEFT JOIN users u_staff ON u_staff.employee_no = gasr.reviewed_by
    LEFT JOIN ga_president_approvals gapa ON gapa.report_id = r.id
    LEFT JOIN users u_pres ON u_pres.employee_no = gapa.decided_by
    LEFT JOIN department_actions da ON da.report_id = r.id
    LEFT JOIN users u_dept ON u_dept.employee_no = da.acted_by
    LEFT JOIN security_final_checks sfc ON sfc.report_id = r.id
    LEFT JOIN users u_sec ON u_sec.employee_no = sfc.checked_by
    WHERE r.report_no = ?";

$sql .= $whereExtra . " LIMIT 1";

$report = db_fetch_one($sql, '', $params);

if (!$report) {
    http_response_code(404);
    echo json_encode(['error' => 'Report not found']);
    exit;
}

$evidenceUrl = null;
if (!empty($report['evidence_image_path'])) {
    $rel = safe_public_rel_path($report['evidence_image_path']);
    $evidenceUrl = $rel ? app_url($rel) : null;
}

$deptEvidenceUrl = null;
if (!empty($report['dept_evidence_image_path'])) {
    $rel = safe_public_rel_path($report['dept_evidence_image_path']);
    $deptEvidenceUrl = $rel ? app_url($rel) : null;
}

$attachments = db_fetch_all(
    'SELECT file_name, file_path, mime_type, file_size_bytes, uploaded_at FROM report_attachments WHERE report_id = ? ORDER BY uploaded_at ASC',
    'i',
    [(int)$report['id']]
);

$attachmentOut = [];
foreach ($attachments as $a) {
    $pathRel = safe_public_rel_path($a['file_path'] ?? null);
    $url = $pathRel ? app_url($pathRel) : null;
    $attachmentOut[] = [
        'file_name'       => $a['file_name'],
        'file_path'       => $a['file_path'],
        'url'             => $url,
        'mime_type'       => $a['mime_type'],
        'file_size_bytes' => $a['file_size_bytes'] !== null ? (int)$a['file_size_bytes'] : null,
        'uploaded_at'     => $a['uploaded_at'],
    ];
}

// Normalise security_type: must be exactly 'internal' or 'external'
$rawSecType = strtolower(trim((string)($report['submitted_by_security_type'] ?? '')));
$securityType = in_array($rawSecType, ['internal', 'external'], true) ? $rawSecType : 'internal';

$out = [
    // Keep legacy "id" as the human-friendly Report No (used widely by ReportModal)
    'id'                 => $report['report_no'],
    // Numeric DB PK - used for print_report.php?report_id=XX
    'reportId'           => (int)$report['id'],
    'reportNo'           => $report['report_no'],
    'subject'            => $report['subject'],
    'category'           => $report['category'],
    'location'           => $report['location'],
    'severity'           => $report['severity'],
    'building'           => $report['building'],
    'departmentId'       => (int)$report['responsible_department_id'],
    'department'         => $report['department_name'],
    'details'            => $report['details'],
    'actionsTaken'       => $report['actions_taken'],
    'remarks'            => $report['remarks'],
    'evidenceImageUrl'   => $evidenceUrl,
    'attachments'        => $attachmentOut,
    'status'             => $report['status'],
    'submittedAt'        => $report['submitted_at'],
    'submittedBy'        => $report['submitted_by_name'],
    'securityType'       => $securityType,   // ← THE MISSING FIELD

    'securityRemarks'    => $report['security_remarks'],
    'resolvedAt'         => $report['resolved_at'],
    'returnedAt'         => $report['returned_at'],

    'reviewedBy'         => $report['ga_staff_reviewer'],
    'reviewedAt'         => $report['reviewed_at'],
    'gaStaffNotes'       => $report['ga_staff_notes'],

    'approvedBy'         => $report['ga_president_name'],
    'approvedAt'         => $report['decided_at'],
    'gaPresidentDecision'=> $report['ga_president_decision'],
    'gaPresidentNotes'   => $report['ga_president_notes'],

    'timeline_days'      => $report['timeline_days'],
    'timeline_start'     => $report['timeline_start'],
    'timeline_due'       => $report['timeline_due'] ?? $report['fix_due_date'],
    'dept_action'        => $report['action_type'],
    'dept_remarks'       => $report['dept_remarks'],
    'deptActedAt'        => $report['dept_acted_at'],
    'deptActedBy'        => $report['dept_acted_by'],
    'deptEvidenceImageUrl' => $deptEvidenceUrl,

    'finalCheckedBy'     => $report['final_checked_by'],
    'finalCheckedAt'     => $report['final_checked_at'],
    'finalDecision'      => $report['final_decision'],
    'finalRemarks'       => $report['final_remarks'],
    'closedAt'           => $report['closed_at'],
];

echo json_encode($out);