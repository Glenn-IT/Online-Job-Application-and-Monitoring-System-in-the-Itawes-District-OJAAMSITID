<?php
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin()
        ? BASE_URL . '/pages/admin/dashboard.php'
        : BASE_URL . '/pages/user/browse-jobs.php'));
    exit;
}

$token      = trim($_GET['token'] ?? '');
$message    = '';
$isError    = false;
$validToken = false;
$tokenEmail = '';
$done       = false;

if ($token !== '') {
    $stmt = $pdo->prepare(
        "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if ($row) {
        $validToken = true;
        $tokenEmail = $row['email'];
    } else {
        $message = 'This reset link is invalid or has already expired. Please request a new one.';
        $isError  = true;
    }
} else {
    $message = 'No reset token provided.';
    $isError  = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPw   = $_POST['password']  ?? '';
    $confirm = $_POST['confirm']   ?? '';

    if (strlen($newPw) < 6) {
        $message = 'Password must be at least 6 characters.';
        $isError  = true;
    } elseif ($newPw !== $confirm) {
        $message = 'Passwords do not match.';
        $isError  = true;
    } else {
        $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?")->execute([$hash, $tokenEmail]);
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$tokenEmail]);

        $done    = true;
        $message = 'Your password has been reset successfully! Redirecting to login…';
        header('Refresh: 2; url=' . BASE_URL . '/login.php');
    }
}

$pageTitle = 'OJAMS - Reset Password';
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
        .password-strength { height: 4px; border-radius: 2px; transition: all 0.3s; margin-top: 6px; background: #e2e8f0; }
        .strength-weak   { background: #ef4444; width: 33%;  }
        .strength-medium { background: #f59e0b; width: 66%;  }
        .strength-strong { background: #22c55e; width: 100%; }
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
            <i class="bi bi-key-fill"></i>
        </div>
        <h1>New Password</h1>
        <p>Choose a strong password to secure your OJAMS account.</p>
        <ul class="auth-hero-features">
            <li><i class="bi bi-check-circle-fill"></i> At least 6 characters</li>
            <li><i class="bi bi-shield-lock-fill"></i> Stored with bcrypt encryption</li>
            <li><i class="bi bi-arrow-repeat"></i> Old password immediately invalidated</li>
            <li><i class="bi bi-clock-history"></i> Token is single-use only</li>
        </ul>
    </div>

    <!-- Right: Form Panel -->
    <div class="auth-form-panel">
        <div class="auth-card">

            <div class="auth-card-header">
                <h2>Set New Password</h2>
                <p>Enter and confirm your new password below</p>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $isError ? 'danger' : 'success'; ?>">
                <i class="bi bi-<?php echo $isError ? 'exclamation-circle' : 'check-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if ($validToken && !$done): ?>
            <form method="post" action="reset-password.php?token=<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">
                <div class="mb-3">
                    <label class="form-label" for="rpPassword">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" id="rpPassword"
                               placeholder="At least 6 characters" required minlength="6" autofocus
                               oninput="checkStrength(this.value)">
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePass('rpPassword','rpEye')">
                            <i class="bi bi-eye" id="rpEye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="strengthBar"></div>
                    <small class="text-muted" id="strengthLabel"></small>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="rpConfirm">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" name="confirm" id="rpConfirm"
                               placeholder="Repeat your new password" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePass('rpConfirm','rpEye2')">
                            <i class="bi bi-eye" id="rpEye2"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-lg me-2"></i>Reset Password
                    </button>
                </div>
            </form>
            <?php elseif ($isError): ?>
            <div class="d-grid">
                <a href="forgot-password.php" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-arrow-repeat me-2"></i>Request a New Link
                </a>
            </div>
            <?php endif; ?>

            <div class="auth-footer-link">
                <a href="login.php"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
            </div>

            <div class="copyright-note">&copy; 2026 OJAMS &mdash; Prototype Version</div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (inp.type === 'password') { inp.type = 'text';     icon.className = 'bi bi-eye-slash'; }
    else                         { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}

document.querySelector('form[method="post"]')?.addEventListener('submit', function () {
    const btn = this.querySelector('[type="submit"]');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Resetting…';
        btn.disabled = true;
    }
});
function checkStrength(val) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    if (!bar) return;
    if (val.length < 6) {
        bar.className   = 'password-strength';
        label.textContent = '';
        return;
    }
    const strong = /[A-Z]/.test(val) && /[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val) && val.length >= 10;
    const medium = val.length >= 8 && (/[A-Z]/.test(val) || /[0-9]/.test(val));
    if (strong) {
        bar.className   = 'password-strength strength-strong';
        label.textContent = 'Strong';
        label.style.color = '#22c55e';
    } else if (medium) {
        bar.className   = 'password-strength strength-medium';
        label.textContent = 'Medium';
        label.style.color = '#f59e0b';
    } else {
        bar.className   = 'password-strength strength-weak';
        label.textContent = 'Weak';
        label.style.color = '#ef4444';
    }
}
</script>
</body>
</html>
