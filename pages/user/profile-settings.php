<?php
require_once __DIR__ . "/../../config/auth.php";
requireUser();
$pageTitle   = "OJAMS - Profile Settings";
$basePath    = "../../";
$currentPage = "profile-settings";

$u = $_SESSION["ojams_user"];

// Current security question (read from DB — older sessions won't have it)
$sqStmt = $pdo->prepare("SELECT security_question FROM users WHERE id = ?");
$sqStmt->execute([$u["id"]]);
$currentSecQuestion = $sqStmt->fetchColumn() ?: null;

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
                    <?php $photo = $u['profile_photo'] ?? null; ?>
                    <?php if ($photo): ?>
                        <img src="<?php echo htmlspecialchars(BASE_URL . '/uploads/avatars/' . $photo); ?>"
                             id="avatarImg" alt="Avatar"
                             class="rounded-circle mb-3 object-fit-cover border"
                             style="width:100px;height:100px;object-fit:cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
                             id="avatarPlaceholder" style="width:100px;height:100px;">
                            <i class="bi bi-person-fill text-white display-5"></i>
                        </div>
                    <?php endif; ?>
                    <h5 class="fw-bold mb-1" id="cardName"><?php echo htmlspecialchars($u["full_name"]); ?></h5>
                    <p class="text-muted mb-2" id="cardEmail"><?php echo htmlspecialchars($u["email"]); ?></p>
                    <?php if (($u['role'] ?? 'user') === 'staff'): ?>
                        <span class="badge bg-warning text-dark">Staff</span>
                    <?php else: ?>
                        <span class="badge bg-primary">Job Seeker</span>
                    <?php endif; ?>
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
                            <tr>
                                <th class="text-muted"><i class="bi bi-patch-question me-2"></i>Security Question</th>
                                <td id="pvSecQuestion"><?php echo $currentSecQuestion
                                    ? htmlspecialchars($currentSecQuestion)
                                    : '<span class="text-warning">Not set — edit your profile to add one</span>'; ?></td>
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
                                           inputmode="numeric" maxlength="11" pattern="\d{11}"
                                           oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)"
                                           placeholder="11 digits, e.g. 09171234567"
                                           value="<?php echo htmlspecialchars($u['contact_number'] ?? ''); ?>">
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
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="editCurrentPassword"
                                           placeholder="Enter current password">
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="togglePwVisibility('editCurrentPassword', this)" title="Show/Hide Password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="editNewPassword"
                                               placeholder="Min 8 chars, uppercase, number, symbol"
                                               oninput="checkUserPwStrength()">
                                        <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePwVisibility('editNewPassword', this)" title="Show/Hide Password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="progress mt-2" style="height:5px;">
                                        <div class="progress-bar" id="userPwStrengthBar" role="progressbar" style="width:0%"></div>
                                    </div>
                                    <small class="text-muted" id="userPwStrengthText"></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="editConfirmPassword"
                                               placeholder="Re-enter new password">
                                        <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePwVisibility('editConfirmPassword', this)" title="Show/Hide Password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-patch-question me-1"></i>
                                Security question — used to verify you on Forgot Password.
                                Leave blank to keep your current one. Requires your current password.
                            </p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Security Question</label>
                                    <select class="form-select" id="editSecQuestion">
                                        <option value="" selected>— Keep current —</option>
                                        <?php foreach (SECURITY_QUESTIONS as $q): ?>
                                        <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Security Answer</label>
                                    <input type="text" class="form-control" id="editSecAnswer"
                                           placeholder="Your answer (not case-sensitive)" maxlength="150" autocomplete="off">
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

