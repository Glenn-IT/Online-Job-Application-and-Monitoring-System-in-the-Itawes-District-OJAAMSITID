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
        (SELECT COUNT(*) FROM applications WHERE status = 'Approved') AS approved_applications
");
$stats = $stmtStats->fetch();

// Recent activity (latest 20)
$stmtLog = $pdo->query("
    SELECT
        DATE_FORMAT(created_at, '%b %d, %Y') AS date,
        DATE_FORMAT(created_at, '%h:%i %p')  AS time,
        action,
        status
    FROM activity_logs
    ORDER BY created_at DESC
    LIMIT 20
");
$activity_history = $stmtLog->fetchAll();

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-admin.php";
?>
<div class="container-fluid">
    <div class="row">
        <?php include $basePath . "layouts/sidebar-admin.php"; ?>
        <div class="col-lg-10 col-md-9 py-4 px-4">
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

        <!-- Activity History Table -->
        <div class="card border-0 shadow-sm mt-2">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Recent Activity
                </h5>
            </div>
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
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox me-2"></i>No activity yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activity_history as $a): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($a["date"]); ?></td>
                                        <td><?php echo htmlspecialchars($a["time"]); ?></td>
                                        <td><?php echo htmlspecialchars($a["action"]); ?></td>
                                        <td>
                                            <?php
                                            $badge = match($a["status"]) {
                                                "New"      => "bg-info",
                                                "Created"  => "bg-primary",
                                                "Updated"  => "bg-secondary",
                                                "Deleted"  => "bg-dark",
                                                "Applied"  => "bg-primary",
                                                "Cancelled"=> "bg-warning text-dark",
                                                "Approved" => "bg-success",
                                                "Rejected" => "bg-danger",
                                                default    => "bg-secondary"
                                            };
                                            ?>
                                            <span class="badge <?php echo $badge; ?>">
                                                <?php echo htmlspecialchars($a["status"]); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div><!-- /col-lg-10 -->
    </div><!-- /row -->
</div><!-- /container-fluid -->
<?php include $basePath . "layouts/footer.php"; ?>