<?php
require_once __DIR__ . '/../../components/under-construction.php';

require_once __DIR__ . "/../../config/auth.php";
requireAdmin();

$pageTitle   = "OJAMS - Reports & Monitoring";
$basePath    = "../../";
$currentPage = "reports";

// Applicants per job — top 20 by applicant count
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

// Monthly application report — last 12 months only
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

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-admin.php";
?>
<div class="admin-layout">
    <?php include $basePath . "layouts/sidebar-admin.php"; ?>
    <main class="admin-main">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-graph-up me-2 text-primary"></i>Reports &amp; Monitoring
                </h2>
                <p class="text-muted mb-0">View analytics and generate system reports.</p>
            </div>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i>Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item"
                           href="export-report.php?type=applicants_csv" download>
                            <i class="bi bi-filetype-csv me-2 text-success"></i>Applicants per Job — CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                           href="export-report.php?type=monthly_csv" download>
                            <i class="bi bi-filetype-csv me-2 text-success"></i>Monthly Report — CSV
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item"
                           href="export-report.php?type=print"
                           target="_blank" rel="noopener">
                            <i class="bi bi-printer me-2 text-primary"></i>Print / Save as PDF
                        </a>
                    </li>
                </ul>
            </div>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
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