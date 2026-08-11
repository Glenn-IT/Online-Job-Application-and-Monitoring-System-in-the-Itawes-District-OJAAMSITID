<?php
require_once __DIR__ . "/../../config/auth.php";
requireUser();
require_once __DIR__ . '/../../components/under-construction.php';
$pageTitle   = "OJAMS - Saved Jobs";
$basePath    = "../../";
$currentPage = "saved-jobs";

$userId = $_SESSION["ojams_user"]["id"];

$stmt = $pdo->prepare("
    SELECT j.*, sj.saved_at
    FROM saved_jobs sj
    JOIN jobs j ON j.id = sj.job_id
    WHERE sj.user_id = ?
    ORDER BY sj.saved_at DESC
");
$stmt->execute([$userId]);
$savedJobs = $stmt->fetchAll();

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-user.php";
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-bookmark-heart me-2 text-primary"></i>Saved Jobs
            </h2>
            <p class="text-muted mb-0">Jobs you bookmarked for later review.</p>
        </div>
        <a href="browse-jobs.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-search me-1"></i>Browse More Jobs
        </a>
    </div>

    <?php if (empty($savedJobs)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-bookmark display-5 text-muted d-block mb-3"></i>
            <p class="text-muted mb-3">You haven't saved any jobs yet. Browse listings and click the bookmark icon to save jobs for later.</p>
            <a href="browse-jobs.php" class="btn btn-primary">
                <i class="bi bi-search me-1"></i>Browse Job Listings
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="savedJobsGrid">
        <?php foreach ($savedJobs as $job): ?>
        <?php
        $isOpen   = $job['status'] === 'Open';
        $deadline = $job['deadline'] ?? null;
        $expired  = $deadline && strtotime($deadline) < time();
        ?>
        <div class="col" id="saved-card-<?php echo $job['id']; ?>">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge <?php echo $isOpen && !$expired ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $isOpen && !$expired ? 'Open' : 'Closed'; ?>
                        </span>
                        <button class="btn btn-sm btn-outline-warning"
                                onclick="unsaveJob(<?php echo $job['id']; ?>)"
                                title="Remove from saved">
                            <i class="bi bi-bookmark-x"></i>
                        </button>
                    </div>
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($job['title']); ?></h6>
                    <p class="text-muted small mb-1"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($job['company']); ?></p>
                    <?php if ($job['location']): ?>
                    <p class="text-muted small mb-1"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($job['location']); ?></p>
                    <?php endif; ?>
                    <?php if ($job['job_type']): ?>
                    <p class="text-muted small mb-2"><i class="bi bi-clock me-1"></i><?php echo htmlspecialchars($job['job_type']); ?></p>
                    <?php endif; ?>
                    <p class="small text-muted mt-auto mb-0">
                        Saved <?php echo date('M j, Y', strtotime($job['saved_at'])); ?>
                    </p>
                </div>
                <div class="card-footer bg-white border-0 pt-0">
                    <a href="job-detail.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i>View Details
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script>
const SAVED_HANDLER = "../../handlers/saved-jobs.php";

function unsaveJob(jobId) {
    fetch(SAVED_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "toggle", job_id: jobId, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? "warning" : "danger");
        if (res.success && !res.saved) {
            const card = document.getElementById("saved-card-" + jobId);
            if (card) card.remove();
            const grid = document.getElementById("savedJobsGrid");
            if (grid && !grid.children.length) location.reload();
        }
    })
    .catch(() => showToast("Request failed.", "danger"));
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>
