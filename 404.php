<?php
// Sent as a proper 404 — also used by ErrorDocument 404
http_response_code(404);

// Try to load BASE_URL for the back-link; fall back gracefully if DB is unavailable.
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
    <title>404 — Page Not Found | OJAMS</title>
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
            background: linear-gradient(135deg, #ede9fe, #c7d2fe);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.8rem;
            color: #4338ca;
        }
        .error-code {
            font-size: 5rem;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #4338ca, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
        .breadcrumb-hint {
            font-size: 0.78rem;
            color: #94a3b8;
            background: #f8fafc;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin-bottom: 1.5rem;
            word-break: break-all;
        }
    </style>
</head>
<body>

<div class="error-card">
    <div class="error-icon-wrap">
        <i class="bi bi-map"></i>
    </div>

    <div class="error-code">404</div>
    <div class="error-title">Page Not Found</div>

    <p class="error-desc">
        The page you're looking for doesn't exist or may have been moved.
        Double-check the URL or head back to a page you know.
    </p>

    <?php if (!empty($_SERVER['REQUEST_URI'])): ?>
    <div class="breadcrumb-hint">
        <i class="bi bi-link-45deg me-1"></i><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>
    </div>
    <?php endif; ?>

    <a href="<?php echo htmlspecialchars($baseUrl); ?>/index.php" class="btn-home">
        <i class="bi bi-house-fill"></i> Go to Home
    </a>

    <br>

    <a href="javascript:history.back()" class="btn-back">
        <i class="bi bi-arrow-left"></i> Go back
    </a>

    <div class="divider"></div>

    <p style="font-size:0.78rem; color:#94a3b8; margin:0;">
        &copy; 2026 OJAMS &mdash; If this keeps happening, contact the administrator.
    </p>
</div>

</body>
</html>
