<?php
require_once __DIR__ . "/../../config/auth.php";
requireUser();
$pageTitle   = "OJAMS - My Applications";
$basePath    = "../../";
$currentPage = "my-applications";

$userId = $_SESSION["ojams_user"]["id"];
$stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, j.company, j.contact_person, j.contact_phone
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
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="bi bi-file-earmark-text me-2 text-primary"></i>My Applications
            </h2>
            <p class="text-muted mb-0">Track the status of your submitted job applications.</p>
        </div>
    </div>

    <!-- Metric Overview Cards (Mobile 3-column grid) -->
    <div class="row g-2 g-sm-3 mb-4">
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center p-2 p-sm-3 rounded-3" style="background: #eef2ff;">
                <span class="text-muted small fw-semibold text-truncate d-block" style="font-size: 0.72rem;">Total Submitted</span>
                <span class="fs-4 fw-bold text-primary"><?= number_format($total) ?></span>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center p-2 p-sm-3 rounded-3" style="background: #fefce8;">
                <span class="text-muted small fw-semibold text-truncate d-block" style="font-size: 0.72rem;">Pending Review</span>
                <span class="fs-4 fw-bold text-warning"><?= number_format($pending) ?></span>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center p-2 p-sm-3 rounded-3" style="background: #f0fdf4;">
                <span class="text-muted small fw-semibold text-truncate d-block" style="font-size: 0.72rem;">Approved</span>
                <span class="fs-4 fw-bold text-success"><?= number_format($approved) ?></span>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                                <td class="text-nowrap"><?php echo $app["date_applied"]; ?></td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $app["status"]; ?></span>
                                    <?php if ($app["status"] === "Approved" && !empty($app["interview_date"])): ?>
                                        <div class="mt-1 small text-success fw-semibold text-nowrap">
                                            <i class="bi bi-calendar2-check me-1"></i><?= date('M d, Y h:i A', strtotime($app['interview_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
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
    <div class="row g-2 g-sm-3 mt-3">
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center p-2 p-sm-3">
                <h4 class="fw-bold text-primary mb-1"><?php echo $total; ?></h4>
                <small class="text-muted text-truncate d-block">Total</small>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center p-2 p-sm-3">
                <h4 class="fw-bold text-warning mb-1"><?php echo $pending; ?></h4>
                <small class="text-muted text-truncate d-block">Pending</small>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center p-2 p-sm-3">
                <h4 class="fw-bold text-success mb-1"><?php echo $approved; ?></h4>
                <small class="text-muted text-truncate d-block">Approved</small>
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
                    <div class="col-12 col-md-6">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Job Information</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr><th class="text-muted" style="width:45%;">Job Title</th>  <td id="vmyAppJobTitle">—</td></tr>
                            <tr><th class="text-muted">Company</th>                        <td id="vmyAppCompany">—</td></tr>
                            <tr><th class="text-muted">Hiring Contact</th>                 <td id="vmyAppContactPerson">—</td></tr>
                            <tr><th class="text-muted">Contact Phone</th>                  <td id="vmyAppContactPhone">—</td></tr>
                            <tr><th class="text-muted">Date Applied</th>                   <td id="vmyAppDate">—</td></tr>
                            <tr><th class="text-muted">Status</th>                         <td id="vmyAppStatus">—</td></tr>
                        </table>
                    </div>
                    <div class="col-12 col-md-6">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Your Info</h6>
                        <table class="table table-borderless table-sm mb-0">
                            <tr><th class="text-muted" style="width:45%;">Name</th>       <td id="vmyAppName">—</td></tr>
                            <tr><th class="text-muted">Email</th>                          <td id="vmyAppEmail">—</td></tr>
                            <tr><th class="text-muted">Contact</th>                        <td id="vmyAppContact">—</td></tr>
                            <tr><th class="text-muted">Address</th>                        <td id="vmyAppAddress">—</td></tr>
                            <tr><th class="text-muted">Birthdate</th>                      <td id="vmyAppBirthdate">—</td></tr>
                            <tr><th class="text-muted">Age</th>                            <td id="vmyAppAge">—</td></tr>
                        </table>
                    </div>
                    
                    <!-- Interview Schedule (if approved) -->
                    <div class="col-12" id="vmyAppInterviewRow" style="display:none;">
                        <div class="alert alert-success d-flex align-items-start gap-3 mb-0 border-success-subtle">
                            <i class="bi bi-calendar2-check-fill fs-3 text-success"></i>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading fw-bold mb-1 text-success">
                                    <i class="bi bi-calendar2-event me-1"></i>Scheduled Interview
                                </h6>
                                <p class="mb-1"><strong>Date &amp; Time:</strong> <span id="vmyAppInterviewDate" class="badge bg-success fs-6">—</span></p>
                                <div id="vmyAppInterviewNotesWrap" style="display:none;">
                                    <p class="mb-0 text-muted small"><strong>Instructions / Venue:</strong> <span id="vmyAppInterviewNotes" class="text-dark">—</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Education</h6>
                        <div class="row g-2">
                            <div class="col-6 col-md-3"><small class="text-muted d-block">Elementary</small><div id="vmyAppElem" class="fw-semibold">—</div></div>
                            <div class="col-6 col-md-3"><small class="text-muted d-block">JHS</small><div id="vmyAppJhs" class="fw-semibold">—</div></div>
                            <div class="col-6 col-md-3"><small class="text-muted d-block">SHS</small><div id="vmyAppShs" class="fw-semibold">—</div></div>
                            <div class="col-6 col-md-3"><small class="text-muted d-block">College</small><div id="vmyAppCollege" class="fw-semibold">—</div></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Skills &amp; Experience</h6>
                        <div class="mb-1"><strong>Skills:</strong> <span id="vmyAppSkills">—</span></div>
                        <div><strong>Experience:</strong> <span id="vmyAppExperience">—</span></div>
                    </div>

                    <!-- Uploaded Documents & Requirements -->
                    <div class="col-12">
                        <h6 class="fw-bold text-primary border-bottom pb-2 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-paperclip me-1"></i>Uploaded Documents &amp; Requirements</span>
                            <span class="badge bg-light text-dark border fw-normal" id="vmyAppDocsCount">0 Attached</span>
                        </h6>
                        <div class="row g-2" id="vmyAppDocumentsList">
                            <div class="col-12 text-muted small py-2">
                                <i class="bi bi-hourglass-split me-1"></i>Loading documents…
                            </div>
                        </div>
                    </div>

                    <!-- Pending Application Document Update Section -->
                    <div class="col-12" id="vmyAppUpdateDocsWrap" style="display:none;">
                        <div class="card border-primary-subtle bg-light-subtle rounded-3 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="bi bi-cloud-arrow-up me-1"></i>Update / Upload Missing Documents
                                </h6>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Pending Review</span>
                            </div>
                            <p class="text-muted small mb-3">
                                You can update or attach missing requirements (Resume, Application Letter, PDS, CSC Eligib, TOR) while your application is still under review.
                            </p>
                            <form id="updateDocsForm" enctype="multipart/form-data">
                                <input type="hidden" id="updateDocsAppId" name="id" value="">
                                <div class="row g-2 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold">Resume / CV (PDF, Word)</label>
                                        <input type="file" class="form-control form-control-sm" id="upResume" name="resume" accept=".pdf,.doc,.docx">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold">Application Letter (PDF, Word)</label>
                                        <input type="file" class="form-control form-control-sm" id="upAppLetter" name="application_letter" accept=".pdf,.doc,.docx">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold">Personal Data Sheet (PDF, Word, Images)</label>
                                        <input type="file" class="form-control form-control-sm" id="upPds" name="pds" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold">CSC Eligibility (PDF, Word, Images)</label>
                                        <input type="file" class="form-control form-control-sm" id="upCsc" name="csc_eligib" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Transcript of Records (PDF, Word, Images)</label>
                                        <input type="file" class="form-control form-control-sm" id="upTor" name="tor" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary" id="saveUpdatedDocsBtn" onclick="submitUpdatedDocs()">
                                        <i class="bi bi-upload me-1"></i>Save &amp; Upload Documents
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">
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

function renderMyAppDocuments(documents, fallbackResume) {
    const docsListEl  = document.getElementById("vmyAppDocumentsList");
    const docsCountEl = document.getElementById("vmyAppDocsCount");
    const docTypesConfig = [
        { key: 'resume',             label: 'Resume / Curriculum Vitae',    icon: 'bi-file-earmark-person' },
        { key: 'application_letter', label: 'Application Letter',           icon: 'bi-envelope-paper' },
        { key: 'pds',                label: 'Personal Data Sheet (PDS)',    icon: 'bi-card-checklist' },
        { key: 'csc_eligib',         label: 'Certificate of CSC Eligib.',  icon: 'bi-award' },
        { key: 'tor',                label: 'Transcript of Records (TOR)',  icon: 'bi-journal-bookmark' },
    ];

    const docsMap = documents || {};
    if (!docsMap.resume && fallbackResume) {
        docsMap.resume = fallbackResume;
    }

    let attachedCount = 0;
    if (docsListEl) {
        let docsHtml = '';
        docTypesConfig.forEach(cfg => {
            const doc = docsMap[cfg.key];
            if (doc) {
                attachedCount++;
                const sizeStr = doc.file_size ? (doc.file_size > 1024 * 1024 ? (doc.file_size / (1024 * 1024)).toFixed(2) + ' MB' : Math.round(doc.file_size / 1024) + ' KB') : '';
                const fileUrl = `../../uploads/resumes/${encodeURIComponent(doc.stored_name)}`;
                docsHtml += `
                <div class="col-12 col-md-6">
                    <div class="border rounded-3 p-2 bg-light d-flex justify-content-between align-items-center h-100">
                        <div class="d-flex align-items-center me-2 text-truncate">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle p-2 me-2 rounded-2">
                                <i class="bi ${cfg.icon} fs-6"></i>
                            </span>
                            <div class="text-truncate">
                                <div class="fw-semibold small text-truncate" title="${cfg.label}">${cfg.label}</div>
                                <div class="text-muted small text-truncate" style="font-size:0.75rem;" title="${doc.original_name}">
                                    ${doc.original_name} ${sizeStr ? `(${sizeStr})` : ''}
                                </div>
                            </div>
                        </div>
                        <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-outline-primary text-nowrap flex-shrink-0" title="Open or Download document">
                            <i class="bi bi-box-arrow-up-right me-1"></i>View
                        </a>
                    </div>
                </div>`;
            } else {
                docsHtml += `
                <div class="col-12 col-md-6">
                    <div class="border border-dashed rounded-3 p-2 bg-white d-flex justify-content-between align-items-center h-100 opacity-75">
                        <div class="d-flex align-items-center me-2 text-truncate">
                            <span class="badge bg-secondary-subtle text-muted border border-secondary-subtle p-2 me-2 rounded-2">
                                <i class="bi ${cfg.icon} fs-6"></i>
                            </span>
                            <div class="text-truncate">
                                <div class="text-muted small text-truncate">${cfg.label}</div>
                                <div class="text-muted fst-italic small" style="font-size:0.72rem;">Not submitted</div>
                            </div>
                        </div>
                        <span class="badge bg-light text-muted border small flex-shrink-0">None</span>
                    </div>
                </div>`;
            }
        });
        docsListEl.innerHTML = docsHtml;
    }
    if (docsCountEl) {
        docsCountEl.textContent = `${attachedCount} Attached`;
        docsCountEl.className = attachedCount > 0 ? "badge bg-primary-subtle text-primary border border-primary-subtle fw-medium" : "badge bg-light text-muted border fw-normal";
    }
}

function viewMyApp(appId) {
    const app = myAppsData.find(a => a.id == appId);
    if (!app) return;
    const setT = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v || "—"; };
    setT("vmyAppJobTitle",      app.job_title);
    setT("vmyAppCompany",       app.company);
    setT("vmyAppContactPerson", app.contact_person);
    setT("vmyAppContactPhone",  app.contact_phone);
    setT("vmyAppDate",          app.date_applied);
    setT("vmyAppName",          app.full_name);
    setT("vmyAppEmail",         app.email);
    setT("vmyAppContact",       app.contact);
    setT("vmyAppAddress",       app.address);
    setT("vmyAppBirthdate",     app.birthdate);
    setT("vmyAppAge",           app.age);
    setT("vmyAppElem",          app.elementary);
    setT("vmyAppJhs",           app.jhs);
    setT("vmyAppShs",           app.shs);
    setT("vmyAppCollege",       app.college);
    setT("vmyAppSkills",        app.skills);
    setT("vmyAppExperience",    app.experience);

    const statusEl = document.getElementById("vmyAppStatus");
    if (statusEl) {
        const cls = app.status === "Approved" ? "bg-success" : app.status === "Rejected" ? "bg-danger" : "bg-warning text-dark";
        statusEl.innerHTML = "<span class=\"badge " + cls + "\">" + app.status + "</span>";
    }

    // ── Interview Schedule ──────────────────────────────
    const interviewRow = document.getElementById("vmyAppInterviewRow");
    const interviewDateEl = document.getElementById("vmyAppInterviewDate");
    const interviewNotesWrap = document.getElementById("vmyAppInterviewNotesWrap");
    const interviewNotesEl = document.getElementById("vmyAppInterviewNotes");

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

    // Toggle update documents section
    const upDocsWrap = document.getElementById("vmyAppUpdateDocsWrap");
    const upDocsAppId = document.getElementById("updateDocsAppId");
    if (upDocsAppId) upDocsAppId.value = appId;
    if (upDocsWrap) {
        upDocsWrap.style.display = app.status === "Pending" ? "" : "none";
        document.getElementById("updateDocsForm")?.reset();
    }

    // Load documents via AJAX
    const docsListEl = document.getElementById("vmyAppDocumentsList");
    if (docsListEl) docsListEl.innerHTML = '<div class="col-12 text-muted small py-2"><i class="bi bi-hourglass-split me-1"></i>Loading documents…</div>';

    fetch(APP_HANDLER_MY, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "getDetails", id: appId, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            if (docsListEl) docsListEl.innerHTML = '<div class="col-12 text-danger small">Failed to load documents.</div>';
            return;
        }
        renderMyAppDocuments(res.documents, res.resume);
    })
    .catch(() => {
        if (docsListEl) docsListEl.innerHTML = '<div class="col-12 text-danger small">Failed to load documents.</div>';
    });
}

