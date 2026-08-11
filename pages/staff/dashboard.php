<?php
require_once __DIR__ . "/../../config/auth.php";
requireStaff();

$pageTitle   = "OJAMS - Staff Dashboard";
$basePath    = "../../";
$currentPage = "dashboard";

// DB stats
$stmtStats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM jobs)                                   AS total_jobs,
        (SELECT COUNT(*) FROM jobs WHERE status = 'Open')             AS open_jobs,
        (SELECT COUNT(*) FROM applications)                           AS total_applicants,
        (SELECT COUNT(*) FROM applications WHERE status = 'Pending')  AS pending_applications,
        (SELECT COUNT(*) FROM applications WHERE status = 'Approved') AS approved_applications,
        (SELECT COUNT(*) FROM applications WHERE status = 'Rejected') AS rejected_applications
");
$stats = $stmtStats->fetch();

// Recent applications awaiting review
$stmtRecentApps = $pdo->query("
    SELECT a.id, a.full_name, a.email, a.status, DATE_FORMAT(a.date_applied, '%b %d, %Y') AS formatted_date,
           j.title AS job_title, j.company
    FROM applications a
    JOIN jobs j ON j.id = a.job_id
    ORDER BY a.id DESC
    LIMIT 8
");
$recentApps = $stmtRecentApps->fetchAll();

// Applicants per job (for Chart.js)
$stmtJobApps = $pdo->query("
    SELECT j.title, COUNT(a.id) AS applicant_count
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    GROUP BY j.id, j.title
    ORDER BY j.date_posted DESC, j.id DESC
    LIMIT 6
");
$jobApplicantCounts = $stmtJobApps->fetchAll();

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-staff.php";
?>
<div class="admin-layout">
    <?php include $basePath . "layouts/sidebar-staff.php"; ?>
    <main class="admin-main">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-speedometer2 me-2 text-warning"></i>Staff Dashboard
                </h2>
                <p class="text-muted mb-0 small">Overview of job listings, candidate evaluations, and recruitment workflows.</p>
            </div>
            <div>
                <a href="applications.php" class="btn btn-warning me-2">
                    <i class="bi bi-file-earmark-person me-1"></i>Review Applications
                    <?php if ($stats['pending_applications'] > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $stats['pending_applications'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="manage-jobs.php" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i>Post Job
                </a>
            </div>
        </div>

        <!-- Metric Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Pending Evaluation</div>
                            <h3 class="fw-bold text-warning mb-0"><?= number_format($stats['pending_applications']) ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-warning-subtle text-warning">
                            <i class="bi bi-clock-history fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Approved Candidates</div>
                            <h3 class="fw-bold text-success mb-0"><?= number_format($stats['approved_applications']) ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-success-subtle text-success">
                            <i class="bi bi-check-circle-fill fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Total Applications</div>
                            <h3 class="fw-bold text-primary mb-0"><?= number_format($stats['total_applicants']) ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-primary-subtle text-primary">
                            <i class="bi bi-file-earmark-text fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold text-uppercase mb-1">Active Job Posts</div>
                            <h3 class="fw-bold text-info mb-0"><?= number_format($stats['open_jobs']) ?> / <?= number_format($stats['total_jobs']) ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-info-subtle text-info">
                            <i class="bi bi-briefcase-fill fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Recent Applications -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-file-earmark-person me-2 text-warning"></i>Recent Job Applications
                        </h6>
                        <a href="applications.php" class="small text-decoration-none fw-semibold">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Position</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentApps)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No applications submitted yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentApps as $app): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?= htmlspecialchars($app['full_name']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($app['email']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="small fw-semibold"><?= htmlspecialchars($app['job_title']) ?></div>
                                                    <div class="text-muted text-truncate style-max-w150"><?= htmlspecialchars($app['company']) ?></div>
                                                </td>
                                                <td class="small text-muted"><?= $app['formatted_date'] ?></td>
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
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Job Applicants Distribution -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Applicants per Job Position
                        </h6>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center p-3">
                        <canvas id="staffJobChart" style="max-height: 280px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('staffJobChart').getContext('2d');
    const labelsData = <?= json_encode(array_column($jobApplicantCounts, 'title')) ?>;
    const countsData = <?= json_encode(array_column($jobApplicantCounts, 'applicant_count')) ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labelsData,
            datasets: [{
                label: 'Applicants',
                data: countsData,
                backgroundColor: 'rgba(79, 70, 229, 0.75)',
                borderColor: '#4f46e5',
                borderWidth: 1.5,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
});
</script>
<?php include $basePath . "layouts/footer.php"; ?>
