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

// ── Field-level Error Helpers ────────────────────────────────
// showFieldError(id, msg) — marks the field red + shows message below it.
// clearFieldError(id)     — removes the error state from one field.
// clearAllFieldErrors(containerId) — wipes all errors inside a container.
function showFieldError(fieldId, message) {
  const el = document.getElementById(fieldId);
  if (!el) return;
  el.classList.add("is-invalid");
  const wrapper = el.closest(".input-group") || el;
  const existing = wrapper.nextElementSibling;
  if (existing && existing.classList.contains("invalid-feedback")) existing.remove();
  const fb = document.createElement("div");
  fb.className = "invalid-feedback d-block";
  fb.textContent = message;
  wrapper.insertAdjacentElement("afterend", fb);
  el.addEventListener("input", () => clearFieldError(fieldId), { once: true });
}

function clearFieldError(fieldId) {
  const el = document.getElementById(fieldId);
  if (!el) return;
  el.classList.remove("is-invalid");
  const wrapper = el.closest(".input-group") || el;
  const next = wrapper.nextElementSibling;
  if (next && next.classList.contains("invalid-feedback")) next.remove();
}

function clearAllFieldErrors(containerId) {
  const root = containerId ? document.getElementById(containerId) : document;
  if (!root) return;
  root.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
  root.querySelectorAll(".invalid-feedback.d-block").forEach(el => el.remove());
}

// ── Button Loading State ─────────────────────────────────────
// btnLoading(btn, true)  → disables button, replaces text with spinner
// btnLoading(btn, false) → restores original text, re-enables button
function btnLoading(btn, loading, loadingText = "Please wait…") {
  if (!btn) return;
  if (loading) {
    btn.dataset.originalHtml = btn.innerHTML;
    btn.innerHTML =
      `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
    btn.disabled = true;
  } else {
    if (btn.dataset.originalHtml !== undefined) {
      btn.innerHTML = btn.dataset.originalHtml;
    }
    btn.disabled = false;
  }
}

// ── Confirmation Modal Utility ─────────────────────────────
// Replaces native browser confirm() dialogs ("localhost says") with custom Bootstrap modals
function showConfirmModal({
  title = "Confirm Action",
  message = "Are you sure you want to proceed?",
  confirmBtnText = "Confirm",
  confirmBtnClass = "btn-primary",
  icon = "bi-question-circle",
  onConfirm = null
}) {
  let modalEl = document.getElementById("ojamsConfirmModal");
  if (!modalEl) {
    modalEl = document.createElement("div");
    modalEl.id = "ojamsConfirmModal";
    modalEl.className = "modal fade";
    modalEl.tabIndex = -1;
    modalEl.setAttribute("aria-hidden", "true");
    modalEl.innerHTML = `
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header" id="ojamsConfirmHeader">
            <h5 class="modal-title" id="ojamsConfirmTitle">
              <i class="bi me-2" id="ojamsConfirmIcon"></i>
              <span id="ojamsConfirmTitleText"></span>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center py-4">
            <i class="bi display-4 d-block mb-3" id="ojamsConfirmBodyIcon"></i>
            <p class="mb-0 fs-6 fw-medium" id="ojamsConfirmMessage"></p>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
              <i class="bi bi-x-lg me-1"></i>Cancel
            </button>
            <button type="button" class="btn px-4" id="ojamsConfirmBtn">
              <span id="ojamsConfirmBtnText">Confirm</span>
            </button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(modalEl);
  }

  const header = document.getElementById("ojamsConfirmHeader");
  const titleText = document.getElementById("ojamsConfirmTitleText");
  const iconEl = document.getElementById("ojamsConfirmIcon");
  const bodyIcon = document.getElementById("ojamsConfirmBodyIcon");
  const messageEl = document.getElementById("ojamsConfirmMessage");
  const confirmBtn = document.getElementById("ojamsConfirmBtn");
  const confirmBtnTextEl = document.getElementById("ojamsConfirmBtnText");

  let headerClass = "bg-primary text-white";
  let bodyIconClass = "bi-question-circle-fill text-primary";
  if (confirmBtnClass.includes("btn-danger")) {
    headerClass = "bg-danger text-white";
    bodyIconClass = "bi-exclamation-triangle-fill text-danger";
  } else if (confirmBtnClass.includes("btn-success")) {
    headerClass = "bg-success text-white";
    bodyIconClass = "bi-check-circle-fill text-success";
  } else if (confirmBtnClass.includes("btn-warning")) {
    headerClass = "bg-warning text-dark";
    bodyIconClass = "bi-exclamation-circle-fill text-warning";
  }

  header.className = `modal-header ${headerClass}`;
  titleText.textContent = title;
  iconEl.className = `bi ${icon} me-2`;
  bodyIcon.className = `bi ${icon} display-4 d-block mb-3 ${bodyIconClass.split(" ")[1] || ""}`;
  messageEl.textContent = message;

  confirmBtn.className = `btn ${confirmBtnClass} px-4`;
  confirmBtnTextEl.textContent = confirmBtnText;

  const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

  const newConfirmBtn = confirmBtn.cloneNode(true);
  confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

  newConfirmBtn.addEventListener("click", function () {
    bsModal.hide();
    if (typeof onConfirm === "function") {
      onConfirm();
    }
  });

  bsModal.show();
}

// ── Global Processing / Loading Modal ────────────────────────
// ── Global Processing / Loading Modal ────────────────────────
let _ojamsLoadingModalInstance = null;

function showLoadingModal(text = "Please wait…") {
  let modalEl = document.getElementById("ojamsLoadingModal");
  if (!modalEl) {
    modalEl = document.createElement("div");
    modalEl.id = "ojamsLoadingModal";
    modalEl.className = "modal fade";
    modalEl.tabIndex = -1;
    modalEl.setAttribute("data-bs-backdrop", "static");
    modalEl.setAttribute("data-bs-keyboard", "false");
    modalEl.setAttribute("aria-hidden", "true");
    modalEl.innerHTML = `
      <div class="modal-dialog modal-dialog-centered" style="max-width: 280px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
          <div class="modal-body text-center p-4">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem; border-width: 0.25rem;" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <h6 class="fw-semibold text-dark mb-0" id="ojamsLoadingText">Please wait…</h6>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(modalEl);
  }

  const textEl = document.getElementById("ojamsLoadingText");
  if (textEl) {
    textEl.textContent = typeof text === "string" ? text : (text?.text || "Please wait…");
  }

  _ojamsLoadingModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
    backdrop: "static",
    keyboard: false
  });
  _ojamsLoadingModalInstance.show();
}

function hideLoadingModal() {
  const modalEl = document.getElementById("ojamsLoadingModal");
  if (modalEl) {
    const bsModal = bootstrap.Modal.getInstance(modalEl) || _ojamsLoadingModalInstance;
    if (bsModal) {
      bsModal.hide();
    }
  }
}



