<?php
require_once __DIR__ . '/components/under-construction.php';
require_once __DIR__ . '/config/db.php';
$pageTitle = 'OJAMS – About / Help / FAQ';
$basePath  = '';
include $basePath . 'layouts/header.php';
include $basePath . 'layouts/navbar-user.php';
?>
<div class="container py-5" style="max-width:860px;">

    <!-- About -->
    <section class="mb-5">
        <h2 class="fw-bold mb-1"><i class="bi bi-info-circle me-2 text-primary"></i>About OJAMS</h2>
        <hr>
        <p>The <strong>Online Job Application and Monitoring System (OJAMS)</strong> is a web-based platform developed for the <strong>Itawes District</strong> to streamline the job-seeking process for applicants and simplify job posting management for administrators.</p>
        <p>OJAMS allows job seekers to browse open positions, submit complete applications online, track the status of each application in real time, and receive feedback without visiting an office in person.</p>
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="card border-0 bg-primary bg-opacity-10 h-100 p-3">
                    <i class="bi bi-search-heart fs-2 text-primary mb-2"></i>
                    <h6 class="fw-bold">Browse Jobs</h6>
                    <p class="small text-muted mb-0">Filter by title, location, or job type and find the right opportunity.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-success bg-opacity-10 h-100 p-3">
                    <i class="bi bi-send-fill fs-2 text-success mb-2"></i>
                    <h6 class="fw-bold">Apply Online</h6>
                    <p class="small text-muted mb-0">Submit your application with your educational background, skills, and optional résumé.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-warning bg-opacity-10 h-100 p-3">
                    <i class="bi bi-graph-up-arrow fs-2 text-warning mb-2"></i>
                    <h6 class="fw-bold">Track Status</h6>
                    <p class="small text-muted mb-0">Check whether your application is Pending, Approved, or Rejected — any time.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="mb-5">
        <h3 class="fw-bold mb-3"><i class="bi bi-question-circle me-2 text-primary"></i>How It Works</h3>
        <ol class="list-group list-group-numbered list-group-flush">
            <li class="list-group-item border-0 px-0"><strong>Create an account</strong> — Register with your name, email, and contact number.</li>
            <li class="list-group-item border-0 px-0"><strong>Browse job listings</strong> — Search and filter to find positions that match your skills.</li>
            <li class="list-group-item border-0 px-0"><strong>Submit an application</strong> — Fill out the online form and optionally attach your résumé (PDF/DOCX, max 5 MB).</li>
            <li class="list-group-item border-0 px-0"><strong>Track your application</strong> — Visit <em>My Applications</em> any time to see the current status.</li>
            <li class="list-group-item border-0 px-0"><strong>Receive a decision</strong> — An admin will review and mark your application as Approved or Rejected.</li>
        </ol>
    </section>

    <!-- FAQ -->
    <section class="mb-5">
        <h3 class="fw-bold mb-3"><i class="bi bi-chat-left-quote me-2 text-primary"></i>Frequently Asked Questions</h3>
        <div class="accordion" id="faqAccordion">

            <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed rounded fw-semibold" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq1">
                        Is OJAMS free to use?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">Yes — OJAMS is completely free for all job seekers. There are no fees to register, browse, or apply.</div>
                </div>
            </div>

            <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed rounded fw-semibold" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq2">
                        Can I apply for more than one job?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">Yes. You may apply to multiple open positions, but only one application per job posting is allowed per account.</div>
                </div>
            </div>

            <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed rounded fw-semibold" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq3">
                        How do I cancel a pending application?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">Go to <strong>My Applications</strong>, find the application with <em>Pending</em> status, and click the <em>Cancel</em> button. Approved or Rejected applications cannot be cancelled.</div>
                </div>
            </div>

            <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed rounded fw-semibold" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq4">
                        What file formats are accepted for résumés?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">PDF, DOC, and DOCX files are accepted. The maximum file size is 5 MB. Résumé upload is optional.</div>
                </div>
            </div>

            <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed rounded fw-semibold" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq5">
                        I forgot my password. What do I do?
                    </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted">Click <strong>Forgot Password</strong> on the login page. Enter your registered email address and follow the reset link sent to your inbox.</div>
                </div>
            </div>

        </div>
    </section>

    <div class="text-center">
        <a href="index.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left me-1"></i>Back to Jobs</a>
        <a href="contact.php" class="btn btn-primary"><i class="bi bi-envelope me-1"></i>Contact Support</a>
    </div>
</div>
<?php include $basePath . 'layouts/footer.php'; ?>
