<?php
require_once __DIR__ . '/../../config/auth.php';
requireUser();

$basePath    = '../../';
$currentPage = 'browse-jobs';

// ── Validate job ID ──────────────────────────────────────────
$jobId = (int)($_GET['id'] ?? 0);
if ($jobId <= 0) {
    header('Location: ' . BASE_URL . '/pages/user/browse-jobs.php');
    exit;
}

// ── Fetch job ────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT j.*, u.full_name AS poster_name, u.role AS poster_role
    FROM jobs j
    LEFT JOIN users u ON u.id = j.created_by
    WHERE j.id = ? LIMIT 1
");
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    header('Location: ' . BASE_URL . '/404.php');
    exit;
}

// ── Has the user already applied or saved? ───────────────────
$userId = $_SESSION['ojams_user']['id'];
$dupStmt = $pdo->prepare("SELECT id, status FROM applications WHERE user_id = ? AND job_id = ? LIMIT 1");
$dupStmt->execute([$userId, $jobId]);
$existingApp = $dupStmt->fetch();
$alreadyApplied = (bool)$existingApp;

$svStmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ? LIMIT 1");
$svStmt->execute([$userId, $jobId]);
$isSaved = (bool)$svStmt->fetch();

// ── Applicant count ──────────────────────────────────────────
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
$cntStmt->execute([$jobId]);
$applicantCount = (int)$cntStmt->fetchColumn();

$isOpen  = $job['status'] === 'Open';
$pageTitle = 'OJAMS - ' . htmlspecialchars($job['title']);

include $basePath . 'layouts/header.php';
include $basePath . 'layouts/navbar-user.php';
?>

