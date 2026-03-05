<?php
// --- KEEP THESE HELPER FUNCTIONS ---
function getStatusBadge($status)
{
    $status = (string)($status ?? '');
    $variant = 'badge--muted';
    if (in_array($status, ['submitted_to_ga_staff', 'submitted_to_ga_president'], true)) {
        $variant = 'badge--warning';
    } elseif (in_array($status, ['ga_staff_reviewed', 'sent_to_department', 'under_department_fix'], true)) {
        $variant = 'badge--info';
    } elseif ($status === 'for_security_final_check') {
        $variant = 'badge--primary';
    } elseif ($status === 'returned_to_department') {
        $variant = 'badge--destructive';
    } elseif (in_array($status, ['approved_by_ga_president', 'resolved'], true)) {
        $variant = 'badge--success';
    }
    return '<span class="badge ' . $variant . '">' . htmlspecialchars(report_status_label($status)) . '</span>';
}

function getSeverityBadge($severity)
{
    $sev = strtolower((string)($severity ?? ''));
    $variant = 'badge--muted';
    if ($sev === 'critical') $variant = 'badge--destructive';
    elseif ($sev === 'high') $variant = 'badge--warning';
    elseif ($sev === 'medium') $variant = 'badge--info';
    elseif ($sev === 'low') $variant = 'badge--muted';
    return '<span class="badge ' . $variant . '">' . htmlspecialchars(severity_label($sev)) . '</span>';
}

function formatDate($dateString)
{
    return date('M d, Y', strtotime($dateString));
}
?>

<style>
    /* Force the table layout */
    #reports-table {
        table-layout: fixed;
        width: 100%;
        border-collapse: collapse;
    }

    /* Target BOTH headers (th) and data (td) */
    #reports-table th,
    #reports-table td {
        text-align: center;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        padding: 12px 8px;
    }

    /* NEW: This forces the Subject column data to align left */
    #reports-table td.subject-column {
        text-align: left !important;
        padding-left: 15px !important;
    }

    /* Optional: Header styling to make titles stand out */
    #reports-table th {
        background-color: var(--muted);
        font-weight: 600;
    }

    /* Column widths */
    .col-id { width: 12%; }
    .col-subject { width: 30%; }
    .col-cat { width: 12%; }
    .col-sev { width: 10%; }
    .col-dept { width: 14%; }
    .col-status { width: 12%; }
    .col-date { width: 10%; }
</style>

<main class="main-content">
    <div class="animate-fade-in">
        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>All Reports</h1>
            <p class="text-sm text-muted-foreground mb-0">View and manage all security reports</p>
        </div>

        <div class="row g-3 align-items-end mb-4">
            <div class="col-12 col-lg-6">
                <label class="form-label text-xs text-muted-foreground mb-1" for="search-input">Search</label>
                <div class="input-group">
                    <span class="input-group-text" aria-hidden="true">
                        <i class="bi bi-search"></i>
                    </span>
                    <input
                        type="text"
                        id="search-input"
                        placeholder="Search reports..."
                        class="form-control"
                    />
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label text-xs text-muted-foreground mb-1" for="building-filter">Building</label>
                <select id="building-filter" class="form-select">
                    <option value="all" <?php echo $selectedBuilding === 'all' ? 'selected' : ''; ?>>All Buildings</option>
                    <option value="NCFL" <?php echo $selectedBuilding === 'NCFL' ? 'selected' : ''; ?>>NCFL</option>
                    <option value="NPFL" <?php echo $selectedBuilding === 'NPFL' ? 'selected' : ''; ?>>NPFL</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label text-xs text-muted-foreground mb-1" for="status-filter">Status</label>
                <select id="status-filter" class="form-select">
                    <option value="all">All Status</option>
                    <option value="submitted_to_ga_staff">Submitted to GA Staff</option>
                    <option value="ga_staff_reviewed">GA Staff Reviewed</option>
                    <option value="submitted_to_ga_president">Waiting GA President</option>
                    <option value="sent_to_department">Sent to Department</option>
                    <option value="under_department_fix">Under Department Fix</option>
                    <option value="for_security_final_check">Security Final Check</option>
                    <option value="returned_to_department">Returned to Department</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
        </div>

        <div class="table-container table-card" style="--table-accent: var(--primary)">
            <div class="p-3 border-b d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="font-semibold text-foreground">Reports</h3>
                    <p class="text-xs text-muted-foreground">Click a row to view details</p>
                </div>
                <div class="text-xs text-muted-foreground">Total: <?php echo (int)count($reports); ?></div>
            </div>
            <div class="table-responsive">
            <table id="reports-table" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 10%;">Report ID</th>
                        <th style="width: 20%;">Subject</th>
                        <th style="width: 13%;">Category</th>
                        <th style="width: 8%;">Severity</th>
                        <th style="width: 15%;">Department</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 10%;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="7" class="text-center text-muted-foreground">No reports found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr data-status="<?php echo htmlspecialchars($report['status']); ?>" class="clickable-row" onclick="ReportModal.open('<?php echo htmlspecialchars($report['report_no']); ?>')">
                                <td class="font-mono text-xs font-medium"><?php echo htmlspecialchars($report['report_no']); ?></td>
                                <td class="font-medium subject-column text-truncate" style="max-width: 360px;">
                                    <?php echo htmlspecialchars($report['subject']); ?>
                                </td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($report['category']); ?></td>
                                <td><?php echo getSeverityBadge($report['severity']); ?></td>
                                <td class="text-muted-foreground"><?php echo htmlspecialchars($report['department_name']); ?></td>
                                <td><?php echo getStatusBadge($report['status']); ?></td>
                                <td class="text-muted-foreground text-xs"><?php echo formatDate($report['submitted_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <div class="p-3 border-t d-flex align-items-center justify-content-between gap-2 flex-wrap bg-body-tertiary">
                <div class="text-xs text-muted-foreground">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalReports); ?> of <?php echo $totalReports; ?> reports
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page - 1])); ?>" class="btn btn-outline-secondary btn-sm">Previous</a>
                    <?php endif; ?>

                    <?php
                    // Simple page number display
                    for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == 1 || $i == $totalPages || ($i >= $page - 1 && $i <= $page + 1)):
                            ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $i])); ?>"
                               class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['p' => $page + 1])); ?>" class="btn btn-outline-secondary btn-sm">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

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
