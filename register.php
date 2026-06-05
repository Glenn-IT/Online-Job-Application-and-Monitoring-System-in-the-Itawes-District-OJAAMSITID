<?php

$configPath = __DIR__ . '/config/auth.php';
require_once $configPath;

// Already logged in? Go to dashboard.
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/user/browse-jobs.php');
    exit;
}

$error   = '';
$success = '';

// ── Generate math CAPTCHA question ──────────────────────────
// Always refresh the question so each page load has a new one.
// On POST we validate against the stored answer, then regenerate.
function generateCaptcha(): void {
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['captcha_answer'] = $a + $b;
    $_SESSION['captcha_question'] = "What is {$a} + {$b}?";
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    generateCaptcha();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $contact   = trim($_POST['contact']   ?? '');
    $password  = $_POST['password']  ?? '';
    $confirm   = $_POST['confirm']   ?? '';

    // ── Bot check 1: Honeypot ────────────────────────────────
    // The `website` field is hidden from real users via CSS.
    // Bots that fill every field will populate it; humans won't.
    if (trim($_POST['website'] ?? '') !== '') {
        // Silently reject — don't reveal the trap
        $success = 'Account created! Redirecting to login...';
        header('Refresh: 1.5; url=' . BASE_URL . '/login.php');
        generateCaptcha();
        goto render;
    }

    // ── Bot check 2: Math CAPTCHA ────────────────────────────
    $captchaInput  = (int)trim($_POST['captcha'] ?? '');
    $captchaAnswer = (int)($_SESSION['captcha_answer'] ?? -1);
    generateCaptcha(); // always regenerate after a submission attempt

    if ($captchaInput !== $captchaAnswer) {
        $error = 'Incorrect answer to the security question. Please try again.';
        goto render;
    }

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

render:

$pageTitle = "OJAMS - Register";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            color: #1e293b;
        }
        .auth-page { min-height: 100vh; display: flex; }
        .auth-hero {
            flex: 1;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        .auth-hero::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .auth-hero::after {
            content: '';
            position: absolute;
            bottom: -100px; left: -60px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,0.03);
        }
        .auth-hero-icon {
            width: 80px; height: 80px;
            border-radius: 22px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .auth-hero h1 { color: #fff; font-size: 2.2rem; font-weight: 900; letter-spacing: -0.5px; margin-bottom: 0.75rem; }
        .auth-hero > p { color: rgba(255,255,255,0.65); font-size: 1rem; max-width: 320px; text-align: center; line-height: 1.6; }
        .auth-hero-features { list-style: none; padding: 0; margin: 2rem 0 0; text-align: left; width: 100%; max-width: 300px; }
        .auth-hero-features li { color: rgba(255,255,255,0.75); font-size: 0.875rem; padding: 0.45rem 0; display: flex; align-items: center; gap: 0.75rem; }
        .auth-hero-features li i { color: #a5b4fc; font-size: 1rem; width: 18px; flex-shrink: 0; }
        .auth-form-panel {
            width: 440px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
            background: #ffffff;
            overflow-y: auto;
        }
        .auth-card { width: 100%; max-width: 380px; }
        .auth-card-header { text-align: center; margin-bottom: 2rem; }
        .auth-card-header h2 { font-size: 1.6rem; font-weight: 800; color: #1e293b; margin-bottom: 0.3rem; }
        .auth-card-header p { color: #64748b; font-size: 0.875rem; }
        .auth-footer-link { text-align: center; margin-top: 1.5rem; font-size: 0.845rem; color: #64748b; }
        .auth-footer-link a { color: #4f46e5; font-weight: 600; text-decoration: none; }
        .auth-footer-link a:hover { text-decoration: underline; }
        .back-to-jobs { text-align: center; margin-top: 0.75rem; font-size: 0.82rem; }
        .back-to-jobs a { color: #64748b; text-decoration: none; }
        .back-to-jobs a:hover { color: #4f46e5; }
        .copyright-note { font-size: 0.72rem; color: #64748b; text-align: center; margin-top: 1.5rem; }
        @media (max-width: 767.98px) {
            .auth-hero { display: none; }
            .auth-form-panel { width: 100%; padding: 1.5rem; min-height: 100vh; }
        }
    </style>
</head>
<body>

<div class="auth-page">

    <!-- Left: Hero Panel -->
    <div class="auth-hero">
        <div class="auth-hero-icon">
            <i class="bi bi-person-plus-fill"></i>
        </div>
        <h1>Join OJAMS</h1>
        <p>Create your free account and start exploring hundreds of job opportunities today.</p>
        <ul class="auth-hero-features">
            <li><i class="bi bi-lightning-charge-fill"></i> Quick &amp; easy registration</li>
            <li><i class="bi bi-search-heart"></i> Browse curated job listings</li>
            <li><i class="bi bi-graph-up-arrow"></i> Track all your applications</li>
            <li><i class="bi bi-bell-fill"></i> Get notified on status updates</li>
        </ul>
    </div>

    <!-- Right: Form Panel -->
    <div class="auth-form-panel">
        <div class="auth-card">

            <div class="auth-card-header">
                <h2>Create account</h2>
                <p>Fill in the details below to get started</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php elseif ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <form id="registerForm" method="post" action="register.php">
                <!-- Honeypot: hidden from humans via CSS; bots fill it in -->
                <div style="position:absolute;left:-9999px;top:-9999px;opacity:0;" aria-hidden="true" tabindex="-1">
                    <label for="website">Leave this blank</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="regName">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" name="full_name" id="regName"
                               placeholder="Your full name"
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="regEmail">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" id="regEmail"
                               placeholder="you@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="regContact">Contact Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-phone"></i></span>
                        <input type="tel" class="form-control" name="contact" id="regContact"
                               placeholder="e.g. 09171234567"
                               value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="regPassword">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" id="regPassword"
                               placeholder="At least 6 characters" required minlength="6">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleRegPass()">
                            <i class="bi bi-eye" id="regEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="regConfirm">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" name="confirm" id="regConfirm"
                               placeholder="Repeat your password" required>
                    </div>
                </div>

                <!-- Math CAPTCHA -->
                <div class="mb-4">
                    <label class="form-label" for="captcha">
                        <i class="bi bi-shield-check me-1 text-primary"></i>
                        Security Check <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text fw-semibold text-primary">
                            <?php echo htmlspecialchars($_SESSION['captcha_question'] ?? 'What is 1 + 1?'); ?>
                        </span>
                        <input type="number" class="form-control" name="captcha" id="captcha"
                               placeholder="Your answer" required autocomplete="off">
                    </div>
                    <div class="form-text text-muted">Answer the simple math question to prove you're human.</div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </div>
            </form>

            <div class="auth-footer-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>

            <div class="back-to-jobs">
                <a href="index.php"><i class="bi bi-arrow-left me-1"></i>Back to Job Listings</a>
            </div>

            <div class="copyright-note">&copy; 2026 OJAMS &mdash; Prototype Version</div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleRegPass() {
    const inp  = document.getElementById('regPassword');
    const icon = document.getElementById('regEyeIcon');
    if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else                         { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}
document.getElementById('registerForm')?.addEventListener('submit', function (e) {
    // Clear previous field errors
    this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    this.querySelectorAll('.invalid-feedback.d-block').forEach(el => el.remove());

    const showErr = (id, msg) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('is-invalid');
        const wrapper = el.closest('.input-group') || el;
        const fb = document.createElement('div');
        fb.className = 'invalid-feedback d-block';
        fb.textContent = msg;
        wrapper.insertAdjacentElement('afterend', fb);
        el.addEventListener('input', () => { el.classList.remove('is-invalid'); fb.remove(); }, { once: true });
    };

    const name    = document.getElementById('regName')?.value.trim();
    const email   = document.getElementById('regEmail')?.value.trim();
    const contact = document.getElementById('regContact')?.value.trim();
    const pw      = document.getElementById('regPassword')?.value;
    const confirm = document.getElementById('regConfirm')?.value;
    let valid = true;

    if (!name)    { showErr('regName',     'Full name is required.');                    valid = false; }
    if (!email)   { showErr('regEmail',    'Email address is required.');                valid = false; }
    if (!contact) { showErr('regContact',  'Contact number is required.');               valid = false; }
    if (!pw)      { showErr('regPassword', 'Password is required.');                     valid = false; }
    else if (pw.length < 6) { showErr('regPassword', 'Password must be at least 6 characters.'); valid = false; }
    if (pw && confirm && pw !== confirm) { showErr('regConfirm', 'Passwords do not match.'); valid = false; }

    if (!valid) { e.preventDefault(); return; }

    const btn = this.querySelector('[type="submit"]');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Creating account…';
        btn.disabled = true;
    }
});
</script>
</body>
</html>
