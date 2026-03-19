<style>
/* ---- Submit Report page overrides ---- */
.sr-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.625rem;
    margin-top: 0.75rem;
}
.sr-preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    background: hsl(var(--muted));
    border: 1px solid hsl(var(--border));
}
.sr-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.sr-preview-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(0,0,0,0.65);
    border: none;
    color: #fff;
    font-size: 13px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
    transition: background 0.15s;
}
.sr-preview-remove:hover { background: rgba(220,38,38,0.9); }
.sr-dropzone {
    border: 2px dashed hsl(var(--border));
    border-radius: 10px;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: hsl(var(--muted) / 0.35);
}
.sr-dropzone:hover, .sr-dropzone.drag-over {
    border-color: hsl(var(--success));
    background: hsl(var(--success) / 0.06);
}
.sr-dropzone i { font-size: 2rem; color: hsl(var(--muted-foreground)); display: block; margin-bottom: 0.4rem; }
.sr-empty-hint { font-size: 0.82rem; color: hsl(var(--muted-foreground)); margin-top: 0.35rem; }
.optional-tag {
    font-size: 0.72rem;
    font-weight: 400;
    color: hsl(var(--muted-foreground));
    margin-left: 0.3rem;
    font-style: italic;
}
</style>

<main class="main-content">
    <div class="animate-fade-in">

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> mb-4" role="alert">
                <?= htmlspecialchars($flash) ?>
            </div>
        <?php endif; ?>

        <?php if ($successReportNo): ?>
        <div class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
            <div class="text-center">
                <div class="rounded-circle bg-primary-10 mx-auto d-flex align-items-center justify-content-center mb-4" style="width: 64px; height: 64px;">
                    <i class="bi bi-send text-primary" aria-hidden="true" style="font-size: 28px;"></i>
                </div>
                <h2 class="h5 fw-bold text-foreground mb-2">Report Submitted</h2>
                <p class="text-sm text-muted-foreground mb-0">Your report has been sent to General Affairs Staff for review.</p>
                <p class="text-xs text-muted-foreground mt-2 mb-0">Report ID: <span class="font-mono"><?= htmlspecialchars($successReportNo) ?></span></p>
            </div>
        </div>
        <?php else: ?>

        <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <!-- Page heading lives inside the centred column so it aligns with the cards -->
            <div class="mb-4">
                <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-send-fill me-2 text-primary"></i>Submit Security Report</h1>
                <p class="text-sm text-muted-foreground mb-0">Create a new security incident report</p>
            </div>

        <form id="submit-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />

            <!-- ── Incident Details ── -->
            <div class="section-card section-accent-info mb-4">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
                    <div>
                        <h2 class="h6 fw-bold text-foreground mb-1">Incident Details</h2>
                        <p class="text-sm text-muted-foreground mb-0">Fill in the key incident information to route the report correctly.</p>
                    </div>
                    <span class="badge badge--info">Security Report</span>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="report-type" class="form-label">Report Type <span class="text-danger">*</span></label>
                        <select id="report-type" name="security_type" class="form-select" required>
                            <option value="" disabled selected>Select report type</option>
                            <option value="internal" <?= (($_POST['security_type'] ?? '') === 'internal') ? 'selected' : '' ?>>Internal</option>
                            <option value="external" <?= (($_POST['security_type'] ?? '') === 'external') ? 'selected' : '' ?>>External</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="building" class="form-label">Entity / Building <span class="text-danger">*</span></label>
                        <select id="building" name="building" class="form-select" required>
                            <option value="" disabled selected>Select entity</option>
                            <option value="NCFL" <?= (($_POST['building'] ?? '') === 'NCFL') ? 'selected' : '' ?>>NCFL</option>
                            <option value="NPFL" <?= (($_POST['building'] ?? '') === 'NPFL') ? 'selected' : '' ?>>NPFL</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" id="subject" name="subject" required
                            placeholder="Brief description of the incident"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" />
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <select id="category" name="category" class="form-select" required>
                            <option value="">Select category</option>
                            <?php foreach ($reportCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                        <input type="text" id="location" name="location" required
                            placeholder="e.g. Building A - 2nd Floor"
                            class="form-control"
                            value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" />
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="severity" class="form-label">Severity Level <span class="text-danger">*</span></label>
                        <select id="severity" name="severity" class="form-select" required>
                            <?php foreach ($severityLevels as $level): ?>
                            <option value="<?= $level ?>" <?= (($_POST['severity'] ?? 'medium') === $level) ? 'selected' : '' ?>><?= ucfirst($level) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                        <select id="department" name="department_id" class="form-select" required>
                            <option value="">Select department</option>
                            <?php foreach (($departmentsDb ?? []) as $dept): ?>
                            <option value="<?= (int)$dept['id'] ?>" <?= ((int)($_POST['department_id'] ?? 0) === (int)$dept['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ── Narrative ── -->
            <div class="section-card section-accent-primary mb-4">
                <div class="mb-3">
                    <h2 class="h6 fw-bold text-foreground mb-1">Narrative</h2>
                    <p class="text-sm text-muted-foreground mb-0">Describe what happened and what has already been done.</p>
                </div>

                <div class="d-grid gap-3">
                    <div>
                        <label for="details" class="form-label">Full Details <span class="text-danger">*</span></label>
                        <textarea id="details" name="details" required rows="4" class="form-control"
                            placeholder="Provide a detailed description of the incident..."><?= htmlspecialchars($_POST['details'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="actions-taken" class="form-label">Actions Taken <span class="optional-tag">(Optional)</span></label>
                            <textarea id="actions-taken" name="actions_taken" rows="3" class="form-control"
                                placeholder="Describe actions already taken..."><?= htmlspecialchars($_POST['actions_taken'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="remarks" class="form-label">Remarks <span class="optional-tag">(Optional)</span></label>
                            <textarea id="remarks" name="remarks" rows="3" class="form-control"
                                placeholder="Any additional remarks..."><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="assessment" class="form-label">Assessment <span class="optional-tag">(Optional)</span></label>
                            <textarea id="assessment" name="assessment" rows="3" class="form-control"
                                placeholder="Your assessment of the situation..."><?= htmlspecialchars($_POST['assessment'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="recommendations" class="form-label">Recommendations <span class="optional-tag">(Optional)</span></label>
                            <textarea id="recommendations" name="recommendations" rows="3" class="form-control"
                                placeholder="Recommended corrective actions..."><?= htmlspecialchars($_POST['recommendations'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Attachments ── -->
            <div class="section-card section-accent-success">
                <div class="mb-3">
                    <h2 class="h6 fw-bold text-foreground mb-1">Attachments <span class="optional-tag" style="font-size:0.78rem;">(Optional)</span></h2>
                    <p class="text-sm text-muted-foreground mb-0">Upload evidence images — PNG or JPG, up to 10 MB each. Multiple files allowed.</p>
                </div>

                <!-- Hidden real input -->
                <input id="evidence" name="evidence[]" type="file" accept="image/png,image/jpeg" multiple style="display:none;" />

                <!-- Drop zone -->
                <div id="evidence-dropzone" class="sr-dropzone">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <p class="text-sm text-muted-foreground mb-0">Click to browse or drag &amp; drop images here</p>
                    <p class="sr-empty-hint">PNG, JPG &mdash; up to 10 MB each</p>
                </div>

                <!-- Preview grid -->
                <div id="sr-preview-grid" class="sr-preview-grid" style="display:none;"></div>

                <div class="d-flex align-items-center justify-content-between pt-3 mt-1 gap-3 flex-wrap">
                    <p id="sr-file-count" class="text-xs text-muted-foreground mb-0"></p>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-send" aria-hidden="true"></i>
                        Submit Report
                    </button>
                </div>
            </div>
        </form>
        </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
(function () {
    'use strict';

    const input    = document.getElementById('evidence');
    const dropzone = document.getElementById('evidence-dropzone');
    const grid     = document.getElementById('sr-preview-grid');
    const counter  = document.getElementById('sr-file-count');
    if (!input || !dropzone || !grid || !counter) return;

    /** Master list of File objects currently queued */
    let fileList = [];

    /** Push the fileList back into the real <input> */
    function syncInput() {
        try {
            const dt = new DataTransfer();
            fileList.forEach(f => dt.items.add(f));
            input.files = dt.files;
        } catch (e) { /* Safari fallback — grid is still visual-only */ }
    }

    /** Rebuild the preview grid from fileList */
    function renderGrid() {
        grid.innerHTML = '';

        if (!fileList.length) {
            grid.style.display = 'none';
            counter.textContent = '';
            return;
        }

        grid.style.display = 'grid';
        counter.textContent = fileList.length === 1
            ? '1 image selected'
            : fileList.length + ' images selected';

        fileList.forEach((file, idx) => {
            const item = document.createElement('div');
            item.className = 'sr-preview-item';

            const img = document.createElement('img');
            img.alt = file.name;
            const url = URL.createObjectURL(file);
            img.src = url;
            img.onload = () => URL.revokeObjectURL(url);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'sr-preview-remove';
            btn.title = 'Remove ' + file.name;
            btn.innerHTML = '<i class="bi bi-x"></i>';
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                fileList.splice(idx, 1);
                syncInput();
                renderGrid();
            });

            item.appendChild(img);
            item.appendChild(btn);
            grid.appendChild(item);
        });
    }

    /** Add new files, skip duplicates by name+size */
    function addFiles(newFiles) {
        newFiles.forEach(f => {
            const dup = fileList.some(x => x.name === f.name && x.size === f.size);
            if (!dup && (f.type === 'image/png' || f.type === 'image/jpeg')) {
                fileList.push(f);
            }
        });
        syncInput();
        renderGrid();
    }

    /* Click on zone → open file picker */
    dropzone.addEventListener('click', () => input.click());

    /* File picker change */
    input.addEventListener('change', () => {
        if (input.files && input.files.length) {
            addFiles(Array.from(input.files));
        }
    });

    /* Drag and drop */
    ['dragenter', 'dragover'].forEach(evt => {
        dropzone.addEventListener(evt, e => {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.add('drag-over');
        });
    });
    ['dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, e => {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.remove('drag-over');
        });
    });
    dropzone.addEventListener('drop', e => {
        const files = e.dataTransfer && e.dataTransfer.files
            ? Array.from(e.dataTransfer.files) : [];
        if (files.length) addFiles(files);
    });
}());
</script>
