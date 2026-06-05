<?php
require_once __DIR__ . "/../../config/auth.php";
requireUser();
$pageTitle   = "OJAMS - Profile Settings";
$basePath    = "../../";
$currentPage = "profile-settings";

$u = $_SESSION["ojams_user"];

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-user.php";
?>
<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1"><i class="bi bi-person-gear me-2 text-primary"></i>Profile Settings</h2>
        <p class="text-muted mb-0">View and manage your personal information.</p>
    </div>
    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body p-4">
                    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:100px;height:100px;">
                        <i class="bi bi-person-fill text-white display-5"></i>
                    </div>
                    <h5 class="fw-bold mb-1" id="cardName"><?php echo htmlspecialchars($u["full_name"]); ?></h5>
                    <p class="text-muted mb-2" id="cardEmail"><?php echo htmlspecialchars($u["email"]); ?></p>
                    <span class="badge bg-primary">Job Seeker</span>
                </div>
            </div>
        </div>
        <!-- Profile Details -->
        <div class="col-md-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Personal Information</h5>
                    <button class="btn btn-sm btn-outline-primary" id="editProfileBtn" onclick="toggleEditProfile()">
                        <i class="bi bi-pencil me-1"></i>Edit Profile
                    </button>
                </div>
                <div class="card-body">
                    <!-- View Mode -->
                    <div id="profileView">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th class="text-muted" style="width:35%;"><i class="bi bi-person me-2"></i>Full Name</th>
                                <td id="pvFullName"><?php echo htmlspecialchars($u["full_name"]); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted"><i class="bi bi-envelope me-2"></i>Email</th>
                                <td id="pvEmail"><?php echo htmlspecialchars($u["email"]); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted"><i class="bi bi-phone me-2"></i>Contact</th>
                                <td id="pvContact"><?php echo htmlspecialchars($u["contact_number"] ?? "—"); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted"><i class="bi bi-geo-alt me-2"></i>Address</th>
                                <td id="pvAddress"><?php echo htmlspecialchars($u["address"] ?? "—"); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted"><i class="bi bi-calendar me-2"></i>Birthdate</th>
                                <td id="pvBirthdate"><?php echo htmlspecialchars($u["birthdate"] ?? "—"); ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted"><i class="bi bi-lock me-2"></i>Password</th>
                                <td>••••••••••</td>
                            </tr>
                        </table>
                    </div>
                    <!-- Edit Mode -->
                    <div id="profileEdit" style="display:none;">
                        <form id="editProfileForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editFullName"
                                           value="<?php echo htmlspecialchars($u['full_name']); ?>" maxlength="150">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="editEmail"
                                           value="<?php echo htmlspecialchars($u['email']); ?>" maxlength="150">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="editContact"
                                           value="<?php echo htmlspecialchars($u['contact_number'] ?? ''); ?>" maxlength="20">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Birthdate</label>
                                    <input type="date" class="form-control" id="editBirthdate"
                                           value="<?php echo htmlspecialchars($u['birthdate'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" id="editAddress"
                                       value="<?php echo htmlspecialchars($u['address'] ?? ''); ?>" maxlength="500">
                            </div>
                            <hr>
                            <p class="text-muted small mb-3">Leave blank to keep your current password.</p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="editCurrentPassword"
                                           placeholder="Enter current password">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="editNewPassword"
                                           placeholder="New password (min 6 chars)">
                                </div>
                            </div>

                            <!-- Lockdown notice -->
                            <div id="pwLockNoticeUser" class="d-none alert alert-warning d-flex align-items-center gap-2 mb-3 py-2">
                                <i class="bi bi-lock-fill fs-5 flex-shrink-0"></i>
                                <div class="small">
                                    Too many failed attempts. Fields re-enable in
                                    <strong id="pwCountdownUser">30</strong>s.
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="saveProfileBtn" onclick="saveProfile()">
                                    <i class="bi bi-save me-1"></i>Save Changes
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="toggleEditProfile()">
                                    <i class="bi bi-x-lg me-1"></i>Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
const PROFILE_HANDLER = "../../handlers/profile.php";

