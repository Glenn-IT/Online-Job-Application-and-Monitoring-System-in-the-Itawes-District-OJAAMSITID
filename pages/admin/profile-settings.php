<?php
require_once __DIR__ . "/../../config/auth.php";
requireAdmin();
$pageTitle   = "OJAMS - Profile Settings";
$basePath    = "../../";
$currentPage = "profile-settings";

$u = $_SESSION["ojams_user"];

// Current security question (read from DB — older sessions won't have it)
$sqStmt = $pdo->prepare("SELECT security_question FROM users WHERE id = ?");
$sqStmt->execute([$u["id"]]);
$currentSecQuestion = $sqStmt->fetchColumn() ?: null;

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-admin.php";
?>
<div class="admin-layout">
    <?php include $basePath . "layouts/sidebar-admin.php"; ?>
    <main class="admin-main">
        <div class="mb-4">
            <h2 class="fw-bold mb-1"><i class="bi bi-person-gear me-2 text-primary"></i>Profile Settings</h2>
            <p class="text-muted mb-0">Manage your administrator account information.</p>
        </div>
        <div class="row">
            <!-- Profile Card -->
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <?php $photo = $u['profile_photo'] ?? null; ?>
                        <?php if ($photo): ?>
                            <img src="<?php echo htmlspecialchars(BASE_URL . '/uploads/avatars/' . $photo); ?>"
                                 id="avatarImg" alt="Avatar"
                                 class="rounded-circle mb-3 border"
                                 style="width:100px;height:100px;object-fit:cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
                                 id="avatarPlaceholder" style="width:100px;height:100px;">
                                <i class="bi bi-person-fill text-white display-5"></i>
                            </div>
                        <?php endif; ?>
                        <h5 class="fw-bold mb-1" id="cardName"><?php echo htmlspecialchars($u["full_name"]); ?></h5>
                        <p class="text-muted mb-2" id="cardEmail"><?php echo htmlspecialchars($u["email"]); ?></p>
                        <span class="badge bg-danger">Administrator</span>
                        <div class="mt-3">
                            <label for="avatarUpload" class="btn btn-sm btn-outline-secondary w-100">
                                <i class="bi bi-camera me-1"></i>Change Photo
                            </label>
                            <input type="file" id="avatarUpload" accept="image/jpeg,image/png,image/gif,image/webp"
                                   class="d-none" onchange="uploadAvatar(this)">
                            <div class="form-text">JPG, PNG, GIF, WebP — max 2 MB</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Personal Info Form -->
            <div class="col-md-8 mb-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="adminFullName"
                                       value="<?php echo htmlspecialchars($u['full_name']); ?>" maxlength="150">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="adminEmail"
                                       value="<?php echo htmlspecialchars($u['email']); ?>" maxlength="150">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="adminContact"
                                       inputmode="numeric" maxlength="11" pattern="\d{11}"
                                       oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)"
                                       placeholder="11 digits, e.g. 09171234567"
                                       value="<?php echo htmlspecialchars($u['contact_number'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Birthdate</label>
                                <input type="date" class="form-control" id="adminBirthdate"
                                       value="<?php echo htmlspecialchars($u['birthdate'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" id="adminAddress"
                                   value="<?php echo htmlspecialchars($u['address'] ?? ''); ?>" maxlength="500">
                        </div>
                        <button type="button" class="btn btn-primary" id="saveInfoBtn" onclick="savePersonalInfo()">
                            <i class="bi bi-save me-1"></i>Save Information
                        </button>
                    </div>
                </div>
                <!-- Change Password Form -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword"
                                   placeholder="Enter current password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPassword"
                                       placeholder="Enter new password" oninput="checkPasswordStrength()">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePwVisibility('newPassword',this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="progress mt-2" style="height:5px;">
                                <div class="progress-bar" id="pwStrengthBar" role="progressbar" style="width:0%"></div>
                            </div>
                            <small class="text-muted" id="pwStrengthText"></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword"
                                   placeholder="Confirm new password">
                        </div>

                        <!-- Lockdown notice -->
                        <div id="pwLockNotice" class="d-none alert alert-warning d-flex align-items-center gap-2 mb-3 py-2">
                            <i class="bi bi-lock-fill fs-5 flex-shrink-0"></i>
                            <div class="small">
                                Too many failed attempts. Fields re-enable in
                                <strong id="pwCountdown">30</strong>s.
                            </div>
                        </div>

                        <button type="button" class="btn btn-warning" id="changePwBtn" onclick="changeAdminPassword()">
                            <i class="bi bi-key me-1"></i>Update Password
                        </button>
                    </div>
                </div>
                <!-- Security Question Form -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-patch-question me-2 text-primary"></i>Security Question</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Used to verify your identity on the Forgot Password page.
                            Current question:
                            <strong id="currentSecQuestion"><?php echo $currentSecQuestion
                                ? htmlspecialchars($currentSecQuestion)
                                : 'Not set'; ?></strong>
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Security Question</label>
                            <select class="form-select" id="secQuestion">
                                <option value="" disabled selected>Choose a question…</option>
                                <?php foreach (SECURITY_QUESTIONS as $q): ?>
                                <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Answer</label>
                            <input type="text" class="form-control" id="secAnswer"
                                   placeholder="Your answer (not case-sensitive)" maxlength="150" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="secCurrentPassword"
                                   placeholder="Confirm with your current password">
                        </div>
                        <button type="button" class="btn btn-primary" id="saveSecQBtn" onclick="saveSecurityQuestion()">
                            <i class="bi bi-shield-check me-1"></i>Save Security Question
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
const PROFILE_HANDLER = "../../handlers/profile.php";

// Security: passwords cannot be copied out of, or pasted into, these fields.
["currentPassword", "newPassword", "confirmPassword"].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    ["copy", "cut", "paste", "drop"].forEach(evt =>
        el.addEventListener(evt, e => e.preventDefault())
    );
});