<div class="container py-4" style="max-width: 860px;">

    <!-- Back link -->
    <a href="browse-jobs.php" class="btn btn-outline-secondary btn-sm mb-4">
        <i class="bi bi-arrow-left me-1"></i>Back to Browse Jobs
    </a>

    <!-- Job Header Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold text-primary mb-1">
                        <i class="bi bi-briefcase-fill me-2"></i><?php echo htmlspecialchars($job['title']); ?>
                    </h2>
                    <h5 class="text-muted mb-0">
                        <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($job['company']); ?>
                    </h5>
                </div>
                <span class="badge fs-6 <?php echo $isOpen ? 'bg-success' : 'bg-secondary'; ?> align-self-start">
                    <?php echo $isOpen ? 'Open' : 'Closed'; ?>
                </span>
            </div>

            <!-- Job type / location / salary badges -->
            <?php if (!empty($job['job_type']) || !empty($job['location']) || !empty($job['salary_range'])): ?>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <?php if (!empty($job['job_type'])): ?>
                <?php $jtBadge = match($job['job_type']) {
                    'Full-time'  => 'bg-primary',   'Part-time'  => 'bg-info text-dark',
                    'Contract'   => 'bg-warning text-dark', 'Internship' => 'bg-success',
                    'Freelance'  => 'bg-secondary',  default     => 'bg-light text-dark border',
                }; ?>
                <span class="badge <?php echo $jtBadge; ?> fs-6">
                    <i class="bi bi-briefcase me-1"></i><?php echo htmlspecialchars($job['job_type']); ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($job['location'])): ?>
                <span class="badge bg-light text-dark border fs-6">
                    <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($job['location']); ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($job['salary_range'])): ?>
                <span class="badge bg-light text-dark border fs-6">
                    <i class="bi bi-cash me-1"></i><?php echo htmlspecialchars($job['salary_range']); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Meta row -->
            <div class="d-flex flex-wrap gap-3 mt-3">
                <span class="text-muted small">
                    <i class="bi bi-calendar-event me-1 text-primary"></i>
                    Posted: <strong><?php echo date('F d, Y', strtotime($job['date_posted'])); ?></strong>
                    <?php if (!empty($job['poster_name'])): ?>
                        <span class="ms-1 text-secondary">&bull; By <strong><?php echo htmlspecialchars($job['poster_name']); ?></strong> (<?php echo ucfirst($job['poster_role'] ?? 'Admin'); ?>)</span>
                    <?php endif; ?>
                </span>
                <?php if (!empty($job['deadline'])): ?>
                <?php $deadlinePast = strtotime($job['deadline']) < strtotime('today'); ?>
                <span class="small <?php echo $deadlinePast ? 'text-danger' : 'text-muted'; ?>">
                    <i class="bi bi-calendar-x me-1 <?php echo $deadlinePast ? '' : 'text-primary'; ?>"></i>
                    Deadline: <strong><?php echo date('F d, Y', strtotime($job['deadline'])); ?></strong>
                    <?php if ($deadlinePast): ?>
                        <span class="badge bg-danger ms-1">Expired</span>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
                <span class="text-muted small">
                    <i class="bi bi-people-fill me-1 text-primary"></i>
                    <strong><?php echo $applicantCount; ?></strong>
                    Applicant<?php echo $applicantCount !== 1 ? 's' : ''; ?>
                </span>
                <span class="text-muted small">
                    <i class="bi bi-hash me-1 text-primary"></i>Job ID: <strong><?php echo $job['id']; ?></strong>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Left: Description + Qualifications + Hiring Contact -->
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold border-bottom pb-2 mb-3">
                        <i class="bi bi-file-text me-2 text-primary"></i>Job Description
                    </h5>
                    <p class="mb-0" style="white-space: pre-wrap; line-height: 1.8; word-break: break-word;">
                        <?php echo htmlspecialchars($job['description']); ?>
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold border-bottom pb-2 mb-3">
                        <i class="bi bi-mortarboard me-2 text-primary"></i>Qualifications
                    </h5>
                    <p class="mb-0" style="white-space: pre-wrap; line-height: 1.8; word-break: break-word;">
                        <?php echo htmlspecialchars($job['qualification']); ?>
                    </p>
                </div>
            </div>

            <!-- Hiring Contact Person & Number -->
            <?php if (!empty($job['contact_person']) || !empty($job['contact_phone'])): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold border-bottom pb-2 mb-3">
                        <i class="bi bi-person-lines-fill me-2 text-primary"></i>Hiring Contact Information
                    </h5>
                    <div class="row g-3">
                        <?php if (!empty($job['contact_person'])): ?>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border">
                                <small class="text-muted d-block mb-1">Contact Person / Recruiter</small>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-person-fill me-2 text-primary"></i><?php echo htmlspecialchars($job['contact_person']); ?>
                                </h6>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($job['contact_phone'])): ?>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border">
                                <small class="text-muted d-block mb-1">Contact Phone Number</small>
                                <h6 class="fw-bold mb-0 text-primary">
                                    <i class="bi bi-telephone-fill me-2"></i><?php echo htmlspecialchars($job['contact_phone']); ?>
                                </h6>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Right: Apply Panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                <div class="card-body p-4 text-center">

                    <?php if ($alreadyApplied): ?>
                        <!-- Already Applied -->
                        <div class="mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width:64px;height:64px;font-size:1.8rem;">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h6 class="fw-bold">Application Submitted</h6>
                            <p class="text-muted small mb-0">You've already applied for this position.</p>
                        </div>
                        <?php
                        $statusMap = [
                            'Pending'  => ['bg-warning text-dark', 'hourglass-split'],
                            'Approved' => ['bg-success',           'check-circle-fill'],
                            'Rejected' => ['bg-danger',            'x-circle-fill'],
                        ];
                        $s = $existingApp['status'];
                        [$cls, $ico] = $statusMap[$s] ?? ['bg-secondary', 'circle'];
                        ?>
                        <span class="badge <?php echo $cls; ?> fs-6 px-3 py-2">
                            <i class="bi bi-<?php echo $ico; ?> me-1"></i><?php echo $s; ?>
                        </span>
                        <div class="mt-3 mb-2">
                            <a href="my-applications.php" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-list-check me-1"></i>View My Applications
                            </a>
                        </div>

                    <?php elseif ($isOpen): ?>
                        <!-- Can Apply -->
                        <div class="mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width:64px;height:64px;font-size:1.8rem;">
                                <i class="bi bi-send-fill"></i>
                            </div>
                            <h6 class="fw-bold">Ready to Apply?</h6>
                            <p class="text-muted small mb-0">Submit your application for this position.</p>
                        </div>
                        <button class="btn btn-primary w-100 btn-lg mb-2"
                            onclick="openApplyModal(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['company'], ENT_QUOTES); ?>')">
                            <i class="bi bi-send me-2"></i>Apply Now
                        </button>

                    <?php else: ?>
                        <!-- Closed -->
                        <div class="mb-3">
                            <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width:64px;height:64px;font-size:1.8rem;">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                            <h6 class="fw-bold">Applications Closed</h6>
                            <p class="text-muted small mb-0">This position is no longer accepting applications.</p>
                        </div>
                        <button class="btn btn-secondary w-100 mb-2" disabled>
                            <i class="bi bi-lock me-2"></i>Closed
                        </button>
                    <?php endif; ?>

                    <!-- Always unlocked Save Job button -->
                    <button class="btn btn-outline-secondary w-100 <?php echo $isSaved ? 'text-warning' : ''; ?>"
                            id="detailSaveBtn"
                            onclick="toggleSaveJobDetail(<?php echo $job['id']; ?>)">
                        <i class="bi bi-bookmark<?php echo $isSaved ? '-fill' : ''; ?> me-1"></i>
                        <span id="detailSaveLabel"><?php echo $isSaved ? 'Saved' : 'Save Job'; ?></span>
                    </button>

                    <hr class="my-3">

                    <a href="browse-jobs.php" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-search me-1"></i>Browse More Jobs
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include $basePath . 'modals/apply-job-modal.php'; ?>
<script>
const APP_HANDLER   = '../../handlers/applications.php';
const SAVED_HANDLER = '../../handlers/saved-jobs.php';

