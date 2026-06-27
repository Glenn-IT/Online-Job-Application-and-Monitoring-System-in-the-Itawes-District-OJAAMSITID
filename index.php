<?php
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['ojams_user'])) {
    $role = $_SESSION['ojams_user']['role'] ?? 'user';
    header('Location: ' . ($role === 'admin'
        ? BASE_URL . '/pages/admin/dashboard.php'
        : BASE_URL . '/pages/user/browse-jobs.php'));
    exit;
}

$pageTitle = 'OJAMS – Job Opportunities';
$basePath  = '';

// ── Fetch open jobs ──────────────────────────────────────────────
$search    = trim($_GET['search'] ?? '');
$params    = [];
$whereSQL  = "WHERE j.status = 'Open'";
if ($search !== '') {
    $whereSQL .= " AND (j.title LIKE ? OR j.company LIKE ?)";
    $params[]  = "%{$search}%";
    $params[]  = "%{$search}%";
}
$stmt = $pdo->prepare("SELECT j.* FROM jobs j {$whereSQL} ORDER BY j.date_posted DESC");
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$totalOpen  = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='Open'")->fetchColumn();
$totalJobs  = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$totalApps  = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="assets/js/script.js" defer></script>
    <style>
        /* ── Public landing overrides ───────────────────── */
        body { background: #f8f9fc; }

        .public-nav {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            box-shadow: 0 2px 12px rgba(79,70,229,.25);
        }
        .public-nav .navbar-brand {
            font-weight: 800;
            font-size: 1.3rem;
            color: #fff !important;
            letter-spacing: -.3px;
        }
        .public-nav .nav-link { color: rgba(255,255,255,.85) !important; font-weight: 500; }
        .public-nav .nav-link:hover { color: #fff !important; }

        /* Hero */
        .hero-section {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 60%, #1e1b4b 100%);
            color: #fff;
            padding: 80px 0 60px;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 400px; height: 400px;
            background: rgba(255,255,255,.05);
            border-radius: 50%;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,.04);
            border-radius: 50%;
        }
        .hero-title {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.5px;
        }
        .hero-sub { font-size: 1.1rem; opacity: .85; max-width: 500px; }

        .hero-search .input-group {
            box-shadow: 0 8px 32px rgba(0,0,0,.2);
            border-radius: 12px;
            overflow: hidden;
        }
        .hero-search .form-control {
            border: none;
            padding: .85rem 1.2rem;
            font-size: 1rem;
        }
        .hero-search .btn-search {
            background: #f59e0b;
            color: #fff;
            border: none;
            padding: .85rem 1.6rem;
            font-weight: 600;
        }
        .hero-search .btn-search:hover { background: #d97706; color: #fff; }

        /* Stat chips */
        .stat-chip {
            background: rgba(255,255,255,.15);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 50px;
            padding: .45rem 1.1rem;
            font-size: .88rem;
            font-weight: 600;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }

        /* Section */
        .jobs-section { padding: 48px 0 64px; }
        .section-header h3 {
            font-weight: 800;
            font-size: 1.65rem;
            color: #1e1b4b;
        }

        /* Card */
        .pub-job-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(79,70,229,.08);
            transition: transform .2s ease, box-shadow .2s ease;
            overflow: hidden;
        }
        .pub-job-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 32px rgba(79,70,229,.16);
        }
        .pub-job-card .card-header-stripe {
            height: 5px;
            background: linear-gradient(90deg, #4f46e5, #818cf8);
        }
        .pub-job-card .card-body { padding: 1.4rem; }
        .pub-job-card .job-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e1b4b;
            margin-bottom: .2rem;
        }
        .pub-job-card .job-company {
            color: #64748b;
            font-size: .9rem;
            font-weight: 500;
        }
        .pub-job-card .job-desc {
            color: #475569;
            font-size: .88rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .pub-job-card .job-qual {
            color: #64748b;
            font-size: .82rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .pub-job-card .divider { border-color: #f1f5f9; margin: .9rem 0; }

        .btn-apply {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            padding: .6rem 0;
            width: 100%;
            transition: opacity .2s;
        }
        .btn-apply:hover { opacity: .88; color: #fff; }

        /* Auth prompt modal */
        .auth-modal .modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }
        .auth-modal-header {
            background: linear-gradient(135deg, #4f46e5, #3730a3);
            color: #fff;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
        }
        .auth-modal-header .modal-icon {
            width: 64px; height: 64px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
        }
        .auth-modal-header h4 { font-weight: 700; margin: 0; }
        .auth-modal-header p { opacity: .85; font-size: .92rem; margin: .4rem 0 0; }

        .auth-btn-login {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff; border: none; border-radius: 12px;
            padding: .85rem; font-weight: 700; font-size: 1rem;
            width: 100%; transition: opacity .2s;
        }
        .auth-btn-login:hover { opacity: .88; color: #fff; }
        .auth-btn-register {
            background: #fff; color: #4f46e5;
            border: 2px solid #4f46e5; border-radius: 12px;
            padding: .8rem; font-weight: 700; font-size: 1rem;
            width: 100%; transition: background .2s, color .2s;
        }
        .auth-btn-register:hover { background: #4f46e5; color: #fff; }

        /* Empty state */
        .empty-state { padding: 64px 0; }
        .empty-state .empty-icon {
            width: 88px; height: 88px;
            background: linear-gradient(135deg, #ede9fe, #e0e7ff);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; color: #4f46e5;
            margin: 0 auto 1.2rem;
        }

        /* Footer */
        .public-footer {
            background: #1e1b4b;
            color: rgba(255,255,255,.6);
            padding: 2rem 0;
            font-size: .88rem;
            text-align: center;
        }
        .public-footer a { color: rgba(255,255,255,.75); text-decoration: none; }
        .public-footer a:hover { color: #fff; }
    </style>
</head>
<body>

<!-- ── Navbar ──────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg public-nav sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-briefcase-fill me-2"></i>OJAMS
        </a>
        <button class="navbar-toggler border-0 text-white" type="button"
                data-bs-toggle="collapse" data-bs-target="#pubNav">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="collapse navbar-collapse" id="pubNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active fw-semibold" href="index.php">
                        <i class="bi bi-house me-1"></i>Job Listings
                    </a>
                </li>
            </ul>
            <div class="d-flex gap-2">
                <a href="login.php" class="btn btn-outline-light btn-sm px-3 fw-600">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Log In
                </a>
                <a href="register.php" class="btn btn-warning btn-sm px-3 fw-bold text-white">
                    <i class="bi bi-person-plus me-1"></i>Register
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ── Hero ───────────────────────────────────────────────── -->
<section class="hero-section">
    <div class="container position-relative" style="z-index:1">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <p class="text-uppercase fw-semibold mb-2" style="opacity:.7;letter-spacing:1.5px;font-size:.8rem">
                    Online Job Application and Monitoring System in the Itawes District
                </p>
                <h1 class="hero-title mb-3">
                    Find Your Next<br>
                    <span style="color:#fbbf24">Opportunity</span>
                </h1>
                <p class="hero-sub mb-4">
                    Discover open positions and apply in minutes.
                    Your dream job is just a click away.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="stat-chip">
                        <i class="bi bi-briefcase-fill"></i><?php echo $totalOpen; ?> Open Positions
                    </span>
                    <span class="stat-chip">
                        <i class="bi bi-people-fill"></i><?php echo $totalApps; ?> Applications
                    </span>
                    <span class="stat-chip">
                        <i class="bi bi-building"></i><?php echo $totalJobs; ?> Total Jobs
                    </span>
                </div>
            </div>
            <div class="col-lg-6">
                <form class="hero-search" method="GET" action="index.php">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0 ps-3">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Search job title or company..."
                               value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
                        <button type="submit" class="btn btn-search">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                </form>
                <?php if ($search !== ''): ?>
                <div class="mt-2 text-center">
                    <a href="index.php" class="text-white-50 small">
                        <i class="bi bi-x-circle me-1"></i>Clear search
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ── Job Listings ───────────────────────────────────────── -->
<section class="jobs-section">
    <div class="container">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">
                    <i class="bi bi-lightning-fill text-primary me-2"></i>Open Positions
                </h3>
                <p class="text-muted mb-0">
                    <?php if ($search !== ''): ?>
                        Showing <strong><?php echo count($jobs); ?></strong> result<?php echo count($jobs) !== 1 ? 's' : ''; ?>
                        for "<strong><?php echo htmlspecialchars($search, ENT_QUOTES); ?></strong>"
                    <?php else: ?>
                        <strong><?php echo count($jobs); ?></strong> open job<?php echo count($jobs) !== 1 ? 's' : ''; ?> available
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-none d-md-block">
                <span class="badge bg-primary fs-6 px-3 py-2">
                    <?php echo count($jobs); ?> / <?php echo $totalOpen; ?> Jobs
                </span>
            </div>
        </div>

        <?php if (empty($jobs)): ?>
        <div class="empty-state text-center">
            <div class="empty-icon"><i class="bi bi-briefcase"></i></div>
            <h5 class="fw-bold text-dark mb-2">No jobs found</h5>
            <p class="text-muted mb-3">
                <?php echo $search !== '' ? 'Try a different search term.' : 'No open positions right now. Check back soon!'; ?>
            </p>
            <?php if ($search !== ''): ?>
            <a href="index.php" class="btn btn-outline-primary px-4">View All Jobs</a>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <div class="row g-4" id="jobCardsContainer">
            <?php foreach ($jobs as $job): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card pub-job-card h-100">
                    <div class="card-header-stripe"></div>
                    <div class="card-body d-flex flex-column">
                        <!-- Title & Company -->
                        <div class="mb-3">
                            <div class="job-title">
                                <i class="bi bi-briefcase text-primary me-2"></i>
                                <?php echo htmlspecialchars($job['title']); ?>
                            </div>
                            <div class="job-company mt-1">
                                <i class="bi bi-building me-1"></i>
                                <?php echo htmlspecialchars($job['company']); ?>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="job-desc flex-grow-1 mb-2">
                            <?php echo htmlspecialchars($job['description']); ?>
                        </p>

                        <!-- Qualifications -->
                        <div class="mb-3">
                            <p class="job-qual mb-0">
                                <i class="bi bi-mortarboard me-1 text-muted"></i>
                                <strong class="text-muted">Requirements:</strong>
                                <?php echo htmlspecialchars($job['qualification']); ?>
                            </p>
                        </div>

                        <hr class="divider">

                        <!-- Meta -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <small class="text-muted">
                                <i class="bi bi-calendar-event me-1"></i>
                                <?php echo date('M j, Y', strtotime($job['date_posted'])); ?>
                            </small>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2">
                                <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle"></i>Open
                            </span>
                        </div>

                        <!-- Apply Button -->
                        <button class="btn btn-apply"
                                onclick="showAuthPrompt('<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['company'], ENT_QUOTES); ?>')">
                            <i class="bi bi-send me-2"></i>Apply Now
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Auth Prompt Modal ──────────────────────────────────── -->
<div class="modal fade auth-modal" id="authPromptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content">
            <div class="auth-modal-header">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                        data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-icon">
                    <i class="bi bi-send-fill"></i>
                </div>
                <h4>Apply for this Job</h4>
                <p id="authJobLabel">Sign in to submit your application.</p>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted text-center mb-4" style="font-size:.92rem">
                    You need an account to apply for jobs. It's free and only takes a minute!
                </p>
                <div class="d-grid gap-3">
                    <a href="login.php" class="auth-btn-login text-center text-decoration-none d-block">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Log In to Apply
                    </a>
                    <a href="register.php" class="auth-btn-register text-center text-decoration-none d-block">
                        <i class="bi bi-person-plus me-2"></i>Create a Free Account
                    </a>
                </div>
                <p class="text-center text-muted mt-4 mb-0" style="font-size:.82rem">
                    Already applied? <a href="login.php" class="text-primary fw-semibold">Log in</a> to track your applications.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ── Footer ─────────────────────────────────────────────── -->
<footer class="public-footer">
    <div class="container">
        <div class="mb-2">
            <i class="bi bi-briefcase-fill me-2"></i>
            <strong style="color:rgba(255,255,255,.9)">OJAMS</strong>
            &nbsp;&mdash;&nbsp;Online Job Application and Monitoring System in the Itawes District
        </div>
        <div>
            <a href="login.php">Log In</a>
            &nbsp;&bull;&nbsp;
            <a href="register.php">Register</a>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showAuthPrompt(title, company) {
    document.getElementById('authJobLabel').textContent =
        'Apply for "' + title + '" at ' + company;
    new bootstrap.Modal(document.getElementById('authPromptModal')).show();
}
</script>
</body>
</html>