function uploadAvatar(input) {
    if (!input.files.length) return;
    const fd = new FormData();
    fd.append("action",     "uploadAvatar");
    fd.append("csrf_token", getCsrfToken());
    fd.append("avatar",     input.files[0]);
    fetch(PROFILE_HANDLER, { method: "POST", body: fd })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) {
            const placeholder = document.getElementById("avatarPlaceholder");
            let img = document.getElementById("avatarImg");
            if (placeholder) {
                placeholder.outerHTML = `<img src="${res.url}" id="avatarImg" alt="Avatar" class="rounded-circle mb-3 border" style="width:100px;height:100px;object-fit:cover;">`;
            } else if (img) {
                img.src = res.url;
            }
        }
    })
    .catch(() => showToast("Upload failed.", "danger"));
    input.value = "";
}

function savePersonalInfo() {
    const fullName  = document.getElementById("adminFullName")?.value.trim();
    const email     = document.getElementById("adminEmail")?.value.trim();
    const contact   = document.getElementById("adminContact")?.value.trim();
    const address   = document.getElementById("adminAddress")?.value.trim();
    const birthdate = document.getElementById("adminBirthdate")?.value;
    let infoValid = true;
    if (!fullName) { showFieldError("adminFullName", "Full name is required.");    infoValid = false; }
    if (!email)    { showFieldError("adminEmail",    "Email address is required."); infoValid = false; }
    if (contact && !/^\d{11}$/.test(contact)) {
        showFieldError("adminContact", "Contact number must be exactly 11 digits (numbers only)."); infoValid = false;
    }
    if (!infoValid) {
        showToast("Please fill in all required personal info fields correctly.", "warning");
        return;
    }
    const btn = document.getElementById("saveInfoBtn");
    btnLoading(btn, true, "Saving…");
    fetch(PROFILE_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "updateInfo", full_name: fullName, email, contact_number: contact, address, birthdate, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) {
            document.getElementById("cardName").textContent  = fullName;
            document.getElementById("cardEmail").textContent = email;
        }
    })
    .catch(() => showToast("Request failed.", "danger"))
    .finally(() => btnLoading(btn, false));
}