let _pwLockTimer = null;
function startPwLockdown(seconds, fieldIds, btnId, noticeId, countdownId) {
    const notice    = document.getElementById(noticeId);
    const countdown = document.getElementById(countdownId);
    const btn       = document.getElementById(btnId);
    fieldIds.forEach(id => { const el = document.getElementById(id); if (el) { el.disabled = true; el.value = ""; } });
    if (btn) btn.disabled = true;
    notice?.classList.remove("d-none");
    if (countdown) countdown.textContent = seconds;
    let remaining = seconds;
    clearInterval(_pwLockTimer);
    _pwLockTimer = setInterval(() => {
        remaining--;
        if (countdown) countdown.textContent = remaining;
        if (remaining <= 0) {
            clearInterval(_pwLockTimer);
            fieldIds.forEach(id => { const el = document.getElementById(id); if (el) el.disabled = false; });
            if (btn) btn.disabled = false;
            notice?.classList.add("d-none");
        }
    }, 1000);
}

function toggleEditProfile() {
    const viewEl = document.getElementById("profileView");
    const editEl = document.getElementById("profileEdit");
    const btn    = document.getElementById("editProfileBtn");
    const isEditing = editEl.style.display !== "none" && editEl.style.display !== "";
    if (isEditing) {
        editEl.style.display = "none";
        viewEl.style.display = "block";
        btn.innerHTML = "<i class=\"bi bi-pencil me-1\"></i>Edit Profile";
        btn.classList.replace("btn-secondary", "btn-outline-primary");
    } else {
        editEl.style.display = "block";
        viewEl.style.display = "none";
        btn.innerHTML = "<i class=\"bi bi-x me-1\"></i>Cancel";
        btn.classList.replace("btn-outline-primary", "btn-secondary");
    }
}

function saveProfile() {
    const fullName  = document.getElementById("editFullName")?.value.trim();
    const email     = document.getElementById("editEmail")?.value.trim();
    const contact   = document.getElementById("editContact")?.value.trim();
    const address   = document.getElementById("editAddress")?.value.trim();
    const birthdate = document.getElementById("editBirthdate")?.value;
    const currentPw = document.getElementById("editCurrentPassword")?.value;
    const newPw     = document.getElementById("editNewPassword")?.value;

    clearAllFieldErrors("editProfileForm");
    let editValid = true;
    if (!fullName) { showFieldError("editFullName", "Full name is required.");     editValid = false; }
    if (!email)    { showFieldError("editEmail",    "Email address is required."); editValid = false; }
    if (currentPw && newPw && newPw.length < 6) {
        showFieldError("editNewPassword", "New password must be at least 6 characters.");
        editValid = false;
    }
    if (!editValid) return;

    const doPasswordChange = currentPw && newPw;

    const saveBtn = document.getElementById("saveProfileBtn");
    btnLoading(saveBtn, true, "Saving…");

    const updateInfo = () => fetch(PROFILE_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "updateInfo", full_name: fullName, email, contact_number: contact, address, birthdate, csrf_token: getCsrfToken() })
    }).then(r => r.json());

    const changePass = () => fetch(PROFILE_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "changePassword", current_password: currentPw, new_password: newPw, confirm_password: newPw, csrf_token: getCsrfToken() })
    }).then(r => r.json());

    updateInfo().then(res => {
        if (!res.success) { showToast(res.message, "danger"); return; }
        document.getElementById("pvFullName").textContent  = fullName;
        document.getElementById("pvEmail").textContent     = email;
        document.getElementById("pvContact").textContent   = contact || "—";
        document.getElementById("pvAddress").textContent   = address || "—";
        document.getElementById("pvBirthdate").textContent = birthdate || "—";
        document.getElementById("cardName").textContent    = fullName;
        document.getElementById("cardEmail").textContent   = email;
        if (doPasswordChange) {
            changePass().then(r2 => {
                if (r2.locked) {
                    startPwLockdown(
                        r2.retry_after,
                        ["editCurrentPassword", "editNewPassword"],
                        "saveProfileBtn",
                        "pwLockNoticeUser",
                        "pwCountdownUser"
                    );
                    showToast(r2.message, "warning");
                    return;
                }
                showToast(r2.success ? "Profile & password updated!" : r2.message, r2.success ? "success" : "danger");
                if (r2.success) {
                    document.getElementById("editCurrentPassword").value = "";
                    document.getElementById("editNewPassword").value     = "";
                }
            });
        } else {
            showToast("Profile updated successfully!", "success");
        }
        toggleEditProfile();
    })
    .catch(() => showToast("Request failed.", "danger"))
    .finally(() => btnLoading(saveBtn, false));
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>