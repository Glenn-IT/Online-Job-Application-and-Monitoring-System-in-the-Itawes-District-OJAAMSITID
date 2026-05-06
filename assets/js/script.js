// OJAMS - Main JavaScript (DB-backed build)
// Pure UI helpers only — no localStorage CRUD

// ── Toast Notification ─────────────────────────────────────
function showToast(msg, type = "success") {
    const iconMap = { success: "check-circle", danger: "x-circle", warning: "exclamation-circle", info: "info-circle" };
    const icon = iconMap[type] || "info-circle";
    let container = document.getElementById("toast-container");
    if (!container) {
        container = document.createElement("div");
        container.id = "toast-container";
        container.style.cssText = "position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;";
        document.body.appendChild(container);
    }
    const div = document.createElement("div");
    const colors = { success: "#198754", danger: "#dc3545", warning: "#856404", info: "#0d6efd" };
    div.style.cssText = "padding:10px 18px;border-radius:8px;color:#fff;font-size:.9rem;box-shadow:0 4px 15px rgba(0,0,0,.2);display:flex;align-items:center;gap:8px;min-width:220px;";
    div.style.background = colors[type] || "#198754";
    div.innerHTML = `<i class="bi bi-${icon}"></i>${msg}`;
    container.appendChild(div);
    setTimeout(() => div.remove(), 3500);
}

// ── Status Badge CSS Class ──────────────────────────────────
function statusBadgeClass(status) {
    switch (status) {
        case "Approved":  return "bg-success";
        case "Rejected":  return "bg-danger";
        case "Pending":   return "bg-warning text-dark";
        case "Open":      return "bg-success";
        case "Closed":    return "bg-secondary";
        case "New":       return "bg-info text-dark";
        case "Created":   return "bg-primary";
        case "Updated":   return "bg-warning text-dark";
        case "Deleted":   return "bg-danger";
        case "Cancelled": return "bg-secondary";
        default:          return "bg-secondary";
    }
}

// ── Helper: set element text ────────────────────────────────
function _setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val || "—";
}

// ── Helper: set input value ─────────────────────────────────
function _setVal(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val || "";
}

// ── Apply Job Modal setup ───────────────────────────────────
let _currentApplyJobId = null;

function setApplyJob(jobId, title, company) {
    _currentApplyJobId = jobId;
    const titleEl   = document.getElementById("applyJobTitle");
    const companyEl = document.getElementById("applyJobCompany");
    if (titleEl)   titleEl.textContent   = title;
    if (companyEl) companyEl.textContent = company;
}

// Stub — overridden by DB-backed pages
function submitApplication() {
    showToast("Submission not configured for this page.", "warning");
}

// ── Profile toggle stub — overridden by profile pages ──────
function toggleEditProfile() {}
function saveProfile() {}

// ── Job CRUD stubs — overridden by manage-jobs.php ─────────
let _editingJobId = null;

function openAddJobModal() {
    _editingJobId = null;
    document.getElementById("addJobModalLabel").innerHTML =
        '<i class="bi bi-plus-circle me-2"></i>Add New Job Post';
    document.getElementById("addJobForm")?.reset();
    const dateField = document.getElementById("jobDatePosted");
    if (dateField) dateField.value = new Date().toISOString().split("T")[0];
    new bootstrap.Modal(document.getElementById("addJobModal")).show();
}

function editJob(title, company, description, qualification, datePosted, status, jobId) {
    _setVal("jobTitle", title);
    _setVal("jobCompany", company);
    _setVal("jobDescription", description);
    _setVal("jobQualification", qualification);
    _setVal("jobDatePosted", datePosted);
    const statusEl = document.getElementById("jobStatus");
    if (statusEl) statusEl.value = status;
    document.getElementById("addJobModalLabel").innerHTML =
        '<i class="bi bi-pencil-square me-2"></i>Edit Job Post';
    new bootstrap.Modal(document.getElementById("addJobModal")).show();
}

function saveJob() { showToast("saveJob not configured.", "warning"); }
function deleteJob(id) { showToast("deleteJob not configured.", "warning"); }

// ── Application cancel stub — overridden by my-applications ─
let _cancelAppId   = null;
let _cancelJobTitle = null;

function confirmCancel(jobTitle, appId) {
    _cancelAppId    = Number(appId);
    _cancelJobTitle = jobTitle;
    _setText("cancelAppJobTitle", jobTitle);
}

function cancelApplication() {
    showToast("cancelApplication not configured.", "warning");
}

// ── Browse jobs search filter ───────────────────────────────
function filterJobs() {
    const q = (document.getElementById("jobSearch")?.value || "").toLowerCase();
    const status = (document.getElementById("statusFilter")?.value || "").toLowerCase();
    document.querySelectorAll("#jobCardsContainer .col-md-6, #jobCardsContainer .col-md-4")
        .forEach(card => {
            const matchText   = !q || card.textContent.toLowerCase().includes(q);
            const matchStatus = !status || card.textContent.toLowerCase().includes(status);
            card.style.display = (matchText && matchStatus) ? "" : "none";
        });
}

// ── Misc ────────────────────────────────────────────────────
function comingSoon() { alert("Feature Coming Soon"); }

document.addEventListener("DOMContentLoaded", function () {
    console.log("OJAMS – DB-backed UI loaded");
});