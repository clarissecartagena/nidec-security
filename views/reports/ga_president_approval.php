<style>
/* ==========================================================================
   GA President Approval  Card Layout (mirrors GA Staff Review style)
   ========================================================================== */

.ga-pending-card {
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid hsl(var(--border));
    background: hsl(var(--card));
    border-radius: 12px;
    position: relative;
    overflow: hidden;
}

.ga-pending-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px -6px rgba(0,0,0,0.12);
    border-color: hsl(var(--primary));
}

.ga-pending-card .ga-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 6px;
}

.ga-pending-card .ga-reportno {
    font-family: var(--font-mono, monospace);
    font-size: 13px;
    font-weight: 700;
    color: hsl(var(--muted-foreground));
    letter-spacing: -0.01em;
}

.ga-pending-card .ga-title {
    font-size: 1.1rem;
    font-weight: 750;
    color: hsl(var(--foreground));
    line-height: 1.3;
    margin-bottom: 0;
}

.ga-pending-card .ga-info-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: hsl(var(--muted-foreground));
    margin-bottom: 3px;
}

.ga-pending-card .ga-info-value {
    font-size: 13.5px;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.ga-pending-card .ga-reviewer-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11.5px;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    background: hsl(var(--muted));
    border-radius: 20px;
    padding: 2px 10px;
}

