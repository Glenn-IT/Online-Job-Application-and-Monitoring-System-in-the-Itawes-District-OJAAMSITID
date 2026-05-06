// OJAMS - Main JavaScript (DB-backed build)
// Pure UI helpers only — no localStorage CRUD

// ── Toast Notification ─────────────────────────────────────
function showToast(msg, type = "success") {
  const iconMap = {
    success: "check-circle",
    danger: "x-circle",
    warning: "exclamation-circle",
    info: "info-circle",
  };
  const icon = iconMap[type] || "info-circle";
  let container = document.getElementById("toast-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "toast-container";
    container.style.cssText =
      "position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;";
    document.body.appendChild(container);
  }
  const div = document.createElement("div");
  const colors = {
    success: "#198754",
    danger: "#dc3545",
    warning: "#856404",
    info: "#0d6efd",
  };
  div.style.cssText =
    "padding:10px 18px;border-radius:8px;color:#fff;font-size:.9rem;box-shadow:0 4px 15px rgba(0,0,0,.2);display:flex;align-items:center;gap:8px;min-width:220px;";
  div.style.background = colors[type] || "#198754";
  div.innerHTML = `<i class="bi bi-${icon}"></i>${msg}`;
  container.appendChild(div);
  setTimeout(() => div.remove(), 3500);
}

// ── Status Badge CSS Class ──────────────────────────────────
function statusBadgeClass(status) {
  switch (status) {
    case "Approved":
      return "bg-success";
    case "Rejected":
      return "bg-danger";
    case "Pending":
      return "bg-warning text-dark";
    case "Open":
      return "bg-success";
    case "Closed":
      return "bg-secondary";
    case "New":
      return "bg-info text-dark";
    case "Created":
      return "bg-primary";
    case "Updated":
      return "bg-warning text-dark";
    case "Deleted":
      return "bg-danger";
    case "Cancelled":
      return "bg-secondary";
    default:
      return "bg-secondary";
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
  const titleEl = document.getElementById("applyJobTitle");
  const companyEl = document.getElementById("applyJobCompany");
  if (titleEl) titleEl.textContent = title;
  if (companyEl) companyEl.textContent = company;
}

// submitApplication is defined per-page (browse-jobs.php, etc.)

// ── Profile — defined per-page (profile-settings.php) ──────
// toggleEditProfile and saveProfile are defined per-page.

// ── Job CRUD — defined per-page (manage-jobs.php) ──────────
// openAddJobModal, editJob, saveJob, deleteJob are defined per-page.
let _editingJobId = null;

// confirmCancel, cancelApplication, filterJobs, submitApplication
// are defined per-page.

// ── Misc ────────────────────────────────────────────────────
function comingSoon() {
  alert("Feature Coming Soon");
}

document.addEventListener("DOMContentLoaded", function () {
  console.log("OJAMS – DB-backed UI loaded");
});
