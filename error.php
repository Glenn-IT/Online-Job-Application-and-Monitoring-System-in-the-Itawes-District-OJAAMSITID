<?php
// Generic error page for 403, 500, and other HTTP error codes.
// Apache passes the status via REDIRECT_STATUS; we also accept ?code= as fallback.
$code = (int)($_SERVER['REDIRECT_STATUS'] ?? $_GET['code'] ?? 500);

// Only handle known error codes; default everything else to 500
$allowed = [400, 403, 405, 500, 503];
if (!in_array($code, $allowed, true)) $code = 500;

http_response_code($code);

$errors = [
    400 => [
        'title' => 'Bad Request',
        'desc'  => 'The server could not understand the request. Please check what you submitted and try again.',
        'icon'  => 'bi-slash-circle',
        'color' => '#f59e0b',
        'bg'    => '#fef3c7, #fde68a',
    ],
    403 => [
        'title' => 'Access Denied',
        'desc'  => "You don't have permission to access this page. If you believe this is a mistake, please contact the administrator.",
        'icon'  => 'bi-shield-lock-fill',
        'color' => '#dc2626',
        'bg'    => '#fee2e2, #fecaca',
    ],
    405 => [
        'title' => 'Method Not Allowed',
        'desc'  => 'The HTTP method used is not allowed for this endpoint.',
        'icon'  => 'bi-x-octagon-fill',
        'color' => '#7c3aed',
        'bg'    => '#ede9fe, #ddd6fe',
    ],
    500 => [
        'title' => 'Internal Server Error',
        'desc'  => 'Something went wrong on our end. The issue has been logged. Please try again in a moment or contact the administrator.',
        'icon'  => 'bi-exclamation-triangle-fill',
        'color' => '#dc2626',
        'bg'    => '#fee2e2, #fecaca',
    ],
    503 => [
        'title' => 'Service Unavailable',
        'desc'  => 'The server is temporarily unable to handle your request. Please try again in a few minutes.',
        'icon'  => 'bi-cloud-slash-fill',
        'color' => '#0891b2',
        'bg'    => '#cffafe, #a5f3fc',
    ],
];

$e = $errors[$code];

// Try to load BASE_URL; fall back gracefully if DB/config is unavailable (e.g. on a 500)
$baseUrl = '/OJAMS';
$configPath = __DIR__ . '/config/config.php';
if (file_exists($configPath)) {
    @include_once $configPath;
    if (defined('BASE_URL')) $baseUrl = BASE_URL;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $code; ?> — <?php echo htmlspecialchars($e['title']); ?> | OJAMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 2rem;
        }
        .error-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 3.5rem 3rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
        }
        .error-icon-wrap {
            width: 96px; height: 96px;
            border-radius: 50%;
            background: linear-gradient(135deg, <?php echo $e['bg']; ?>);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.8rem;
            color: <?php echo $e['color']; ?>;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 900;
            line-height: 1;
            color: <?php echo $e['color']; ?>;
            margin-bottom: 0.5rem;
        }
        .error-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }
        .error-desc {
            color: #64748b;
            font-size: 0.925rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .btn-home {
            background: linear-gradient(135deg, #4338ca, #6366f1);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.2s;
        }
        .btn-home:hover { opacity: 0.88; color: #fff; }
        .btn-back {
            color: #64748b;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 1rem;
        }
        .btn-back:hover { color: #4338ca; }
        .divider { border-top: 1px solid #e2e8f0; margin: 1.75rem 0; }
    </style>
</head>
<body>

<div class="error-card">
    <div class="error-icon-wrap">
        <i class="bi <?php echo $e['icon']; ?>"></i>
    </div>

    <div class="error-code"><?php echo $code; ?></div>
    <div class="error-title"><?php echo htmlspecialchars($e['title']); ?></div>

    <p class="error-desc"><?php echo htmlspecialchars($e['desc']); ?></p>

    <a href="<?php echo htmlspecialchars($baseUrl); ?>/index.php" class="btn-home">
        <i class="bi bi-house-fill"></i> Go to Home
    </a>

    <br>

    <a href="javascript:history.back()" class="btn-back">
        <i class="bi bi-arrow-left"></i> Go back
    </a>

    <div class="divider"></div>

    <p style="font-size:0.78rem; color:#94a3b8; margin:0;">
        &copy; 2026 OJAMS &mdash; Error <?php echo $code; ?> &mdash; If this keeps happening, contact the administrator.
    </p>
</div>

</body>
</html>