function submitUpdatedDocs() {
    const appId = document.getElementById("updateDocsAppId")?.value;
    if (!appId) return;

    const resumeFile = document.getElementById("upResume")?.files?.[0];
    const letterFile = document.getElementById("upAppLetter")?.files?.[0];
    const pdsFile    = document.getElementById("upPds")?.files?.[0];
    const cscFile    = document.getElementById("upCsc")?.files?.[0];
    const torFile    = document.getElementById("upTor")?.files?.[0];

    if (!resumeFile && !letterFile && !pdsFile && !cscFile && !torFile) {
        showToast("Please select at least one document to upload or update.", "warning");
        return;
    }

    const fd = new FormData();
    fd.append("action", "updateDocuments");
    fd.append("csrf_token", getCsrfToken());
    fd.append("id", appId);

    if (resumeFile) fd.append("resume", resumeFile);
    if (letterFile) fd.append("application_letter", letterFile);
    if (pdsFile)    fd.append("pds", pdsFile);
    if (cscFile)    fd.append("csc_eligib", cscFile);
    if (torFile)    fd.append("tor", torFile);

    const btn = document.getElementById("saveUpdatedDocsBtn");
    btnLoading(btn, true, "Uploading…");

    fetch(APP_HANDLER_MY, { method: "POST", body: fd })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) {
            document.getElementById("updateDocsForm")?.reset();
            viewMyApp(appId);
        }
    })
    .catch(() => showToast("Upload failed. Please try again.", "danger"))
    .finally(() => btnLoading(btn, false));
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>