function toggleSaveJobDetail(jobId) {
    fetch(SAVED_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle', job_id: jobId, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) {
            const btn   = document.getElementById('detailSaveBtn');
            const icon  = btn?.querySelector('i');
            const label = document.getElementById('detailSaveLabel');
            if (res.saved) {
                btn?.classList.add('text-warning');
                if (icon)  icon.className  = 'bi bi-bookmark-fill me-1';
                if (label) label.textContent = 'Saved';
            } else {
                btn?.classList.remove('text-warning');
                if (icon)  icon.className  = 'bi bi-bookmark me-1';
                if (label) label.textContent = 'Save Job';
            }
        }
    })
    .catch(() => showToast('Request failed.', 'danger'));
}

function computeAge(birthdate) {
    if (!birthdate) return '';
    const today = new Date(), dob = new Date(birthdate);
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    return age >= 0 ? age : '';
}

function openApplyModal(jobId, title, company) {
    document.getElementById('applyJobTitle').textContent   = title;
    document.getElementById('applyJobCompany').textContent = company;
    document.getElementById('applicationForm').reset();
    document.getElementById('applicationForm').dataset.jobId = jobId;
    const sess = <?php echo json_encode([
        'full_name'      => $_SESSION['ojams_user']['full_name'],
        'contact_number' => $_SESSION['ojams_user']['contact_number'] ?? '',
        'address'        => $_SESSION['ojams_user']['address'] ?? '',
        'birthdate'      => $_SESSION['ojams_user']['birthdate'] ?? '',
    ]); ?>;
    const setV = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ''; };
    setV('appFullName',  sess.full_name);
    setV('appContact',   sess.contact_number);
    setV('appAddress',   sess.address);
    setV('appBirthdate', sess.birthdate);
    const ageEl = document.getElementById('appAge');
    if (ageEl && sess.birthdate) ageEl.value = computeAge(sess.birthdate);
    new bootstrap.Modal(document.getElementById('applyJobModal')).show();
}

document.addEventListener('DOMContentLoaded', function () {
    const bdEl = document.getElementById('appBirthdate');
    const ageEl = document.getElementById('appAge');
    if (bdEl && ageEl) {
        bdEl.addEventListener('change', function () { ageEl.value = computeAge(this.value); });
    }
});

