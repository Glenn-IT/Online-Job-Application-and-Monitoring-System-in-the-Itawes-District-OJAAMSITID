<?php
require_once __DIR__ . "/../../config/auth.php";
requireUser();
$pageTitle   = "OJAMS - Browse Jobs";
$basePath    = "../../";
$currentPage = "browse-jobs";

// ── Filters from URL ────────────────────────────────────────
$search        = trim($_GET['search']   ?? '');
$statusFilter  = $_GET['status']        ?? '';
$jobTypeFilter = $_GET['job_type']      ?? '';
$allowedStatus = ['', 'Open', 'Closed'];
if (!in_array($statusFilter, $allowedStatus)) $statusFilter = '';
$allowedTypes  = ['', 'Full-time','Part-time','Contract','Internship','Freelance'];
if (!in_array($jobTypeFilter, $allowedTypes)) $jobTypeFilter = '';

// ── Build WHERE clause ───────────────────────────────────────
$where  = [];
$params = [];
if ($search !== '') {
    $where[]  = "(j.title LIKE ? OR j.company LIKE ? OR j.location LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($statusFilter !== '') {
    $where[]  = "j.status = ?";
    $params[] = $statusFilter;
}
if ($jobTypeFilter !== '') {
    $where[]  = "j.job_type = ?";
    $params[] = $jobTypeFilter;
}
$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// ── Pagination ───────────────────────────────────────────────
$perPage    = PER_PAGE_USER;
$page       = max(1, (int)($_GET['page'] ?? 1));

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM jobs j {$whereSQL}");
$countStmt->execute($params);
$filteredTotal = (int)$countStmt->fetchColumn();
$totalPages    = max(1, (int)ceil($filteredTotal / $perPage));
$page          = min($page, $totalPages);
$offset        = ($page - 1) * $perPage;

