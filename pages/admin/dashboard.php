<?php
require_once __DIR__ . "/../../config/auth.php";
requireAdmin();

$pageTitle   = "OJAMS - Admin Dashboard";
$basePath    = "../../";
$currentPage = "dashboard";

// DB stats
$stmtStats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM jobs)                                   AS total_jobs,
        (SELECT COUNT(*) FROM applications)                          AS total_applicants,
        (SELECT COUNT(*) FROM applications WHERE status = 'Pending') AS pending_applications,
        (SELECT COUNT(*) FROM applications WHERE status = 'Approved') AS approved_applications,
        (SELECT COUNT(*) FROM applications WHERE status = 'Rejected') AS rejected_applications
");
$stats = $stmtStats->fetch();

// Applicants per job (for line chart)
$stmtJobApps = $pdo->query("
    SELECT j.title, COUNT(a.id) AS applicant_count
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    GROUP BY j.id, j.title
    ORDER BY j.date_posted ASC, j.id ASC
");
$jobApplicantCounts = $stmtJobApps->fetchAll();

// ── Activity Log Filters ─────────────────────────────────────
$logSearch   = trim($_GET['log_search']  ?? '');
$logStatus   = $_GET['log_status']       ?? '';
$logDateFrom = $_GET['log_date_from']    ?? '';
$logDateTo   = $_GET['log_date_to']      ?? '';
$logPage     = max(1, (int)($_GET['log_page'] ?? 1));
$logPerPage  = 20;

$logWhere  = [];
$logParams = [];
if ($logSearch !== '') {
    $logWhere[]  = "action LIKE ?";
    $logParams[] = "%{$logSearch}%";
}
if ($logStatus !== '') {
    $logWhere[]  = "status = ?";
    $logParams[] = $logStatus;
}
if ($logDateFrom !== '') {
    $logWhere[]  = "DATE(created_at) >= ?";
    $logParams[] = $logDateFrom;
}
if ($logDateTo !== '') {
    $logWhere[]  = "DATE(created_at) <= ?";
    $logParams[] = $logDateTo;
}
$logWhereSQL = $logWhere ? "WHERE " . implode(" AND ", $logWhere) : "";

// Total for pagination
$logCountStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs {$logWhereSQL}");
$logCountStmt->execute($logParams);
$logTotal      = (int)$logCountStmt->fetchColumn();
$logTotalPages = max(1, (int)ceil($logTotal / $logPerPage));
$logPage       = min($logPage, $logTotalPages);
$logOffset     = ($logPage - 1) * $logPerPage;

$stmtLog = $pdo->prepare("
    SELECT
        DATE_FORMAT(created_at, '%b %d, %Y') AS date,
        DATE_FORMAT(created_at, '%h:%i %p')  AS time,
        action,
        status
    FROM activity_logs
    {$logWhereSQL}
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmtLog->execute(array_merge($logParams, [$logPerPage, $logOffset]));
$activity_history = $stmtLog->fetchAll();

// Distinct statuses for the filter dropdown
$logStatuses = $pdo->query("SELECT DISTINCT status FROM activity_logs ORDER BY status")->fetchAll(PDO::FETCH_COLUMN);

function logPageUrl(int $p): string {
    $q = $_GET;
    $q['log_page'] = $p;
    // Scroll to log section on navigate
    return '?' . http_build_query($q) . '#activityLog';
}

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-admin.php";
?>
<div class="admin-layout">
    <?php include $basePath . "layouts/sidebar-admin.php"; ?>
    <main class="admin-main">
        <!-- Page Header -->
        <div class="mb-4">
            <h2 class="fw-bold mb-1">
                <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
            </h2>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION["ojams_user"]["full_name"]); ?>! Here&#39;s an overview of the system.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <?php
            $statTitle = "Total Job Posts";
            $statValue = $stats["total_jobs"];
            $statIcon  = "bi-briefcase-fill";
            $statColor = "#0d6efd";
            include $basePath . "components/stats-card.php";

            $statTitle = "Total Applicants";
            $statValue = $stats["total_applicants"];
            $statIcon  = "bi-people-fill";
            $statColor = "#198754";
            include $basePath . "components/stats-card.php";

            $statTitle = "Pending Applications";
            $statValue = $stats["pending_applications"];
            $statIcon  = "bi-hourglass-split";
            $statColor = "#ffc107";
            include $basePath . "components/stats-card.php";

            $statTitle = "Approved Applications";
            $statValue = $stats["approved_applications"];
            $statIcon  = "bi-check-circle-fill";
            $statColor = "#20c997";
            include $basePath . "components/stats-card.php";
            ?>
        </div>

        <!-- Charts Row -->
        <div class="row mt-2">
            <!-- Applications Overview Chart -->
            <div class="col-lg-5 mb-3 mb-lg-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Applications Overview
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ((int)$stats["total_applicants"] === 0): ?>
                            <p class="text-muted text-center py-4 mb-0">No applications yet.</p>
                        <?php else: ?>
                            <div style="max-width:320px; margin:0 auto;">
                                <canvas id="applicationsChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Applicants per Job Chart -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-graph-up me-2 text-primary"></i>Applicants per Job
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jobApplicantCounts)): ?>
                            <p class="text-muted text-center py-4 mb-0">No jobs posted yet.</p>
                        <?php else: ?>
                            <canvas id="jobApplicantsChart" height="130"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="card border-0 shadow-sm mt-2" id="activityLog">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Activity Log
                    <span class="badge bg-light text-muted border ms-1" style="font-size:.75rem;"><?php echo $logTotal; ?></span>
                </h5>
                <button class="btn btn-sm btn-outline-danger" onclick="clearOldLogs()">
                    <i class="bi bi-trash me-1"></i>Clear &gt;90 Days
                </button>
            </div>

            <!-- Filter Bar -->
            <div class="card-body border-bottom py-2">
                <form method="get" action="#activityLog" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="log_search"
                                   placeholder="Search actions…"
                                   value="<?php echo htmlspecialchars($logSearch); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="log_status">
                            <option value="">All Status</option>
                            <?php foreach ($logStatuses as $ls): ?>
                            <option value="<?php echo htmlspecialchars($ls); ?>"
                                    <?php echo $logStatus === $ls ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ls); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" name="log_date_from"
                               value="<?php echo htmlspecialchars($logDateFrom); ?>"
                               placeholder="From">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" name="log_date_to"
                               value="<?php echo htmlspecialchars($logDateTo); ?>"
                               placeholder="To">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <?php if ($logSearch !== '' || $logStatus !== '' || $logDateFrom !== '' || $logDateTo !== ''): ?>
                        <a href="dashboard.php#activityLog" class="btn btn-outline-secondary btn-sm" title="Clear">
                            <i class="bi bi-x-lg"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <?php
                        $columns = ["Date", "Time", "Action", "Status"];
                        include $basePath . "components/table-header.php";
                        ?>
                        <tbody>
                            <?php if (empty($activity_history)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <i class="bi bi-activity display-5 text-muted d-block mb-3"></i>
                                        <p class="text-muted mb-3">No activity logged yet. Actions like posting jobs and reviewing applications will appear here.</p>
                                        <a href="manage-jobs.php" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-plus-circle me-1"></i>Add Your First Job
                                        </a>
                                    </td>
                                </tr>
                            <?php else: foreach ($activity_history as $a):
                                $badge = match($a["status"]) {
                                    "New"       => "bg-info",
                                    "Created"   => "bg-primary",
                                    "Updated"   => "bg-secondary",
                                    "Deleted"   => "bg-dark",
                                    "Applied"   => "bg-primary",
                                    "Cancelled" => "bg-warning text-dark",
                                    "Approved"  => "bg-success",
                                    "Rejected"  => "bg-danger",
                                    default     => "bg-secondary"
                                };
                            ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($a["date"]); ?></td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($a["time"]); ?></td>
                                    <td><?php echo htmlspecialchars($a["action"]); ?></td>
                                    <td>
                                        <span class="badge <?php echo $badge; ?>">
                                            <?php echo htmlspecialchars($a["status"]); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($logTotalPages > 1): ?>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing <?php echo $logOffset + 1; ?>–<?php echo min($logOffset + $logPerPage, $logTotal); ?> of <?php echo $logTotal; ?>
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?php echo $logPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo logPageUrl($logPage - 1); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($p = 1; $p <= $logTotalPages; $p++):
                            if (!($p === 1 || $p === $logTotalPages || abs($p - $logPage) <= 2)) continue;
                        ?>
                            <li class="page-item <?php echo $p === $logPage ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo logPageUrl($p); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $logPage >= $logTotalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo logPageUrl($logPage + 1); ?>">
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
<script>
function clearOldLogs() {
    showConfirmModal({
        title: "Clear Activity Logs",
        message: "Delete all activity logs older than 90 days? This action cannot be undone.",
        confirmBtnText: "Yes, Clear Logs",
        confirmBtnClass: "btn-danger",
        icon: "bi-trash-fill",
        onConfirm: () => {
            fetch("../../handlers/admin.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "clearLogs", days: 90, csrf_token: getCsrfToken() })
            })
            .then(r => r.json())
            .then(res => {
                showToast(res.message, res.success ? "success" : "danger");
                if (res.success) setTimeout(() => location.reload(), 1200);
            })
            .catch(() => showToast("Request failed.", "danger"));
        }
    });
}
</script>
<?php if ((int)$stats["total_applicants"] > 0 || !empty($jobApplicantCounts)): ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const appsCtx = document.getElementById('applicationsChart');
if (appsCtx) {
    new Chart(appsCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [
                    <?php echo (int)$stats["pending_applications"]; ?>,
                    <?php echo (int)$stats["approved_applications"]; ?>,
                    <?php echo (int)$stats["rejected_applications"]; ?>
                ],
                backgroundColor: ['#ffc107', '#20c997', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

const jobApplicantsCtx = document.getElementById('jobApplicantsChart');
if (jobApplicantsCtx) {
    new Chart(jobApplicantsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($jobApplicantCounts, 'title')); ?>,
            datasets: [{
                label: 'Applicants',
                data: <?php echo json_encode(array_map('intval', array_column($jobApplicantCounts, 'applicant_count'))); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.15)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#0d6efd',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
}
</script>
<?php endif; ?>
<?php include $basePath . "layouts/footer.php"; ?>