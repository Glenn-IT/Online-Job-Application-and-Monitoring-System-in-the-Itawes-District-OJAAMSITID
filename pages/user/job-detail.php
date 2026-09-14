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

// ── Has the user already applied? ───────────────────────────
$userId = (int)$_SESSION['ojams_user']['id'];
$dupStmt = $pdo->prepare("SELECT id, status FROM applications WHERE user_id = ? AND job_id = ? LIMIT 1");
$dupStmt->execute([$userId, $jobId]);
$existingApp = $dupStmt->fetch();
$alreadyApplied = (bool)$existingApp;

// ── Has the user bookmarked this job? ───────────────────────
$saveCheckStmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ? LIMIT 1");
$saveCheckStmt->execute([$userId, $jobId]);
$isSaved = (bool)$saveCheckStmt->fetch();

// ── Applicant count ──────────────────────────────────────────
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
$cntStmt->execute([$jobId]);
$applicantCount = (int)$cntStmt->fetchColumn();

$isOpen  = $job['status'] === 'Open';
$pageTitle = 'OJAMS - ' . htmlspecialchars($job['title']);

// ── Helpers ──────────────────────────────────────────────────
function getCompanyInitials(string $company): string {
    $words = preg_split('/\s+/', trim($company));
    if (empty($words) || $words[0] === '') return 'JB';
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($words[0], 0, 2));
}

function getCompanyPalette(string $company): array {
    $palettes = [
        ['bg' => '#eef2ff', 'color' => '#4338ca'],
        ['bg' => '#f0fdf4', 'color' => '#15803d'],
        ['bg' => '#f0f9ff', 'color' => '#0369a1'],
        ['bg' => '#fefce8', 'color' => '#a16207'],
        ['bg' => '#faf5ff', 'color' => '#7e22ce'],
        ['bg' => '#fff1f2', 'color' => '#be123c'],
        ['bg' => '#f5f3ff', 'color' => '#6d28d9'],
        ['bg' => '#ecfeff', 'color' => '#0e7490'],
    ];
    $hash = crc32($company);
    $idx = abs($hash) % count($palettes);
    return $palettes[$idx];
}

$company  = $job['company'] ?? 'Company';
$initials = getCompanyInitials($company);
$palette  = getCompanyPalette($company);
$jtSlug   = strtolower(str_replace(['-', ' '], '', $job['job_type'] ?? ''));

include $basePath . 'layouts/header.php';
include $basePath . 'layouts/navbar-user.php';
?>

