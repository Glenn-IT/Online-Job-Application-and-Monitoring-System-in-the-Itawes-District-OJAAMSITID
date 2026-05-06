<?php


// ── Database Credentials ────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'ojams_db');
define('DB_USER', 'root');       // Change for production
define('DB_PASS', '');           // Change for production
define('DB_CHARSET', 'utf8mb4');

// ── App Settings ────────────────────────────────────────────
define('APP_NAME',    'OJAMS');
define('APP_VERSION', '2.0.0');  // 1.x = localStorage prototype, 2.x = MySQL backend

// ── URL / Path Helpers ──────────────────────────────────────
// Base URL used for absolute redirects (no trailing slash)
define('BASE_URL', 'http://localhost/OJAMS');

// ── Session Name ────────────────────────────────────────────
// Avoids collisions if multiple projects run on the same localhost
define('SESSION_NAME', 'ojams_session');

// ── Password Hashing ────────────────────────────────────────
// Cost factor for bcrypt — increase to 12+ in production
define('BCRYPT_COST', 12);

// ── Timezone ────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');
