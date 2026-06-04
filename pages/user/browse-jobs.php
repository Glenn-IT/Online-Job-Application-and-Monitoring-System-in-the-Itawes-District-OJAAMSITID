<?php
require_once __DIR__ . "/../../config/auth.php";
requireUser();
$pageTitle   = "OJAMS - Browse Jobs";
$basePath    = "../../";
$currentPage = "browse-jobs";

// ── Filters from URL ────────────────────────────────────────
$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$allowedStatus = ['', 'Open', 'Closed'];
if (!in_array($statusFilter, $allowedStatus)) $statusFilter = '';

// ── Build filtered query ─────────────────────────────────────
$where  = [];
$params = [];
if ($search !== '') {
    $where[]  = "(j.title LIKE ? OR j.company LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($statusFilter !== '') {
    $where[]  = "j.status = ?";
    $params[] = $statusFilter;
}
$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$jobsStmt = $pdo->prepare("SELECT j.* FROM jobs j {$whereSQL} ORDER BY j.date_posted DESC");
$jobsStmt->execute($params);
$jobs = $jobsStmt->fetchAll();

$appliedStmt = $pdo->prepare("SELECT job_id FROM applications WHERE user_id = ?");
$appliedStmt->execute([$_SESSION["ojams_user"]["id"]]);
$appliedJobIds = array_column($appliedStmt->fetchAll(), "job_id");

$countRows = $pdo->query("SELECT job_id, COUNT(*) as cnt FROM applications GROUP BY job_id")->fetchAll();
$appCounts = [];
foreach ($countRows as $row) {
    $appCounts[$row["job_id"]] = $row["cnt"];
}

$totalJobs = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-user.php";
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-search me-2 text-primary"></i>Browse Jobs</h2>
            <p class="text-muted mb-0">Find and apply to job openings that match your skills.</p>
        </div>
        <span class="badge bg-primary fs-6">
            <?php echo count($jobs); ?> of <?php echo $totalJobs; ?> Jobs
        </span>
    </div>
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" placeholder="Search jobs by title or company..."
                       id="jobSearch" onkeyup="filterJobs()"
                       value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="statusFilter" onchange="filterJobs()">
                <option value="">All Status</option>
                <option value="Open"   <?php echo $statusFilter === 'Open'   ? 'selected' : ''; ?>>Open</option>
                <option value="Closed" <?php echo $statusFilter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        <div class="col-md-1">
            <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="browse-jobs.php" class="btn btn-outline-secondary w-100" title="Clear filters">
                <i class="bi bi-x-lg"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="row" id="jobCardsContainer">
        <?php if (empty($jobs)): ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-briefcase display-4 d-block mb-2"></i>No job postings available.
            </div>
        <?php else: ?>
        <?php foreach ($jobs as $job):
            $alreadyApplied = in_array($job["id"], $appliedJobIds);
            $isOpen         = $job["status"] === "Open";
            $cnt            = $appCounts[$job["id"]] ?? 0;
        ?>
        <div class="col-md-6 col-lg-4 mb-4 job-card-wrap"
             data-title="<?php echo strtolower(htmlspecialchars($job['title'])); ?>"
             data-company="<?php echo strtolower(htmlspecialchars($job['company'])); ?>"
             data-status="<?php echo $job['status']; ?>">
            <div class="card h-100 shadow-sm border-0 job-card">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-bold text-primary">
                        <i class="bi bi-briefcase me-2"></i><?php echo htmlspecialchars($job['title']); ?>
                    </h5>
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="bi bi-building me-1"></i><?php echo htmlspecialchars($job['company']); ?>
                    </h6>
                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars($job['description']); ?></p>
                    <p class="card-text">
                        <small class="text-muted">
                            <i class="bi bi-mortarboard me-1"></i>
                            <strong>Qualifications:</strong> <?php echo htmlspecialchars($job['qualification']); ?>
                        </small>
                    </p>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <i class="bi bi-calendar-event me-1"></i>Posted: <?php echo $job['date_posted']; ?>
                        </small>
                        <span class="badge <?php echo $isOpen ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $job['status']; ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-people-fill text-primary me-1"></i>
                            <?php echo $cnt; ?> Applicant<?php echo $cnt !== 1 ? 's' : ''; ?>
                        </span>
                    </div>
                    <?php if ($alreadyApplied): ?>
                        <button class="btn btn-success w-100" disabled>
                            <i class="bi bi-check-circle me-1"></i>Already Applied
                        </button>
                    <?php elseif ($isOpen): ?>
                        <button class="btn btn-primary w-100"
                            onclick="openApplyModal(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['company'], ENT_QUOTES); ?>')">
                            <i class="bi bi-send me-1"></i>Apply Now
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100" disabled>
                            <i class="bi bi-lock me-1"></i>Closed
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include $basePath . "modals/apply-job-modal.php"; ?>
<script>
const APP_HANDLER = "../../handlers/applications.php";

