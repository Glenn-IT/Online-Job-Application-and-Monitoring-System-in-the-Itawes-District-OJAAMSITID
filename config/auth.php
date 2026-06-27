<?php
// ============================================================
// OJAMS - Authentication & Session Guard Helpers
// ============================================================
// Include at the TOP of every protected page:
//   require_once __DIR__ . '/../../config/auth.php';  (from pages/*/)
//   require_once __DIR__ . '/../config/auth.php';     (from root pages)
//
// db.php is already included here (starts the session too).
// ============================================================

require_once __DIR__ . '/db.php';

function getCurrentUser(): ?array {
    return $_SESSION['ojams_user'] ?? null;
}

function isLoggedIn(): bool {
    return isset($_SESSION['ojams_user']);
}

function isAdmin(): bool {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function isUser(): bool {
    $user = getCurrentUser();
    return $user && $user['role'] === 'user';
}

function requireAdmin(
    string $loginPath     = BASE_URL . '/login.php',
    string $wrongRolePath = BASE_URL . '/pages/user/browse-jobs.php'
): void {
    if (!isLoggedIn()) {
        header('Location: ' . $loginPath);
        exit;
    }
    if (!isAdmin()) {
        header('Location: ' . $wrongRolePath);
        exit;
    }
}

function requireUser(
    string $loginPath     = BASE_URL . '/login.php',
    string $wrongRolePath = BASE_URL . '/pages/admin/dashboard.php'
): void {
    if (!isLoggedIn()) {
        header('Location: ' . $loginPath);
        exit;
    }
    if (!isUser()) {
        header('Location: ' . $wrongRolePath);
        exit;
    }
}

// Generic guard - redirect to login if not logged in.
function requireLogin(
    string $loginPath = BASE_URL . '/login.php'
): void {
    if (!isLoggedIn()) {
        header('Location: ' . $loginPath);
        exit;
    }
}

function logError(string $message, array $context = []): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($context) {
        $line .= ' ' . json_encode($context);
    }
    error_log($line . PHP_EOL, 3, LOG_FILE);
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function rateLimit(string $endpoint, int $maxHits = 60, int $windowSec = 60): void {
    global $pdo;

    $ip  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip  = trim(explode(',', $ip)[0]);
    $key = substr($ip . '::' . $endpoint, 0, 120);

    $stmt = $pdo->prepare("SELECT hits, window_start FROM rate_limits WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    $now = time();

    if (!$row || ($now - strtotime($row['window_start'])) >= $windowSec) {
        $pdo->prepare(
            "INSERT INTO rate_limits (`key`, hits, window_start) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE hits = 1, window_start = NOW()"
        )->execute([$key]);
        return;
    }

    if ((int)$row['hits'] >= $maxHits) {
        $retryAfter = $windowSec - ($now - strtotime($row['window_start']));
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        echo json_encode([
            'success' => false,
            'message' => "Too many requests. Please wait {$retryAfter} second(s) before trying again.",
        ]);
        exit;
    }

    $pdo->prepare("UPDATE rate_limits SET hits = hits + 1 WHERE `key` = ?")->execute([$key]);
}