<div class="container py-4 pb-5 mb-5 mb-lg-0" style="max-width: 980px;">

    <!-- Navigation Bar / Breadcrumb -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <a href="browse-jobs.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Browse Jobs
        </a>
        <span class="text-muted small">
            Job Reference: #<?= (int)$job['id'] ?>
        </span>
    </div>

    <!-- Modern Job Header Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 18px; overflow: hidden;">
        <div class="card-body p-3 p-sm-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="company-monogram" style="width: 58px; height: 58px; font-size: 1.4rem; border-radius: 16px; background: <?= $palette['bg'] ?>; color: <?= $palette['color'] ?>;">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-1 text-dark" style="font-size: clamp(1.3rem, 3vw, 1.8rem); line-height: 1.25;">
                            <?= htmlspecialchars($job['title']) ?>
                        </h2>
                        <h5 class="text-muted mb-0 fw-semibold" style="font-size: 1.05rem;">
                            <i class="bi bi-building me-1 text-primary"></i><?= htmlspecialchars($job['company']) ?>
                        </h5>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" 
                            class="btn-bookmark <?= $isSaved ? 'is-saved' : '' ?>" 
                            onclick="toggleSaveJob(this, <?= $jobId ?>)"
                            title="<?= $isSaved ? 'Remove from saved' : 'Save job' ?>"
                            aria-label="Bookmark job">
                        <i class="bi <?= $isSaved ? 'bi-bookmark-fill' : 'bi-bookmark' ?>"></i>
                    </button>
                    <span class="badge fs-6 px-3 py-2 <?= $isOpen ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $isOpen ? 'Open' : 'Closed' ?>
                    </span>
                </div>
            </div>

            <!-- Soft Modern Badge Tags -->
            <div class="d-flex flex-wrap gap-2 mt-3 pt-2 border-top">
                <?php if (!empty($job['job_type'])): ?>
                <span class="badge-tag tag-<?= $jtSlug ?> fs-6">
                    <i class="bi bi-briefcase"></i><?= htmlspecialchars($job['job_type']) ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($job['salary_range'])): ?>
                <span class="badge-tag tag-salary fs-6">
                    <i class="bi bi-cash"></i><?= htmlspecialchars($job['salary_range']) ?>
                </span>
                <?php endif; ?>
                <?php if (!empty($job['location'])): ?>
                <span class="badge-tag tag-location fs-6">
                    <i class="bi bi-geo-alt"></i><?= htmlspecialchars($job['location']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 6-Box Quick Highlight Tiles Grid -->
    <div class="row g-3 mb-4">
        <!-- Tile 1: Job Type -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="detail-stat-box h-100">
                <span class="text-muted d-block small mb-1"><i class="bi bi-briefcase me-1 text-primary"></i>Job Type</span>
                <span class="fw-bold text-dark text-truncate d-block" title="<?= htmlspecialchars($job['job_type'] ?? 'N/A') ?>">
                    <?= htmlspecialchars($job['job_type'] ?? 'Full-time') ?>
                </span>
            </div>
        </div>

        <!-- Tile 2: Salary -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="detail-stat-box h-100">
                <span class="text-muted d-block small mb-1"><i class="bi bi-cash me-1 text-success"></i>Salary</span>
                <span class="fw-bold text-dark text-truncate d-block" title="<?= htmlspecialchars($job['salary_range'] ?? 'Negotiable') ?>">
                    <?= htmlspecialchars($job['salary_range'] ?: 'Negotiable') ?>
                </span>
            </div>
        </div>

        <!-- Tile 3: Location -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="detail-stat-box h-100">
                <span class="text-muted d-block small mb-1"><i class="bi bi-geo-alt me-1 text-danger"></i>Location</span>
                <span class="fw-bold text-dark text-truncate d-block" title="<?= htmlspecialchars($job['location'] ?? 'Itawes District') ?>">
                    <?= htmlspecialchars($job['location'] ?: 'Itawes') ?>
                </span>
            </div>
        </div>

        <!-- Tile 4: Date Posted -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="detail-stat-box h-100">
                <span class="text-muted d-block small mb-1"><i class="bi bi-calendar-event me-1 text-info"></i>Posted</span>
                <span class="fw-bold text-dark text-truncate d-block">
                    <?= date('M d, Y', strtotime($job['date_posted'])) ?>
                </span>
            </div>
        </div>

        <!-- Tile 5: Deadline -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="detail-stat-box h-100">
                <span class="text-muted d-block small mb-1"><i class="bi bi-calendar-x me-1 text-warning"></i>Deadline</span>
                <?php if (!empty($job['deadline'])): ?>
                    <?php $deadlinePast = strtotime($job['deadline']) < strtotime('today'); ?>
                    <span class="fw-bold text-truncate d-block <?= $deadlinePast ? 'text-danger' : 'text-dark' ?>" title="<?= htmlspecialchars($job['deadline']) ?>">
                        <?= date('M d, Y', strtotime($job['deadline'])) ?>
                    </span>
                <?php else: ?>
                    <span class="fw-bold text-dark text-truncate d-block">Until Filled</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tile 6: Applicants -->
        <div class="col-6 col-md-4 col-lg-2">
            <div class="detail-stat-box h-100">
                <span class="text-muted d-block small mb-1"><i class="bi bi-people me-1 text-primary"></i>Applicants</span>
                <span class="fw-bold text-dark text-truncate d-block">
                    <?= number_format($applicantCount) ?> applied
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content Section: Left Specs & Right Apply Action Card -->
    <div class="row g-4">

        <!-- Left Column: Detailed Description + Qualifications + Hiring Contact -->
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-body p-3 p-sm-4">
                    <h5 class="fw-bold border-bottom pb-3 mb-3 text-dark">
                        <i class="bi bi-file-text me-2 text-primary"></i>Job Description
                    </h5>
                    <div class="text-secondary" style="white-space: pre-wrap; line-height: 1.85; word-break: break-word; font-size: 0.95rem;">
                        <?= htmlspecialchars($job['description']) ?>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
                <div class="card-body p-3 p-sm-4">
                    <h5 class="fw-bold border-bottom pb-3 mb-3 text-dark">
                        <i class="bi bi-mortarboard me-2 text-primary"></i>Qualifications & Requirements
                    </h5>
                    <div class="text-secondary" style="white-space: pre-wrap; line-height: 1.85; word-break: break-word; font-size: 0.95rem;">
                        <?= htmlspecialchars($job['qualification']) ?>
                    </div>
                </div>
            </div>

            <!-- Hiring Contact Person & Tap-to-Call -->
            <?php if (!empty($job['contact_person']) || !empty($job['contact_phone'])): ?>
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-3 p-sm-4">
                    <h5 class="fw-bold border-bottom pb-3 mb-3 text-dark">
                        <i class="bi bi-person-lines-fill me-2 text-primary"></i>Hiring Contact Information
                    </h5>
                    <div class="row g-3">
                        <?php if (!empty($job['contact_person'])): ?>
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block mb-1">Recruiter / Contact Person</small>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-person-fill me-2 text-primary"></i><?= htmlspecialchars($job['contact_person']) ?>
                                </h6>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($job['contact_phone'])): ?>
                        <div class="col-12 col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <small class="text-muted d-block mb-1">Direct Telephone / Mobile</small>
                                <h6 class="fw-bold mb-0">
                                    <a href="tel:<?= htmlspecialchars($job['contact_phone']) ?>" class="text-decoration-none text-primary">
                                        <i class="bi bi-telephone-fill me-2"></i><?= htmlspecialchars($job['contact_phone']) ?>
                                    </a>
                                </h6>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Right Column: Interactive Apply Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 85px; border-radius: 18px;">
                <div class="card-body p-4 text-center">

                    <?php if ($alreadyApplied): ?>
                        <!-- Already Applied State -->
                        <div class="mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width: 68px; height: 68px; font-size: 2rem;">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Application Submitted</h5>
                            <p class="text-muted small mb-3">You have already submitted an application for this position.</p>
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
                        <span class="badge <?= $cls ?> fs-6 px-3 py-2 w-100 mb-3">
                            <i class="bi bi-<?= $ico ?> me-1"></i>Current Status: <?= $s ?>
                        </span>
                        <a href="my-applications.php" class="btn btn-outline-primary btn-sm w-100 py-2">
                            <i class="bi bi-list-check me-1"></i>View in My Applications
                        </a>

                    <?php elseif ($isOpen): ?>
                        <!-- Can Apply State -->
                        <div class="mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width: 68px; height: 68px; font-size: 2rem;">
                                <i class="bi bi-send-fill"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Ready to Apply?</h5>
                            <p class="text-muted small mb-0">Submit your profile and resume directly to <?= htmlspecialchars($company) ?>.</p>
                        </div>
                        <button class="btn btn-primary w-100 btn-lg mb-2 py-3"
                            onclick="openApplyModal(<?= $job['id'] ?>, '<?= htmlspecialchars($job['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($job['company'], ENT_QUOTES) ?>')">
                            <i class="bi bi-send-fill me-2"></i>Apply Now
                        </button>
                        <small class="text-muted d-block mt-2" style="font-size: 0.76rem;">
                            <i class="bi bi-shield-check me-1 text-success"></i>Direct application verified by OJAMS
                        </small>

                    <?php else: ?>
                        <!-- Closed State -->
                        <div class="mb-3">
                            <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width: 68px; height: 68px; font-size: 2rem;">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Applications Closed</h5>
                            <p class="text-muted small mb-0">This position is no longer accepting new submissions.</p>
                        </div>
                        <button class="btn btn-secondary w-100 py-2 mb-2" disabled>
                            <i class="bi bi-lock me-2"></i>Closed
                        </button>
                    <?php endif; ?>

                    <hr class="my-3">

                    <a href="browse-jobs.php" class="btn btn-outline-secondary btn-sm w-100 py-2">
                        <i class="bi bi-search me-1"></i>Explore More Openings
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- Mobile Sticky Bottom Action Bar -->
<div class="mobile-sticky-apply-bar d-lg-none">
    <div class="container d-flex align-items-center justify-content-between gap-2 p-0">
        <div class="min-w-0 flex-grow-1 pe-2">
            <div class="fw-bold text-dark text-truncate small" style="line-height: 1.25;"><?= htmlspecialchars($job['title']) ?></div>
            <div class="text-muted text-truncate" style="font-size: 0.74rem;"><i class="bi bi-building me-1"></i><?= htmlspecialchars($company) ?></div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button type="button" 
                    class="btn-bookmark <?= $isSaved ? 'is-saved' : '' ?>" 
                    onclick="toggleSaveJob(this, <?= $jobId ?>)"
                    title="<?= $isSaved ? 'Remove from saved' : 'Save job' ?>"
                    aria-label="Bookmark job">
                <i class="bi <?= $isSaved ? 'bi-bookmark-fill' : 'bi-bookmark' ?>"></i>
            </button>
            <?php if ($alreadyApplied): ?>
                <button class="btn btn-success btn-sm px-3 py-2 fw-semibold" disabled>
                    <i class="bi bi-check-circle me-1"></i>Applied
                </button>
            <?php elseif ($isOpen): ?>
                <button class="btn btn-primary btn-sm px-3 py-2 fw-semibold shadow-sm"
                    onclick="openApplyModal(<?= $job['id'] ?>, '<?= htmlspecialchars($job['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($job['company'], ENT_QUOTES) ?>')">
                    <i class="bi bi-send-fill me-1"></i>Apply Now
                </button>
            <?php else: ?>
                <button class="btn btn-secondary btn-sm px-3 py-2" disabled>
                    <i class="bi bi-lock me-1"></i>Closed
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include $basePath . 'modals/apply-job-modal.php'; ?>

