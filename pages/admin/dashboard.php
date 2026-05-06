<?php

require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$pageTitle   = "OJAMS - Admin Dashboard";
$basePath    = "../../";
$currentPage = "dashboard";

// Load sample data
include $basePath . 'data/sample-data.php';

// Include header and admin navbar
include $basePath . 'layouts/header.php';
include $basePath . 'layouts/navbar-admin.php';
?>

<!-- ── Admin Layout: Sidebar + Content ── -->
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include $basePath . 'layouts/sidebar-admin.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-10 col-md-9 py-4 px-4">
            <!-- Page Header -->
            <div class="mb-4">
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
                </h2>
                <p class="text-muted mb-0">Welcome back, Administrator! Here's an overview of the system.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <?php
                // Total Job Posts
                $statTitle = "Total Job Posts";
                $statValue = $stats['total_jobs'];
                $statIcon  = "bi-briefcase-fill";
                $statColor = "#0d6efd";
                include $basePath . 'components/stats-card.php';

                // Total Applicants
                $statTitle = "Total Applicants";
                $statValue = $stats['total_applicants'];
                $statIcon  = "bi-people-fill";
                $statColor = "#198754";
                include $basePath . 'components/stats-card.php';

                // Pending Applications
                $statTitle = "Pending Applications";
                $statValue = $stats['pending_applications'];
                $statIcon  = "bi-hourglass-split";
                $statColor = "#ffc107";
                include $basePath . 'components/stats-card.php';

                // Approved Applications
                $statTitle = "Approved Applications";
                $statValue = $stats['approved_applications'];
                $statIcon  = "bi-check-circle-fill";
                $statColor = "#20c997";
                include $basePath . 'components/stats-card.php';
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
                            $columns = ['Date', 'Time', 'Action', 'Status'];
                            include $basePath . 'components/table-header.php';
                            ?>
                            <tbody id="activityTbody">
                                <?php foreach ($activity_history as $activity): ?>
                                    <tr>
                                        <td><?php echo $activity['date']; ?></td>
                                        <td><?php echo $activity['time']; ?></td>
                                        <td><?php echo $activity['action']; ?></td>
                                        <td>
                                            <?php
                                            $actBadge = match($activity['status']) {
                                                'New'      => 'bg-info',
                                                'Created'  => 'bg-primary',
                                                'Approved' => 'bg-success',
                                                'Rejected' => 'bg-danger',
                                                default    => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $actBadge; ?>">
                                                <?php echo $activity['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $basePath . 'layouts/footer.php'; ?>

<script>
requireAdmin('../../login.php', '../../pages/user/browse-jobs.php');

// Refresh stats from localStorage
document.addEventListener('DOMContentLoaded', () => {
    const jobs = Jobs.all();
    const apps = Applications.all();
    const pending  = apps.filter(a => a.status === 'Pending').length;
    const approved = apps.filter(a => a.status === 'Approved').length;

    const statVals = document.querySelectorAll('.stat-value');
    if (statVals[0]) statVals[0].textContent = jobs.length;
    if (statVals[1]) statVals[1].textContent = apps.length;
    if (statVals[2]) statVals[2].textContent = pending;
    if (statVals[3]) statVals[3].textContent = approved;

    // Render activity log from localStorage
    const tbody = document.getElementById('activityTbody');
    if (tbody) {
        const log = getActivityLog();
        if (log.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No activity yet.</td></tr>';
        } else {
            tbody.innerHTML = log.map(a => `
                <tr>
                    <td>${a.date}</td>
                    <td>${a.time}</td>
                    <td>${a.action}</td>
                    <td><span class="badge ${statusBadgeClass(a.status)}">${a.status}</span></td>
                </tr>`).join('');
        }
    }

    // Show logged-in admin name
    const user = Session.get();
    const nameEls = document.querySelectorAll('.navbar-admin-name, #adminNavName');
    nameEls.forEach(el => { if (el) el.textContent = user?.full_name || 'Administrator'; });
});
</script>
