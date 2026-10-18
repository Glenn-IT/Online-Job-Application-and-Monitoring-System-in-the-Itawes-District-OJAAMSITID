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
$stmt = $pdo->prepare("
    SELECT j.*, u.full_name AS poster_name, u.role AS poster_role
    FROM jobs j
    LEFT JOIN users u ON u.id = j.created_by
    {$whereSQL}
    ORDER BY j.date_posted DESC
");
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$totalOpen  = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='Open'")->fetchColumn();
$totalJobs  = (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$totalApps  = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

function getJobTypeClass(?string $type): string {
    return match(strtolower($type ?? '')) {
        'full-time'  => 'tag-fulltime',
        'part-time'  => 'tag-parttime',
        'contract'   => 'tag-contract',
        'internship' => 'tag-internship',
        'freelance'  => 'tag-freelance',
        default      => 'tag-location'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="icon" type="image/png" href="img/Piat-Logo.png">
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
            color: #475569;
            font-size: .84rem;
            line-height: 1.5;
        }
        .pub-job-card .divider { border-color: #f1f5f9; margin: .9rem 0; }

        .tag-pill {
            font-size: 0.76rem;
            font-weight: 600;
            padding: 0.28rem 0.65rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .tag-fulltime   { background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
        .tag-parttime   { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .tag-contract   { background: #fef9c3; color: #a16207; border: 1px solid #fef08a; }
        .tag-internship { background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .tag-freelance  { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
        .tag-location   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .tag-salary     { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .tag-deadline   { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }

        .qual-box-preview {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #4f46e5;
            border-radius: 8px;
            padding: 0.75rem;
        }

        .stat-grid-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.85rem 1rem;
        }
        .stat-grid-box .stat-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 0.2rem;
        }
        .stat-grid-box .stat-val {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e1b4b;
        }

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
<nav class="navbar public-nav sticky-top py-2">
    <div class="container d-flex align-items-center justify-content-end">
        <div class="d-flex align-items-center gap-2">
            <a href="login.php" class="btn btn-outline-light btn-sm px-3 fw-600">
                <i class="bi bi-box-arrow-in-right me-1"></i>Log In
            </a>
            <a href="register.php" class="btn btn-warning btn-sm px-3 fw-bold text-white">
                <i class="bi bi-person-plus me-1"></i>Register
            </a>
        </div>
    </div>
</nav>

<!-- ── Hero ───────────────────────────────────────────────── -->
<section class="hero-section">
    <div class="container position-relative" style="z-index:1">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="img/Piat-Logo.png" alt="Piat Logo" style="width: 42px; height: 42px; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));">
                    <span class="text-uppercase fw-semibold text-white text-opacity-75" style="letter-spacing:1px;font-size:.82rem">Municipality of Piat &bull; Itawes District</span>
                </div>
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
            <?php foreach ($jobs as $job): 
                $jobType = $job['job_type'] ?: 'Full-time';
                $location = $job['location'] ?: 'Piat / Itawes District';
                $salary = $job['salary_range'] ?: 'Competitive / Standard Rate';
                $deadlineFormatted = !empty($job['deadline']) ? date('M j, Y', strtotime($job['deadline'])) : 'Open Until Filled';
                
                $jobPayload = [
                    'id'             => $job['id'],
                    'title'          => $job['title'],
                    'company'        => $job['company'],
                    'description'    => $job['description'],
                    'qualification'  => $job['qualification'],
                    'job_type'       => $jobType,
                    'location'       => $location,
                    'salary_range'   => $salary,
                    'deadline'       => $deadlineFormatted,
                    'contact_person' => $job['contact_person'] ?? 'HR Recruitment Officer',
                    'contact_phone'  => $job['contact_phone'] ?? 'LGU Trunkline',
                    'date_posted'    => date('M j, Y', strtotime($job['date_posted'])),
                    'poster_name'    => $job['poster_name'] ?? 'OJAMS Admin',
                ];
                $jsonSafe = htmlspecialchars(json_encode($jobPayload), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card pub-job-card h-100">
                    <div class="card-header-stripe"></div>
                    <div class="card-body d-flex flex-column">
                        <!-- Title & Company -->
                        <div class="mb-2">
                            <div class="job-title">
                                <i class="bi bi-briefcase text-primary me-2"></i>
                                <?php echo htmlspecialchars($job['title']); ?>
                            </div>
                            <div class="job-company mt-1">
                                <i class="bi bi-building me-1"></i>
                                <?php echo htmlspecialchars($job['company']); ?>
                            </div>
                        </div>

                        <!-- Major Details Badges -->
                        <div class="d-flex flex-wrap gap-1 mb-3">
                            <span class="tag-pill <?php echo getJobTypeClass($jobType); ?>">
                                <i class="bi bi-clock-history"></i><?php echo htmlspecialchars($jobType); ?>
                            </span>
                            <span class="tag-pill tag-location">
                                <i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($location); ?>
                            </span>
                            <span class="tag-pill tag-salary">
                                <i class="bi bi-cash-stack"></i><?php echo htmlspecialchars($salary); ?>
                            </span>
                            <?php if (!empty($job['deadline'])): ?>
                            <span class="tag-pill tag-deadline">
                                <i class="bi bi-calendar-x"></i>Due: <?php echo date('M j', strtotime($job['deadline'])); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Brief Description -->
                        <p class="job-desc mb-3 text-muted" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            <?php echo htmlspecialchars($job['description']); ?>
                        </p>

                        <!-- Prominent Qualifications Box -->
                        <div class="qual-box-preview mb-3 flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-primary small">
                                    <i class="bi bi-mortarboard-fill me-1"></i>Key Qualifications &amp; Eligibility:
                                </span>
                                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:0.68rem;">Must Qualify</span>
                            </div>
                            <div class="job-qual" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;white-space:pre-line;">
                                <?php echo htmlspecialchars($job['qualification']); ?>
                            </div>
                        </div>

                        <!-- Meta Info -->
                        <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top">
                            <small class="text-muted">
                                <i class="bi bi-calendar-event me-1"></i>Posted <?php echo date('M j, Y', strtotime($job['date_posted'])); ?>
                            </small>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2">
                                <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle"></i>Open
                            </span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-column gap-2 mt-auto">
                            <button type="button" class="btn btn-outline-primary btn-sm fw-semibold w-100 py-2"
                                    data-job-info='<?php echo $jsonSafe; ?>'
                                    onclick="viewJobPublic(JSON.parse(this.dataset.jobInfo))">
                                <i class="bi bi-card-checklist me-1"></i>View Full Qualifications &amp; Details
                            </button>
                            <button class="btn btn-apply"
                                    onclick="showAuthPrompt('<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($job['company'], ENT_QUOTES); ?>')">
                                <i class="bi bi-send me-2"></i>Apply Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── Comprehensive Public Job Details Modal (No Login Required) ────────────────── -->
<div class="modal fade" id="publicJobModal" tabindex="-1" aria-labelledby="publicJobModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); padding: 1.25rem 1.5rem;">
                <div>
                    <span class="badge bg-white bg-opacity-25 text-white mb-1" id="pubModalCompany">—</span>
                    <h5 class="modal-title fw-bold mb-0" id="pubModalTitle">Job Details</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- 4 Highlights Grid -->
                <div class="row g-2 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-grid-box h-100">
                            <div class="stat-label"><i class="bi bi-clock-history me-1 text-primary"></i>Type</div>
                            <div class="stat-val" id="pubModalType">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-grid-box h-100">
                            <div class="stat-label"><i class="bi bi-geo-alt me-1 text-danger"></i>Location</div>
                            <div class="stat-val" id="pubModalLocation">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-grid-box h-100">
                            <div class="stat-label"><i class="bi bi-cash-stack me-1 text-success"></i>Salary</div>
                            <div class="stat-val text-success" id="pubModalSalary">—</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-grid-box h-100">
                            <div class="stat-label"><i class="bi bi-calendar-event me-1 text-warning"></i>Deadline</div>
                            <div class="stat-val text-danger" id="pubModalDeadlineStat">—</div>
                        </div>
                    </div>
                </div>

                <!-- Section: Qualifications & Eligibility Requirements -->
                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 mb-4 shadow-sm" style="border-radius: 12px;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary text-white p-2 rounded-circle">
                                <i class="bi bi-mortarboard-fill"></i>
                            </span>
                            <h6 class="fw-bold text-primary mb-0 fs-6">
                                Minimum Qualifications &amp; Eligibility Requirements
                            </h6>
                        </div>
                        <p class="small text-muted mb-2">Review these requirements carefully to verify your qualification for this municipal opening:</p>
                        <div id="pubModalQual" class="p-3 bg-white rounded-3 border text-dark" style="white-space: pre-line; line-height: 1.65; font-size: 0.92rem;">
                            —
                        </div>
                    </div>
                </div>

                <!-- Section: Description & Scope of Work -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-file-earmark-text-fill text-primary me-2"></i>Job Description &amp; Scope of Responsibilities
                    </h6>
                    <div id="pubModalDesc" class="p-3 bg-light rounded-3 border text-secondary" style="white-space: pre-line; line-height: 1.65; font-size: 0.92rem;">
                        —
                    </div>
                </div>

                <!-- Section: Contact Details & Poster Info -->
                <div class="mb-4 p-3 bg-light rounded-3 border">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-info-circle-fill text-info me-2"></i>Hiring Office &amp; Contact Details
                    </h6>
                    <div class="row g-2 small text-muted">
                        <div class="col-sm-6" id="pubModalContactPerson"><strong>Contact Person:</strong> —</div>
                        <div class="col-sm-6" id="pubModalContactPhone"><strong>Contact Phone:</strong> —</div>
                        <div class="col-sm-6" id="pubModalDatePosted"><strong>Date Posted:</strong> —</div>
                        <div class="col-sm-6" id="pubModalDeadline"><strong>Deadline:</strong> —</div>
                    </div>
                </div>

                <!-- Section: Notice on Mandatory Requirements -->
                <div class="alert alert-warning border-warning border-opacity-50 mb-0 d-flex gap-3 align-items-start" style="border-radius: 12px;">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning flex-shrink-0"></i>
                    <div class="small">
                        <strong>Civil Service Application Note:</strong> All official applicants must submit the 5 standard civil service documents upon application:
                        <em>Resume/CV, Application Letter, CS Form 212 (PDS), Certificate of CSC Eligibility, and Transcript of Records (TOR)</em>.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary px-4 fw-bold shadow-sm" id="pubModalApplyBtn">
                    <i class="bi bi-send-fill me-1"></i>Apply for this Job
                </button>
            </div>
        </div>
    </div>
</div>

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
        <div class="mb-2 d-flex align-items-center justify-content-center gap-2 flex-wrap">
            <img src="img/Piat-Logo.png" alt="Piat Logo" style="width: 22px; height: 22px; object-fit: contain;">
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

function viewJobPublic(job) {
    document.getElementById('pubModalTitle').textContent = job.title;
    document.getElementById('pubModalCompany').textContent = job.company;
    document.getElementById('pubModalType').textContent = job.job_type || 'Unspecified';
    document.getElementById('pubModalLocation').textContent = job.location || 'Municipal / District Office';
    document.getElementById('pubModalSalary').textContent = job.salary_range || 'Competitive Rate';
    document.getElementById('pubModalDeadlineStat').textContent = job.deadline || 'Open until filled';
    document.getElementById('pubModalQual').textContent = job.qualification || 'No specific qualification recorded.';
    document.getElementById('pubModalDesc').textContent = job.description || 'No job description provided.';
    document.getElementById('pubModalContactPerson').innerHTML = '<strong>Contact Person:</strong> ' + (job.contact_person || 'HR Recruitment Unit');
    document.getElementById('pubModalContactPhone').innerHTML = '<strong>Phone:</strong> ' + (job.contact_phone || 'LGU Piat Trunkline');
    document.getElementById('pubModalDatePosted').innerHTML = '<strong>Posted Date:</strong> ' + (job.date_posted || '—');
    document.getElementById('pubModalDeadline').innerHTML = '<strong>Application Deadline:</strong> ' + (job.deadline || 'Open until filled');

    const applyBtn = document.getElementById('pubModalApplyBtn');
    if (applyBtn) {
        applyBtn.onclick = function() {
            const pubModalEl = document.getElementById('publicJobModal');
            bootstrap.Modal.getInstance(pubModalEl)?.hide();
            setTimeout(() => showAuthPrompt(job.title, job.company), 350);
        };
    }
    new bootstrap.Modal(document.getElementById('publicJobModal')).show();
}
</script>
</body>
</html>
