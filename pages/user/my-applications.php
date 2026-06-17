<?php
require_once __DIR__ . '/../../components/under-construction.php';
require_once __DIR__ . "/../../config/auth.php";
requireUser();
$pageTitle   = "OJAMS - My Applications";
$basePath    = "../../";
$currentPage = "my-applications";

$userId = $_SESSION["ojams_user"]["id"];
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.company
    FROM applications a
    JOIN jobs j ON j.id = a.job_id
    WHERE a.user_id = ?
    ORDER BY a.date_applied DESC
");
$stmt->execute([$userId]);
$myApps = $stmt->fetchAll();

$total    = count($myApps);
$pending  = count(array_filter($myApps, fn($a) => $a["status"] === "Pending"));
$approved = count(array_filter($myApps, fn($a) => $a["status"] === "Approved"));

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-user.php";
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-file-earmark-text me-2 text-primary"></i>My Applications
            </h2>
            <p class="text-muted mb-0">Track the status of your submitted job applications.</p>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <?php
                    $columns = ["#", "Job Title", "Company", "Date Applied", "Status", "Actions"];
                    include $basePath . "components/table-header.php";
                    ?>
                    <tbody id="myAppsTbody">
                        <?php if (empty($myApps)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-inbox display-5 text-muted d-block mb-3"></i>
                                    <p class="text-muted mb-3">You haven't submitted any applications yet.</p>
                                    <a href="browse-jobs.php" class="btn btn-primary">
                                        <i class="bi bi-search me-1"></i>Browse Job Listings
                                    </a>
                                </td>
                            </tr>
                        <?php else: $count = 1; foreach ($myApps as $app):
                            $badgeClass = match($app["status"]) {
                                "Approved" => "bg-success",
                                "Rejected" => "bg-danger",
                                "Pending"  => "bg-warning text-dark",
                                default    => "bg-secondary"
                            };
                        ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><i class="bi bi-briefcase me-1 text-primary"></i><?php echo htmlspecialchars($app["job_title"]); ?></td>
                                <td><?php echo htmlspecialchars($app["company"]); ?></td>
                                <td><?php echo $app["date_applied"]; ?></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $app["status"]; ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick="viewMyApp(<?php echo $app['id']; ?>)"
                                        data-bs-toggle="modal" data-bs-target="#viewMyApplicationModal">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <?php if ($app["status"] === "Pending"): ?>
                                    <button class="btn btn-sm btn-outline-danger"
                                        onclick="confirmCancel('<?php echo htmlspecialchars($app['job_title'], ENT_QUOTES); ?>', <?php echo $app['id']; ?>)"
                                        data-bs-toggle="modal" data-bs-target="#cancelApplicationModal">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Summary Cards -->
    <div class="row mt-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <h4 class="fw-bold text-primary mb-1"><?php echo $total; ?></h4>
                <small class="text-muted">Total Applications</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <h4 class="fw-bold text-warning mb-1"><?php echo $pending; ?></h4>
                <small class="text-muted">Pending</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <h4 class="fw-bold text-success mb-1"><?php echo $approved; ?></h4>
                <small class="text-muted">Approved</small>
            </div>
        </div>
    </div>
</div>
<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelApplicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Cancel Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-x-circle-fill text-danger display-4 mb-3 d-block"></i>
                <p class="mb-1">Are you sure you want to cancel your application for:</p>
                <h5 class="fw-bold" id="cancelAppJobTitle">—</h5>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-1"></i>Go Back
                </button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                    <i class="bi bi-x-circle me-1"></i>Yes, Cancel Application
                </button>
            </div>
        </div>
    </div>
</div>
<!-- View Application Modal -->
<div class="modal fade" id="viewMyApplicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>My Application Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Job Information</h6>
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:45%;">Job Title</th>  <td id="vmyAppJobTitle">—</td></tr>
                            <tr><th class="text-muted">Company</th>                        <td id="vmyAppCompany">—</td></tr>
                            <tr><th class="text-muted">Date Applied</th>                   <td id="vmyAppDate">—</td></tr>
                            <tr><th class="text-muted">Status</th>                         <td id="vmyAppStatus">—</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Your Info</h6>
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:45%;">Name</th>       <td id="vmyAppName">—</td></tr>
                            <tr><th class="text-muted">Email</th>                          <td id="vmyAppEmail">—</td></tr>
                            <tr><th class="text-muted">Contact</th>                        <td id="vmyAppContact">—</td></tr>
                            <tr><th class="text-muted">Address</th>                        <td id="vmyAppAddress">—</td></tr>
                            <tr><th class="text-muted">Birthdate</th>                      <td id="vmyAppBirthdate">—</td></tr>
                            <tr><th class="text-muted">Age</th>                            <td id="vmyAppAge">—</td></tr>
                        </table>
                    </div>
                    <div class="col-12">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Education</h6>
                        <div class="row">
                            <div class="col-md-3"><small class="text-muted">Elementary</small><div id="vmyAppElem">—</div></div>
                            <div class="col-md-3"><small class="text-muted">JHS</small><div id="vmyAppJhs">—</div></div>
                            <div class="col-md-3"><small class="text-muted">SHS</small><div id="vmyAppShs">—</div></div>
                            <div class="col-md-3"><small class="text-muted">College</small><div id="vmyAppCollege">—</div></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Skills &amp; Experience</h6>
                        <div class="mb-1"><strong>Skills:</strong> <span id="vmyAppSkills">—</span></div>
                        <div><strong>Experience:</strong> <span id="vmyAppExperience">—</span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
<script>
const APP_HANDLER_MY = "../../handlers/applications.php";
let _cancelAppId = null;

function confirmCancel(title, appId) {
    _cancelAppId = appId;
    const el = document.getElementById("cancelAppJobTitle");
    if (el) el.textContent = title;
}

document.getElementById("confirmCancelBtn").addEventListener("click", function() {
    if (!_cancelAppId) return;
    fetch(APP_HANDLER_MY, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "cancel", id: _cancelAppId, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        bootstrap.Modal.getInstance(document.getElementById("cancelApplicationModal"))?.hide();
        showToast(res.message, res.success ? "warning" : "danger");
        if (res.success) setTimeout(() => location.reload(), 900);
    })
    .catch(() => showToast("Request failed.", "danger"));
    _cancelAppId = null;
});

// PHP-embedded application data for view modal
const myAppsData = <?php echo json_encode(array_values($myApps)); ?>;

function viewMyApp(appId) {
    const app = myAppsData.find(a => a.id == appId);
    if (!app) return;
    const setT = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || "—"; };
    setT("vmyAppJobTitle",  app.job_title);
    setT("vmyAppCompany",   app.company);
    setT("vmyAppDate",      app.date_applied);
    setT("vmyAppName",      app.full_name);
    setT("vmyAppEmail",     app.email);
    setT("vmyAppContact",   app.contact);
    setT("vmyAppAddress",   app.address);
    setT("vmyAppBirthdate", app.birthdate);
    setT("vmyAppAge",       app.age);
    setT("vmyAppElem",      app.elementary);
    setT("vmyAppJhs",       app.jhs);
    setT("vmyAppShs",       app.shs);
    setT("vmyAppCollege",   app.college);
    setT("vmyAppSkills",    app.skills);
    setT("vmyAppExperience",app.experience);
    const statusEl = document.getElementById("vmyAppStatus");
    if (statusEl) {
        const cls = app.status === "Approved" ? "bg-success" : app.status === "Rejected" ? "bg-danger" : "bg-warning text-dark";
        statusEl.innerHTML = "<span class=\"badge " + cls + "\">" + app.status + "</span>";
    }
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>