// ── Fetch paginated jobs ─────────────────────────────────────
$jobsStmt = $pdo->prepare("
    SELECT j.* FROM jobs j {$whereSQL}
    ORDER BY j.date_posted DESC
    LIMIT ? OFFSET ?
");
$jobsStmt->execute(array_merge($params, [$perPage, $offset]));
$jobs = $jobsStmt->fetchAll();

// Applied and saved job IDs for this user
$appliedStmt = $pdo->prepare("SELECT job_id FROM applications WHERE user_id = ?");
$appliedStmt->execute([$_SESSION["ojams_user"]["id"]]);
$appliedJobIds = array_column($appliedStmt->fetchAll(), "job_id");

$savedStmt = $pdo->prepare("SELECT job_id FROM saved_jobs WHERE user_id = ?");
$savedStmt->execute([$_SESSION["ojams_user"]["id"]]);
$savedJobIds = array_column($savedStmt->fetchAll(), "job_id");

// Applicant counts — cached in session for 5 minutes to reduce DB hits
$cacheKey = 'browse_app_counts';
$cacheTtl = 300;
if (empty($_SESSION[$cacheKey]) || (time() - ($_SESSION[$cacheKey . '_ts'] ?? 0)) > $cacheTtl) {
    $countRows = $pdo->query("SELECT job_id, COUNT(*) as cnt FROM applications GROUP BY job_id")->fetchAll();
    $appCounts = [];
    foreach ($countRows as $row) { $appCounts[$row["job_id"]] = $row["cnt"]; }
    $_SESSION[$cacheKey]        = $appCounts;
    $_SESSION[$cacheKey . '_ts'] = time();
} else {
    $appCounts = $_SESSION[$cacheKey];
}

$totalJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();

// ── URL helper — preserves active filters in pagination links ─
function browseJobsUrl(int $p): string {
    $q = $_GET;
    $q['page'] = $p;
    unset($q['page']); // remove first so we control order
    $q = array_filter($q, fn($v) => $v !== '');
    $q['page'] = $p;
    return '?' . http_build_query($q);
}

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-user.php";
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-search me-2 text-primary"></i>Browse Jobs</h2>
            <p class="text-muted mb-0">Find and apply to job openings that match your skills.</p>
        </div>
        <span class="badge bg-primary fs-6">
            <?php echo $filteredTotal; ?> of <?php echo $totalJobs; ?> Jobs
        </span>
    </div>

    <!-- Filter bar -->
    <form method="get" action="" class="row g-2 mb-4 align-items-end">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" name="search"
                       placeholder="Search by title, company or location…"
                       value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
            </div>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="Open"   <?php echo $statusFilter === 'Open'   ? 'selected' : ''; ?>>Open</option>
                <option value="Closed" <?php echo $statusFilter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="job_type">
                <option value="">All Types</option>
                <?php foreach (['Full-time','Part-time','Contract','Internship','Freelance'] as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $jobTypeFilter === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-funnel me-1"></i>Search
            </button>
        </div>
        <div class="col-md-1">
            <?php if ($search !== '' || $statusFilter !== '' || $jobTypeFilter !== ''): ?>
            <a href="browse-jobs.php" class="btn btn-outline-secondary w-100" title="Clear filters">
                <i class="bi bi-x-lg"></i>
            </a>
            <?php endif; ?>
        </div>
    </form>
    <div class="row" id="jobCardsContainer">
        <?php if (empty($jobs)): ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-briefcase display-4 d-block mb-2"></i>No job postings available.
            </div>
        <?php else: ?>
        <?php foreach ($jobs as $job):
            $alreadyApplied = in_array($job["id"], $appliedJobIds);
            $isSaved        = in_array($job["id"], $savedJobIds);
            $isOpen         = $job["status"] === "Open";
            $cnt            = $appCounts[$job["id"]] ?? 0;
        ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm border-0 job-card">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-bold text-primary">
                        <i class="bi bi-briefcase me-2"></i><?php echo htmlspecialchars($job['title']); ?>
                    </h5>
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($job['company']); ?>
                    </h6>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php if (!empty($job['job_type'])): ?>
                        <?php $jt = $job['job_type']; $jtBadge = match($jt) {
                            'Full-time'  => 'bg-primary',   'Part-time' => 'bg-info text-dark',
                            'Contract'   => 'bg-warning text-dark', 'Internship' => 'bg-success',
                            'Freelance'  => 'bg-secondary', default    => 'bg-light text-dark border',
                        }; ?>
                        <span class="badge <?php echo $jtBadge; ?>"><?php echo htmlspecialchars($jt); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($job['location'])): ?>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($job['location']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($job['salary_range'])): ?>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-cash me-1"></i><?php echo htmlspecialchars($job['salary_range']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars($job['description']); ?></p>
                    <p class="card-text">
                        <small class="text-muted">
                            <i class="bi bi-mortarboard me-1"></i>
                            <strong>Qualifications:</strong> <?php echo htmlspecialchars($job['qualification']); ?>
                        </small>
                    </p>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <i class="bi bi-calendar-event me-1"></i>Posted: <?php echo $job['date_posted']; ?>
                        </small>
                        <span class="badge <?php echo $isOpen ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $job['status']; ?>
                        </span>
                    </div>
                    <?php if (!empty($job['deadline'])): ?>
                    <?php $isPast = strtotime($job['deadline']) < strtotime('today'); ?>
                    <div class="mb-2">
                        <small class="<?php echo $isPast ? 'text-danger' : 'text-muted'; ?>">
                            <i class="bi bi-calendar-x me-1"></i>
                            Deadline: <strong><?php echo $job['deadline']; ?></strong>
                            <?php if ($isPast): ?>
                                <span class="badge bg-danger ms-1">Expired</span>
                            <?php endif; ?>
                        </small>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-people-fill text-primary me-1"></i>
                            <?php echo $cnt; ?> Applicant<?php echo $cnt !== 1 ? 's' : ''; ?>
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="job-detail.php?id=<?php echo $job['id']; ?>"
                           class="btn btn-outline-primary flex-grow-1">
                            <i class="bi bi-eye me-1"></i>View Details
                        </a>
                        <?php if ($alreadyApplied): ?>
                            <button class="btn btn-success flex-grow-1" disabled>
                                <i class="bi bi-check-circle me-1"></i>Applied
                            </button>
                        <?php elseif ($isOpen): ?>
                            <button class="btn btn-primary flex-grow-1"
                                onclick="openApplyModal(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['company'], ENT_QUOTES); ?>')">
                                <i class="bi bi-send me-1"></i>Apply
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary flex-grow-1" disabled>
                                <i class="bi bi-lock me-1"></i>Closed
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-outline-secondary <?php echo $isSaved ? 'text-warning' : ''; ?>"
                                id="save-btn-<?php echo $job['id']; ?>"
                                onclick="toggleSaveJob(<?php echo $job['id']; ?>)"
                                title="<?php echo $isSaved ? 'Remove from saved' : 'Save job'; ?>">
                            <i class="bi bi-bookmark<?php echo $isSaved ? '-fill' : ''; ?>"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-4 d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $perPage, $filteredTotal); ?>
            of <?php echo $filteredTotal; ?> jobs
        </small>
        <ul class="pagination mb-0">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo browseJobsUrl($page - 1); ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++):
                if (!($p === 1 || $p === $totalPages || abs($p - $page) <= 2)) continue;
                $prevP = $p - 1;
                if ($p > 1 && abs($p - $page) === 3): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo browseJobsUrl($p); ?>"><?php echo $p; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo browseJobsUrl($page + 1); ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