<script>
const APP_HANDLER = '../../handlers/applications.php';
const SAVED_HANDLER = '../../handlers/saved-jobs.php';

// ── Interactive Bookmark / Saved Jobs Toggle ──────────────────
function toggleSaveJob(btn, jobId) {
    if (!jobId) return;
    btn.disabled = true;
    fetch(SAVED_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'toggle',
            job_id: jobId,
            csrf_token: getCsrfToken()
        })
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.success) {
            const icon = btn.querySelector('i');
            if (res.saved) {
                btn.classList.add('is-saved');
                if (icon) icon.className = 'bi bi-bookmark-fill';
                btn.title = 'Remove from saved';
            } else {
                btn.classList.remove('is-saved');
                if (icon) icon.className = 'bi bi-bookmark';
                btn.title = 'Save job';
            }
            showToast(res.message, 'success');
        } else {
            showToast(res.message || 'Unable to update bookmark.', 'danger');
        }
    })
    .catch(() => {
        btn.disabled = false;
        showToast('Network error updating saved job.', 'danger');
    });
}

// ── Age Trapping & Validation ────────────────────────────────
function computeAge(birthdate) {
    if (!birthdate) return '';
    const today = new Date(), dob = new Date(birthdate);
    if (isNaN(dob.getTime())) return '';
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
    clearAllFieldErrors('applicationForm');
    document.getElementById('resumeFileInfo')?.classList.add('d-none');

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
    if (sess.birthdate) {
        const computed = computeAge(sess.birthdate);
        if (ageEl) ageEl.value = computed;
        if (computed !== '' && computed < 18) {
            showFieldError('appBirthdate', 'Applicants must be at least 18 years old to apply.');
        }
    } else if (ageEl) {
        ageEl.value = '';
    }
    new bootstrap.Modal(document.getElementById('applyJobModal')).show();
}

