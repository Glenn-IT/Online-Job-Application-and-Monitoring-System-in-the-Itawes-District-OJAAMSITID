<?php
require_once __DIR__ . "/../../config/auth.php";
requireAdmin();
$pageTitle   = "OJAMS - Applications";
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

// ── URL helpers ───────────────────────────────────────────────
function appsPageUrl(int $p): string {
    $q = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
}
function appsSortUrl(string $col): string {
    global $appSortCol, $appSortDir;
    $q = $_GET;
    $q['sort'] = $col;
    $q['dir']  = ($appSortCol === $col && $appSortDir === 'ASC') ? 'desc' : 'asc';
    $q['page'] = 1;
    return '?' . http_build_query(array_filter($q, fn($v) => $v !== ''));
}
function appsSortIcon(string $col): string {
    global $appSortCol, $appSortDir;
    if ($appSortCol !== $col) return '<i class="bi bi-arrow-down-up opacity-50 ms-1 small"></i>';
    return $appSortDir === 'ASC'
        ? '<i class="bi bi-sort-up-alt text-warning ms-1"></i>'
        : '<i class="bi bi-sort-down text-warning ms-1"></i>';
}
function appsSortTh(string $label, string $col): string {
    return '<a href="' . appsSortUrl($col) . '" class="text-decoration-none text-white">'
         . htmlspecialchars($label) . appsSortIcon($col) . '</a>';
}

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-admin.php";
?>
<div class="admin-layout">
    <?php include $basePath . "layouts/sidebar-admin.php"; ?>
    <main class="admin-main">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-file-earmark-person me-2 text-primary"></i>Applications
                    </h2>
                    <p class="text-muted mb-0">
                        Review and manage all job applications.
                        <span class="small">(<?php echo $total; ?> result<?php echo $total !== 1 ? 's' : ''; ?>)</span>
                    </p>
                </div>
                <!-- Status tabs — preserve search/date params -->
                <div class="btn-group" role="group">
                    <?php
                    foreach (["All", "Pending", "Approved", "Rejected"] as $s):
                        $active  = $filter === $s ? "active" : "";
                        $variant = match($s) { "Pending" => "warning", "Approved" => "success", "Rejected" => "danger", default => "primary" };
                        $tabQ    = array_filter(['status' => $s, 'search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
                    ?>
                    <a href="?<?php echo http_build_query($tabQ); ?>"
                       class="btn btn-outline-<?php echo $variant; ?> btn-sm <?php echo $active; ?>">
                        <?php echo $s; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Search & Filter Bar -->
            <form method="get" action="" class="card border-0 shadow-sm mb-4 filter-form">
                <div class="card-body py-3">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold mb-1">Search</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" name="search"
                                       placeholder="Name, email, job title or company…"
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Applied From</label>
                            <input type="date" class="form-control form-control-sm" name="date_from"
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold mb-1">Applied To</label>
                            <input type="date" class="form-control form-control-sm" name="date_to"
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="bi bi-funnel me-1"></i>Search
                            </button>
                            <?php if ($search !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
                            <a href="?status=<?php echo htmlspecialchars($filter); ?>"
                               class="btn btn-outline-secondary btn-sm" title="Clear search">
                                <i class="bi bi-x-lg"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Bulk Action Toolbar (hidden until rows are selected) -->
            <div id="bulkToolbarApps" class="d-none mb-3 p-3 rounded-3 border border-primary bg-primary bg-opacity-10 d-flex align-items-center gap-3 flex-wrap">
                <span class="fw-semibold text-primary">
                    <i class="bi bi-check2-square me-1"></i>
                    <span id="bulkCountApps">0</span> selected
                </span>
                <button class="btn btn-sm btn-success" onclick="bulkAppAction('Approved')">
                    <i class="bi bi-check-circle me-1"></i>Approve All
                </button>
                <button class="btn btn-sm btn-danger" onclick="bulkAppAction('Rejected')">
                    <i class="bi bi-x-circle me-1"></i>Reject All
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="bulkAppDelete()">
                    <i class="bi bi-trash me-1"></i>Delete All
                </button>
                <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="clearAppSelection()">
                    <i class="bi bi-x-lg me-1"></i>Clear
                </button>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <?php
                            $columns = [
                                "<input type='checkbox' id='selectAllApps' class='form-check-input'>",
                                "#",
                                appsSortTh("Applicant Name", "full_name"),
                                appsSortTh("Job Title",       "job_title"),
                                appsSortTh("Date Applied",    "date_applied"),
                                appsSortTh("Status",          "status"),
                                "Actions",
                            ];
                            include $basePath . "components/table-header.php";
                            ?>
                            <tbody id="applicationsTableBody">
                                <?php if (empty($applications)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No <?php echo $filter !== "All" ? strtolower($filter) : ""; ?> applications found.
                                        </td>
                                    </tr>
                                <?php else: $rowNum = $offset + 1; foreach ($applications as $app):
                                    $badgeClass = match($app["status"]) {
                                        "Approved" => "bg-success",
                                        "Rejected" => "bg-danger",
                                        "Pending"  => "bg-warning text-dark",
                                        default    => "bg-secondary"
                                    };
                                ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input app-checkbox" value="<?php echo $app['id']; ?>"></td>
                                        <td><?php echo $rowNum++; ?></td>
                                        <td><?php echo htmlspecialchars($app["full_name"]); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($app["job_title"]); ?>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($app["company"]); ?></small>
                                        </td>
                                        <td><?php echo $app["date_applied"]; ?></td>
                                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $app["status"]; ?></span></td>
                                        <td>
                                            <?php if ($app["status"] !== "Approved"): ?>
                                            <button class="btn btn-sm btn-outline-success me-1"
                                                onclick="updateAppStatus(<?php echo $app['id']; ?>, 'Approved')">
                                                <i class="bi bi-check-circle"></i> Approve
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($app["status"] !== "Rejected"): ?>
                                            <button class="btn btn-sm btn-outline-danger me-1"
                                                onclick="updateAppStatus(<?php echo $app['id']; ?>, 'Rejected')">
                                                <i class="bi bi-x-circle"></i> Reject
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="viewAppDetails(<?php echo $app['id']; ?>)"
                                                data-bs-toggle="modal" data-bs-target="#viewApplicationModal">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
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
                                <a class="page-link" href="<?php echo appsPageUrl($page - 1); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++):
                                if (!($p === 1 || $p === $totalPages || abs($p - $page) <= 2)) continue;
                            ?>
                                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo appsPageUrl($p); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo appsPageUrl($page + 1); ?>">
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
<?php include $basePath . "modals/view-application-modal.php"; ?>
<script>
const APP_HANDLER_ADMIN = "../../handlers/applications.php";
const appsData = <?php echo json_encode(array_values($applications)); ?>;

function updateAppStatus(id, status) {
    const label = status === "Approved" ? "approve" : "reject";
    if (!confirm("Are you sure you want to " + label + " this application?")) return;
    fetch(APP_HANDLER_ADMIN, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "updateStatus", id: id, status: status, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? (status === "Approved" ? "success" : "danger") : "warning");
        if (res.success) setTimeout(() => location.reload(), 900);
    })
    .catch(() => showToast("Request failed.", "danger"));
}

// ── Bulk selection ───────────────────────────────────────────
function getSelectedAppIds() {
    return [...document.querySelectorAll('.app-checkbox:checked')].map(cb => parseInt(cb.value));
}
function updateAppBulkToolbar() {
    const ids     = getSelectedAppIds();
    const toolbar = document.getElementById('bulkToolbarApps');
    const counter = document.getElementById('bulkCountApps');
    if (ids.length > 0) {
        toolbar.classList.remove('d-none');
        toolbar.classList.add('d-flex');
        counter.textContent = ids.length;
    } else {
        toolbar.classList.add('d-none');
        toolbar.classList.remove('d-flex');
    }
}
function clearAppSelection() {
    document.querySelectorAll('.app-checkbox').forEach(cb => cb.checked = false);
    const sa = document.getElementById('selectAllApps');
    if (sa) sa.checked = false;
    updateAppBulkToolbar();
}
document.addEventListener('DOMContentLoaded', function () {
    const sa = document.getElementById('selectAllApps');
    if (sa) {
        sa.addEventListener('change', function () {
            document.querySelectorAll('.app-checkbox').forEach(cb => cb.checked = this.checked);
            updateAppBulkToolbar();
        });
    }
    document.querySelectorAll('.app-checkbox').forEach(cb => {
        cb.addEventListener('change', updateAppBulkToolbar);
    });
});
function bulkAppAction(status) {
    const ids = getSelectedAppIds();
    if (!ids.length) return;
    const label = status === 'Approved' ? 'approve' : 'reject';
    if (!confirm(`${label.charAt(0).toUpperCase()+label.slice(1)} ${ids.length} application(s)?`)) return;
    fetch(APP_HANDLER_ADMIN, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulkUpdateStatus', ids, status, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 900);
    })
    .catch(() => showToast('Request failed.', 'danger'));
}
function bulkAppDelete() {
    const ids = getSelectedAppIds();
    if (!ids.length) return;
    if (!confirm(`Permanently delete ${ids.length} application(s)? This cannot be undone.`)) return;
    fetch(APP_HANDLER_ADMIN, {
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

function viewAppDetails(appId) {
    // Show modal immediately with loading state
    const histEl = document.getElementById("viewAppHistory");
    if (histEl) histEl.innerHTML = '<p class="text-muted small"><i class="bi bi-hourglass-split me-1"></i>Loading…</p>';

    // Fetch full details + history from API
    fetch(APP_HANDLER_ADMIN, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "getDetails", id: appId, csrf_token: getCsrfToken() })
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
            resumeDiv.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                <i class="bi bi-file-earmark-check me-1"></i>${res.resume.original_name}
            </span>`;
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
        res.history.forEach((h, i) => {
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
    });
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>
