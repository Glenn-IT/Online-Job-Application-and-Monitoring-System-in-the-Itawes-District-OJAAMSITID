<?php
require_once __DIR__ . "/../../config/auth.php";
requireAdmin();
$pageTitle   = "OJAMS - Applications";
$basePath    = "../../";
$currentPage = "applications";

$filter  = $_GET["status"] ?? "All";
$allowed = ["All", "Pending", "Approved", "Rejected"];
if (!in_array($filter, $allowed)) $filter = "All";

// ── Pagination ───────────────────────────────────────────────
$perPage    = 15;
$page       = max(1, (int)($_GET["page"] ?? 1));
$offset     = ($page - 1) * $perPage;

// Total count for pagination
if ($filter === "All") {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
} else {
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status = ?");
    $cStmt->execute([$filter]);
    $total = (int)$cStmt->fetchColumn();
}
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ── Fetch current page ───────────────────────────────────────
if ($filter === "All") {
    $stmt = $pdo->prepare("
        SELECT a.*, j.title as job_title, j.company
        FROM applications a
        JOIN jobs j ON j.id = a.job_id
        ORDER BY a.date_applied DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$perPage, $offset]);
} else {
    $stmt = $pdo->prepare("
        SELECT a.*, j.title as job_title, j.company
        FROM applications a
        JOIN jobs j ON j.id = a.job_id
        WHERE a.status = ?
        ORDER BY a.date_applied DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$filter, $perPage, $offset]);
}
$applications = $stmt->fetchAll();

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
                        <span class="text-muted small">(<?php echo $total; ?> total)</span>
                    </p>
                </div>
                <div class="btn-group" role="group">
                    <?php foreach (["All", "Pending", "Approved", "Rejected"] as $s):
                        $active  = $filter === $s ? "active" : "";
                        $variant = match($s) { "Pending" => "warning", "Approved" => "success", "Rejected" => "danger", default => "primary" };
                    ?>
                    <a href="?status=<?php echo $s; ?>"
                       class="btn btn-outline-<?php echo $variant; ?> btn-sm <?php echo $active; ?>">
                        <?php echo $s; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <?php
                            $columns = ["#", "Applicant Name", "Job Title", "Date Applied", "Status", "Actions"];
                            include $basePath . "components/table-header.php";
                            ?>
                            <tbody id="applicationsTableBody">
                                <?php if (empty($applications)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
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
                                <a class="page-link" href="?status=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <?php
                                // Show first, last, and pages around current
                                $show = ($p === 1 || $p === $totalPages || abs($p - $page) <= 2);
                                $ellipsisBefore = ($p === 2 && $page > 4);
                                $ellipsisAfter  = ($p === $totalPages - 1 && $page < $totalPages - 3);
                                if (!$show) continue;
                                ?>
                                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?status=<?php echo $filter; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?status=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">
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

function viewAppDetails(appId) {
    const app = appsData.find(a => a.id == appId);
    if (!app) return;
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
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>
