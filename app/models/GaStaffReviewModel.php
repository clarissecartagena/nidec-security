<?php

class GaStaffReviewModel
{
    public function findReportByNo(string $reportNo): ?array
    {
        $row = db_fetch_one(
            'SELECT id, status, current_reviewer FROM reports WHERE report_no = ? LIMIT 1',
            's',
            [$reportNo]
        );

        return $row ?: null;
    }

    public function getPendingReports(?string $buildingFilter): array
    {
                $sql = "SELECT r.report_no, r.subject, r.category, r.location, r.details, r.severity, r.building,
                                             r.submitted_at, d.name AS department_name, u.name AS submitted_by_name
                                FROM reports r
                                JOIN departments d ON d.id = r.responsible_department_id
                                LEFT JOIN users u ON u.employee_no = r.submitted_by
                                WHERE r.status = 'submitted_to_ga_staff'
                                    AND r.current_reviewer = 'ga_staff'";
        $params = [];
        if ($buildingFilter) {
            $sql .= ' AND r.building = ?';
            $params[] = $buildingFilter;
        }
        $sql .= ' ORDER BY r.submitted_at DESC';

        return db_fetch_all($sql, '', $params);
    }

    public function forwardToPresident(int $reportId, int $reviewedBy, string $notes): void
    {
        db_execute(
            "UPDATE reports SET status = 'submitted_to_ga_president', current_reviewer = 'ga_president' WHERE id = ?",
            'i',
            [$reportId]
        );

        db_execute(
            "INSERT INTO ga_staff_reviews (report_id, reviewed_by, decision, notes, reviewed_at)
             VALUES (?, ?, 'forwarded', ?, NOW())
             ON DUPLICATE KEY UPDATE reviewed_by=VALUES(reviewed_by), decision=VALUES(decision), notes=VALUES(notes), reviewed_at=VALUES(reviewed_at)",
            'iis',
            [$reportId, $reviewedBy, $notes]
        );

        db_execute(
            'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at)
             VALUES (?, ?, ?, ?, NOW())',
            'isis',
            [$reportId, 'submitted_to_ga_president', $reviewedBy, ($notes === '' ? 'Forwarded to GA President' : $notes)]
        );
    }

    public function returnToSecurity(int $reportId, int $reviewedBy, string $notes): void
    {
        db_execute(
            "UPDATE reports SET status = 'submitted_to_ga_staff', current_reviewer = 'security' WHERE id = ?",
            'i',
            [$reportId]
        );

        db_execute(
            "INSERT INTO ga_staff_reviews (report_id, reviewed_by, decision, notes, reviewed_at)
             VALUES (?, ?, 'returned', ?, NOW())
             ON DUPLICATE KEY UPDATE reviewed_by=VALUES(reviewed_by), decision=VALUES(decision), notes=VALUES(notes), reviewed_at=VALUES(reviewed_at)",
            'iis',
            [$reportId, $reviewedBy, $notes]
        );

        db_execute(
            'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at)
             VALUES (?, ?, ?, ?, NOW())',
            'isis',
            [$reportId, 'submitted_to_ga_staff', $reviewedBy, ($notes === '' ? 'Returned to Security for completion' : $notes)]
        );
    }
}
