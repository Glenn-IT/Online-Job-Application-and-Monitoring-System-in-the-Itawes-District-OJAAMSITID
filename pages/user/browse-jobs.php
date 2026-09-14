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
$savedFilter   = !empty($_GET['saved']);
$allowedStatus = ['', 'Open', 'Closed'];
if (!in_array($statusFilter, $allowedStatus)) $statusFilter = '';
$allowedTypes  = ['', 'Full-time','Part-time','Contract','Internship','Freelance'];
if (!in_array($jobTypeFilter, $allowedTypes)) $jobTypeFilter = '';

// ── User Saved Jobs ─────────────────────────────────────────
$userId = (int)$_SESSION["ojams_user"]["id"];
$savedStmt = $pdo->prepare("SELECT job_id FROM saved_jobs WHERE user_id = ?");
$savedStmt->execute([$userId]);
$savedJobIds = array_column($savedStmt->fetchAll(), "job_id");
$savedCount  = count($savedJobIds);

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
if ($savedFilter) {
    if (empty($savedJobIds)) {
        $where[] = "1 = 0"; // No saved jobs, return empty
    } else {
        $inPlaceholders = implode(',', array_fill(0, count($savedJobIds), '?'));
        $where[] = "j.id IN ({$inPlaceholders})";
        foreach ($savedJobIds as $sId) {
            $params[] = $sId;
        }
    }
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

// ── Fetch paginated jobs with poster details ─────────────────
$jobsStmt = $pdo->prepare("
    SELECT j.*, u.full_name AS poster_name, u.role AS poster_role
    FROM jobs j
    LEFT JOIN users u ON u.id = j.created_by
    {$whereSQL}
    ORDER BY j.date_posted DESC
    LIMIT ? OFFSET ?
");
$jobsStmt->execute(array_merge($params, [$perPage, $offset]));
$jobs = $jobsStmt->fetchAll();

// Applied job IDs for this user
$appliedStmt = $pdo->prepare("SELECT job_id FROM applications WHERE user_id = ?");
$appliedStmt->execute([$userId]);
$appliedJobIds = array_column($appliedStmt->fetchAll(), "job_id");

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
        ['bg' => '#eef2ff', 'color' => '#4338ca'], // Indigo
        ['bg' => '#f0fdf4', 'color' => '#15803d'], // Emerald
        ['bg' => '#f0f9ff', 'color' => '#0369a1'], // Sky
        ['bg' => '#fefce8', 'color' => '#a16207'], // Amber
        ['bg' => '#faf5ff', 'color' => '#7e22ce'], // Purple
        ['bg' => '#fff1f2', 'color' => '#be123c'], // Rose
        ['bg' => '#f5f3ff', 'color' => '#6d28d9'], // Violet
        ['bg' => '#ecfeff', 'color' => '#0e7490'], // Cyan
    ];
    $hash = crc32($company);
    $idx = abs($hash) % count($palettes);
    return $palettes[$idx];
}

function formatJobPostingTime(string $dateStr): string {
    $timestamp = strtotime($dateStr);
    if (!$timestamp) return $dateStr;
    $diff = time() - $timestamp;
    if ($diff < 86400 && date('Y-m-d') === date('Y-m-d', $timestamp)) {
        return 'Posted today';
    }
    $days = (int)floor($diff / 86400);
    if ($days === 1) return 'Yesterday';
    if ($days < 7) return $days . 'd ago';
    if ($days < 30) return ceil($days / 7) . 'w ago';
    return date('M d, Y', $timestamp);
}

