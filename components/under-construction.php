<?php
// v1.01 gate — outputs a full page and exits.
// Remove this file's require_once from a page when that page is ready to present.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJAMS — Under Construction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1.5rem;
        }
        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 3rem 2.5rem;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
        }
        .icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 22px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #4338ca;
            margin: 0 auto 1.5rem;
        }
        h1 { font-size: 1.75rem; font-weight: 900; color: #1e293b; margin-bottom: 0.5rem; }
        .badge-version {
            display: inline-block;
            background: #ede9fe;
            color: #4338ca;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            margin-bottom: 1.25rem;
            letter-spacing: 0.05em;
        }
        p.desc {
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .btn-login {
            background: #4338ca;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.7rem 1.75rem;
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }
        .btn-login:hover { background: #3730a3; color: #fff; }
        .footer-note {
            margin-top: 2rem;
            font-size: 0.72rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <i class="bi bi-cone-striped"></i>
        </div>
        <h1>Under Construction</h1>
        <div class="badge-version">Version 1.02</div>
        <p class="desc">
            This page is not yet available in the current version.<br>
            It will be unlocked in an upcoming presentation.
        </p>
        <a href="<?php echo str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/', strpos($_SERVER['PHP_SELF'], '/OJAMS/') + 7)); ?>login.php" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Go to Login
        </a>
        <div class="footer-note">&copy; 2026 OJAMS &mdash; Prototype v1.02</div>
    </div>
</body>
</html>
<?php exit; ?>
