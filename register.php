<?php

$configPath = __DIR__ . '/config/db.php';
require_once $configPath;

// Already logged in? Go to dashboard.
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/user/browse-jobs.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $contact   = trim($_POST['contact']   ?? '');
    $password  = $_POST['password']  ?? '';
    $confirm   = $_POST['confirm']   ?? '';

    // Basic validation
    if (!$full_name || !$email || !$contact || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'That email address is already registered.';
        } else {
            // Insert new user
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $stmt = $pdo->prepare("
                INSERT INTO users (role, full_name, email, password_hash, contact_number)
                VALUES ('user', ?, ?, ?, ?)
            ");
            $stmt->execute([$full_name, $email, $hash, $contact]);
            $success = 'Account created! Redirecting to login...';
            header('Refresh: 1.5; url=' . BASE_URL . '/login.php');
        }
    }
}

$pageTitle = "OJAMS - Register";
$basePath  = "";
include 'layouts/header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">

                <!-- Registration Card -->
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Logo / Brand -->
                        <div class="text-center mb-4">
                            <i class="bi bi-briefcase-fill text-primary display-3"></i>
                            <h3 class="fw-bold mt-2">Create Account</h3>
                            <p class="text-muted">Join OJAMS to find your dream job</p>
                        </div>

                        <!-- Alert Box -->
                        <?php if ($error): ?>
                        <div class="alert alert-danger mb-3"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
                        <?php elseif ($success): ?>
                        <div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <!-- Registration Form -->
                        <form id="registerForm" method="post" action="register.php">
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" name="full_name" id="regName"
                                           placeholder="Enter your full name"
                                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" id="regEmail"
                                           placeholder="Enter your email"
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                    <input type="tel" class="form-control" name="contact" id="regContact"
                                           placeholder="e.g. 09171234567"
                                           value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" name="password" id="regPassword"
                                           placeholder="Create a password" required minlength="6">
                                    <button type="button" class="btn btn-outline-secondary" onclick="toggleRegPass()">
                                        <i class="bi bi-eye" id="regEyeIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" name="confirm" id="regConfirm"
                                           placeholder="Confirm your password" required>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-person-plus me-2"></i>Register
                                </button>
                            </div>
                        </form>

                        <!-- Login Link -->
                        <div class="text-center mt-4">
                            <p class="text-muted mb-0">
                                Already have an account?
                                <a href="login.php" class="text-primary fw-semibold">Login here</a>
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

<script>
function toggleRegPass() {
    const inp  = document.getElementById('regPassword');
    const icon = document.getElementById('regEyeIcon');
    if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else                         { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}
</script>
