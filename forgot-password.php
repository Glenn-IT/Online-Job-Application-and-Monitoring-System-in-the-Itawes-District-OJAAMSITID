<?php
require_once __DIR__ . '/components/under-construction.php';

require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin()
        ? BASE_URL . '/pages/admin/dashboard.php'
        : BASE_URL . '/pages/user/browse-jobs.php'));
    exit;
}

$message = '';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $isError  = true;
    } else {
        // Look up user — but never reveal whether the email exists
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Remove any existing token for this address (one active token at a time)
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $pdo->prepare(
                "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)"
            )->execute([$email, $token, $expires]);

            $resetLink = BASE_URL . '/reset-password.php?token=' . $token;

            $subject = APP_NAME . ' — Password Reset Request';
            $body    = "Hello {$user['full_name']},\r\n\r\n"
                     . "You requested a password reset for your OJAMS account.\r\n\r\n"
                     . "Click the link below to reset your password (valid for 1 hour):\r\n"
                     . $resetLink . "\r\n\r\n"
                     . "If you did not request this, you can safely ignore this email.\r\n\r\n"
                     . "— The OJAMS Team";
            $headers = "From: noreply@ojams.com\r\nX-Mailer: PHP/" . phpversion();

            @mail($email, $subject, $body, $headers);

            // Development fallback: write the link to the app log
            logError("[FORGOT PASSWORD] Reset link for {$email}: {$resetLink}", []);
        }

        // Always show the same message — do not reveal whether the email is registered
        $message = 'If that email address is registered, a reset link has been sent. Check your inbox (and spam folder).';
    }
}

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
        <p>Enter your registered email and we'll send you a link to reset your password.</p>
        <ul class="auth-hero-features">
            <li><i class="bi bi-envelope-check-fill"></i> Reset link sent to your inbox</li>
            <li><i class="bi bi-clock-fill"></i> Link expires in 1 hour</li>
            <li><i class="bi bi-shield-fill-check"></i> Secure one-time token</li>
            <li><i class="bi bi-arrow-repeat"></i> Request a new link anytime</li>
        </ul>
    </div>

    <!-- Right: Form Panel -->
    <div class="auth-form-panel">
        <div class="auth-card">

            <div class="auth-card-header">
                <h2>Forgot Password?</h2>
                <p>We'll email you a secure reset link</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $isError ? 'danger' : 'success'; ?>">
                <i class="bi bi-<?php echo $isError ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if (!$message || $isError): ?>
            <form method="post" action="forgot-password.php">
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
                        <i class="bi bi-send me-2"></i>Send Reset Link
                    </button>
                </div>
            </form>
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
document.querySelector('form[action="forgot-password.php"]')?.addEventListener('submit', function () {
    const btn = this.querySelector('[type="submit"]');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Sending…';
        btn.disabled = true;
    }
});
</script>
</body>
</html>
