<?php

require_once __DIR__ . '/config/auth.php';

// Already logged in? Redirect to the right dashboard.
if (isLoggedIn()) {
    header('Location: ' . (isAdmin()
        ? BASE_URL . '/pages/admin/dashboard.php'
        : BASE_URL . '/pages/user/browse-jobs.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } else {
            // Build a clean session payload (never store password_hash)
            $_SESSION['ojams_user'] = [
                'id'             => $user['id'],
                'role'           => $user['role'],
                'full_name'      => $user['full_name'],
                'email'          => $user['email'],
                'contact_number' => $user['contact_number'],
                'address'        => $user['address'],
                'birthdate'      => $user['birthdate'],
            ];

            // Role-based redirect
            header('Location: ' . ($user['role'] === 'admin'
                ? BASE_URL . '/pages/admin/dashboard.php'
                : BASE_URL . '/pages/user/browse-jobs.php'));
            exit;
        }
    }
}

$pageTitle = "OJAMS - Login";
$basePath  = "";
include 'layouts/header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">

                <!-- Login Card -->
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Logo / Brand -->
                        <div class="text-center mb-4">
                            <i class="bi bi-briefcase-fill text-primary display-3"></i>
                            <h3 class="fw-bold mt-2">OJAMS</h3>
                            <p class="text-muted">Online Job Application Monitoring System</p>
                        </div>

                        <!-- Alert Box -->
                        <?php if ($error): ?>
                        <div class="alert alert-danger mb-3" role="alert">
                            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form id="loginForm" method="post" action="login.php">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" id="loginEmail"
                                           placeholder="Enter your email"
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" name="password" id="loginPassword"
                                           placeholder="Enter your password" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="toggleLoginPass()">
                                        <i class="bi bi-eye" id="loginEyeIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Quick Login Hints -->
                            <div class="alert alert-info py-2 small mb-3">
                                <strong>Demo Accounts:</strong><br>
                                <i class="bi bi-shield-lock me-1"></i><strong>Admin:</strong> admin@ojams.com / admin123<br>
                                <i class="bi bi-person me-1"></i><strong>User:</strong> juan@email.com / password123
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </div>
                        </form>

                        <!-- Register Link -->
                        <div class="text-center">
                            <p class="text-muted mb-0">
                                Don't have an account?
                                <a href="register.php" class="text-primary fw-semibold">Register here</a>
                            </p>
                            <p class="text-muted mt-2 mb-0">
                                <a href="#" class="text-secondary small" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                    <i class="bi bi-lock me-1"></i>Forgot your password?
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="text-center mt-3">
                    <small class="text-muted">&copy; 2026 OJAMS. Prototype Version</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="forgotPasswordModalLabel">
                    <i class="bi bi-lock me-2"></i>Forgot Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Enter Email -->
                <div id="fp-step1">
                    <p class="text-muted mb-3">Enter your registered email address and we'll send you a password reset link.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="fp-email" placeholder="Enter your email address">
                        </div>
                        <div class="invalid-feedback" id="fp-email-error">Please enter a valid email address.</div>
                    </div>
                </div>
                <!-- Step 2: Success Message -->
                <div id="fp-step2" class="text-center py-3 d-none">
                    <i class="bi bi-envelope-check-fill text-success display-4 mb-3 d-block"></i>
                    <h5 class="fw-bold">Reset Link Sent!</h5>
                    <p class="text-muted">A password reset link has been sent to <strong id="fp-sent-email"></strong>. Please check your inbox.</p>
                    <p class="text-muted small">(This is a prototype — no actual email is sent.)</p>
                </div>
            </div>
            <div class="modal-footer" id="fp-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="sendResetLink()">
                    <i class="bi bi-send me-1"></i>Send Reset Link
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function sendResetLink() {
    const emailInput = document.getElementById('fp-email');
    const email = emailInput.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email || !emailRegex.test(email)) {
        emailInput.classList.add('is-invalid');
        return;
    }
    emailInput.classList.remove('is-invalid');
    document.getElementById('fp-sent-email').textContent = email;
    document.getElementById('fp-step1').classList.add('d-none');
    document.getElementById('fp-step2').classList.remove('d-none');
    document.getElementById('fp-footer').innerHTML = `
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
            <i class="bi bi-check-lg me-1"></i>Done
        </button>`;
}
// Reset modal state when closed
document.getElementById('forgotPasswordModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('fp-step1').classList.remove('d-none');
    document.getElementById('fp-step2').classList.add('d-none');
    document.getElementById('fp-email').value = '';
    document.getElementById('fp-email').classList.remove('is-invalid');
    document.getElementById('fp-footer').innerHTML = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancel</button>
        <button type="button" class="btn btn-primary" onclick="sendResetLink()"><i class="bi bi-send me-1"></i>Send Reset Link</button>`;
});
</script>

<script>
function toggleLoginPass() {
    const inp  = document.getElementById('loginPassword');
    const icon = document.getElementById('loginEyeIcon');
    if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else                         { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}
</script>