// Security: passwords cannot be copied out of, or pasted into, these fields.
["editCurrentPassword", "editNewPassword", "editConfirmPassword"].forEach(id => {
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
        clearAllFieldErrors("editProfileForm");
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
    const currentPw = document.getElementById("editCurrentPassword")?.value || "";
    const newPw     = document.getElementById("editNewPassword")?.value || "";
    const confirmPw = document.getElementById("editConfirmPassword")?.value || "";
    const secQ      = document.getElementById("editSecQuestion")?.value;
    const secA      = document.getElementById("editSecAnswer")?.value.trim();

    clearAllFieldErrors("editProfileForm");
    let editValid = true;
    if (!fullName) { showFieldError("editFullName", "Full name is required.");     editValid = false; }
    if (!email)    { showFieldError("editEmail",    "Email address is required."); editValid = false; }
    if (contact && !/^\d{11}$/.test(contact)) {
        showFieldError("editContact", "Contact number must be exactly 11 digits (numbers only)."); editValid = false;
    }
    if (birthdate) {
        const bd  = new Date(birthdate);
        const now = new Date();
        if (bd >= now) {
            showFieldError("editBirthdate", "Birthdate cannot be a future date."); editValid = false;
        } else {
            const age = Math.floor((now - bd) / (365.25 * 24 * 3600 * 1000));
            if (age < 16 || age > 80) { showFieldError("editBirthdate", "Age must be between 16 and 80 years."); editValid = false; }
        }
    }

    if (newPw || confirmPw || (currentPw && !secQ && !secA)) {
        if (!currentPw) {
            showFieldError("editCurrentPassword", "Current password is required.");
            editValid = false;
        }
        if (!newPw) {
            showFieldError("editNewPassword", "New password is required.");
            editValid = false;
        } else {
            if (newPw.length < 8) {
                showFieldError("editNewPassword", "New password must be at least 8 characters.");
                editValid = false;
            } else if (!/[A-Z]/.test(newPw)) {
                showFieldError("editNewPassword", "New password must contain at least one uppercase letter.");
                editValid = false;
            } else if (!/[0-9]/.test(newPw)) {
                showFieldError("editNewPassword", "New password must contain at least one number.");
                editValid = false;
            } else if (!/[^A-Za-z0-9]/.test(newPw)) {
                showFieldError("editNewPassword", "New password must contain at least one special character.");
                editValid = false;
            }
        }
        if (!confirmPw) {
            showFieldError("editConfirmPassword", "Please confirm your new password.");
            editValid = false;
        } else if (newPw && confirmPw && newPw !== confirmPw) {
            showFieldError("editConfirmPassword", "New passwords do not match.");
            editValid = false;
        }
    }

    if (secQ || secA) {
        if (!secQ)      { showFieldError("editSecQuestion", "Choose a security question.");            editValid = false; }
        if (!secA)      { showFieldError("editSecAnswer",   "Security answer is required.");           editValid = false; }
        else if (secA.length < 2) { showFieldError("editSecAnswer", "Answer must be at least 2 characters."); editValid = false; }
        if (!currentPw) { showFieldError("editCurrentPassword", "Current password is required to change your security question."); editValid = false; }
    }
    if (!editValid) {
        showToast("Please fill in all required profile fields correctly.", "warning");
        return;
    }

    const doPasswordChange   = !!(currentPw && newPw && confirmPw);
    const doSecurityQuestion = !!(secQ && secA && currentPw);

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
        body: JSON.stringify({ action: "changePassword", current_password: currentPw, new_password: newPw, confirm_password: confirmPw, csrf_token: getCsrfToken() })
    }).then(r => r.json());

    const updateSecurity = () => fetch(PROFILE_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "updateSecurityQuestion", question: secQ, answer: secA, current_password: currentPw, csrf_token: getCsrfToken() })
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
        if (doSecurityQuestion) {
            updateSecurity().then(r3 => {
                if (r3.success) {
                    document.getElementById("pvSecQuestion").textContent = r3.question;
                    document.getElementById("editSecQuestion").value = "";
                    document.getElementById("editSecAnswer").value   = "";
                    if (!doPasswordChange) {
                        showToast("Profile & security question updated!", "success");
                        document.getElementById("editCurrentPassword").value = "";
                    }
                } else {
                    showToast(r3.message, "danger");
                }
            });
        }
        if (doPasswordChange) {
            changePass().then(r2 => {
                if (r2.locked) {
                    startPwLockdown(
                        r2.retry_after,
                        ["editCurrentPassword", "editNewPassword", "editConfirmPassword"],
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
                    document.getElementById("editConfirmPassword").value = "";
                    const bar = document.getElementById("userPwStrengthBar");
                    const txt = document.getElementById("userPwStrengthText");
                    if (bar) bar.style.width = "0%";
                    if (txt) txt.textContent = "";
                }
            });
        } else if (!doSecurityQuestion) {
            showToast("Profile updated successfully!", "success");
        }
        toggleEditProfile();
    })
    .catch(() => showToast("Request failed.", "danger"))
    .finally(() => btnLoading(saveBtn, false));
}

function checkUserPwStrength() {
    const pw  = document.getElementById("editNewPassword")?.value || "";
    const bar = document.getElementById("userPwStrengthBar");
    const txt = document.getElementById("userPwStrengthText");
    if (!bar || !txt) return;
    let strength = 0;
    if (pw.length >= 8)  strength++;
    if (pw.length >= 12) strength++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) strength++;
    if (/\d/.test(pw))   strength++;
    if (/[^A-Za-z0-9]/.test(pw)) strength++;
    const levels = [
        { pct: "0%",   cls: "",           label: "" },
        { pct: "25%",  cls: "bg-danger",  label: "Weak" },
        { pct: "50%",  cls: "bg-warning", label: "Fair" },
        { pct: "75%",  cls: "bg-info",    label: "Good" },
        { pct: "100%", cls: "bg-success", label: "Strong" }
    ];
    const lvl = levels[Math.min(strength, 4)];
    bar.style.width = lvl.pct;
    bar.className   = "progress-bar " + lvl.cls;
    txt.textContent = lvl.label;
}

function togglePwVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn ? btn.querySelector("i") : null;
    if (!input || !icon) return;
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