document.addEventListener('DOMContentLoaded', function () {
    const bdEl = document.getElementById('appBirthdate');
    const ageEl = document.getElementById('appAge');
    if (bdEl) {
        const d = new Date();
        d.setFullYear(d.getFullYear() - 18);
        bdEl.max = d.toISOString().split('T')[0];

        bdEl.addEventListener('change', function () {
            clearFieldError('appBirthdate');
            const age = computeAge(this.value);
            if (ageEl) ageEl.value = age;
            if (this.value) {
                if (age === '' || age < 18) {
                    showFieldError('appBirthdate', 'Applicants must be at least 18 years old to apply.');
                } else if (age > 80) {
                    showFieldError('appBirthdate', 'Age must be 80 years old or below.');
                }
            }
        });
    }

    const resumeInput = document.getElementById('appResume');
    if (resumeInput) {
        resumeInput.addEventListener('change', function () {
            clearFieldError('appResume');
            const infoEl = document.getElementById('resumeFileInfo');
            const nameEl = document.getElementById('resumeFileName');
            const sizeEl = document.getElementById('resumeFileSize');
            if (!this.files.length) { infoEl?.classList.add('d-none'); return; }
            const f = this.files[0];
            const kb = (f.size / 1024).toFixed(1);
            const mb = (f.size / (1024 * 1024)).toFixed(2);
            if (nameEl) nameEl.textContent = f.name;
            if (sizeEl) sizeEl.textContent = f.size > 1024 * 1024 ? mb + ' MB' : kb + ' KB';
            infoEl?.classList.remove('d-none');
        });
    }
});