function saveSecurityQuestion() {
    const question  = document.getElementById("secQuestion")?.value;
    const answer    = document.getElementById("secAnswer")?.value.trim();
    const currentPw = document.getElementById("secCurrentPassword")?.value;
    let sqValid = true;
    if (!question)  { showFieldError("secQuestion",        "Choose a security question.");        sqValid = false; }
    if (!answer)    { showFieldError("secAnswer",          "Answer is required.");                sqValid = false; }
    else if (answer.length < 2) { showFieldError("secAnswer", "Answer must be at least 2 characters."); sqValid = false; }
    if (!currentPw) { showFieldError("secCurrentPassword", "Current password is required.");      sqValid = false; }
    if (!sqValid) {
        showToast("Please fill in all required security question fields.", "warning");
        return;
    }

    const btn = document.getElementById("saveSecQBtn");
    btnLoading(btn, true, "Saving…");
    fetch(PROFILE_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "updateSecurityQuestion", question, answer, current_password: currentPw, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) {
            document.getElementById("currentSecQuestion").textContent = res.question;
            document.getElementById("secQuestion").selectedIndex = 0;
            document.getElementById("secAnswer").value          = "";
            document.getElementById("secCurrentPassword").value = "";
        }
    })
    .catch(() => showToast("Request failed.", "danger"))
    .finally(() => btnLoading(btn, false));
}

const PW_FIELDS_ADMIN = ["currentPassword", "newPassword", "confirmPassword"];

function changeAdminPassword() {
    if (document.getElementById("currentPassword")?.disabled) {
        showToast("Please wait for the lockout to expire.", "warning"); return;
    }
    const current = document.getElementById("currentPassword")?.value;
    const newPw   = document.getElementById("newPassword")?.value;
    const confirm = document.getElementById("confirmPassword")?.value;
    let pwValid = true;
    if (!current) { showFieldError("currentPassword", "Current password is required.");  pwValid = false; }
    if (!newPw)   { showFieldError("newPassword",     "New password is required.");      pwValid = false; }
    else if (newPw.length < 6) { showFieldError("newPassword", "Password must be at least 6 characters."); pwValid = false; }
    if (!confirm) { showFieldError("confirmPassword", "Please confirm your new password."); pwValid = false; }
    else if (newPw && confirm && newPw !== confirm) { showFieldError("confirmPassword", "Passwords do not match."); pwValid = false; }
    if (!pwValid) {
        showToast("Please fix the password errors before submitting.", "warning");
        return;
    }

    const changePwBtnEl = document.getElementById("changePwBtn");
    btnLoading(changePwBtnEl, true, "Updating…");
    fetch(PROFILE_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "changePassword", current_password: current, new_password: newPw, confirm_password: confirm, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? "success" : "danger");
        if (res.locked) {
            startPwLockdown(res.retry_after, PW_FIELDS_ADMIN, "changePwBtn", "pwLockNotice", "pwCountdown");
            return;
        }
        if (res.success) {
            PW_FIELDS_ADMIN.forEach(id => { const el = document.getElementById(id); if (el) el.value = ""; });
            document.getElementById("pwStrengthBar").style.width = "0%";
            document.getElementById("pwStrengthText").textContent = "";
        }
    })
    .catch(() => showToast("Request failed.", "danger"))
    .finally(() => { if (!document.getElementById("currentPassword")?.disabled) btnLoading(changePwBtnEl, false); });
}

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

function checkPasswordStrength() {
    const pw  = document.getElementById("newPassword")?.value || "";
    const bar = document.getElementById("pwStrengthBar");
    const txt = document.getElementById("pwStrengthText");
    let strength = 0;
    if (pw.length >= 6)  strength++;
    if (pw.length >= 10) strength++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) strength++;
    if (/\d/.test(pw))   strength++;
    if (/[^A-Za-z0-9]/.test(pw)) strength++;
    const levels = [
        { pct: "0%",   cls: "",          label: "" },
        { pct: "25%",  cls: "bg-danger", label: "Weak" },
        { pct: "50%",  cls: "bg-warning",label: "Fair" },
        { pct: "75%",  cls: "bg-info",   label: "Good" },
        { pct: "100%", cls: "bg-success",label: "Strong" }
    ];
    const lvl = levels[Math.min(strength, 4)];
    bar.style.width = lvl.pct;
    bar.className   = "progress-bar " + lvl.cls;
    txt.textContent = lvl.label;
}

function togglePwVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.className = "bi bi-eye-slash";
    } else {
        input.type = "password";
        icon.className = "bi bi-eye";
    }
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>