function browseFilterUrl(array $overrides = []): string {
    $q = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    if (isset($overrides['job_type']) || isset($overrides['saved']) || isset($overrides['status']) || isset($overrides['search'])) {
        unset($q['page']);
    }
    return 'browse-jobs.php' . (!empty($q) ? '?' . http_build_query($q) : '');
}

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-user.php";
?>
<div class="container py-4">

    <!-- ── Modern Discovery Hero Card ──────────────────────── -->
    <div class="browse-hero-card">
        <div class="position-relative" style="z-index: 2;">
            <div class="row align-items-center mb-3">
                <div class="col-lg-8">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2 px-3 py-1 fw-semibold d-inline-flex align-items-center gap-2" style="letter-spacing: 0.5px;">
                        <img src="<?= $basePath ?>img/Piat-Logo.png" alt="Piat Logo" style="width: 18px; height: 18px; object-fit: contain;">
                        <span>Municipality of Piat &bull; Explore Opportunities</span>
                    </span>
                    <h1 class="fw-bold mb-2 text-dark" style="font-size: clamp(1.6rem, 3.5vw, 2.3rem); letter-spacing: -0.5px; color: #0f172a !important;">
                        Find the Career You Deserve
                    </h1>
                    <p class="text-secondary mb-3 mb-lg-4" style="font-size: 0.98rem; max-width: 600px; line-height: 1.6;">
                        Browse verified job openings across the Itawes district and apply directly in minutes with your saved profile.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end d-none d-lg-block">
                    <div class="d-inline-flex flex-column align-items-end p-3 rounded-4 bg-white border shadow-sm">
                        <span class="text-muted small fw-semibold">Available Openings</span>
                        <span class="fs-2 fw-bold text-primary"><?= number_format($totalJobs) ?></span>
                        <span class="text-muted" style="font-size: 0.75rem;">Updated in real time</span>
                    </div>
                </div>
            </div>

            <!-- Unified Search Box -->
            <form method="get" action="browse-jobs.php" class="browse-search-box">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0 text-primary ps-3 pe-2">
                                <i class="bi bi-search fs-5"></i>
                            </span>
                            <input type="text" class="form-control ps-1" name="search"
                                   placeholder="Job title, company, skills, or location…"
                                   value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 border-start-md">
                        <select class="form-select text-secondary ps-3" name="status">
                            <option value="">Status: All</option>
                            <option value="Open" <?= $statusFilter === 'Open' ? 'selected' : '' ?>>Status: Open Only</option>
                            <option value="Closed" <?= $statusFilter === 'Closed' ? 'selected' : '' ?>>Status: Closed</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <?php if ($jobTypeFilter !== ''): ?>
                            <input type="hidden" name="job_type" value="<?= htmlspecialchars($jobTypeFilter, ENT_QUOTES) ?>">
                        <?php endif; ?>
                        <?php if ($savedFilter): ?>
                            <input type="hidden" name="saved" value="1">
                        <?php endif; ?>
                        <button type="submit" class="btn btn-search w-100 py-2">
                            <i class="bi bi-search me-1"></i>Search Jobs
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Quick-Filter Pills & Stats Bar ──────────────────── -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <!-- Scrollable Filter Chips -->
        <div class="filter-pills-scroll flex-grow-1 w-100">
            <a href="<?= browseFilterUrl(['job_type' => '', 'saved' => '']) ?>" 
               class="filter-chip <?= ($jobTypeFilter === '' && !$savedFilter) ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i>All Jobs
            </a>
            <?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'] as $t): ?>
            <a href="<?= browseFilterUrl(['job_type' => ($jobTypeFilter === $t ? '' : $t), 'saved' => '']) ?>" 
               class="filter-chip <?= ($jobTypeFilter === $t && !$savedFilter) ? 'active' : '' ?>">
                <?= htmlspecialchars($t) ?>
            </a>
            <?php endforeach; ?>
            <a href="<?= browseFilterUrl(['saved' => $savedFilter ? '' : '1']) ?>" 
               class="filter-chip <?= $savedFilter ? 'active' : '' ?>"
               title="View bookmarked jobs">
                <i class="bi bi-bookmark<?= $savedFilter ? '-fill' : '' ?>"></i>Saved Jobs
                <span class="badge rounded-pill <?= $savedFilter ? 'bg-white text-primary' : 'bg-primary-subtle text-primary' ?> ms-1" style="font-size: 0.72rem;">
                    <?= $savedCount ?>
                </span>
            </a>
        </div>

        <!-- Result count & reset filter button -->
        <div class="d-flex align-items-center justify-content-between justify-content-md-end w-100 w-md-auto gap-2 flex-shrink-0 text-nowrap">
            <span class="text-muted small">
                Showing <strong><?= $filteredTotal ?></strong> job<?= $filteredTotal !== 1 ? 's' : '' ?>
            </span>
            <?php if ($search !== '' || $statusFilter !== '' || $jobTypeFilter !== '' || $savedFilter): ?>
            <a href="browse-jobs.php" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Reset all filters">
                <i class="bi bi-x-circle me-1"></i>Reset
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Active Filter Indicator (if any) ────────────────── -->
    <?php if ($search !== '' || $statusFilter !== '' || $jobTypeFilter !== '' || $savedFilter): ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-4 p-2 px-3 bg-light rounded-3 border">
        <span class="text-muted small fw-semibold"><i class="bi bi-funnel me-1"></i>Active Filters:</span>
        <?php if ($search !== ''): ?>
            <span class="badge bg-white text-dark border">Keyword: "<?= htmlspecialchars($search) ?>"</span>
        <?php endif; ?>
        <?php if ($statusFilter !== ''): ?>
            <span class="badge bg-white text-dark border">Status: <?= htmlspecialchars($statusFilter) ?></span>
        <?php endif; ?>
        <?php if ($jobTypeFilter !== ''): ?>
            <span class="badge bg-white text-dark border">Type: <?= htmlspecialchars($jobTypeFilter) ?></span>
        <?php endif; ?>
        <?php if ($savedFilter): ?>
            <span class="badge bg-white text-warning border"><i class="bi bi-bookmark-fill me-1"></i>Saved Jobs Only</span>
        <?php endif; ?>
        <a href="browse-jobs.php" class="small text-danger text-decoration-none ms-auto fw-semibold">
            Clear all
        </a>
    </div>
    <?php endif; ?>

    <!-- ── Job Cards Grid ──────────────────────────────────── -->
    <div class="row" id="jobCardsContainer">
        <?php if (empty($jobs)): ?>
            <div class="col-12 py-5 text-center">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-briefcase text-muted fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">No Jobs Found</h4>
                <p class="text-muted mb-4" style="max-width: 480px; margin: 0 auto;">
                    <?php if ($savedFilter): ?>
                        You haven't saved any job postings yet. Click the bookmark icon on any job card to save it for later.
                    <?php else: ?>
                        We couldn't find any job postings matching your current search or filter criteria. Try adjusting your search query.
                    <?php endif; ?>
                </p>
                <a href="browse-jobs.php" class="btn btn-primary btn-sm px-3 py-2">
                    <i class="bi bi-arrow-repeat me-1"></i>Browse All Jobs
                </a>
            </div>
        <?php else: ?>
        <?php foreach ($jobs as $job):
            $jobId          = (int)$job["id"];
            $alreadyApplied = in_array($jobId, $appliedJobIds);
            $isSaved        = in_array($jobId, $savedJobIds);
            $isOpen         = $job["status"] === "Open";
            $cnt            = $appCounts[$jobId] ?? 0;
            $company        = $job["company"] ?? "Company";
            $initials       = getCompanyInitials($company);
            $palette        = getCompanyPalette($company);
            $timeAgo        = formatJobPostingTime($job["date_posted"] ?? "");
            $jtSlug         = strtolower(str_replace(['-', ' '], '', $job['job_type'] ?? ''));
        ?>
        <div class="col-12 col-md-6 col-lg-4 mb-4">
            <div class="job-card-modern h-100 p-3 p-sm-4 shadow-sm">
                
                <!-- Card Header: Company Monogram + Names + Bookmark Toggle -->
                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <div class="company-monogram" style="background: <?= $palette['bg'] ?>; color: <?= $palette['color'] ?>;">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <div class="min-w-0">
                            <div class="text-muted small fw-semibold text-truncate" title="<?= htmlspecialchars($company) ?>">
                                <i class="bi bi-building me-1"></i><?= htmlspecialchars($company) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.74rem;">
                                <i class="bi bi-clock me-1"></i><?= $timeAgo ?>
                            </div>
                        </div>
                    </div>
                    <button type="button" 
                            class="btn-bookmark <?= $isSaved ? 'is-saved' : '' ?>" 
                            onclick="toggleSaveJob(this, <?= $jobId ?>)"
                            title="<?= $isSaved ? 'Remove from saved' : 'Save job' ?>"
                            aria-label="Bookmark job">
                        <i class="bi <?= $isSaved ? 'bi-bookmark-fill' : 'bi-bookmark' ?>"></i>
                    </button>
                </div>

                <!-- Job Title & Status -->
                <div class="mb-2">
                    <h5 class="fw-bold mb-1" style="font-size: 1.05rem; line-height: 1.35;">
                        <a href="job-detail.php?id=<?= $jobId ?>" class="text-decoration-none text-dark hover-primary text-truncate d-block" title="<?= htmlspecialchars($job['title']) ?>">
                            <?= htmlspecialchars($job['title']) ?>
                        </a>
                    </h5>
                </div>

                <!-- Soft Modern Badge Tags (Job Type, Salary, Location) -->
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <?php if (!empty($job['job_type'])): ?>
                    <span class="badge-tag tag-<?= $jtSlug ?>">
                        <i class="bi bi-briefcase"></i><?= htmlspecialchars($job['job_type']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($job['salary_range'])): ?>
                    <span class="badge-tag tag-salary">
                        <i class="bi bi-cash"></i><?= htmlspecialchars($job['salary_range']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($job['location'])): ?>
                    <span class="badge-tag tag-location text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($job['location']) ?>">
                        <i class="bi bi-geo-alt"></i><?= htmlspecialchars($job['location']) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- 2-Line Description Snippet -->
                <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.55;">
                    <?= htmlspecialchars($job['description']) ?>
                </p>

                <!-- Mini Qualifications Snippet -->
                <div class="mb-3 text-truncate" style="font-size: 0.78rem;">
                    <span class="text-muted">
                        <i class="bi bi-mortarboard me-1 text-primary"></i><strong>Req:</strong> <?= htmlspecialchars($job['qualification']) ?>
                    </span>
                </div>

                <!-- Metadata Row: Applicants & Deadline / Status -->
                <div class="pt-2 border-top d-flex justify-content-between align-items-center mb-3 text-muted" style="font-size: 0.78rem;">
                    <span>
                        <i class="bi bi-people-fill text-primary me-1"></i><strong><?= $cnt ?></strong> applicant<?= $cnt !== 1 ? 's' : '' ?>
                    </span>
                    <?php if (!empty($job['deadline'])): ?>
                    <?php $isPast = strtotime($job['deadline']) < strtotime('today'); ?>
                    <span class="<?= $isPast ? 'text-danger fw-semibold' : 'text-muted' ?>">
                        <i class="bi bi-calendar-x me-1"></i><?= $isPast ? 'Expired' : date('M d', strtotime($job['deadline'])) ?>
                    </span>
                    <?php else: ?>
                    <span class="badge <?= $isOpen ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?>">
                        <?= $isOpen ? 'Open' : 'Closed' ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons: View Details & Apply -->
                <div class="d-flex gap-2 pt-1 mt-auto">
                    <a href="job-detail.php?id=<?= $jobId ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                        <i class="bi bi-eye me-1"></i>Details
                    </a>
                    <?php if ($alreadyApplied): ?>
                        <button class="btn btn-success btn-sm flex-grow-1" disabled>
                            <i class="bi bi-check-circle me-1"></i>Applied
                        </button>
                    <?php elseif ($isOpen): ?>
                        <button class="btn btn-primary btn-sm flex-grow-1"
                                onclick="openApplyModal(<?= $jobId ?>, '<?= htmlspecialchars($job['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($job['company'], ENT_QUOTES) ?>')">
                            <i class="bi bi-send me-1"></i>Apply
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm flex-grow-1" disabled>
                            <i class="bi bi-lock me-1"></i>Closed
                        </button>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── Pagination ──────────────────────────────────────── -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
        <small class="text-muted text-center text-sm-start">
            Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $filteredTotal) ?>
            of <?= $filteredTotal ?> jobs
        </small>
        <ul class="pagination mb-0 justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= browseFilterUrl(['page' => $page - 1]) ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++):
                if (!($p === 1 || $p === $totalPages || abs($p - $page) <= 2)) continue;
                if ($p > 1 && abs($p - $page) === 3): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= browseFilterUrl(['page' => $p]) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= browseFilterUrl(['page' => $page + 1]) ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include $basePath . "modals/apply-job-modal.php"; ?>

