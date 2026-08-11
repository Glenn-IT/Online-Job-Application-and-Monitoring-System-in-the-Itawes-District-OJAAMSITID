<?php
require_once __DIR__ . '/../../config/auth.php';
requireStaff();

$pageTitle   = "OJAMS - Staff Job Postings";
$basePath    = "../../";
$currentPage = "manage-jobs";

// ── Filters from URL ─────────────────────────────────────────
$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status']   ?? 'All';
$jobTypeFilter= $_GET['job_type'] ?? '';
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to']   ?? '';
if (!in_array($statusFilter, ['All', 'Open', 'Closed'])) $statusFilter = 'All';
$allowedTypes = ['Full-time','Part-time','Contract','Internship','Freelance'];
if (!in_array($jobTypeFilter, $allowedTypes)) $jobTypeFilter = '';

// ── Build WHERE clause ────────────────────────────────────────
$where  = [];
$params = [];
if ($search !== '') {
    $where[]  = "(j.title LIKE ? OR j.company LIKE ? OR j.location LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($jobTypeFilter !== '') {
    $where[]  = "j.job_type = ?";
    $params[] = $jobTypeFilter;
}
if ($statusFilter !== 'All') {
    $where[]  = "j.status = ?";
    $params[] = $statusFilter;
}
if ($dateFrom !== '') {
    $where[]  = "j.date_posted >= ?";
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[]  = "j.date_posted <= ?";
    $params[] = $dateTo;
}
$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// ── Sorting ───────────────────────────────────────────────────
$allowedJobSorts = [
    'title'       => 'j.title',
    'company'     => 'j.company',
    'date_posted' => 'j.date_posted',
    'deadline'    => 'j.deadline',
    'applicants'  => 'applicant_count',
    'status'      => 'j.status',
];
$jobSortCol = $_GET['sort'] ?? 'date_posted';
$jobSortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
if (!array_key_exists($jobSortCol, $allowedJobSorts)) $jobSortCol = 'date_posted';
$jobOrderSQL = $allowedJobSorts[$jobSortCol] . ' ' . $jobSortDir;

