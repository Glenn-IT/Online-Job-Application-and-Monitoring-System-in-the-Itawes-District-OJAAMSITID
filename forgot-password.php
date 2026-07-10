<?php
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin()
        ? BASE_URL . '/pages/admin/dashboard.php'
        : BASE_URL . '/pages/user/browse-jobs.php'));
    exit;
}

// ── Security-question based reset flow ──────────────────────
// Step 1: user enters their email.
// Step 2: user must pick the CORRECT security question from the
//         full list AND answer it. The full list is always shown
//         (even for unknown emails) so the page never reveals
//         whether an address is registered.
// Success: a one-time reset token is created and the user is
//         redirected to reset-password.php to set a new password.

$message = '';
$isError = false;

// Restart the flow ("use a different email")
if (isset($_GET['restart'])) {
    unset($_SESSION['fp_email'], $_SESSION['fp_attempts'], $_SESSION['fp_locked_until']);
    header('Location: ' . BASE_URL . '/forgot-password.php');
    exit;
}

$step = !empty($_SESSION['fp_email']) ? 2 : 1;

// Attempt throttle state
$fpAttempts    = (int)($_SESSION['fp_attempts']     ?? 0);
$fpLockedUntil = (int)($_SESSION['fp_locked_until'] ?? 0);
$lockRemaining = max(0, $fpLockedUntil - time());
if ($fpLockedUntil > 0 && $lockRemaining === 0) {
    // Lock expired — reset counter
    $_SESSION['fp_attempts']     = 0;
    $_SESSION['fp_locked_until'] = 0;
    $fpAttempts = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postStep = $_POST['step'] ?? '';

    // ── Step 1: capture email ────────────────────────────────
    if ($postStep === 'email') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $isError = true;
        } else {
            $_SESSION['fp_email'] = $email;
            $step = 2;
        }
    }

    // ── Step 2: verify question + answer ─────────────────────
    if ($postStep === 'verify' && !empty($_SESSION['fp_email'])) {
        $step = 2;

        if ($lockRemaining > 0) {
            $message = "Too many failed attempts. Please wait {$lockRemaining} second(s) and try again.";
            $isError = true;
        } else {
            $question = trim($_POST['security_question'] ?? '');
            $answer   = mb_strtolower(trim($_POST['security_answer'] ?? ''));
            $email    = $_SESSION['fp_email'];

            $stmt = $pdo->prepare(
                "SELECT id, security_question, security_answer_hash
                 FROM users WHERE LOWER(email) = ? AND is_active = 1 AND is_approved = 1 LIMIT 1"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            $ok = $user
                && $user['security_question'] !== null
                && $user['security_answer_hash'] !== null
                && $question === $user['security_question']
                && $answer !== ''
                && password_verify($answer, $user['security_answer_hash']);

            if ($ok) {
                // Verified — issue a one-time reset token and send the
                // user straight to the set-new-password page.
                unset($_SESSION['fp_email']);
                $_SESSION['fp_attempts']     = 0;
                $_SESSION['fp_locked_until'] = 0;

                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $pdo->prepare(
                    "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)"
                )->execute([$email, $token, $expires]);

                header('Location: ' . BASE_URL . '/reset-password.php?token=' . $token);
                exit;
            }

            // Wrong question and/or answer — generic error, count the attempt
            $fpAttempts++;
            $_SESSION['fp_attempts'] = $fpAttempts;
            if ($fpAttempts >= SQ_MAX_ATTEMPTS) {
                $_SESSION['fp_locked_until'] = time() + SQ_LOCKOUT_SECONDS;
                $lockRemaining = SQ_LOCKOUT_SECONDS;
                $message = 'Too many failed attempts. Please wait ' . SQ_LOCKOUT_SECONDS . ' seconds before trying again.';
            } else {
                $left    = SQ_MAX_ATTEMPTS - $fpAttempts;
                $message = "Security question or answer is incorrect. {$left} attempt(s) remaining.";
            }
            $isError = true;
        }
    }
}

