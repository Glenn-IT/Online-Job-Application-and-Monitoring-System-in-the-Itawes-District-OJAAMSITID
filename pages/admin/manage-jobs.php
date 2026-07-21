<?php
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$pageTitle   = "OJAMS - Manage Jobs";
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

// ── Fetch filtered jobs with applicant counts ─────────────────
$stmt = $pdo->prepare("
    SELECT j.*, COUNT(a.id) AS applicant_count
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    {$whereSQL}
    GROUP BY j.id
    ORDER BY {$jobOrderSQL}
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$jobs = $stmt->fetchAll();

// ── Build URL helpers ─────────────────────────────────────────
function jobsPageUrl(int $p): string {
    $q = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
}
function jobsSortUrl(string $col): string {
    global $jobSortCol, $jobSortDir;
    $q = $_GET;
    $q['sort'] = $col;
    $q['dir']  = ($jobSortCol === $col && $jobSortDir === 'ASC') ? 'desc' : 'asc';
    $q['page'] = 1;
    return '?' . http_build_query(array_filter($q, fn($v) => $v !== ''));
}
function jobsSortIcon(string $col): string {
    global $jobSortCol, $jobSortDir;
    if ($jobSortCol !== $col) return '<i class="bi bi-arrow-down-up opacity-50 ms-1 small"></i>';
    return $jobSortDir === 'ASC'
        ? '<i class="bi bi-sort-up-alt text-warning ms-1"></i>'
        : '<i class="bi bi-sort-down text-warning ms-1"></i>';
}
function jobsSortTh(string $label, string $col): string {
    return '<a href="' . jobsSortUrl($col) . '" class="text-decoration-none text-white">'
         . htmlspecialchars($label) . jobsSortIcon($col) . '</a>';
}

// Include header and admin navbar
include $basePath . 'layouts/header.php';
include $basePath . 'layouts/navbar-admin.php';
?>

<div class="admin-layout">
    <?php include $basePath . 'layouts/sidebar-admin.php'; ?>
    <main class="admin-main">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-kanban me-2 text-primary"></i>Manage Jobs
                    </h2>
                    <p class="text-muted mb-0">Create, edit, and manage job postings.
                        <span class="small">(<?php echo $total; ?> result<?php echo $total !== 1 ? 's' : ''; ?>)</span>
                    </p>
                </div>
                <button class="btn btn-success" onclick="openAddJobModal()">
                    <i class="bi bi-plus-circle me-1"></i>Add New Job
                </button>
            </div>

            <!-- Search & Filter Bar -->
            <form method="get" action="" class="card border-0 shadow-sm mb-4 filter-form">
                <div class="card-body py-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">Search</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" name="search"
                                       placeholder="Title or company…"
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Status</label>
                            <select class="form-select form-select-sm" name="status">
                                <?php foreach (['All', 'Open', 'Closed'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>>
                                    <?php echo $s; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Job Type</label>
                            <select class="form-select form-select-sm" name="job_type">
                                <option value="">All Types</option>
                                <?php foreach (['Full-time','Part-time','Contract','Internship','Freelance'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $jobTypeFilter === $t ? 'selected' : ''; ?>>
                                    <?php echo $t; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Date From</label>
                            <input type="date" class="form-control form-control-sm" name="date_from"
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Date To</label>
                            <input type="date" class="form-control form-control-sm" name="date_to"
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <?php if ($search !== '' || $statusFilter !== 'All' || $jobTypeFilter !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
                            <a href="manage-jobs.php" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Bulk Action Toolbar (hidden until rows are selected) -->
            <div id="bulkToolbarJobs" class="d-none mb-3 p-3 rounded-3 border border-danger bg-danger bg-opacity-10 d-flex align-items-center gap-3 flex-wrap">
                <span class="fw-semibold text-danger">
                    <i class="bi bi-check2-square me-1"></i>
                    <span id="bulkCountJobs">0</span> selected
                </span>
                <button class="btn btn-sm btn-outline-danger" onclick="bulkJobDelete()">
                    <i class="bi bi-trash me-1"></i>Delete Selected
                </button>
                <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="clearJobSelection()">
                    <i class="bi bi-x-lg me-1"></i>Clear
                </button>
            </div>

            <!-- Jobs Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <?php
                            $columns = [
                                "<input type='checkbox' id='selectAllJobs' class='form-check-input'>",
                                '#',
                                jobsSortTh('Job Title',   'title'),
                                jobsSortTh('Company',     'company'),
                                'Location',
                                'Type',
                                jobsSortTh('Date Posted', 'date_posted'),
                                jobsSortTh('Deadline',    'deadline'),
                                jobsSortTh('Applicants',  'applicants'),
                                jobsSortTh('Status',      'status'),
                                'Actions',
                            ];
                            include $basePath . 'components/table-header.php';
                            ?>
                            <tbody id="jobsTableBody">
                                <?php if (empty($jobs)): ?>
                                    <tr><td colspan="11" class="text-center text-muted py-4">No jobs found. Add your first job post!</td></tr>
                                <?php else: ?>
                                <?php $count = 1; foreach ($jobs as $job): ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input job-checkbox" value="<?= $job['id'] ?>"></td>
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <i class="bi bi-briefcase me-1 text-primary"></i>
                                            <?= htmlspecialchars($job['title']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($job['company']) ?></td>
                                        <td>
                                            <?php if (!empty($job['location'])): ?>
                                            <small><i class="bi bi-geo-alt me-1 text-muted"></i><?= htmlspecialchars($job['location']) ?></small>
                                            <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($job['job_type'])): ?>
                                            <?php $typeBadge = match($job['job_type']) {
                                                'Full-time'  => 'bg-primary',
                                                'Part-time'  => 'bg-info text-dark',
                                                'Contract'   => 'bg-warning text-dark',
                                                'Internship' => 'bg-success',
                                                'Freelance'  => 'bg-secondary',
                                                default      => 'bg-light text-dark border',
                                            }; ?>
                                            <span class="badge <?= $typeBadge ?>"><?= $job['job_type'] ?></span>
                                            <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                                        </td>
                                        <td><?= $job['date_posted'] ?></td>
                                        <td>
                                            <?php if (!empty($job['deadline'])): ?>
                                                <?php
                                                $deadlinePast = strtotime($job['deadline']) < strtotime('today');
                                                $dlClass = $deadlinePast ? 'text-danger' : 'text-dark';
                                                ?>
                                                <span class="<?= $dlClass ?> small">
                                                    <i class="bi bi-calendar-x me-1"></i><?= $job['deadline'] ?>
                                                    <?= $deadlinePast ? '<span class="badge bg-danger ms-1">Expired</span>' : '' ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php $cnt = (int)$job['applicant_count']; ?>
                                            <span class="badge <?= $cnt > 0 ? 'bg-primary' : 'bg-light text-muted border' ?>">
                                                <i class="bi bi-people-fill me-1"></i><?= $cnt ?>
                                            </span>
                                            <?php if ($cnt > 0): ?>
                                            <a href="<?= $basePath ?>pages/admin/applications.php"
                                               class="ms-1 small text-primary text-decoration-none"
                                               title="View applicants">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $job['status'] === 'Open' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $job['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-warning me-1"
                                                onclick="editJob(
                                                    this.dataset.title,
                                                    this.dataset.company,
                                                    this.dataset.description,
                                                    this.dataset.qualification,
                                                    this.dataset.date,
                                                    this.dataset.status,
                                                    this.dataset.deadline,
                                                    <?= $job['id'] ?>)"
                                                data-title="<?= htmlspecialchars($job['title'], ENT_QUOTES) ?>"
                                                data-company="<?= htmlspecialchars($job['company'], ENT_QUOTES) ?>"
                                                data-description="<?= htmlspecialchars($job['description'], ENT_QUOTES) ?>"
                                                data-qualification="<?= htmlspecialchars($job['qualification'], ENT_QUOTES) ?>"
                                                data-location="<?= htmlspecialchars($job['location'] ?? '', ENT_QUOTES) ?>"
                                                data-jobtype="<?= htmlspecialchars($job['job_type'] ?? '', ENT_QUOTES) ?>"
                                                data-salary="<?= htmlspecialchars($job['salary_range'] ?? '', ENT_QUOTES) ?>"
                                                data-date="<?= $job['date_posted'] ?>"
                                                data-status="<?= $job['status'] ?>"
                                                data-deadline="<?= $job['deadline'] ?? '' ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                onclick="deleteJob(<?= $job['id'] ?>)">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $perPage, $total); ?> of <?php echo $total; ?>
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo jobsPageUrl($page - 1); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++):
                                if (!($p === 1 || $p === $totalPages || abs($p - $page) <= 2)) continue;
                            ?>
                                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo jobsPageUrl($p); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo jobsPageUrl($page + 1); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>

            </div>
    </main>
</div>

<!-- Modals -->
<?php include $basePath . 'modals/add-job-modal.php'; ?>

<script>
// ── Manage Jobs ──
const JOBS_HANDLER = '../../handlers/jobs.php';

// Override: open Add modal
function openAddJobModal() {
    _editingJobId = null;
    document.getElementById('addJobModalLabel').innerHTML =
        '<i class="bi bi-plus-circle me-2"></i>Add New Job Post';
    document.getElementById('addJobForm').reset();
    document.getElementById('jobDatePosted').value = new Date().toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('addJobModal')).show();
};

// Override: open Edit modal
function editJob(title, company, description, qualification, datePosted, status, deadline, id) {
    _editingJobId = id;
    const btn = event.currentTarget;
    document.getElementById('addJobModalLabel').innerHTML =
        '<i class="bi bi-pencil-square me-2"></i>Edit Job Post';
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
};

// Override: save (add or edit) via fetch
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
};

// ── Bulk selection ───────────────────────────────────────────
function getSelectedJobIds() {
    return [...document.querySelectorAll('.job-checkbox:checked')].map(cb => parseInt(cb.value));
}
function updateJobBulkToolbar() {
    const ids     = getSelectedJobIds();
    const toolbar = document.getElementById('bulkToolbarJobs');
    const counter = document.getElementById('bulkCountJobs');
    if (ids.length > 0) {
        toolbar.classList.remove('d-none');
        toolbar.classList.add('d-flex');
        counter.textContent = ids.length;
    } else {
        toolbar.classList.add('d-none');
        toolbar.classList.remove('d-flex');
    }
}
function clearJobSelection() {
    document.querySelectorAll('.job-checkbox').forEach(cb => cb.checked = false);
    const sa = document.getElementById('selectAllJobs');
    if (sa) sa.checked = false;
    updateJobBulkToolbar();
}
document.addEventListener('DOMContentLoaded', function () {
    const sa = document.getElementById('selectAllJobs');
    if (sa) {
        sa.addEventListener('change', function () {
            document.querySelectorAll('.job-checkbox').forEach(cb => cb.checked = this.checked);
            updateJobBulkToolbar();
        });
    }
    document.querySelectorAll('.job-checkbox').forEach(cb => {
        cb.addEventListener('change', updateJobBulkToolbar);
    });
});
function bulkJobDelete() {
    const ids = getSelectedJobIds();
    if (!ids.length) return;
    if (!confirm(`Permanently delete ${ids.length} job post(s) and all their applications? This cannot be undone.`)) return;
    fetch(JOBS_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulkDelete', ids, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 900);
    })
    .catch(() => showToast('Request failed.', 'danger'));
}

// Override: delete via fetch
function deleteJob(id) {
    if (!confirm('Are you sure you want to delete this job post?')) return;
    fetch(JOBS_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: id, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.success ? 'Job deleted.' : res.message, res.success ? 'danger' : 'warning');
        if (res.success) setTimeout(() => location.reload(), 800);
    })
    .catch(() => showToast('Request failed. Please try again.', 'danger'));
};
</script>
<?php include $basePath . 'layouts/footer.php'; ?>