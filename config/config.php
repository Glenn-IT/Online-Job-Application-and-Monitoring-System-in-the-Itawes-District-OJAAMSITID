<?php

// ── Load .env file ──────────────────────────────────────────
// Simple parser: reads KEY=VALUE lines, skips # comments and blanks.
// No Composer dependency needed.
$_envFile = __DIR__ . '/../.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        $_line = trim($_line);
        if ($_line === '' || $_line[0] === '#') continue;
        if (strpos($_line, '=') === false) continue;
        [$_key, $_val] = explode('=', $_line, 2);
        $_key = trim($_key);
        $_val = trim($_val);
        if ($_key !== '' && getenv($_key) === false) {
            putenv("{$_key}={$_val}");
            $_ENV[$_key] = $_val;
        }
    }
}
unset($_envFile, $_line, $_key, $_val);

// ── Database Credentials ────────────────────────────────────
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'ojams_db');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// ── App Settings ────────────────────────────────────────────
define('APP_NAME',    getenv('APP_NAME')  ?: 'OJAMS');
define('APP_VERSION', '2.0.0');

// ── URL / Path Helpers ──────────────────────────────────────
// Use .env BASE_URL if set; otherwise derive from the current request so the
// redirect after login works regardless of which port XAMPP is running on.
if (getenv('BASE_URL') !== false && getenv('BASE_URL') !== '') {
    define('BASE_URL', getenv('BASE_URL'));
} elseif (isset($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . '/OJAMS');
} else {
    define('BASE_URL', 'http://localhost/OJAMS');
}

// ── Logging ─────────────────────────────────────────────────
define('LOG_FILE', __DIR__ . '/../logs/app.log');

// ── Session Name ────────────────────────────────────────────
define('SESSION_NAME', 'ojams_session');

// ── Password Hashing ────────────────────────────────────────
define('BCRYPT_COST', 12);

// ── Timezone ────────────────────────────────────────────────
// Single source of truth — used by both PHP and MySQL (via db.php).
define('APP_TIMEZONE',        'Asia/Manila');
define('APP_TIMEZONE_OFFSET', '+08:00');       // MySQL SET time_zone format

date_default_timezone_set(APP_TIMEZONE);

// ── Pagination ──────────────────────────────────────────────
define('PER_PAGE_USER',  12);   // Job cards per page on Browse Jobs
define('PER_PAGE_ADMIN', 15);   // Rows per page on admin tables

// ── Login brute-force protection ────────────────────────────
define('LOGIN_MAX_ATTEMPTS',    5);
define('LOGIN_LOCKOUT_SECONDS', 30);

// ── Password-change throttle ────────────────────────────────
define('PW_MAX_ATTEMPTS',    3);
define('PW_LOCKOUT_SECONDS', 30);

// ── Forgot-password security questions ──────────────────────
// Users pick one at registration (or in Profile Settings) and must
// select the correct question AND answer it to reset their password.
define('SECURITY_QUESTIONS', [
    'What is the name of your first pet?',
    'What is your mother\'s maiden name?',
    'What city were you born in?',
    'What is the name of your elementary school?',
    'What was the name of your childhood best friend?',
    'What is your favorite food?',
]);

// Forgot-password attempt throttle (per session)
define('SQ_MAX_ATTEMPTS',    5);
define('SQ_LOCKOUT_SECONDS', 60);