<?php include $basePath . "modals/apply-job-modal.php"; ?>
<script>
const APP_HANDLER   = "../../handlers/applications.php";
const SAVED_HANDLER = "../../handlers/saved-jobs.php";

function toggleSaveJob(jobId) {
    fetch(SAVED_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "toggle", job_id: jobId, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) {
            const btn  = document.getElementById("save-btn-" + jobId);
            const icon = btn?.querySelector("i");
            if (res.saved) {
                btn?.classList.add("text-warning");
                btn?.setAttribute("title", "Remove from saved");
                if (icon) icon.className = "bi bi-bookmark-fill";
            } else {
                btn?.classList.remove("text-warning");
                btn?.setAttribute("title", "Save job");
                if (icon) icon.className = "bi bi-bookmark";
            }
        }
    })
    .catch(() => showToast("Request failed.", "danger"));
}

function computeAge(birthdate) {
    if (!birthdate) return "";
    const today = new Date();
    const dob   = new Date(birthdate);
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    return age >= 0 ? age : "";
}

function openApplyModal(jobId, title, company) {
    document.getElementById("applyJobTitle").textContent   = title;
    document.getElementById("applyJobCompany").textContent = company;
    document.getElementById("applicationForm").reset();
    document.getElementById("applicationForm").dataset.jobId = jobId;
    const sess = <?php echo json_encode([
        "full_name"      => $_SESSION["ojams_user"]["full_name"],
        "contact_number" => $_SESSION["ojams_user"]["contact_number"] ?? "",
        "address"        => $_SESSION["ojams_user"]["address"] ?? "",
        "birthdate"      => $_SESSION["ojams_user"]["birthdate"] ?? "",
    ]); ?>;
    const setV = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ""; };
    setV("appFullName",  sess.full_name);
    setV("appContact",   sess.contact_number);
    setV("appAddress",   sess.address);
    setV("appBirthdate", sess.birthdate);
    // Auto-compute age from session birthdate
    const ageEl = document.getElementById("appAge");
    if (ageEl && sess.birthdate) ageEl.value = computeAge(sess.birthdate);
    new bootstrap.Modal(document.getElementById("applyJobModal")).show();
}

// Auto-compute age when birthdate input changes
document.addEventListener("DOMContentLoaded", function () {
    const bdEl = document.getElementById("appBirthdate");
    const ageEl = document.getElementById("appAge");
    if (bdEl && ageEl) {
        bdEl.addEventListener("change", function () {
            ageEl.value = computeAge(this.value);
        });
    }
});

