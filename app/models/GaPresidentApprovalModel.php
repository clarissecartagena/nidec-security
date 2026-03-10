<?php

class GaPresidentApprovalModel
{
    public function findReportByNo(string $reportNo): ?array
    {
        $row = db_fetch_one(
            'SELECT id, status, current_reviewer, responsible_department_id FROM reports WHERE report_no = ? LIMIT 1',
            's',
            [$reportNo]
        );

        return $row ?: null;
    }

    public function getPendingReports(?string $buildingFilter): array
    {
        $sql = "SELECT r.report_no, r.subject, r.category, r.severity, r.submitted_at,
                       r.location, r.details,
                       d.name AS department_name,
                       u.name AS ga_staff_reviewer
                FROM reports r
                JOIN departments d ON d.id = r.responsible_department_id
                LEFT JOIN ga_staff_reviews gasr ON gasr.report_id = r.id
                LEFT JOIN users u ON u.employee_no = gasr.reviewed_by
                WHERE r.status = 'submitted_to_ga_president'
                  AND r.current_reviewer = 'ga_president'";
        $params = [];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }
        $sql .= ' ORDER BY r.submitted_at DESC';

        return db_fetch_all($sql, '', $params);
    }

    public function approve(int $reportId, int $decidedBy, string $notes): void
    {
        db_execute(
            "UPDATE reports SET status = 'sent_to_department', current_reviewer = 'department' WHERE id = ?",
            'i',
            [$reportId]
        );

        db_execute(
            "INSERT INTO ga_president_approvals (report_id, decided_by, decision, notes, decided_at)
             VALUES (?, ?, 'approved', ?, NOW())
             ON DUPLICATE KEY UPDATE decided_by=VALUES(decided_by), decision=VALUES(decision), notes=VALUES(notes), decided_at=VALUES(decided_at)",
            'iis',
            [$reportId, $decidedBy, $notes]
        );

        db_execute(
            "INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at)
             VALUES (?, ?, ?, ?, NOW())",
            'isis',
            [$reportId, 'sent_to_department', $decidedBy, ($notes === '' ? 'Approved and sent to Department' : $notes)]
        );
    }

    public function returnToGaStaff(int $reportId, int $decidedBy, string $notes): void
    {
        // Preserve existing behavior (status remains submitted_to_ga_president but reviewer returns to GA Staff)
        db_execute(
            "UPDATE reports SET status = 'submitted_to_ga_president', current_reviewer = 'ga_staff' WHERE id = ?",
            'i',
            [$reportId]
        );

        db_execute(
            "INSERT INTO ga_president_approvals (report_id, decided_by, decision, notes, decided_at)
             VALUES (?, ?, 'returned', ?, NOW())
             ON DUPLICATE KEY UPDATE decided_by=VALUES(decided_by), decision=VALUES(decision), notes=VALUES(notes), decided_at=VALUES(decided_at)",
            'iis',
            [$reportId, $decidedBy, $notes]
        );

        db_execute(
            "INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at)
             VALUES (?, ?, ?, ?, NOW())",
            'isis',
            [$reportId, 'submitted_to_ga_president', $decidedBy, ($notes === '' ? 'Returned to GA Staff for revision' : $notes)]
        );
    }

    public function reject(int $reportId, int $decidedBy, string $notes): void
    {
        // Use the dedicated 'rejected' status — distinct from 'resolved' so
        // dashboards and queries can differentiate closed-clean from rejected.
        db_execute(
            "UPDATE reports SET status = 'rejected', current_reviewer = NULL WHERE id = ?",
            'i',
            [$reportId]
        );

        db_execute(
            "INSERT INTO ga_president_approvals (report_id, decided_by, decision, notes, decided_at)
             VALUES (?, ?, 'rejected', ?, NOW())
             ON DUPLICATE KEY UPDATE decided_by=VALUES(decided_by), decision=VALUES(decision), notes=VALUES(notes), decided_at=VALUES(decided_at)",
            'iis',
            [$reportId, $decidedBy, $notes]
        );

        db_execute(
            "INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at)
             VALUES (?, ?, ?, ?, NOW())",
            'isis',
            [$reportId, 'rejected', $decidedBy, ($notes === '' ? 'Rejected by GA President' : $notes)]
        );
    }
}
