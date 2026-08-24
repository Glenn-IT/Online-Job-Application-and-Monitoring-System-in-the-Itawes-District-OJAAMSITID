<?php
require_once __DIR__ . "/../../config/auth.php";
requireStaff();

$pageTitle   = "OJAMS - Staff Applications";
$basePath    = "../../";
$currentPage = "applications";

// ── Filters from URL ─────────────────────────────────────────
$filter    = $_GET["status"]    ?? "All";
$search    = trim($_GET["search"]    ?? "");
$dateFrom  = $_GET["date_from"] ?? "";
$dateTo    = $_GET["date_to"]   ?? "";
$allowed   = ["All", "Pending", "Approved", "Rejected"];
if (!in_array($filter, $allowed)) $filter = "All";

// ── Build WHERE clause ────────────────────────────────────────
$where  = [];
$params = [];

if ($filter !== "All") {
    $where[]  = "a.status = ?";
    $params[] = $filter;
}
if ($search !== "") {
    $where[]  = "(a.full_name LIKE ? OR a.email LIKE ? OR j.title LIKE ? OR j.company LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($dateFrom !== "") {
    $where[]  = "a.date_applied >= ?";
    $params[] = $dateFrom;
}
if ($dateTo !== "") {
    $where[]  = "a.date_applied <= ?";
    $params[] = $dateTo;
}
$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// ── Sorting ───────────────────────────────────────────────────
$allowedAppSorts = [
    'full_name'    => 'a.full_name',
    'job_title'    => 'j.title',
    'date_applied' => 'a.date_applied',
    'status'       => 'a.status',
];
$appSortCol = $_GET['sort'] ?? 'date_applied';
$appSortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
if (!array_key_exists($appSortCol, $allowedAppSorts)) $appSortCol = 'date_applied';
$appOrderSQL = $allowedAppSorts[$appSortCol] . ' ' . $appSortDir;

// ── Pagination ───────────────────────────────────────────────
$perPage = PER_PAGE_ADMIN;
$page    = max(1, (int)($_GET["page"] ?? 1));

$cStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM applications a
    JOIN jobs j ON j.id = a.job_id
    {$whereSQL}
");
$cStmt->execute($params);
$total      = (int)$cStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ── Fetch current page ───────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.company
    FROM applications a
    JOIN jobs j ON j.id = a.job_id
    {$whereSQL}
    ORDER BY {$appOrderSQL}
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$applications = $stmt->fetchAll();

// Fetch counts for tabs
$countsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Pending')  AS pending,
        SUM(status = 'Approved') AS approved,
        SUM(status = 'Rejected') AS rejected
    FROM applications
");
$tabCounts = $countsStmt->fetch();