// ── Pagination ────────────────────────────────────────────────
$perPage    = PER_PAGE_ADMIN;
$page       = max(1, (int)($_GET['page'] ?? 1));
$countStmt  = $pdo->prepare("SELECT COUNT(*) FROM jobs j {$whereSQL}");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ── Fetch filtered jobs with applicant counts & poster details ────
$stmt = $pdo->prepare("
    SELECT j.*, COUNT(a.id) AS applicant_count, u.full_name AS poster_name, u.role AS poster_role
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    LEFT JOIN users u ON u.id = j.created_by
    {$whereSQL}
    GROUP BY j.id
    ORDER BY {$jobOrderSQL}
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$jobs = $stmt->fetchAll();

// ── Build URL helpers ─────────────────────────────────────────
function staffJobsPageUrl(int $p): string {
    $q = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
}
function staffJobsSortUrl(string $col): string {
    global $jobSortCol, $jobSortDir;
    $q = $_GET;
    $q['sort'] = $col;
    $q['dir']  = ($jobSortCol === $col && $jobSortDir === 'ASC') ? 'desc' : 'asc';
    $q['page'] = 1;
    return '?' . http_build_query(array_filter($q, fn($v) => $v !== ''));
}
function staffJobsSortIcon(string $col): string {
    global $jobSortCol, $jobSortDir;
    if ($jobSortCol !== $col) return '<i class="bi bi-arrow-down-up opacity-50 ms-1 small"></i>';
    return $jobSortDir === 'ASC'
        ? '<i class="bi bi-sort-up-alt text-warning ms-1"></i>'
        : '<i class="bi bi-sort-down text-warning ms-1"></i>';
}
function staffJobsSortTh(string $label, string $col): string {
    return '<a href="' . staffJobsSortUrl($col) . '" class="text-decoration-none text-white">'
         . htmlspecialchars($label) . staffJobsSortIcon($col) . '</a>';
}

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-staff.php";
?>
<div class="admin-layout">
    <?php include $basePath . "layouts/sidebar-staff.php"; ?>
    <main class="admin-main">
        <!-- Toast Container -->
        <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;"></div>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-kanban me-2 text-warning"></i>Job Listings &amp; Postings
                </h2>
                <p class="text-muted mb-0 small">
                    Post new opportunities, edit job qualifications, and update listing status.
                    <span class="small">(<?= $total ?> job<?= $total !== 1 ? 's' : '' ?> found)</span>
                </p>
            </div>
            <div>
                <button type="button" class="btn btn-warning" onclick="openAddJobModal()">
                    <i class="bi bi-plus-lg me-1"></i>Post New Job
                </button>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="get" action="manage-jobs.php" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search job title, company, location..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="All" <?= $statusFilter === 'All' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="Open" <?= $statusFilter === 'Open' ? 'selected' : '' ?>>Open Only</option>
                            <option value="Closed" <?= $statusFilter === 'Closed' ? 'selected' : '' ?>>Closed Only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="job_type" class="form-select form-select-sm">
                            <option value="">All Job Types</option>
                            <?php foreach ($allowedTypes as $type): ?>
                                <option value="<?= $type ?>" <?= $jobTypeFilter === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-1">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>" title="Posted after">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>" title="Posted before">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-warning btn-sm w-100"><i class="bi bi-funnel-fill"></i></button>
                        <?php if ($search !== '' || $statusFilter !== 'All' || $jobTypeFilter !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
                            <a href="manage-jobs.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th><?= staffJobsSortTh('Job Title & Company', 'title') ?></th>
                            <th>Posted By</th>
                            <th>Job Type / Location</th>
                            <th>Salary Range</th>
                            <th><?= staffJobsSortTh('Applicants', 'applicants') ?></th>
                            <th><?= staffJobsSortTh('Date Posted', 'date_posted') ?></th>
                            <th><?= staffJobsSortTh('Status', 'status') ?></th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jobs)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-briefcase fs-1 d-block mb-2 text-secondary"></i>
                                    No job posts found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($job['title']) ?></div>
                                        <div class="text-muted small"><i class="bi bi-building me-1"></i><?= htmlspecialchars($job['company']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($job['poster_name'])): ?>
                                            <div class="small fw-semibold text-dark"><?= htmlspecialchars($job['poster_name']) ?></div>
                                            <span class="badge <?= ($job['poster_role'] ?? '') === 'admin' ? 'bg-danger' : (($job['poster_role'] ?? '') === 'staff' ? 'bg-warning text-dark' : 'bg-secondary') ?>" style="font-size:0.68rem;">
                                                <?= ucfirst($job['poster_role'] ?? 'System') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-dark mb-1"><?= htmlspecialchars($job['job_type'] ?: 'Full-time') ?></span>
                                        <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($job['location'] ?: 'Not specified') ?></div>
                                    </td>
                                    <td class="small fw-semibold text-dark">
                                        <?= htmlspecialchars($job['salary_range'] ?: 'Unspecified') ?>
                                    </td>
                                    <td>
                                        <a href="applications.php?search=<?= urlencode($job['title']) ?>" class="badge bg-primary rounded-pill text-decoration-none" title="View Applicants">
                                            <i class="bi bi-people-fill me-1"></i><?= (int)$job['applicant_count'] ?>
                                        </a>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('M d, Y', strtotime($job['date_posted'])) ?>
                                        <?php if (!empty($job['deadline'])): ?>
                                            <div class="small text-danger">Closes: <?= date('M d, Y', strtotime($job['deadline'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $job['status'] === 'Open' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $job['status'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                                data-location="<?= htmlspecialchars($job['location'] ?? '') ?>"
                                                data-jobtype="<?= htmlspecialchars($job['job_type'] ?? '') ?>"
                                                data-salary="<?= htmlspecialchars($job['salary_range'] ?? '') ?>"
                                                onclick="editJob('<?= addslashes(htmlspecialchars($job['title'])) ?>', '<?= addslashes(htmlspecialchars($job['company'])) ?>', '<?= addslashes(htmlspecialchars(str_replace(["\r", "\n"], ' ', $job['description']))) ?>', '<?= addslashes(htmlspecialchars(str_replace(["\r", "\n"], ' ', $job['qualification']))) ?>', '<?= $job['date_posted'] ?>', '<?= $job['status'] ?>', '<?= $job['deadline'] ?? '' ?>', <?= $job['id'] ?>)"
                                                title="Edit Job Post">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm <?= $job['status'] === 'Open' ? 'btn-outline-secondary' : 'btn-outline-success' ?>" onclick="toggleJobStatus(<?= $job['id'] ?>, '<?= $job['status'] === 'Open' ? 'Closed' : 'Open' ?>')" title="Toggle Open/Closed">
                                            <i class="bi <?= $job['status'] === 'Open' ? 'bi-lock-fill' : 'bi-unlock-fill' ?>"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Page <?= $page ?> of <?= $totalPages ?> (Total <?= $total ?>)
                    </div>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= staffJobsPageUrl($page - 1) ?>">Prev</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= staffJobsPageUrl($p) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= staffJobsPageUrl($page + 1) ?>">Next</a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include $basePath . 'modals/add-job-modal.php'; ?>

<script>
const JOBS_HANDLER = '<?= $basePath ?>handlers/jobs.php';

function openAddJobModal() {
    _editingJobId = null;
    const modalLabel = document.getElementById('addJobModalLabel');
    if (modalLabel) {
        modalLabel.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add New Job Post';
    }
    const form = document.getElementById('addJobForm');
    if (form) form.reset();
    const datePosted = document.getElementById('jobDatePosted');
    if (datePosted) datePosted.value = new Date().toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('addJobModal')).show();
}

function editJob(title, company, description, qualification, datePosted, status, deadline, id) {
    _editingJobId = id;
    const btn = event.currentTarget;
    const modalLabel = document.getElementById('addJobModalLabel');
    if (modalLabel) {
        modalLabel.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Job Post';
    }
    document.getElementById('jobTitle').value         = title;
    document.getElementById('jobCompany').value       = company;
    document.getElementById('jobDescription').value   = description;
    document.getElementById('jobQualification').value = qualification;
    document.getElementById('jobLocation').value      = btn.dataset.location    || '';
    document.getElementById('jobType').value          = btn.dataset.jobtype     || '';
    document.getElementById('jobSalaryRange').value   = btn.dataset.salary      || '';
    document.getElementById('jobDatePosted').value    = datePosted;
    document.getElementById('jobStatus').value        = status;
    document.getElementById('jobDeadline').value      = deadline || '';
    new bootstrap.Modal(document.getElementById('addJobModal')).show();
}

function saveJob() {
    const title         = document.getElementById('jobTitle')?.value.trim();
    const company       = document.getElementById('jobCompany')?.value.trim();
    const description   = document.getElementById('jobDescription')?.value.trim();
    const qualification = document.getElementById('jobQualification')?.value.trim();
    const location      = document.getElementById('jobLocation')?.value.trim()     || null;
    const job_type      = document.getElementById('jobType')?.value                || null;
    const salary_range  = document.getElementById('jobSalaryRange')?.value.trim() || null;
    const date_posted   = document.getElementById('jobDatePosted')?.value;
    const status        = document.getElementById('jobStatus')?.value;
    const deadline      = document.getElementById('jobDeadline')?.value            || null;

    clearAllFieldErrors('addJobForm');
    let valid = true;
    if (!title)         { showFieldError('jobTitle',         'Job title is required.');     valid = false; }
    if (!company)       { showFieldError('jobCompany',       'Company is required.');       valid = false; }
    if (!description)   { showFieldError('jobDescription',   'Description is required.');  valid = false; }
    if (!qualification) { showFieldError('jobQualification', 'Qualifications are required.'); valid = false; }
    if (salary_range && /[a-zA-Z]/.test(salary_range)) { showFieldError('jobSalaryRange', 'Salary range cannot contain letters.'); valid = false; }
    if (!valid) return;

    const payload = _editingJobId
        ? { action: 'edit', id: _editingJobId, title, company, description, qualification, location, job_type, salary_range, date_posted, status, deadline, csrf_token: getCsrfToken() }
        : { action: 'add',                     title, company, description, qualification, location, job_type, salary_range, date_posted, status, deadline, csrf_token: getCsrfToken() };

    const saveBtn = document.getElementById('saveJobBtn');
    btnLoading(saveBtn, true, 'Saving…');

    fetch(JOBS_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        bootstrap.Modal.getInstance(document.getElementById('addJobModal'))?.hide();
        if (res.success) {
            showToast(_editingJobId ? 'Job updated successfully!' : 'Job added successfully!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(() => showToast('Request failed. Please try again.', 'danger'))
    .finally(() => btnLoading(saveBtn, false));
}

function toggleJobStatus(jobId, newStatus) {
    if (!confirm(`Switch job listing status to ${newStatus}?`)) return;

    fetch(JOBS_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'edit', id: jobId, status: newStatus, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(`Job status updated to ${newStatus}`, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Action failed.', 'danger');
        }
    })
    .catch(err => showToast('An error occurred. Please try again.', 'danger'));
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>
