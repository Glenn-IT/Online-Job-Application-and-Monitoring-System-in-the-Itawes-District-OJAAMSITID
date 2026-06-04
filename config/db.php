<?php
// ============================================================
// OJAMS - PDO Database Connection
// ============================================================
// Provides a single shared $pdo instance.
// Include this file in any PHP page or handler that needs DB.
//
// Usage:
//   require_once __DIR__ . '/../config/db.php';
//   // $pdo is now available
// ============================================================

require_once __DIR__ . '/config.php';

// Start the named session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // Log the real error server-side before showing a safe message to the user
    $logFile = __DIR__ . '/../logs/app.log';
    $logLine = '[' . date('Y-m-d H:i:s') . '] DB connection failed: ' . $e->getMessage() . PHP_EOL;
    error_log($logLine, 3, $logFile);

    http_response_code(500);
    die(
        '<div style="font-family:sans-serif;padding:40px;max-width:600px;margin:auto;">'
      . '<h2 style="color:#dc3545;">Database Connection Failed</h2>'
      . '<p>OJAMS could not connect to the database. Please check your configuration.</p>'
      . '<p style="color:#6c757d;font-size:.85rem;">Check <code>logs/app.log</code> for details.</p>'
      . '<p>Verify credentials in <code>.env</code> and ensure MySQL is running.</p>'
      . '</div>'
    );
}
