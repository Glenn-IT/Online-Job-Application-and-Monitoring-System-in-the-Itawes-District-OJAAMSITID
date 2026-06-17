<?php
require_once __DIR__ . '/components/under-construction.php';
require_once __DIR__ . '/config/db.php';
$pageTitle = 'OJAMS – Contact Us';
$basePath  = '';
include $basePath . 'layouts/header.php';
include $basePath . 'layouts/navbar-user.php';
?>
<div class="container py-5" style="max-width:760px;">

    <h2 class="fw-bold mb-1"><i class="bi bi-envelope me-2 text-primary"></i>Contact Us / Support</h2>
    <p class="text-muted mb-4">Need help or have a question? Reach out to the OJAMS administrator using the details below.</p>
    <hr class="mb-4">

    <div class="row g-4">
        <!-- Contact info cards -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100 p-4">
                <h5 class="fw-bold mb-3">Get in Touch</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-3 d-flex align-items-start gap-3">
                        <i class="bi bi-geo-alt-fill text-primary fs-5 flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Office Address</strong><br>
                            <span class="text-muted small">OJAMS District Office, Itawes District, Cagayan, Philippines</span>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start gap-3">
                        <i class="bi bi-envelope-fill text-primary fs-5 flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Email</strong><br>
                            <a href="mailto:admin@ojams.com" class="text-muted small text-decoration-none">admin@ojams.com</a>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start gap-3">
                        <i class="bi bi-clock-fill text-primary fs-5 flex-shrink-0 mt-1"></i>
                        <div>
                            <strong>Office Hours</strong><br>
                            <span class="text-muted small">Monday – Friday, 8:00 AM – 5:00 PM (PST)</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Quick help -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm p-4 mb-3">
                <h5 class="fw-bold mb-3">Quick Help</h5>
                <div class="list-group list-group-flush">
                    <a href="about.php#faq1" class="list-group-item list-group-item-action border-0 px-0">
                        <i class="bi bi-question-circle me-2 text-primary"></i>Is OJAMS free?
                    </a>
                    <a href="about.php#faq3" class="list-group-item list-group-item-action border-0 px-0">
                        <i class="bi bi-question-circle me-2 text-primary"></i>How to cancel an application?
                    </a>
                    <a href="forgot-password.php" class="list-group-item list-group-item-action border-0 px-0">
                        <i class="bi bi-key me-2 text-primary"></i>Reset my password
                    </a>
                    <a href="about.php" class="list-group-item list-group-item-action border-0 px-0">
                        <i class="bi bi-info-circle me-2 text-primary"></i>Read the full FAQ
                    </a>
                </div>
            </div>
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Tip:</strong> For the fastest response, include your registered email address and a brief description of your issue when contacting us.
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Jobs</a>
    </div>
</div>
<?php include $basePath . 'layouts/footer.php'; ?>