function submitApplication() {
    const form  = document.getElementById('applicationForm');
    const jobId = form ? form.dataset.jobId : null;
    if (!jobId) { showToast('No job selected.', 'danger'); return; }

    const g = (id) => document.getElementById(id) ? document.getElementById(id).value.trim() : '';
    clearAllFieldErrors('applicationForm');
    let valid = true;

    // 1. Full Name
    if (!g('appFullName')) { showFieldError('appFullName', 'Full name is required.'); valid = false; }

    // 2. Address
    if (!g('appAddress'))  { showFieldError('appAddress',  'Address is required.');   valid = false; }

    // 3. Contact Number (exactly 11 digits)
    const contactVal = g('appContact');
    if (!contactVal) {
        showFieldError('appContact', 'Contact number is required.'); valid = false;
    } else if (!/^\d{11}$/.test(contactVal)) {
        showFieldError('appContact', 'Contact number must be exactly 11 digits (numbers only).'); valid = false;
    }

    // 4. Birthdate & Age Trapping (Must be at least 18 years old)
    const bdVal = document.getElementById('appBirthdate')?.value;
    if (!bdVal) {
        showFieldError('appBirthdate', 'Birthdate is required.'); valid = false;
    } else {
        const bd  = new Date(bdVal);
        const now = new Date();
        if (bd >= now) {
            showFieldError('appBirthdate', 'Birthdate cannot be a future date.'); valid = false;
        } else {
            const age = computeAge(bdVal);
            if (age === '' || age < 18) {
                showFieldError('appBirthdate', 'Applicants must be at least 18 years old to apply.'); valid = false;
            } else if (age > 80) {
                showFieldError('appBirthdate', 'Age must be 80 years old or below.'); valid = false;
            }
        }
    }

    // 5. Educational Attainment (All Required)
    if (!g('appElementary')) { showFieldError('appElementary', 'Elementary school is required.'); valid = false; }
    if (!g('appJhs'))        { showFieldError('appJhs', 'Junior High School (JHS) is required.'); valid = false; }
    if (!g('appShs'))        { showFieldError('appShs', 'Senior High School (SHS) is required.'); valid = false; }
    if (!g('appCollege'))    { showFieldError('appCollege', 'College education is required.'); valid = false; }

    // 6. Additional Information (All Required)
    if (!g('appSkills'))     { showFieldError('appSkills', 'Skills are required.'); valid = false; }
    if (!g('appExperience')) { showFieldError('appExperience', 'Work experience is required (enter N/A if none).'); valid = false; }

    // 7. Resume Upload (Required)
    const resumeInput = document.getElementById('appResume');
    const resumeFile  = resumeInput?.files ? resumeInput.files[0] : null;
    if (!resumeFile) {
        showFieldError('appResume', 'Resume / CV file is required.'); valid = false;
    }

    if (!valid) {
        showToast('All fields are required. Please fill in all fields correctly.', 'warning');
        return;
    }

    const submitBtn = document.getElementById('submitAppBtn');
    btnLoading(submitBtn, true, 'Submitting…');

    bootstrap.Modal.getInstance(document.getElementById('applyJobModal'))?.hide();
    showLoadingModal('Submitting application…');

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
    fd.append('resume',     resumeFile);

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
</script>
<?php include $basePath . 'layouts/footer.php'; ?>