<script>
const APP_HANDLER = "../../handlers/applications.php";
const SAVED_HANDLER = "../../handlers/saved-jobs.php";

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

// ── Age Trapping & Auto-computation ──────────────────────────
function computeAge(birthdate) {
    if (!birthdate) return "";
    const today = new Date();
    const dob   = new Date(birthdate);
    if (isNaN(dob.getTime())) return "";
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
    clearAllFieldErrors("applicationForm");
    document.getElementById("resumeFileInfo")?.classList.add("d-none");

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

    // Auto-compute age and check trapping from session birthdate
    const ageEl = document.getElementById("appAge");
    if (sess.birthdate) {
        const computed = computeAge(sess.birthdate);
        if (ageEl) ageEl.value = computed;
        if (computed !== "" && computed < 18) {
            showFieldError("appBirthdate", "Applicants must be at least 18 years old to apply.");
        }
    } else if (ageEl) {
        ageEl.value = "";
    }
    new bootstrap.Modal(document.getElementById("applyJobModal")).show();
}

// Auto-compute age and enforce trapping when birthdate input changes
document.addEventListener("DOMContentLoaded", function () {
    const bdEl = document.getElementById("appBirthdate");
    const ageEl = document.getElementById("appAge");
    if (bdEl) {
        // Enforce max date to 18 years ago in the datepicker
        const d = new Date();
        d.setFullYear(d.getFullYear() - 18);
        bdEl.max = d.toISOString().split("T")[0];

        bdEl.addEventListener("change", function () {
            clearFieldError("appBirthdate");
            const age = computeAge(this.value);
            if (ageEl) ageEl.value = age;
            if (this.value) {
                if (age === "" || age < 18) {
                    showFieldError("appBirthdate", "Applicants must be at least 18 years old to apply.");
                } else if (age > 80) {
                    showFieldError("appBirthdate", "Age must be 80 years old or below.");
                }
            }
        });
    }

    const resumeInput = document.getElementById("appResume");
    if (resumeInput) {
        resumeInput.addEventListener("change", function () {
            clearFieldError("appResume");
            const infoEl = document.getElementById("resumeFileInfo");
            const nameEl = document.getElementById("resumeFileName");
            const sizeEl = document.getElementById("resumeFileSize");
            if (!this.files.length) { infoEl?.classList.add("d-none"); return; }
            const f = this.files[0];
            const kb = (f.size / 1024).toFixed(1);
            const mb = (f.size / (1024 * 1024)).toFixed(2);
            if (nameEl) nameEl.textContent = f.name;
            if (sizeEl) sizeEl.textContent = f.size > 1024 * 1024 ? mb + " MB" : kb + " KB";
            infoEl?.classList.remove("d-none");
        });
    }
});

