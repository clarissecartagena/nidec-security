<?php

class SubmitReportModel
{
    public function isActiveDepartment(int $departmentId): bool
    {
        if ($departmentId <= 0) {
            return false;
        }

        $row = db_fetch_one(
            'SELECT id FROM departments WHERE id = ? AND is_active = 1 LIMIT 1',
            'i',
            [$departmentId]
        );

        return (bool)$row;
    }

    public function generateSecurityReportNo(): string
    {
        $year = date('Y');
        $prefix = 'SR-' . $year . '-';

        $row = db_fetch_one(
            'SELECT report_no FROM reports WHERE report_no LIKE ? ORDER BY report_no DESC LIMIT 1',
            's',
            [$prefix . '%']
        );

        $last = $row['report_no'] ?? null;
        $seq = 0;
        if ($last && preg_match('/^SR-' . preg_quote($year, '/') . '-(\d{4})$/', $last, $m)) {
            $seq = (int)$m[1];
        }
        $seq++;

        return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }

    public function insertReport(array $data): int
    {
        db_execute(
            "INSERT INTO reports (report_no, subject, category, location, severity, building, responsible_department_id, details, actions_taken, remarks, assessment, recommendations, submitted_by, status, current_reviewer, submitted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted_to_ga_staff', 'ga_staff', NOW())",
            'ssssssissssss',
            [
                $data['report_no'],
                $data['subject'],
                $data['category'],
                $data['location'],
                $data['severity'],
                $data['building'],
                (int)$data['department_id'],
                $data['details'],
                $data['actions_taken'],
                $data['remarks'],
                $data['assessment'],
                $data['recommendations'],
                (string)$data['submitted_by'],
            ]
        );

        return (int)db_last_insert_id();
    }

    public function insertStatusHistory(int $reportId, string $status, string $changedBy, string $notes): void
    {
        db_execute(
            'INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES (?, ?, ?, ?, NOW())',
            'isss',
            [$reportId, $status, $changedBy, $notes]
        );
    }

    public function insertAttachment(int $reportId, string $fileName, string $filePath, string $mimeType, int $fileSizeBytes, string $uploadedBy): void
    {
        db_execute(
            'INSERT INTO report_attachments (report_id, file_name, file_path, mime_type, file_size_bytes, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)',
            'isssis',
            [$reportId, $fileName, $filePath, $mimeType, $fileSizeBytes, $uploadedBy]
        );
    }

    public function updateEvidenceImagePath(int $reportId, string $evidencePath): void
    {
        db_execute('UPDATE reports SET evidence_image_path = ? WHERE id = ?', 'si', [$evidencePath, $reportId]);
    }
}