$fpEmail   = $_SESSION['fp_email'] ?? '';
$pageTitle = 'OJAMS - Forgot Password';
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
            margin: 0; padding: 0;
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
            position: absolute; top: -80px; right: -80px;
            width: 320px; height: 320px; border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .auth-hero::after {
            content: '';
            position: absolute; bottom: -100px; left: -60px;
            width: 400px; height: 400px; border-radius: 50%;
            background: rgba(255,255,255,0.03);
        }
        .auth-hero-icon {
            width: 80px; height: 80px; border-radius: 22px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; color: #fff; margin-bottom: 1.5rem;
        }
        .auth-hero h1 { color: #fff; font-size: 2.2rem; font-weight: 900; letter-spacing: -0.5px; margin-bottom: 0.75rem; }
        .auth-hero > p { color: rgba(255,255,255,0.65); font-size: 1rem; max-width: 320px; text-align: center; line-height: 1.6; }
        .auth-hero-features { list-style: none; padding: 0; margin: 2rem 0 0; text-align: left; width: 100%; max-width: 300px; }
        .auth-hero-features li { color: rgba(255,255,255,0.75); font-size: 0.875rem; padding: 0.45rem 0; display: flex; align-items: center; gap: 0.75rem; }
        .auth-hero-features li i { color: #a5b4fc; font-size: 1rem; width: 18px; flex-shrink: 0; }
        .auth-form-panel {
            width: 440px; max-width: 100%;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 2.5rem; background: #ffffff; overflow-y: auto;
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
        .step-badge {
            display: inline-flex; align-items: center; gap: 0.4rem;
            font-size: 0.75rem; font-weight: 700; color: #4f46e5;
            background: #eef2ff; border-radius: 999px;
            padding: 0.25rem 0.75rem; margin-bottom: 0.75rem;
        }
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
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h1>Reset Password</h1>
        <p>Verify your identity by answering your security question, then set a new password.</p>
        <ul class="auth-hero-features">
            <li><i class="bi bi-envelope-check-fill"></i> Enter your registered email</li>
            <li><i class="bi bi-patch-question-fill"></i> Pick your security question</li>
            <li><i class="bi bi-chat-left-text-fill"></i> Answer it correctly</li>
            <li><i class="bi bi-key-fill"></i> Set your new password</li>
        </ul>
    </div>

    <!-- Right: Form Panel -->
    <div class="auth-form-panel">
        <div class="auth-card">

            <div class="auth-card-header">
                <div class="step-badge">
                    <i class="bi bi-<?php echo $step === 1 ? '1' : '2'; ?>-circle-fill"></i>
                    Step <?php echo $step; ?> of 2
                </div>
                <h2>Forgot Password?</h2>
                <p><?php echo $step === 1
                    ? 'Enter your registered email address to begin'
                    : 'Select your security question and answer it'; ?></p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $isError ? 'danger' : 'success'; ?>">
                <i class="bi bi-<?php echo $isError ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <!-- ── Step 1: Email ── -->
            <form method="post" action="forgot-password.php">
                <input type="hidden" name="step" value="email">
                <div class="mb-4">
                    <label class="form-label" for="fpEmail">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" id="fpEmail"
                               placeholder="you@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required autofocus>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-arrow-right-circle me-2"></i>Continue
                    </button>
                </div>
            </form>

            <?php else: ?>
            <!-- ── Step 2: Security Question ── -->
            <div class="alert alert-light border small py-2">
                <i class="bi bi-person-circle me-1"></i>
                Resetting password for <strong><?php echo htmlspecialchars($fpEmail); ?></strong>
                &mdash; <a href="forgot-password.php?restart=1">use a different email</a>
            </div>

            <form method="post" action="forgot-password.php" id="verifyForm">
                <input type="hidden" name="step" value="verify">
                <div class="mb-3">
                    <label class="form-label" for="fpQuestion">Your Security Question</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-patch-question"></i></span>
                        <select class="form-select" name="security_question" id="fpQuestion" required <?php echo $lockRemaining > 0 ? 'disabled' : ''; ?>>
                            <option value="" disabled selected>Pick the question you chose…</option>
                            <?php foreach (SECURITY_QUESTIONS as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-text text-muted">You must pick the exact question you selected on your account.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="fpAnswer">Your Answer</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-chat-left-text"></i></span>
                        <input type="text" class="form-control" name="security_answer" id="fpAnswer"
                               placeholder="Your answer" maxlength="150" autocomplete="off"
                               required <?php echo $lockRemaining > 0 ? 'disabled' : ''; ?>>
                    </div>
                    <div class="form-text text-muted">Not case-sensitive.</div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg" id="verifyBtn" <?php echo $lockRemaining > 0 ? 'disabled' : ''; ?>>
                        <i class="bi bi-shield-check me-2"></i>Verify &amp; Reset Password
                    </button>
                </div>
            </form>

            <?php if ($lockRemaining > 0): ?>
            <div class="alert alert-warning d-flex align-items-center gap-2 mt-3 py-2">
                <i class="bi bi-lock-fill fs-5 flex-shrink-0"></i>
                <div class="small">Locked. You can try again in <strong id="fpCountdown"><?php echo $lockRemaining; ?></strong>s.</div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="auth-footer-link">
                Remembered your password? <a href="login.php">Sign in here</a>
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
document.querySelectorAll('form[action="forgot-password.php"]').forEach(form => {
    form.addEventListener('submit', function () {
        const btn = this.querySelector('[type="submit"]');
        if (btn && !btn.disabled) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Please wait…';
            btn.disabled = true;
        }
    });
});

<?php if ($lockRemaining > 0): ?>
// Countdown until the lockout expires, then re-enable the form
(function () {
    let remaining = <?php echo (int)$lockRemaining; ?>;
    const el = document.getElementById('fpCountdown');
    const timer = setInterval(() => {
        remaining--;
        if (el) el.textContent = remaining;
        if (remaining <= 0) {
            clearInterval(timer);
            ['fpQuestion', 'fpAnswer', 'verifyBtn'].forEach(id => {
                const f = document.getElementById(id);
                if (f) f.disabled = false;
            });
            el?.closest('.alert')?.classList.add('d-none');
        }
    }, 1000);
})();
<?php endif; ?>
</script>
</body>
</html>
