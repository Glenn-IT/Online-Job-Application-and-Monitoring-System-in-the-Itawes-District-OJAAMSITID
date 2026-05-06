<?php

$configPath = __DIR__ . '/config/db.php';
require_once $configPath;

// Destroy PHP session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Redirect to login
header('Location: ' . BASE_URL . '/login.php');
exit;
