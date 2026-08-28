<?php
require_once __DIR__ . "/../../config/auth.php";
requireAdmin();

$pageTitle   = "OJAMS - Reports & Monitoring";
$basePath    = "../../";
$currentPage = "reports";

// ── Overall Application Summary Stats ────────────────────────
$stmtSummary = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Approved') AS approved,
        SUM(status = 'Rejected') AS rejected,
        SUM(status = 'Pending')  AS pending
    FROM applications
");
$appSummary   = $stmtSummary->fetch() ?: ['total' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0];
$totalApps    = (int)($appSummary['total'] ?? 0);
$approvedApps = (int)($appSummary['approved'] ?? 0);
$rejectedApps = (int)($appSummary['rejected'] ?? 0);
$pendingApps  = (int)($appSummary['pending'] ?? 0);

// ── Applicants per job — top 20 by applicant count ───────────
$stmtPerJob = $pdo->query("
    SELECT j.title AS job_title, COUNT(a.id) AS applicants
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    GROUP BY j.id, j.title
    ORDER BY applicants DESC
    LIMIT 20
");
$applicants_per_job = $stmtPerJob->fetchAll();
$maxApplicants = !empty($applicants_per_job) ? max(array_column($applicants_per_job, "applicants")) : 1;

// ── Monthly application report — last 12 months only ─────────
$stmtMonthly = $pdo->query("
    SELECT
        DATE_FORMAT(date_applied, '%M %Y') AS month,
        COUNT(*)                           AS applications,
        SUM(status = 'Approved')           AS approved,
        SUM(status = 'Rejected')           AS rejected,
        SUM(status = 'Pending')            AS pending
    FROM applications
    GROUP BY DATE_FORMAT(date_applied, '%Y-%m')
    ORDER BY MIN(date_applied) DESC
    LIMIT 12
");
$monthly_report = $stmtMonthly->fetchAll();
$maxMonthly = !empty($monthly_report) ? max(array_column($monthly_report, "applications")) : 1;

// ── Detailed Applications List (Approved / Rejected / Pending) ─
$appStatusFilter = $_GET['status'] ?? 'All';
$allowedStatuses = ['All', 'Approved', 'Pending', 'Rejected'];
if (!in_array($appStatusFilter, $allowedStatuses, true)) {
    $appStatusFilter = 'All';
}

$appSearch   = trim($_GET['search'] ?? '');
$appDateFrom = $_GET['date_from'] ?? '';
$appDateTo   = $_GET['date_to'] ?? '';

$appWhere  = [];
$appParams = [];

if ($appStatusFilter !== 'All') {
    $appWhere[]  = "a.status = ?";
    $appParams[] = $appStatusFilter;
}
if ($appSearch !== '') {
    $appWhere[]  = "(a.full_name LIKE ? OR a.email LIKE ? OR a.contact LIKE ? OR j.title LIKE ? OR j.company LIKE ?)";
    $appParams[] = "%{$appSearch}%";
    $appParams[] = "%{$appSearch}%";
    $appParams[] = "%{$appSearch}%";
    $appParams[] = "%{$appSearch}%";
    $appParams[] = "%{$appSearch}%";
}
if ($appDateFrom !== '') {
    $appWhere[]  = "a.date_applied >= ?";
    $appParams[] = $appDateFrom;
}
if ($appDateTo !== '') {
    $appWhere[]  = "a.date_applied <= ?";
    $appParams[] = $appDateTo;
}
$appWhereSQL = $appWhere ? "WHERE " . implode(" AND ", $appWhere) : "";

// Count for applications list pagination
$cStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM applications a
    JOIN jobs j ON j.id = a.job_id
    {$appWhereSQL}
");
$cStmt->execute($appParams);
$totalFilteredApps = (int)$cStmt->fetchColumn();

$appPerPage    = 10;
$appPage       = max(1, (int)($_GET['page'] ?? 1));
$appTotalPages = max(1, (int)ceil($totalFilteredApps / $appPerPage));
$appPage       = min($appPage, $appTotalPages);
$appOffset     = ($appPage - 1) * $appPerPage;

$stmtList = $pdo->prepare("
    SELECT a.*, j.title AS job_title, j.company
    FROM applications a
    JOIN jobs j ON j.id = a.job_id
    {$appWhereSQL}
    ORDER BY a.date_applied DESC, a.id DESC
    LIMIT ? OFFSET ?
");
$stmtList->execute(array_merge($appParams, [$appPerPage, $appOffset]));
$detailedApplications = $stmtList->fetchAll();

// URL builder for application filters and pagination
function reportFilterUrl(array $changes = []): string {
    $q = array_merge($_GET, $changes);
    return '?' . http_build_query(array_filter($q, fn($v) => $v !== '' && $v !== null)) . '#applicationsListSection';
}

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-admin.php";
?>
<div class="admin-layout">
    <?php include $basePath . "layouts/sidebar-admin.php"; ?>
    <main class="admin-main">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-graph-up me-2 text-primary"></i>Reports &amp; Monitoring
                </h2>
                <p class="text-muted mb-0">View analytics, monitor applicant records, and generate system reports.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="export-report.php?type=print_applications&status=<?php echo urlencode($appStatusFilter); ?>&search=<?php echo urlencode($appSearch); ?>&date_from=<?php echo urlencode($appDateFrom); ?>&date_to=<?php echo urlencode($appDateTo); ?>"
                   target="_blank" rel="noopener" class="btn btn-outline-primary shadow-sm">
                    <i class="bi bi-printer me-1"></i>Print Report
                </a>
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle shadow-sm" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i>Export &amp; Print
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="dropdown-header text-uppercase small fw-bold">Application Status Reports</li>
                        <li>
                            <a class="dropdown-item"
                               href="export-report.php?type=print_applications&status=All"
                               target="_blank" rel="noopener">
                                <i class="bi bi-printer me-2 text-primary"></i>Print All Applications (Approved/Rejected/Pending)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                               href="export-report.php?type=print_applications&status=Approved"
                               target="_blank" rel="noopener">
                                <i class="bi bi-check2-circle me-2 text-success"></i>Print Approved Applications Only
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                               href="export-report.php?type=print_applications&status=Pending"
                               target="_blank" rel="noopener">
                                <i class="bi bi-hourglass-split me-2 text-warning"></i>Print Pending Applications Only
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                               href="export-report.php?type=print_applications&status=Rejected"
                               target="_blank" rel="noopener">
                                <i class="bi bi-x-circle me-2 text-danger"></i>Print Rejected Applications Only
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                               href="export-report.php?type=applications_csv&status=<?php echo urlencode($appStatusFilter); ?>" download>
                                <i class="bi bi-filetype-csv me-2 text-success"></i>Export Current List — CSV
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-header text-uppercase small fw-bold">General Analytics</li>
                        <li>
                            <a class="dropdown-item"
                               href="export-report.php?type=applicants_csv" download>
                                <i class="bi bi-filetype-csv me-2 text-success"></i>Applicants per Job — CSV
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                               href="export-report.php?type=monthly_csv" download>
                                <i class="bi bi-filetype-csv me-2 text-success"></i>Monthly Trends — CSV
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item"
                               href="export-report.php?type=print"
                               target="_blank" rel="noopener">
                                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Print Comprehensive System Report
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Summary KPI Metrics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10 border-start border-primary border-4">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Total Applications</div>
                                <div class="fs-3 fw-bold text-primary"><?php echo number_format($totalApps); ?></div>
                            </div>
                            <div class="p-3 bg-primary bg-opacity-10 rounded-circle text-primary">
                                <i class="bi bi-files fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10 border-start border-success border-4">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Approved</div>
                                <div class="fs-3 fw-bold text-success"><?php echo number_format($approvedApps); ?></div>
                            </div>
                            <div class="p-3 bg-success bg-opacity-10 rounded-circle text-success">
                                <i class="bi bi-check-circle-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-warning bg-opacity-10 border-start border-warning border-4">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Pending</div>
                                <div class="fs-3 fw-bold text-warning-emphasis"><?php echo number_format($pendingApps); ?></div>
                            </div>
                            <div class="p-3 bg-warning bg-opacity-10 rounded-circle text-warning">
                                <i class="bi bi-hourglass-split fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 bg-danger bg-opacity-10 border-start border-danger border-4">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Rejected</div>
                                <div class="fs-3 fw-bold text-danger"><?php echo number_format($rejectedApps); ?></div>
                            </div>
                            <div class="p-3 bg-danger bg-opacity-10 rounded-circle text-danger">
                                <i class="bi bi-x-circle-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications by Status (Approved / Rejected / Pending) -->
        <div class="card border-0 shadow-sm mb-4" id="applicationsListSection">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-person-lines-fill me-2 text-primary"></i>Applications List by Status
                    </h5>
                    <small class="text-muted">Showing detailed records for Approved, Rejected, and Pending applicants.</small>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <!-- Status Filter Tabs -->
                    <div class="btn-group btn-group-sm" role="group">
                        <?php
                        $tabStatuses = [
                            'All'      => ['label' => 'All', 'count' => $totalApps, 'color' => 'primary'],
                            'Approved' => ['label' => 'Approved', 'count' => $approvedApps, 'color' => 'success'],
                            'Pending'  => ['label' => 'Pending', 'count' => $pendingApps, 'color' => 'warning'],
                            'Rejected' => ['label' => 'Rejected', 'count' => $rejectedApps, 'color' => 'danger'],
                        ];
                        foreach ($tabStatuses as $st => $info):
                            $isActive = ($appStatusFilter === $st);
                            $btnClass = $isActive ? "btn-{$info['color']}" : "btn-outline-{$info['color']}";
                        ?>
                            <a href="<?php echo reportFilterUrl(['status' => $st, 'page' => 1]); ?>"
                               class="btn <?php echo $btnClass; ?> position-relative">
                                <?php echo $info['label']; ?>
                                <span class="badge <?php echo $isActive ? 'bg-white text-dark' : 'bg-' . $info['color']; ?> rounded-pill ms-1">
                                    <?php echo $info['count']; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Print / Export for this list -->
                    <a href="export-report.php?type=print_applications&status=<?php echo urlencode($appStatusFilter); ?>&search=<?php echo urlencode($appSearch); ?>&date_from=<?php echo urlencode($appDateFrom); ?>&date_to=<?php echo urlencode($appDateTo); ?>"
                       target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm" title="Print this filtered list">
                        <i class="bi bi-printer me-1"></i>Print
                    </a>
                    <a href="export-report.php?type=applications_csv&status=<?php echo urlencode($appStatusFilter); ?>&search=<?php echo urlencode($appSearch); ?>&date_from=<?php echo urlencode($appDateFrom); ?>&date_to=<?php echo urlencode($appDateTo); ?>"
                       class="btn btn-outline-success btn-sm" title="Export as CSV">
                        <i class="bi bi-filetype-csv me-1"></i>CSV
                    </a>
                </div>
            </div>

            <!-- Search & Date Filter Bar -->
            <div class="card-body border-bottom bg-light bg-opacity-25 py-3">
                <form method="get" action="" class="row g-2 align-items-end">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($appStatusFilter); ?>">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-muted mb-1">Search Keyword</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="search"
                                   placeholder="Applicant name, email, contact, job title, company…"
                                   value="<?php echo htmlspecialchars($appSearch); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Date From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from"
                               value="<?php echo htmlspecialchars($appDateFrom); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted mb-1">Date To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to"
                               value="<?php echo htmlspecialchars($appDateTo); ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <?php if ($appSearch !== '' || $appDateFrom !== '' || $appDateTo !== ''): ?>
                            <a href="<?php echo reportFilterUrl(['search' => '', 'date_from' => '', 'date_to' => '', 'page' => 1]); ?>"
                               class="btn btn-outline-secondary btn-sm" title="Clear search filters">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table of Applications -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <?php
                        $columns = ["#", "Applicant Details", "Job Applied", "Date Applied", "Status", "Interview / Schedule", "Action"];
                        include $basePath . "components/table-header.php";
                        ?>
                        <tbody>
                            <?php if (empty($detailedApplications)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox display-6 d-block mb-2 text-secondary"></i>
                                        No <?php echo $appStatusFilter !== 'All' ? strtolower($appStatusFilter) : ''; ?> applications found matching your criteria.
                                    </td>
                                </tr>
                            <?php else:
                                $rowIdx = $appOffset + 1;
                                foreach ($detailedApplications as $app):
                                    $statusBadge = match($app['status']) {
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        'Pending'  => 'bg-warning text-dark',
                                        default    => 'bg-secondary'
                                    };
                            ?>
                                <tr>
                                    <td><?php echo $rowIdx++; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($app['full_name']); ?></div>
                                        <div class="text-muted small">
                                            <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($app['email']); ?>
                                            <?php if (!empty($app['contact'])): ?>
                                                &nbsp;|&nbsp;<i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($app['contact']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-primary"><?php echo htmlspecialchars($app['job_title']); ?></div>
                                        <small class="text-muted"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($app['company']); ?></small>
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar3 me-1 text-muted"></i>
                                        <?php echo date('M d, Y', strtotime($app['date_applied'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusBadge; ?> px-2 py-1">
                                            <?php echo $app['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($app['status'] === 'Approved' && !empty($app['interview_date'])): ?>
                                            <div class="badge bg-success-subtle text-success border border-success-subtle text-start p-2">
                                                <div><i class="bi bi-calendar-event me-1"></i><?= date('M d, Y', strtotime($app['interview_date'])) ?></div>
                                                <div class="small fw-normal text-muted"><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($app['interview_date'])) ?></div>
                                            </div>
                                        <?php elseif ($app['status'] === 'Approved'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Not scheduled</span>
                                        <?php elseif ($app['status'] === 'Rejected'): ?>
                                            <span class="text-muted small">Application Closed</span>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-clock-history me-1"></i>Under Review</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="viewAppDetails(<?php echo $app['id']; ?>)"
                                                data-bs-toggle="modal" data-bs-target="#viewApplicationModal"
                                                title="View Full Application Details">
                                            <i class="bi bi-eye me-1"></i>View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Applications Pagination -->
            <?php if ($appTotalPages > 1): ?>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
                    <small class="text-muted">
                        Showing <?php echo $appOffset + 1; ?>–<?php echo min($appOffset + $appPerPage, $totalFilteredApps); ?> of <?php echo $totalFilteredApps; ?> applications
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $appPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo reportFilterUrl(['page' => $appPage - 1]); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($p = 1; $p <= $appTotalPages; $p++):
                                if (!($p === 1 || $p === $appTotalPages || abs($p - $appPage) <= 2)) continue;
                            ?>
                                <li class="page-item <?php echo $p === $appPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo reportFilterUrl(['page' => $p]); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $appPage >= $appTotalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo reportFilterUrl(['page' => $appPage + 1]); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>

        <!-- Applicants per Job -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-bar-chart me-2 text-primary"></i>Total Applicants per Job
                </h5>
                <div class="btn-group btn-group-sm" role="group" id="jobChartToggle">
                    <button type="button" class="btn btn-primary active" onclick="showJobView('chart')">
                        <i class="bi bi-bar-chart-horizontal"></i> Chart
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="showJobView('table')">
                        <i class="bi bi-table"></i> Table
                    </button>
                </div>
            </div>

            <!-- Chart View -->
            <div id="jobChartView" class="card-body">
                <div style="position:relative; height:<?php echo max(200, count($applicants_per_job) * 38); ?>px;">
                    <canvas id="jobChart"></canvas>
                </div>
            </div>

            <!-- Table View (hidden by default) -->
            <div id="jobTableView" class="card-body p-0 d-none">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <?php
                        $columns = ["#", "Job Title", "Total Applicants", "Visual"];
                        include $basePath . "components/table-header.php";
                        ?>
                        <tbody>
                            <?php if (empty($applicants_per_job)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox me-2"></i>No job data yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applicants_per_job as $i => $row): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td>
                                            <i class="bi bi-briefcase me-1 text-primary"></i>
                                            <?php echo htmlspecialchars($row["job_title"]); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary rounded-pill"><?php echo $row["applicants"]; ?></span>
                                        </td>
                                        <td style="width:40%;">
                                            <div class="progress" style="height:20px;">
                                                <?php $pct = $maxApplicants > 0 ? round(($row["applicants"] / $maxApplicants) * 100) : 0; ?>
                                                <div class="progress-bar bg-primary" style="width:<?php echo $pct; ?>%">
                                                    <?php if ($pct > 10): ?><?php echo $row["applicants"]; ?><?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- /jobTableView -->
        </div>

        <!-- Monthly Application Report -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-calendar-month me-2 text-primary"></i>Monthly Application Report
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive mb-4">
                    <table class="table table-hover">
                        <?php
                        $columns = ["#", "Month", "Total", "Approved", "Rejected", "Pending", "Visual"];
                        include $basePath . "components/table-header.php";
                        ?>
                        <tbody>
                            <?php if (empty($monthly_report)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox me-2"></i>No application data yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monthly_report as $i => $row): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td>
                                            <i class="bi bi-calendar me-1 text-primary"></i>
                                            <?php echo htmlspecialchars($row["month"]); ?>
                                        </td>
                                        <td><span class="badge bg-secondary rounded-pill"><?php echo $row["applications"]; ?></span></td>
                                        <td><span class="badge bg-success rounded-pill"><?php echo $row["approved"]; ?></span></td>
                                        <td><span class="badge bg-danger rounded-pill"><?php echo $row["rejected"]; ?></span></td>
                                        <td><span class="badge bg-warning text-dark rounded-pill"><?php echo $row["pending"]; ?></span></td>
                                        <td style="width:30%;">
                                            <div class="progress" style="height:20px;">
                                                <?php $pct = $maxMonthly > 0 ? round(($row["applications"] / $maxMonthly) * 100) : 0; ?>
                                                <div class="progress-bar bg-success" style="width:<?php echo $pct; ?>%">
                                                    <?php if ($pct > 10): ?><?php echo $row["applications"]; ?><?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Chart — rendered by Chart.js below -->
                <div class="mt-3">
                    <canvas id="monthlyChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </main>
</div>

<?php 
include $basePath . "modals/view-application-modal.php";
?>

<!-- Chart.js & Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const APP_HANDLER_ADMIN = "../../handlers/applications.php";

// ── Application Modal Details Viewer ───────────────────────────
function viewAppDetails(appId) {
    const histEl = document.getElementById("viewAppHistory");
    if (histEl) histEl.innerHTML = '<p class="text-muted small"><i class="bi bi-hourglass-split me-1"></i>Loading…</p>';

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

        // ── Interview Schedule ──────────────────────────────
        const interviewRow = document.getElementById("viewAppInterviewRow");
        const interviewDateEl = document.getElementById("viewAppInterviewDate");
        const interviewNotesWrap = document.getElementById("viewAppInterviewNotesWrap");
        const interviewNotesEl = document.getElementById("viewAppInterviewNotes");

        if (app.status === "Approved" && app.interview_date) {
            if (interviewRow) interviewRow.style.display = "";
            if (interviewDateEl) {
                const d = new Date(app.interview_date.replace(' ', 'T'));
                interviewDateEl.textContent = isNaN(d.getTime()) ? app.interview_date : d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
            }
            if (app.interview_notes && interviewNotesWrap && interviewNotesEl) {
                interviewNotesWrap.style.display = "";
                interviewNotesEl.textContent = app.interview_notes;
            } else if (interviewNotesWrap) {
                interviewNotesWrap.style.display = "none";
            }
        } else if (interviewRow) {
            interviewRow.style.display = "none";
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

// ── PHP data → JS ─────────────────────────────────────────────
const jobLabels    = <?php echo json_encode(array_column($applicants_per_job, 'job_title')); ?>;
const jobCounts    = <?php echo json_encode(array_map('intval', array_column($applicants_per_job, 'applicants'))); ?>;

// Monthly data comes DESC — reverse for chronological (left → right)
const monthlyRaw   = <?php echo json_encode(array_reverse($monthly_report)); ?>;
const monthLabels  = monthlyRaw.map(r => r.month);
const monthTotal   = monthlyRaw.map(r => parseInt(r.applications));
const monthApproved= monthlyRaw.map(r => parseInt(r.approved));
const monthRejected= monthlyRaw.map(r => parseInt(r.rejected));
const monthPending = monthlyRaw.map(r => parseInt(r.pending));

// ── Shared defaults ───────────────────────────────────────────
Chart.defaults.font.family = "'Inter', 'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#64748b';

// ── Chart 1: Applicants per Job (horizontal bar) ─────────────
const jobCtx = document.getElementById('jobChart');
if (jobCtx) {
    new Chart(jobCtx, {
        type: 'bar',
        data: {
            labels: jobLabels,
            datasets: [{
                label: 'Applicants',
                data: jobCounts,
                backgroundColor: 'rgba(79, 70, 229, 0.75)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.x} applicant${ctx.parsed.x !== 1 ? 's' : ''}`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        callback: function(val) {
                            const label = this.getLabelForValue(val);
                            return label.length > 28 ? label.substring(0, 26) + '…' : label;
                        }
                    }
                }
            }
        }
    });
}

// ── Chart 2: Monthly Applications (grouped bar) ──────────────
const monthCtx = document.getElementById('monthlyChart');
if (monthCtx) {
    new Chart(monthCtx, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [
                {
                    label: 'Approved',
                    data: monthApproved,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Rejected',
                    data: monthRejected,
                    backgroundColor: 'rgba(239, 68, 68, 0.75)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Pending',
                    data: monthPending,
                    backgroundColor: 'rgba(245, 158, 11, 0.75)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Total',
                    data: monthTotal,
                    type: 'line',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    backgroundColor: 'rgba(79, 70, 229, 0.08)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y',
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, padding: 16 }
                },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { grid: { color: 'rgba(0,0,0,0.04)' } },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                }
            }
        }
    });
}

// ── Toggle: Chart ↔ Table ─────────────────────────────────────
function showJobView(view) {
    const chartView = document.getElementById('jobChartView');
    const tableView = document.getElementById('jobTableView');
    const btns = document.querySelectorAll('#jobChartToggle button');
    if (view === 'chart') {
        chartView.classList.remove('d-none');
        tableView.classList.add('d-none');
        btns[0].classList.add('active'); btns[0].classList.replace('btn-outline-primary','btn-primary');
        btns[1].classList.remove('active'); btns[1].classList.replace('btn-primary','btn-outline-primary');
    } else {
        chartView.classList.add('d-none');
        tableView.classList.remove('d-none');
        btns[1].classList.add('active'); btns[1].classList.replace('btn-outline-primary','btn-primary');
        btns[0].classList.remove('active'); btns[0].classList.replace('btn-primary','btn-outline-primary');
    }
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>