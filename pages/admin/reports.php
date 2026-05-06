<?php
require_once __DIR__ . "/../../config/auth.php";
requireAdmin();

$pageTitle   = "OJAMS - Reports & Monitoring";
$basePath    = "../../";
$currentPage = "reports";

// Applicants per job
$stmtPerJob = $pdo->query("
    SELECT j.title AS job_title, COUNT(a.id) AS applicants
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    GROUP BY j.id, j.title
    ORDER BY applicants DESC
");
$applicants_per_job = $stmtPerJob->fetchAll();
$maxApplicants = !empty($applicants_per_job) ? max(array_column($applicants_per_job, "applicants")) : 1;

// Monthly application report
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
");
$monthly_report = $stmtMonthly->fetchAll();
$maxMonthly = !empty($monthly_report) ? max(array_column($monthly_report, "applications")) : 1;

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-admin.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php include $basePath . "layouts/sidebar-admin.php"; ?>
        <div class="col-lg-10 col-md-9 py-4 px-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-graph-up me-2 text-primary"></i>Reports &amp; Monitoring
                </h2>
                <p class="text-muted mb-0">View analytics and generate system reports.</p>
            </div>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-download me-1"></i>Download Report
            </button>
        </div>

        <!-- Applicants per Job -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-bar-chart me-2 text-primary"></i>Total Applicants per Job
                </h5>
            </div>
            <div class="card-body p-0">
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
            </div>
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

                <!-- Chart Placeholder -->
                <div class="border rounded p-5 text-center bg-light">
                    <i class="bi bi-bar-chart-line display-1 text-muted"></i>
                    <p class="text-muted mt-3 mb-0">
                        <strong>Chart Placeholder</strong><br>
                        A visual chart (e.g., Chart.js) would be rendered here in the full implementation.
                    </p>
                </div>
            </div>
        </div>
        </div><!-- /col-lg-10 -->
    </div><!-- /row -->
</div><!-- /container-fluid -->
<?php include $basePath . "layouts/footer.php"; ?><?php include $basePath . "layouts/footer.php"; ?>