.ga-pending-card .ga-summary {
    font-size: 13.5px;
    line-height: 1.6;
    color: hsl(var(--muted-foreground));
    margin-top: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ga-pending-card .ga-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-hourglass-split me-2 text-primary"></i>GA Pending Reports</h1>
                <p class="text-sm text-muted-foreground mb-0">Review reports pending your final approval</p>
            </div>
            <div class="d-flex align-items-center gap-2 align-self-end">
                <span class="text-xs text-muted-foreground">Building</span>
                <select id="building-filter" class="form-select form-select-sm" style="min-width: 160px;">
                    <option value="all"  <?php echo $selectedBuilding === 'all'  ? 'selected' : ''; ?>>All</option>
                    <option value="NCFL" <?php echo $selectedBuilding === 'NCFL' ? 'selected' : ''; ?>>NCFL</option>
                    <option value="NPFL" <?php echo $selectedBuilding === 'NPFL' ? 'selected' : ''; ?>>NPFL</option>
                </select>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'danger' : 'success'; ?> mb-4" role="alert">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pending)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted-foreground py-5">No reports waiting for your approval.</div>
            </div>
        <?php else: ?>
            <?php foreach ($pending as $r): ?>
                <?php
                    $sevRaw   = strtolower((string)($r['severity'] ?? ''));
                    if ($sevRaw === 'critical')     $sevBadge = 'badge--destructive';
                    elseif ($sevRaw === 'high')     $sevBadge = 'badge--warning';
                    elseif ($sevRaw === 'medium')   $sevBadge = 'badge--info';
                    else                            $sevBadge = 'badge--muted';
                    $reportNo = htmlspecialchars($r['report_no'] ?? '');
                    $details  = trim((string)($r['details'] ?? ''));
                    $summary  = $details !== '' ? (strlen($details) > 220 ? substr($details, 0, 220) . '...' : $details) : 'No details provided.';
                    $reviewer = trim((string)($r['ga_staff_reviewer'] ?? ''));
                ?>
                <div class="card shadow-sm mb-3 ga-pending-card"
                     onclick="ReportModal.open('<?php echo $reportNo; ?>')">
                    <div class="card-body p-3">

                        <!-- Top row: meta + actions -->
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                            <div class="flex-grow-1">
                                <div class="ga-meta">
                                    <span class="ga-reportno"><?php echo $reportNo; ?></span>
                                    <span class="badge <?php echo $sevBadge; ?>"><?php echo htmlspecialchars($r['severity'] ?? 'N/A'); ?></span>
                                    <span class="badge badge--warning">Pending Approval</span>
                                    <?php if ($reviewer !== ''): ?>
                                        <span class="ga-reviewer-chip">
                                            <i class="bi bi-person-check"></i>
                                            Reviewed by <?php echo htmlspecialchars($reviewer); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="ga-title"><?php echo htmlspecialchars($r['subject'] ?? 'Untitled'); ?></div>
                            </div>

                            <div class="ga-actions" onclick="event.stopPropagation();">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <input type="hidden" name="report_no"  value="<?php echo htmlspecialchars($r['report_no']); ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">
                                        <i class="bi bi-check-lg me-1"></i>Approve
                                    </button>
                                </form>
                                <button type="button" class="btn btn-outline btn-sm"
                                        data-return-report-no="<?php echo htmlspecialchars($r['report_no']); ?>"
                                        data-return-subject="<?php echo htmlspecialchars($r['subject']); ?>">
                                    <i class="bi bi-arrow-left me-1"></i>Return
                                </button>
                                <button type="button" class="btn btn-destructive btn-sm"
                                        data-reject-report-no="<?php echo htmlspecialchars($r['report_no']); ?>"
                                        data-reject-subject="<?php echo htmlspecialchars($r['subject']); ?>">
                                    <i class="bi bi-x-lg me-1"></i>Reject
                                </button>
                            </div>
                        </div>

                        <!-- Info grid -->
                        <div class="row g-2 mt-2">
                            <div class="col-6 col-md-3">
                                <div class="ga-info-label">Category</div>
                                <div class="ga-info-value"><?php echo htmlspecialchars($r['category'] ?? '---'); ?></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="ga-info-label">Location</div>
                                <div class="ga-info-value"><?php echo htmlspecialchars($r['location'] ?? '---'); ?></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="ga-info-label">Department</div>
                                <div class="ga-info-value"><?php echo htmlspecialchars($r['department_name'] ?? '---'); ?></div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="ga-info-label">Submitted</div>
                                <div class="ga-info-value"><?php echo !empty($r['submitted_at']) ? date('m/d/Y', strtotime($r['submitted_at'])) : '---'; ?></div>
                            </div>
                        </div>

                        <hr class="my-3">
                        <p class="ga-summary"><?php echo htmlspecialchars($summary); ?></p>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<div id="ga-action-overlay" class="modal-overlay hidden" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ga-reject-title">
        <div class="modal-header">
            <h2 id="ga-reject-title" class="text-lg font-semibold text-foreground">Action</h2>
            <p id="ga-action-subtitle" class="text-sm text-muted-foreground mt-1">Please provide a reason.</p>
        </div>
        <div class="modal-section" style="margin-bottom: 0.75rem;">
            <div class="text-xs text-muted-foreground" id="ga-reject-meta"></div>
        </div>
        <label class="text-sm font-medium text-foreground" for="ga-reject-reason">Reason</label>
        <textarea id="ga-reject-reason" class="form-control mt-2" placeholder="Type the reason..." maxlength="500" style="resize: vertical; min-height: 100px;" required></textarea>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline btn-sm" id="ga-reject-cancel">Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" id="ga-action-submit">Submit</button>
        </div>
    </div>
</div>

<form id="ga-action-form" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
    <input type="hidden" name="action" value="" />
    <input type="hidden" name="report_no" value="" />
    <input type="hidden" name="notes" value="" />
</form>

<script>
(() => {
    const overlay = document.getElementById('ga-action-overlay');
    const meta = document.getElementById('ga-reject-meta');
    const reason = document.getElementById('ga-reject-reason');
    const cancelBtn = document.getElementById('ga-reject-cancel');
    const submitBtn = document.getElementById('ga-action-submit');
    const subtitle = document.getElementById('ga-action-subtitle');
    const title = document.getElementById('ga-reject-title');
    const form = document.getElementById('ga-action-form');

    if (!overlay || !reason || !cancelBtn || !submitBtn || !form) return;

    let currentReportNo = '';
    let currentSubject = '';
    let currentAction = '';

    function openModal(action, reportNo, subject) {
        currentAction = action;
        currentReportNo = reportNo;
        currentSubject = subject;

        if (action === 'return') {
            title.textContent = 'Return Report';
            subtitle.textContent = 'Explain why this needs revision by GA Staff.';
            submitBtn.textContent = 'Confirm Return';
            submitBtn.className = 'btn btn-primary btn-sm';
        } else {
            title.textContent = 'Reject Report';
            subtitle.textContent = 'Explain why this report is being rejected.';
            submitBtn.textContent = 'Confirm Reject';
            submitBtn.className = 'btn btn-destructive btn-sm';
        }

        meta.textContent = `Report: ${reportNo} - ${subject}`;
        reason.value = '';
        overlay.classList.remove('hidden');
        overlay.classList.add('active');
        setTimeout(() => reason.focus(), 50);
    }

    function closeModal() {
        overlay.classList.remove('active');
        overlay.classList.add('hidden');
    }

    document.querySelectorAll('[data-reject-report-no]').forEach(btn => {
        btn.addEventListener('click', () => {
            openModal('reject', btn.getAttribute('data-reject-report-no'), btn.getAttribute('data-reject-subject'));
        });
    });

    document.querySelectorAll('[data-return-report-no]').forEach(btn => {
        btn.addEventListener('click', () => {
            openModal('return', btn.getAttribute('data-return-report-no'), btn.getAttribute('data-return-subject'));
        });
    });

    cancelBtn.addEventListener('click', closeModal);

    submitBtn.addEventListener('click', () => {
        const val = reason.value.trim();
        if (!val) return alert('Reason is required');
        form.querySelector('[name="action"]').value = currentAction;
        form.querySelector('[name="report_no"]').value = currentReportNo;
        form.querySelector('[name="notes"]').value = val;
        form.submit();
    });
})();
</script>

<script>
    (function () {
        const el = document.getElementById('building-filter');
        if (!el) return;
        el.addEventListener('change', () => {
            const val = el.value;
            const url = new URL(window.location.href);
            if (val === 'all') url.searchParams.delete('building');
            else url.searchParams.set('building', val);
            window.location.href = url.toString();
        });
    })();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
