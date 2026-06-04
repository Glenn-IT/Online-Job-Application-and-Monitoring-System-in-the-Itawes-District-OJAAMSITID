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

// Returns the currently logged-in user array, or null.
function getCurrentUser(): ?array {
    return $_SESSION['ojams_user'] ?? null;
}

// Returns true if a user is logged in via PHP session.
function isLoggedIn(): bool {
    return isset($_SESSION['ojams_user']);
}

// Returns true if the logged-in user is an admin.
function isAdmin(): bool {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// Returns true if the logged-in user is a regular user.
function isUser(): bool {
    $user = getCurrentUser();
    return $user && $user['role'] === 'user';
}

// Redirect to login if not logged in, or to user page if not admin.
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

// Redirect to login if not logged in, or to admin page if not a user.
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

// Appends a timestamped error entry to the app log file.
function logError(string $message, array $context = []): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($context) {
        $line .= ' ' . json_encode($context);
    }
    error_log($line . PHP_EOL, 3, LOG_FILE);
}

// Generates a CSRF token for the current session (creates once, reuses after).
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validates the submitted CSRF token against the session token.
function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
