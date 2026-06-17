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

// Constants come from config/config.php via auth.php
// LOGIN_LOGIN_MAX_ATTEMPTS and LOGIN_LOGIN_LOCKOUT_SECONDS are defined there.

// Get client IP (support reverse proxies)
$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['HTTP_X_REAL_IP']
    ?? $_SERVER['REMOTE_ADDR']
    ?? '0.0.0.0';
// Use only the first IP if a comma-separated list is forwarded
$clientIp = trim(explode(',', $clientIp)[0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // ── Check lockout ────────────────────────────────────────
    $lockStmt = $pdo->prepare("
        SELECT attempts, last_attempt_at
        FROM login_attempts
        WHERE ip = ? AND last_attempt_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $lockStmt->execute([$clientIp, LOGIN_LOCKOUT_SECONDS]);
    $lockRow = $lockStmt->fetch();

    if ($lockRow && $lockRow['attempts'] >= LOGIN_MAX_ATTEMPTS) {
        $retryAfter = (new DateTime($lockRow['last_attempt_at']))
            ->modify('+' . LOGIN_LOCKOUT_SECONDS . ' seconds');
        $secsLeft = max(1, (int)ceil($retryAfter->getTimestamp() - time()));
        $error = "Too many failed attempts. Please try again in {$secsLeft} second(s).";
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash']) || (isset($user['is_active']) && !$user['is_active'])) {
            // ── Record failed attempt ────────────────────────
            $pdo->prepare("
                INSERT INTO login_attempts (ip, attempts)
                VALUES (?, 1)
                ON DUPLICATE KEY UPDATE
                    attempts        = IF(last_attempt_at > DATE_SUB(NOW(), INTERVAL ? SECOND), attempts + 1, 1),
                    last_attempt_at = NOW()
            ")->execute([$clientIp, LOGIN_LOCKOUT_SECONDS]);

            // Warn user how many tries remain
            $attStmt = $pdo->prepare("SELECT attempts FROM login_attempts WHERE ip = ?");
            $attStmt->execute([$clientIp]);
            $attRow   = $attStmt->fetch();
            $attempts = $attRow['attempts'] ?? 1;
            $remaining = LOGIN_MAX_ATTEMPTS - $attempts;

            if ($remaining > 0) {
                $error = "Invalid email or password. {$remaining} attempt(s) remaining before lockout.";
            } else {
                $error = "Too many failed attempts. Please try again in " . LOGIN_LOCKOUT_SECONDS . " second(s).";
            }
        } else {
            // ── Clear failed attempts on success ─────────────
            $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$clientIp]);

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']); // force fresh CSRF token for new session

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
            <i class="bi bi-briefcase-fill"></i>
        </div>
        <h1>OJAMS</h1>
        <p>Online Job Application and Monitoring System in the Itawes District — track, manage, and grow your career.</p>
        <ul class="auth-hero-features">
            <li><i class="bi bi-check-circle-fill"></i> Browse open job listings instantly</li>
            <li><i class="bi bi-check-circle-fill"></i> Track your application status live</li>
            <li><i class="bi bi-check-circle-fill"></i> Manage your professional profile</li>
            <li><i class="bi bi-shield-lock-fill"></i> Secure &amp; privacy-first platform</li>
        </ul>
    </div>

    <!-- Right: Form Panel -->
    <div class="auth-form-panel">
        <div class="auth-card">

            <div class="auth-card-header">
                <h2>Welcome back</h2>
                <p>Sign in to your OJAMS account</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form id="loginForm" method="post" action="login.php">
                <div class="mb-3">
                    <label class="form-label" for="loginEmail">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" name="email" id="loginEmail"
                               placeholder="you@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0" for="loginPassword">Password</label>
                        <a href="forgot-password.php" class="small text-primary text-decoration-none">Forgot password?</a>
                    </div>
                    <div class="input-group mt-1">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" id="loginPassword"
                               placeholder="Enter your password" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleLoginPass()">
                            <i class="bi bi-eye" id="loginEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </div>
            </form>

            <div class="auth-footer-link">
                Don't have an account? <a href="register.php">Create one free</a>
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
function toggleLoginPass() {
    const inp  = document.getElementById('loginPassword');
    const icon = document.getElementById('loginEyeIcon');
    if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else                         { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}
document.getElementById('loginForm')?.addEventListener('submit', function () {
    const btn = this.querySelector('[type="submit"]');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Signing in…';
        btn.disabled = true;
    }
});
</script>
</body>
</html>