function computeAge(birthdate) {
    if (!birthdate) return "";
    const today = new Date();
    const dob   = new Date(birthdate);
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    return age >= 0 ? age : "";
}

function openApplyModal(jobId, title, company) {
    document.getElementById("applyJobTitle").textContent   = title;
    document.getElementById("applyJobCompany").textContent = company;
    document.getElementById("applicationForm").reset();
    document.getElementById("applicationForm").dataset.jobId = jobId;
    const sess = <?php echo json_encode([
        "full_name"      => $_SESSION["ojams_user"]["full_name"],
        "contact_number" => $_SESSION["ojams_user"]["contact_number"] ?? "",
        "address"        => $_SESSION["ojams_user"]["address"] ?? "",
        "birthdate"      => $_SESSION["ojams_user"]["birthdate"] ?? "",
    ]); ?>;
    const setV = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ""; };
    setV("appFullName",  sess.full_name);
    setV("appContact",   sess.contact_number);
    setV("appAddress",   sess.address);
    setV("appBirthdate", sess.birthdate);
    // Auto-compute age from session birthdate
    const ageEl = document.getElementById("appAge");
    if (ageEl && sess.birthdate) ageEl.value = computeAge(sess.birthdate);
    new bootstrap.Modal(document.getElementById("applyJobModal")).show();
}

// Auto-compute age when birthdate input changes
document.addEventListener("DOMContentLoaded", function () {
    const bdEl = document.getElementById("appBirthdate");
    const ageEl = document.getElementById("appAge");
    if (bdEl && ageEl) {
        bdEl.addEventListener("change", function () {
            ageEl.value = computeAge(this.value);
        });
    }
});

function submitApplication() {
    const form  = document.getElementById("applicationForm");
    const jobId = form ? form.dataset.jobId : null;
    if (!jobId) { showToast("No job selected.", "danger"); return; }

    const g = (id) => document.getElementById(id) ? document.getElementById(id).value.trim() : "";
    const payload = {
        action:     "apply",
        job_id:     parseInt(jobId),
        full_name:  g("appFullName"),
        email:      "<?php echo htmlspecialchars($_SESSION['ojams_user']['email'], ENT_QUOTES); ?>",
        contact:    g("appContact"),
        address:    g("appAddress"),
        birthdate:  document.getElementById("appBirthdate") ? document.getElementById("appBirthdate").value : "",
        age:        document.getElementById("appAge") ? parseInt(document.getElementById("appAge").value) || 0 : 0,
        elementary: g("appElementary"),
        jhs:        g("appJhs"),
        shs:        g("appShs"),
        college:    g("appCollege"),
        skills:     g("appSkills"),
        experience: g("appExperience"),
        csrf_token: getCsrfToken(),
    };

    if (!payload.full_name) { showToast("Full name is required.", "warning"); return; }

    fetch(APP_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        bootstrap.Modal.getInstance(document.getElementById("applyJobModal"))?.hide();
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) setTimeout(() => location.reload(), 1200);
    })
    .catch(() => showToast("Request failed. Please try again.", "danger"));
}

let _filterTimer = null;
function filterJobs() {
    // Instant client-side hide for immediate feedback
    const q      = (document.getElementById("jobSearch")?.value || "").toLowerCase();
    const status = (document.getElementById("statusFilter")?.value || "").toLowerCase();
    document.querySelectorAll(".job-card-wrap").forEach(card => {
        const matchText   = !q || card.dataset.title.includes(q) || card.dataset.company.includes(q);
        const matchStatus = !status || card.dataset.status.toLowerCase() === status;
        card.style.display = (matchText && matchStatus) ? "" : "none";
    });
    // Persist state in URL (debounced) so filters survive page reload
    clearTimeout(_filterTimer);
    _filterTimer = setTimeout(() => {
        const params = new URLSearchParams();
        if (q)      params.set("search", q);
        if (status) params.set("status", document.getElementById("statusFilter").value);
        const newUrl = window.location.pathname + (params.toString() ? "?" + params.toString() : "");
        history.replaceState(null, "", newUrl);
    }, 400);
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>