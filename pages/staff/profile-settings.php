<?php
require_once __DIR__ . "/../../config/auth.php";
requireStaff();
$pageTitle   = "OJAMS - Staff Profile Settings";
$basePath    = "../../";
$currentPage = "profile-settings";

$u = $_SESSION["ojams_user"];

// Current security question
$sqStmt = $pdo->prepare("SELECT security_question FROM users WHERE id = ?");
$sqStmt->execute([$u["id"]]);
$currentSecQuestion = $sqStmt->fetchColumn() ?: null;

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-staff.php";
?>
<div class="admin-layout">
    <?php include $basePath . "layouts/sidebar-staff.php"; ?>
    <main class="admin-main">
        <div class="mb-4">
            <h2 class="fw-bold mb-1"><i class="bi bi-person-gear me-2 text-warning"></i>Profile Settings</h2>
            <p class="text-muted mb-0">Manage your staff officer profile, contact details, and account security.</p>
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
                            <div class="rounded-circle bg-warning d-inline-flex align-items-center justify-content-center mb-3"
                                 id="avatarPlaceholder" style="width:100px;height:100px;">
                                <i class="bi bi-person-fill text-dark display-5"></i>
                            </div>
                        <?php endif; ?>
                        <h5 class="fw-bold mb-1" id="cardName"><?php echo htmlspecialchars($u["full_name"]); ?></h5>
                        <p class="text-muted mb-2" id="cardEmail"><?php echo htmlspecialchars($u["email"]); ?></p>
                        <span class="badge bg-warning text-dark">Staff Officer</span>
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

            <!-- Profile Forms -->
            <div class="col-md-8 mb-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-warning"></i>Personal Information</h6>
                    </div>
                    <div class="card-body p-4">
                        <div id="profileAlert"></div>
                        <form id="profileForm">
                            <input type="hidden" name="action" value="updateProfile">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="profName">Full Name</label>
                                <input type="text" class="form-control" id="profName" name="full_name"
                                       value="<?php echo htmlspecialchars($u["full_name"]); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="profEmail">Email Address</label>
                                <input type="email" class="form-control" id="profEmail" name="email"
                                       value="<?php echo htmlspecialchars($u["email"]); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="profContact">Contact Number</label>
                                <input type="text" class="form-control" id="profContact" name="contact_number"
                                       value="<?php echo htmlspecialchars($u["contact_number"] ?? ""); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="profAddress">Address</label>
                                <textarea class="form-control" id="profAddress" name="address" rows="2"><?php echo htmlspecialchars($u["address"] ?? ""); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="profBirthdate">Birthdate</label>
                                <input type="date" class="form-control" id="profBirthdate" name="birthdate"
                                       value="<?php echo htmlspecialchars($u["birthdate"] ?? ""); ?>">
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save me-1"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Password Form -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-warning"></i>Change Password</h6>
                    </div>
                    <div class="card-body p-4">
                        <div id="passAlert"></div>
                        <form id="passForm">
                            <input type="hidden" name="action" value="changePassword">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="currentPass">Current Password</label>
                                <input type="password" class="form-control" id="currentPass" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="newPass">New Password</label>
                                <input type="password" class="form-control" id="newPass" name="new_password" required minlength="8">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="confirmPass">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirmPass" name="confirm_password" required minlength="8">
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key me-1"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Security Question Form -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-question-circle me-2 text-warning"></i>Security Question</h6>
                    </div>
                    <div class="card-body p-4">
                        <div id="sqAlert"></div>
                        <form id="sqForm">
                            <input type="hidden" name="action" value="setSecurityQuestion">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="sqSelect">Select Question</label>
                                <select class="form-select" id="sqSelect" name="security_question" required>
                                    <option value="">Choose a question...</option>
                                    <?php
                                    $questions = [
                                        "What is the name of your first pet?",
                                        "What was the model of your first car?",
                                        "In what city were you born?",
                                        "What is your mother's maiden name?",
                                        "What was the name of your elementary school?",
                                    ];
                                    foreach ($questions as $q):
                                        $sel = ($currentSecQuestion === $q) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo htmlspecialchars($q); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($q); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="sqAnswer">Your Answer</label>
                                <input type="password" class="form-control" id="sqAnswer" name="security_answer"
                                       placeholder="<?php echo $currentSecQuestion ? 'Leave blank to keep existing answer' : 'Enter your secret answer'; ?>">
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-shield-check me-1"></i>Save Security Question
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const CSRF_TOKEN = '<?php echo generateCsrfToken(); ?>';

document.getElementById('profileForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    fetch('<?php echo $basePath; ?>handlers/profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        const box = document.getElementById('profileAlert');
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) {
            box.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${res.message}</div>`;
            document.getElementById('cardName').textContent = data.full_name;
            document.getElementById('cardEmail').textContent = data.email;
        } else {
            box.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${res.message}</div>`;
        }
    })
    .catch(() => showToast('Request failed. Please try again.', 'danger'));
});

document.getElementById('passForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    fetch('<?php echo $basePath; ?>handlers/profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        const box = document.getElementById('passAlert');
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) {
            box.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${res.message}</div>`;
            this.reset();
        } else {
            box.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${res.message}</div>`;
        }
    })
    .catch(() => showToast('Request failed. Please try again.', 'danger'));
});

document.getElementById('sqForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    fetch('<?php echo $basePath; ?>handlers/profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        const box = document.getElementById('sqAlert');
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) {
            box.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${res.message}</div>`;
            document.getElementById('sqAnswer').value = '';
        } else {
            box.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${res.message}</div>`;
        }
    })
    .catch(() => showToast('Request failed. Please try again.', 'danger'));
});

function uploadAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const formData = new FormData();
    formData.append('action', 'uploadAvatar');
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('avatar', input.files[0]);

    fetch('<?php echo $basePath; ?>handlers/profile.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message || (res.success ? 'Avatar updated.' : 'Avatar upload failed.'), res.success ? 'success' : 'danger');
        if (res.success) {
            setTimeout(() => location.reload(), 800);
        }
    })
    .catch(() => showToast('Avatar upload failed. Please try again.', 'danger'));
}
</script>
<?php include $basePath . "layouts/footer.php"; ?>