// ── URL helpers ───────────────────────────────────────────────
function staffAppsPageUrl(int $p): string {
    $q = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
}
function staffAppsSortUrl(string $col): string {
    global $appSortCol, $appSortDir;
    $q = $_GET;
    $q['sort'] = $col;
    $q['dir']  = ($appSortCol === $col && $appSortDir === 'ASC') ? 'desc' : 'asc';
    $q['page'] = 1;
    return '?' . http_build_query(array_filter($q, fn($v) => $v !== ''));
}
function staffAppsSortIcon(string $col): string {
    global $appSortCol, $appSortDir;
    if ($appSortCol !== $col) return '<i class="bi bi-arrow-down-up opacity-50 ms-1 small"></i>';
    return $appSortDir === 'ASC'
        ? '<i class="bi bi-sort-up-alt text-warning ms-1"></i>'
        : '<i class="bi bi-sort-down text-warning ms-1"></i>';
}
function staffAppsSortTh(string $label, string $col): string {
    return '<a href="' . staffAppsSortUrl($col) . '" class="text-decoration-none text-white">'
         . htmlspecialchars($label) . staffAppsSortIcon($col) . '</a>';
}

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-staff.php";
?>
<div class="admin-layout">
    <?php include $basePath . "layouts/sidebar-staff.php"; ?>
    <main class="admin-main">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-file-earmark-person me-2 text-warning"></i>Job Applications
                </h2>
                <p class="text-muted mb-0 small">
                    Evaluate applicant submissions, view candidate profiles, and update status.
                    <span class="small">(<?= $total ?> application<?= $total !== 1 ? 's' : '' ?> found)</span>
                </p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <?php
            $tabs = [
                'All'      => ['label' => 'All',      'count' => $tabCounts['total'] ?? 0],
                'Pending'  => ['label' => 'Pending',  'count' => $tabCounts['pending'] ?? 0],
                'Approved' => ['label' => 'Approved', 'count' => $tabCounts['approved'] ?? 0],
                'Rejected' => ['label' => 'Rejected', 'count' => $tabCounts['rejected'] ?? 0],
            ];
            foreach ($tabs as $key => $tab):
                $active = ($filter === $key) ? ' active fw-bold' : '';
                $tabUrl = '?' . http_build_query(array_merge($_GET, ['status' => $key, 'page' => 1]));
            ?>
                <li class="nav-item">
                    <a class="nav-link<?= $active ?>" href="<?= $tabUrl ?>">
                        <?= $tab['label'] ?>
                        <span class="badge bg-secondary ms-1"><?= (int)$tab['count'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Search & Filter Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="get" action="applications.php" class="row g-2 align-items-center">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search applicant, email, job title..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>" title="Applied from date">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>" title="Applied to date">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-warning btn-sm w-100" title="Apply filter"><i class="bi bi-funnel-fill"></i></button>
                        <?php if ($search !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
                            <a href="applications.php?status=<?= urlencode($filter) ?>" class="btn btn-outline-secondary btn-sm" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bulk Actions & Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <input type="checkbox" id="selectAllApps" class="form-check-input my-0">
                    <label for="selectAllApps" class="form-check-label small fw-semibold">Select All</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success disabled" id="bulkApproveBtn" onclick="bulkUpdate('Approved')">
                        <i class="bi bi-check-circle me-1"></i>Approve Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger disabled" id="bulkRejectBtn" onclick="bulkUpdate('Rejected')">
                        <i class="bi bi-x-circle me-1"></i>Reject Selected
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th><?= staffAppsSortTh('Applicant Name', 'full_name') ?></th>
                            <th><?= staffAppsSortTh('Applied Job Position', 'job_title') ?></th>
                            <th>Contact Info</th>
                            <th><?= staffAppsSortTh('Date Applied', 'date_applied') ?></th>
                            <th><?= staffAppsSortTh('Status', 'status') ?></th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    No applications match your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input app-checkbox" value="<?= $app['id'] ?>" onchange="updateBulkBtnState()">
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($app['full_name']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($app['email']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($app['job_title']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($app['company']) ?></div>
                                    </td>
                                    <td>
                                        <div class="small"><?= htmlspecialchars($app['contact'] ?: '—') ?></div>
                                    </td>
                                    <td class="small text-muted">
                                        <?= date('M d, Y', strtotime($app['date_applied'])) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = match($app['status']) {
                                            'Approved' => 'bg-success',
                                            'Rejected' => 'bg-danger',
                                            default    => 'bg-warning text-dark',
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= $app['status'] ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="viewApplicationDetails(<?= $app['id'] ?>)" title="View Application Details">
                                            <i class="bi bi-eye-fill"></i> View
                                        </button>
                                        <?php if ($app['status'] === 'Pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success me-1" onclick="updateAppStatus(<?= $app['id'] ?>, 'Approved')" title="Approve">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="updateAppStatus(<?= $app['id'] ?>, 'Rejected')" title="Reject">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php endif; ?>
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
                            <a class="page-link" href="<?= staffAppsPageUrl($page - 1) ?>">Prev</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= staffAppsPageUrl($p) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= staffAppsPageUrl($page + 1) ?>">Next</a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include $basePath . "modals/view-application-modal.php"; ?>

<script>
const APP_HANDLER_STAFF = '<?= $basePath ?>handlers/applications.php';

document.getElementById('selectAllApps')?.addEventListener('change', function() {
    document.querySelectorAll('.app-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkBtnState();
});

function updateBulkBtnState() {
    const checked = document.querySelectorAll('.app-checkbox:checked');
    const hasChecked = checked.length > 0;
    document.getElementById('bulkApproveBtn')?.classList.toggle('disabled', !hasChecked);
    document.getElementById('bulkRejectBtn')?.classList.toggle('disabled', !hasChecked);
}

function updateAppStatus(appId, status) {
    const isApprove = status === 'Approved';
    showConfirmModal({
        title: isApprove ? "Approve Application" : "Reject Application",
        message: `Are you sure you want to mark this application as ${status}?`,
        confirmBtnText: isApprove ? "Yes, Approve" : "Yes, Reject",
        confirmBtnClass: isApprove ? "btn-success" : "btn-danger",
        icon: isApprove ? "bi-check-circle-fill" : "bi-x-circle-fill",
        onConfirm: () => {
            showLoadingModal();
            fetch(APP_HANDLER_STAFF, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'updateStatus', id: appId, status: status, csrf_token: getCsrfToken() })
            })
            .then(r => r.json())
            .then(data => {
                hideLoadingModal();
                if (data.success) {
                    showToast(`Application marked as ${status}!`, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Action failed.', 'danger');
                }
            })
            .catch(() => {
                hideLoadingModal();
                showToast('An error occurred. Please try again.', 'danger');
            });
        }
    });
}

function bulkUpdate(status) {
    const checked = Array.from(document.querySelectorAll('.app-checkbox:checked')).map(cb => parseInt(cb.value));
    if (checked.length === 0) return;
    const isApprove = status === 'Approved';
    showConfirmModal({
        title: isApprove ? "Bulk Approve Applications" : "Bulk Reject Applications",
        message: `Are you sure you want to mark ${checked.length} application(s) as ${status}?`,
        confirmBtnText: isApprove ? "Yes, Approve Selected" : "Yes, Reject Selected",
        confirmBtnClass: isApprove ? "btn-success" : "btn-danger",
        icon: isApprove ? "bi-check-circle-fill" : "bi-x-circle-fill",
        onConfirm: () => {
            showLoadingModal();
            fetch(APP_HANDLER_STAFF, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'bulkUpdateStatus', ids: checked, status: status, csrf_token: getCsrfToken() })
            })
            .then(r => r.json())
            .then(data => {
                hideLoadingModal();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Bulk update failed.', 'danger');
                }
            })
            .catch(() => {
                hideLoadingModal();
                showToast('An error occurred. Please try again.', 'danger');
            });
        }
    });
}

function viewApplicationDetails(appId) {
    const histEl = document.getElementById("viewAppHistory");
    if (histEl) histEl.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> Loading…';

    const modal = new bootstrap.Modal(document.getElementById("viewApplicationModal"));
    modal.show();

    fetch(APP_HANDLER_STAFF, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getDetails', id: appId, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) { showToast(res.message, "danger"); return; }
        const app = res.data;
        const setT = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || "—"; };
        setT("viewAppName",       app.full_name);
        setT("viewAppEmail",      app.email);
        setT("viewAppContact",    app.contact);
        setT("viewAppAddress",    app.address);
        setT("viewAppBirthdate",  app.birthdate);
        setT("viewAppAge",        app.age);
        setT("viewAppJobTitle",   app.job_title);
        setT("viewAppCompany",    app.company);
        setT("viewAppContactPerson", app.contact_person);
        setT("viewAppContactPhone",  app.contact_phone);
        setT("viewAppDate",       app.date_applied);
        setT("viewAppElemSchool", app.elementary);
        setT("viewAppJhsSchool",  app.jhs);
        setT("viewAppShsSchool",  app.shs);
        setT("viewAppCollege",    app.college);
        setT("viewAppSkills",     app.skills);
        setT("viewAppExperience", app.experience);

        const statusEl = document.getElementById("viewAppStatus");
        if (statusEl) {
            const cls = app.status === "Approved" ? "bg-success" : app.status === "Rejected" ? "bg-danger" : "bg-warning text-dark";
            statusEl.className = "badge " + cls;
            statusEl.textContent = app.status;
        }

        // ── Resume ──────────────────────────────────────────
        const resumeRow = document.getElementById("viewAppResumeRow");
        const resumeDiv = document.getElementById("viewAppResume");
        if (res.resume && resumeDiv) {
            resumeRow.style.display = "";
            resumeDiv.innerHTML = `<a href="<?= $basePath ?>uploads/resumes/${res.resume.stored_name}" target="_blank" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download me-1"></i>Download Resume (${res.resume.original_name})
            </a>`;
        } else if (resumeRow) {
            resumeRow.style.display = "none";
        }

        // ── Status History Timeline ──────────────────────────
        if (!histEl) return;
        if (!res.history || res.history.length === 0) {
            histEl.innerHTML = '<p class="text-muted small fst-italic mb-0"><i class="bi bi-info-circle me-1"></i>No status changes recorded yet.</p>';
            return;
        }
        const statusColor = s => s === "Approved" ? "success" : s === "Rejected" ? "danger" : "warning";
        const statusIcon  = s => s === "Approved" ? "check-circle-fill" : s === "Rejected" ? "x-circle-fill" : "hourglass-split";
        let html = '<div class="timeline">';
        res.history.forEach((h) => {
            const from = h.from_status || "New";
            const to   = h.to_status;
            const col  = statusColor(to);
            const ico  = statusIcon(to);
            html += `
            <div class="d-flex gap-3 mb-3 align-items-start">
                <div class="flex-shrink-0 mt-1">
                    <span class="badge bg-${col} rounded-circle p-2" style="font-size:.75rem;">
                        <i class="bi bi-${ico}"></i>
                    </span>
                </div>
                <div>
                    <div class="fw-semibold small">
                        <span class="text-muted">${from}</span>
                        <i class="bi bi-arrow-right mx-1 text-muted"></i>
                        <span class="text-${col === 'warning' ? 'dark' : col}">${to}</span>
                    </div>
                    <div class="text-muted" style="font-size:0.78rem;">
                        <i class="bi bi-person me-1"></i>${h.changed_by || 'System'}
                        &nbsp;·&nbsp;
                        <i class="bi bi-clock me-1"></i>${h.changed_at}
                    </div>
                </div>
            </div>`;
        });
        html += '</div>';
        histEl.innerHTML = html;
    })
    .catch(() => {
        if (histEl) histEl.innerHTML = '<p class="text-danger small">Failed to load details.</p>';
        showToast("Failed to load application details.", "danger");
    });
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>