function submitApplication() {
    const form  = document.getElementById('applicationForm');
    const jobId = form?.dataset.jobId;
    if (!jobId) { showToast('No job selected.', 'danger'); return; }

    const g = id => document.getElementById(id)?.value.trim() ?? '';
    // Field-level validation
    clearAllFieldErrors('applicationForm');
    let valid = true;
    if (!g('appFullName')) { showFieldError('appFullName', 'Full name is required.'); valid = false; }
    if (!g('appAddress'))  { showFieldError('appAddress',  'Address is required.');   valid = false; }
    const contactVal = g('appContact');
    if (!contactVal) {
        showFieldError('appContact', 'Contact number is required.'); valid = false;
    } else if (!/^\d{11}$/.test(contactVal)) {
        showFieldError('appContact', 'Contact number must be exactly 11 digits (numbers only).'); valid = false;
    }
    const bdVal = document.getElementById('appBirthdate')?.value;
    if (!bdVal) {
        showFieldError('appBirthdate', 'Birthdate is required.'); valid = false;
    } else {
        const bd  = new Date(bdVal);
        const now = new Date();
        if (bd >= now) {
            showFieldError('appBirthdate', 'Birthdate cannot be a future date.'); valid = false;
        } else {
            const age = Math.floor((now - bd) / (365.25 * 24 * 3600 * 1000));
            if (age < 16 || age > 80) { showFieldError('appBirthdate', 'Age must be between 16 and 80 years old.'); valid = false; }
        }
    }
    if (!valid) {
        showToast("Please fill in all required application fields correctly.", "warning");
        return;
    }

    const submitBtn = document.getElementById('submitAppBtn');
    btnLoading(submitBtn, true, 'Submitting…');

    // Hide input modal and show clean loading spinner
    bootstrap.Modal.getInstance(document.getElementById('applyJobModal'))?.hide();
    showLoadingModal("Submitting application…");

    const fd = new FormData();
    fd.append('action',     'apply');
    fd.append('csrf_token', getCsrfToken());
    fd.append('job_id',     jobId);
    fd.append('full_name',  g('appFullName'));
    fd.append('email',      '<?php echo htmlspecialchars($_SESSION['ojams_user']['email'], ENT_QUOTES); ?>');
    fd.append('contact',    g('appContact'));
    fd.append('address',    g('appAddress'));
    fd.append('birthdate',  document.getElementById('appBirthdate')?.value ?? '');
    fd.append('age',        document.getElementById('appAge')?.value ?? '0');
    fd.append('elementary', g('appElementary'));
    fd.append('jhs',        g('appJhs'));
    fd.append('shs',        g('appShs'));
    fd.append('college',    g('appCollege'));
    fd.append('skills',     g('appSkills'));
    fd.append('experience', g('appExperience'));

    const resumeFile = document.getElementById('appResume')?.files[0];
    if (resumeFile) fd.append('resume', resumeFile);

    fetch(APP_HANDLER, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        hideLoadingModal();
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 1000);
    })
    .catch(() => {
        hideLoadingModal();
        showToast('Request failed. Please try again.', 'danger');
    })
    .finally(() => btnLoading(submitBtn, false));
}

// Resume file name preview
document.addEventListener('DOMContentLoaded', function () {
    const resumeInput = document.getElementById('appResume');
    if (resumeInput) {
        resumeInput.addEventListener('change', function () {
            const infoEl = document.getElementById('resumeFileInfo');
            const nameEl = document.getElementById('resumeFileName');
            const sizeEl = document.getElementById('resumeFileSize');
            if (!this.files.length) { infoEl?.classList.add('d-none'); return; }
            const f  = this.files[0];
            const kb = (f.size / 1024).toFixed(1);
            const mb = (f.size / (1024 * 1024)).toFixed(2);
            if (nameEl) nameEl.textContent = f.name;
            if (sizeEl) sizeEl.textContent = f.size > 1024 * 1024 ? mb + ' MB' : kb + ' KB';
            infoEl?.classList.remove('d-none');
        });
    }
});
</script>
<?php include $basePath . 'layouts/footer.php'; ?>
