<?php
/**
 * My Profile page
 * - Users can update their email address
 * - Users can upload a signature image (one-time, permanent)
 */
$roleLabels = [
    'security'     => 'Security Officer',
    'ga_staff'     => 'General Affairs Staff',
    'ga_president' => 'General Affairs President',
    'department'   => 'Department',
];
$userRoleLabel = $roleLabels[$dbUser['role'] ?? ''] ?? ucfirst(str_replace('_', ' ', $dbUser['role'] ?? ''));
$hasSignature  = !empty($dbUser['signature_path']);
$signatureUrl  = $hasSignature ? htmlspecialchars(app_url($dbUser['signature_path'])) : '';
?>

<main class="main-content">
    <div class="animate-fade-in">

        <div class="mb-4">
            <h1 class="h4 fw-bold text-foreground mb-1">
                <i class="bi bi-person-circle me-2 text-primary"></i>My Profile
            </h1>
            <p class="text-sm text-muted-foreground mb-0">
                Manage your account information and signature.
            </p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flashType === 'error' ? 'danger' : 'success'; ?> mb-4" role="alert">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- ── Account Info Card ───────────────────────────────────────── -->
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person me-2"></i>Account Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo htmlspecialchars(app_url('profile.php')); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>" />
                            <input type="hidden" name="action" value="update_email" />

                            <div class="mb-3">
                                <label class="form-label fw-medium">Full Name</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($dbUser['name'] ?? ''); ?>"
                                       disabled readonly />
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Username</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($dbUser['username'] ?? ''); ?>"
                                       disabled readonly />
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Role</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($userRoleLabel); ?>"
                                       disabled readonly />
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium" for="profile-email">
                                    Email Address
                                </label>
                                <input type="email" id="profile-email" name="email"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($dbUser['email'] ?? ''); ?>"
                                       placeholder="your@email.com"
                                       required />
                                <div class="form-text">This email may be shown on PDF reports.</div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Save Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Signature Card ──────────────────────────────────────────── -->
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-pen me-2"></i>Signature
                        </h5>
                    </div>
                    <div class="card-body">

                        <?php if ($hasSignature): ?>
                            <!-- Signature already uploaded — read-only -->
                            <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
                                <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                                <div>
                                    Your signature has been uploaded. It will appear on PDF reports when you pass a report to the next person.
                                    <strong>Signatures cannot be changed once set.</strong>
                                </div>
                            </div>
                            <div class="border rounded p-3 bg-body-tertiary d-flex flex-column align-items-center gap-2"
                                 style="min-height:120px;">
                                <p class="text-xs text-muted-foreground mb-1">Preview — how it looks above your name:</p>
                                <img src="<?php echo $signatureUrl; ?>"
                                     alt="Your signature"
                                     style="max-height:80px; max-width:220px; object-fit:contain;"
                                     class="mb-1" />
                                <span class="fw-semibold text-foreground" style="font-size:0.9rem; letter-spacing:0.05em;">
                                    <?php echo htmlspecialchars(strtoupper($dbUser['name'] ?? '')); ?>
                                </span>
                            </div>

                        <?php else: ?>
                            <!-- No signature yet — show upload button -->
                            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                                <div>
                                    You have not uploaded a signature yet. Your signature will appear above your name on PDF reports
                                    once you have forwarded or approved a report.
                                    <strong>This is a one-time upload and cannot be changed.</strong>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-outline-primary"
                                        onclick="ProfilePage.openSignatureModal()">
                                    <i class="bi bi-upload me-2"></i>Upload Signature
                                </button>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div><!-- /row -->
    </div>
</main>