function submitApplication() {
    const form  = document.getElementById("applicationForm");
    const jobId = form ? form.dataset.jobId : null;
    if (!jobId) { showToast("No job selected.", "danger"); return; }

    const g = (id) => document.getElementById(id) ? document.getElementById(id).value.trim() : "";
    clearAllFieldErrors("applicationForm");
    let valid = true;

    // 1. Full Name
    if (!g("appFullName")) { showFieldError("appFullName", "Full name is required."); valid = false; }

    // 2. Address
    if (!g("appAddress"))  { showFieldError("appAddress",  "Address is required.");   valid = false; }

    // 3. Contact Number (exactly 11 digits)
    const contactVal = g("appContact");
    if (!contactVal) {
        showFieldError("appContact", "Contact number is required."); valid = false;
    } else if (!/^\d{11}$/.test(contactVal)) {
        showFieldError("appContact", "Contact number must be exactly 11 digits (numbers only)."); valid = false;
    }

    // 4. Birthdate & Age Trapping (Must be at least 18 years old)
    const bdVal = document.getElementById("appBirthdate")?.value;
    if (!bdVal) {
        showFieldError("appBirthdate", "Birthdate is required."); valid = false;
    } else {
        const bd  = new Date(bdVal);
        const now = new Date();
        if (bd >= now) {
            showFieldError("appBirthdate", "Birthdate cannot be a future date."); valid = false;
        } else {
            const age = computeAge(bdVal);
            if (age === "" || age < 18) {
                showFieldError("appBirthdate", "Applicants must be at least 18 years old to apply."); valid = false;
            } else if (age > 80) {
                showFieldError("appBirthdate", "Age must be 80 years old or below."); valid = false;
            }
        }
    }

    // 5. Educational Attainment (All Required)
    if (!g("appElementary")) { showFieldError("appElementary", "Elementary school is required."); valid = false; }
    if (!g("appJhs"))        { showFieldError("appJhs", "Junior High School (JHS) is required."); valid = false; }
    if (!g("appShs"))        { showFieldError("appShs", "Senior High School (SHS) is required."); valid = false; }
    if (!g("appCollege"))    { showFieldError("appCollege", "College education is required."); valid = false; }

    // 6. Additional Information (All Required)
    if (!g("appSkills"))     { showFieldError("appSkills", "Skills are required."); valid = false; }
    if (!g("appExperience")) { showFieldError("appExperience", "Work experience is required (enter N/A if none)."); valid = false; }

    // 7. Resume Upload (Required)
    const resumeInput = document.getElementById("appResume");
    const resumeFile  = resumeInput?.files ? resumeInput.files[0] : null;
    if (!resumeFile) {
        showFieldError("appResume", "Resume / CV file is required."); valid = false;
    }

    if (!valid) {
        showToast("All fields are required. Please fill in all fields correctly.", "warning");
        return;
    }

    const submitBtn = document.getElementById("submitAppBtn");
    btnLoading(submitBtn, true, "Submitting…");

    bootstrap.Modal.getInstance(document.getElementById("applyJobModal"))?.hide();
    showLoadingModal("Submitting application…");

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
    fd.append("resume",     resumeFile);

    fetch(APP_HANDLER, { method: "POST", body: fd })
    .then(r => r.json())
    .then(res => {
        hideLoadingModal();
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) setTimeout(() => location.reload(), 1000);
    })
    .catch(() => {
        hideLoadingModal();
        showToast("Request failed. Please try again.", "danger");
    })
    .finally(() => btnLoading(submitBtn, false));
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>