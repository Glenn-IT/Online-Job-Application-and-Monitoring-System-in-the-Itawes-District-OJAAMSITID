<?php
// OJAMS - Shared HTML Head
// config/db.php (which pulls in config.php) is included here
// so every page that uses this layout gets $pdo and session automatically.
// Pages set $configPath before including this layout:
//   $configPath = __DIR__ . '/../../config/auth.php';  (from pages/*/)
//   $configPath = __DIR__ . '/../config/auth.php';     (from root pages)
if (!defined('DB_HOST')) {
    $configPath = $configPath ?? (__DIR__ . '/../config/db.php');
    require_once $configPath;
}
$_csrfToken = function_exists('generateCsrfToken') ? generateCsrfToken() : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'OJAMS - Job Monitoring System'; ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_csrfToken, ENT_QUOTES); ?>">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="<?php echo $basePath ?? ''; ?>assets/css/style.css" rel="stylesheet">
    <!-- Custom Scripts (deferred so DOM is ready) -->
    <script src="<?php echo $basePath ?? ''; ?>assets/js/script.js" defer></script>
</head>
<body>
