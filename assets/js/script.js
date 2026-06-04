// OJAMS - Main JavaScript (DB-backed build)
// Pure UI helpers only — no localStorage CRUD

// ── Toast Notification ─────────────────────────────────────
function showToast(msg, type = "success") {
  const iconMap = {
    success: "check-circle-fill",
    danger: "x-circle-fill",
    warning: "exclamation-triangle-fill",
    info: "info-circle-fill",
  };
  const icon = iconMap[type] || "info-circle-fill";
  let container = document.getElementById("toastContainer");
  if (!container) {
    container = document.createElement("div");
    container.id = "toastContainer";
    document.body.appendChild(container);
  }
  const div = document.createElement("div");
  div.className = `ojams-toast toast-${type}`;
  div.innerHTML = `<i class="bi bi-${icon}"></i><span>${msg}</span>`;
  container.appendChild(div);
  setTimeout(() => {
    div.style.animation = "fadeOutRight 0.25s ease forwards";
    setTimeout(() => div.remove(), 250);
  }, 3500);
}

// ── Status Badge CSS Class ──────────────────────────────────
function statusBadgeClass(status) {
  switch (status) {
    case "Approved": return "badge-status-approved";
    case "Rejected":  return "badge-status-rejected";
    case "Pending":   return "badge-status-pending";
    case "Open":      return "badge-status-open";
    case "Closed":    return "badge-status-closed";
    case "New":       return "bg-info";
    case "Created":   return "bg-primary";
    case "Updated":   return "bg-secondary";
    case "Deleted":   return "bg-danger";
    case "Cancelled": return "badge-status-closed";
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

// ── CSRF Token ──────────────────────────────────────────────
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute("content") : "";
}

// ── Misc ────────────────────────────────────────────────────
function comingSoon() {
  alert("Feature Coming Soon");
}

