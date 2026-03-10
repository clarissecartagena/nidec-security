<?php

require_once __DIR__ . '/../models/GaStaffReviewModel.php';

class GaStaffReviewService
{
    private GaStaffReviewModel $model;

    public function __construct(?GaStaffReviewModel $model = null)
    {
        $this->model = $model ?: new GaStaffReviewModel();
    }

    public function handlePost(array $post, array $currentUser): array
    {
        $flash = null;
        $flashType = 'success';

        $action = $post['action'] ?? '';
        $reportNo = trim((string)($post['report_no'] ?? ''));
        $notes = trim((string)($post['notes'] ?? ''));
        $token = (string)($post['csrf_token'] ?? '');

        if (!csrf_validate($token)) {
            return ['flash' => 'Security check failed. Please refresh and try again.', 'flashType' => 'error'];
        }

        if (!in_array($action, ['forward', 'return'], true) || $reportNo === '') {
            return ['flash' => 'Invalid request.', 'flashType' => 'error'];
        }

        $reportRow = $this->model->findReportByNo($reportNo);
        if (!$reportRow) {
            return ['flash' => 'Report not found.', 'flashType' => 'error'];
        }

        if (($reportRow['status'] ?? '') !== 'submitted_to_ga_staff') {
            return ['flash' => 'This report is no longer waiting for GA Staff review.', 'flashType' => 'error'];
        }

        $reportId = (int)($reportRow['id'] ?? 0);
        $reviewedBy = (string)($currentUser['employee_no'] ?? '');

        $conn = db();
        $conn->beginTransaction();

        try {
            if ($action === 'forward') {
                $this->model->forwardToPresident($reportId, $reviewedBy, $notes);
                notify_role('ga_president', $reportId, 'Report Waiting for Final GA Approval');
                $flash = 'Report forwarded to GA President.';
                $flashType = 'success';
            } else {
                $this->model->returnToSecurity($reportId, $reviewedBy, $notes);
                notify_role('security', $reportId, 'Report Returned by GA Staff (Action Required)');
                $flash = 'Report returned to Security.';
                $flashType = 'success';
            }

            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $flash = 'Failed to update report. Please try again.';
            $flashType = 'error';
        }

        return ['flash' => $flash, 'flashType' => $flashType];
    }

    public function getPendingList(?string $buildingFilter): array
    {
        return $this->model->getPendingReports($buildingFilter);
    }
}