<!-- ── Signature Upload Modal ──────────────────────────────────────────────── -->
<div id="signature-modal" class="modal-backdrop hidden" role="dialog" aria-modal="true"
     aria-labelledby="sig-modal-title" style="z-index:1050;">
    <div class="modal-dialog" style="max-width:520px;">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="sig-modal-title" class="modal-title">
                    <i class="bi bi-pen me-2"></i>Upload Signature
                </h5>
                <button type="button" class="btn-close" onclick="ProfilePage.closeSignatureModal()"
                        aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Instructions -->
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Important — please read before uploading:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Remove the background of your signature image before uploading (use a transparent PNG).</li>
                            <li>Accepted formats: PNG, JPG, GIF, WebP (max 2 MB).</li>
                            <li><strong>This upload is final and cannot be changed once confirmed.</strong></li>
                        </ul>
                    </div>
                </div>

                <!-- File picker -->
                <div class="mb-3">
                    <label class="form-label fw-medium" for="sig-file-input">
                        Select signature image
                    </label>
                    <input type="file" id="sig-file-input" class="form-control"
                           accept="image/png,image/jpeg,image/gif,image/webp"
                           onchange="ProfilePage.previewSignature(this)" />
                </div>

                <!-- Preview card -->
                <div id="sig-preview-wrap" class="hidden mb-3">
                    <p class="text-sm text-muted-foreground mb-2">Preview — how your signature will look on the report:</p>
                    <div class="border rounded p-3 bg-body-tertiary d-flex flex-column align-items-center gap-1"
                         style="min-height:110px;">
                        <img id="sig-preview-img" src="" alt="Signature preview"
                             style="max-height:80px; max-width:220px; object-fit:contain;" />
                        <span class="fw-semibold text-foreground mt-1" style="font-size:0.9rem; letter-spacing:0.05em;">
                            <?php echo htmlspecialchars(strtoupper($dbUser['name'] ?? '')); ?>
                        </span>
                    </div>
                </div>

                <!-- Confirmation checkbox -->
                <div id="sig-confirm-wrap" class="hidden form-check mb-2">
                    <input type="checkbox" id="sig-confirm-check" class="form-check-input"
                           onchange="ProfilePage.toggleUploadBtn()" />
                    <label class="form-check-label text-sm" for="sig-confirm-check">
                        I understand this signature cannot be changed once uploaded.
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        onclick="ProfilePage.closeSignatureModal()">Cancel</button>
                <button type="button" id="sig-upload-btn" class="btn btn-primary" disabled
                        onclick="ProfilePage.submitSignature()">
                    <i class="bi bi-check-circle me-1"></i>Confirm &amp; Upload
                </button>
            </div>

        </div>
    </div>
</div>

<style>
.modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.5);
    display: flex; align-items: center; justify-content: center;
    padding: 1rem;
}
.modal-backdrop.hidden { display: none; }
</style>

<script>
const ProfilePage = {
    openSignatureModal() {
        document.getElementById('sig-file-input').value = '';
        document.getElementById('sig-preview-wrap').classList.add('hidden');
        document.getElementById('sig-confirm-wrap').classList.add('hidden');
        document.getElementById('sig-confirm-check').checked = false;
        document.getElementById('sig-upload-btn').disabled = true;
        document.getElementById('signature-modal').classList.remove('hidden');
    },

    closeSignatureModal() {
        document.getElementById('signature-modal').classList.add('hidden');
    },

    previewSignature(input) {
        const file = input.files[0];
        const previewWrap = document.getElementById('sig-preview-wrap');
        const confirmWrap = document.getElementById('sig-confirm-wrap');
        const img         = document.getElementById('sig-preview-img');

        if (!file) {
            previewWrap.classList.add('hidden');
            confirmWrap.classList.add('hidden');
            document.getElementById('sig-upload-btn').disabled = true;
            return;
        }

        const validTypes = ['image/png','image/jpeg','image/gif','image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Invalid file type. Please select a PNG, JPG, GIF, or WebP image.');
            input.value = '';
            previewWrap.classList.add('hidden');
            confirmWrap.classList.add('hidden');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('File is too large. Maximum size is 2 MB.');
            input.value = '';
            previewWrap.classList.add('hidden');
            confirmWrap.classList.add('hidden');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            img.src = e.target.result;
            previewWrap.classList.remove('hidden');
            confirmWrap.classList.remove('hidden');
            document.getElementById('sig-confirm-check').checked = false;
            document.getElementById('sig-upload-btn').disabled = true;
        };
        reader.readAsDataURL(file);
    },

    toggleUploadBtn() {
        const checked = document.getElementById('sig-confirm-check').checked;
        const hasFile = document.getElementById('sig-file-input').files.length > 0;
        document.getElementById('sig-upload-btn').disabled = !(checked && hasFile);
    },

    async submitSignature() {
        const fileInput = document.getElementById('sig-file-input');
        const file = fileInput.files[0];
        if (!file) return;

        const btn = document.getElementById('sig-upload-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Uploading…';

        const formData = new FormData();
        formData.append('signature', file);
        formData.append('csrf_token', <?php echo json_encode(csrf_token()); ?>);

        try {
            const res = await fetch(<?php echo json_encode(app_url('api/upload_signature.php')); ?>, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });
            const json = await res.json();
            if (json.success) {
                // Reload page to show the saved signature
                window.location.reload();
            } else {
                alert('Upload failed: ' + (json.error || 'Unknown error'));
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirm & Upload';
            }
        } catch (err) {
            alert('Network error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirm & Upload';
        }
    },
};
</script>