function submitApplication() {
    const form  = document.getElementById("applicationForm");
    const jobId = form ? form.dataset.jobId : null;
    if (!jobId) { showToast("No job selected.", "danger"); return; }

    const g = (id) => document.getElementById(id) ? document.getElementById(id).value.trim() : "";
    // Field-level validation
    clearAllFieldErrors("applicationForm");
    let valid = true;
    if (!g("appFullName")) { showFieldError("appFullName", "Full name is required."); valid = false; }
    if (!g("appAddress"))  { showFieldError("appAddress",  "Address is required.");   valid = false; }
    const contactVal = g("appContact");
    if (!contactVal) {
        showFieldError("appContact", "Contact number is required."); valid = false;
    } else if (!/^\+?[\d\s\-\(\)\.]{7,20}$/.test(contactVal)) {
        showFieldError("appContact", "Contact number may only contain digits, spaces, +, hyphens, or parentheses."); valid = false;
    }
    const bdVal = document.getElementById("appBirthdate")?.value;
    if (!bdVal) {
        showFieldError("appBirthdate", "Birthdate is required."); valid = false;
    } else {
        const bd  = new Date(bdVal);
        const now = new Date();
        if (bd >= now) {
            showFieldError("appBirthdate", "Birthdate cannot be a future date."); valid = false;
        } else {
            const age = Math.floor((now - bd) / (365.25 * 24 * 3600 * 1000));
            if (age < 16 || age > 80) { showFieldError("appBirthdate", "Age must be between 16 and 80 years old."); valid = false; }
        }
    }
    if (!valid) return;

    const submitBtn = document.getElementById("submitAppBtn");
    btnLoading(submitBtn, true, "Submitting…");

    const fd = new FormData();
    fd.append("action",     "apply");
    fd.append("csrf_token", getCsrfToken());
    fd.append("job_id",     jobId);
    fd.append("full_name",  g("appFullName"));
    fd.append("email",      "<?php echo htmlspecialchars($_SESSION['ojams_user']['email'], ENT_QUOTES); ?>");
    fd.append("contact",    g("appContact"));
    fd.append("address",    g("appAddress"));
    fd.append("birthdate",  document.getElementById("appBirthdate")?.value ?? "");
    fd.append("age",        document.getElementById("appAge")?.value ?? "0");
    fd.append("elementary", g("appElementary"));
    fd.append("jhs",        g("appJhs"));
    fd.append("shs",        g("appShs"));
    fd.append("college",    g("appCollege"));
    fd.append("skills",     g("appSkills"));
    fd.append("experience", g("appExperience"));

    const resumeFile = document.getElementById("appResume")?.files[0];
    if (resumeFile) fd.append("resume", resumeFile);

    fetch(APP_HANDLER, { method: "POST", body: fd })
    .then(r => r.json())
    .then(res => {
        bootstrap.Modal.getInstance(document.getElementById("applyJobModal"))?.hide();
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) setTimeout(() => location.reload(), 1200);
    })
    .catch(() => showToast("Request failed. Please try again.", "danger"))
    .finally(() => btnLoading(submitBtn, false));
}

// Resume file name preview
document.addEventListener("DOMContentLoaded", function () {
    const resumeInput = document.getElementById("appResume");
    if (resumeInput) {
        resumeInput.addEventListener("change", function () {
            const infoEl    = document.getElementById("resumeFileInfo");
            const nameEl    = document.getElementById("resumeFileName");
            const sizeEl    = document.getElementById("resumeFileSize");
            if (!this.files.length) { infoEl?.classList.add("d-none"); return; }
            const f = this.files[0];
            const kb = (f.size / 1024).toFixed(1);
            const mb = (f.size / (1024*1024)).toFixed(2);
            if (nameEl) nameEl.textContent = f.name;
            if (sizeEl) sizeEl.textContent = f.size > 1024*1024 ? mb + " MB" : kb + " KB";
            infoEl?.classList.remove("d-none");
        });
    }
});

// Filtering is now fully server-side via form GET — no client-side filterJobs needed.
</script>
<?php include $basePath . "layouts/footer.php"; ?>