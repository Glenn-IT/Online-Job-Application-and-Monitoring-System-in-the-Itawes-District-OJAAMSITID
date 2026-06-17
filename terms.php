<?php
require_once __DIR__ . '/config/db.php';
$pageTitle = 'OJAMS – Terms of Service & Privacy Policy';
$basePath  = '';
include $basePath . 'layouts/header.php';
include $basePath . 'layouts/navbar-user.php';
?>
<div class="container py-5" style="max-width:860px;">

    <h2 class="fw-bold mb-1"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Terms of Service &amp; Privacy Policy</h2>
    <p class="text-muted mb-4">Last updated: June 2026 &mdash; <em>Prototype Version 2.0</em></p>
    <hr class="mb-4">

    <!-- Terms of Service -->
    <section class="mb-5">
        <h3 class="fw-bold mb-3">Terms of Service</h3>

        <h6 class="fw-bold mt-3">1. Acceptance of Terms</h6>
        <p class="text-muted">By registering for or using OJAMS, you agree to be bound by these terms. If you do not agree, please do not use the system.</p>

        <h6 class="fw-bold mt-3">2. Eligibility</h6>
        <p class="text-muted">You must be at least 16 years old to create an account. By registering, you represent that the information you provide is accurate and complete.</p>

        <h6 class="fw-bold mt-3">3. Account Responsibilities</h6>
        <p class="text-muted">You are responsible for maintaining the confidentiality of your password. You agree to notify the administrator immediately if you suspect any unauthorized access to your account.</p>

        <h6 class="fw-bold mt-3">4. Acceptable Use</h6>
        <p class="text-muted">You agree not to submit false, misleading, or fraudulent application information. OJAMS is intended solely for legitimate job applications within the Itawes District.</p>

        <h6 class="fw-bold mt-3">5. Intellectual Property</h6>
        <p class="text-muted">All content, design, and code within OJAMS is the property of the Itawes District OJAMS project. Unauthorized reproduction or redistribution is prohibited.</p>

        <h6 class="fw-bold mt-3">6. Termination</h6>
        <p class="text-muted">Administrators reserve the right to deactivate any account that violates these terms, provides false information, or misuses the system.</p>

        <h6 class="fw-bold mt-3">7. Limitation of Liability</h6>
        <p class="text-muted">OJAMS is provided as a prototype system on a best-effort basis. We do not guarantee continuous availability and are not liable for any loss arising from system downtime or data inaccuracies.</p>
    </section>

    <hr class="mb-4">

    <!-- Privacy Policy -->
    <section class="mb-5">
        <h3 class="fw-bold mb-3">Privacy Policy</h3>

        <h6 class="fw-bold mt-3">1. Information We Collect</h6>
        <p class="text-muted">We collect the personal information you provide during registration (name, email, contact number) and during the application process (educational background, skills, work experience, optional résumé file).</p>

        <h6 class="fw-bold mt-3">2. How We Use Your Information</h6>
        <ul class="text-muted">
            <li>To process and evaluate job applications.</li>
            <li>To communicate application status updates.</li>
            <li>To maintain system security and audit logs.</li>
        </ul>

        <h6 class="fw-bold mt-3">3. Data Sharing</h6>
        <p class="text-muted">Your personal information is shared only with administrators of the OJAMS system who are responsible for evaluating applications. We do not sell or share your data with third parties.</p>

        <h6 class="fw-bold mt-3">4. Data Security</h6>
        <p class="text-muted">Passwords are hashed using bcrypt. Uploaded résumé files are stored in a restricted server directory. We take reasonable measures to protect your data, but no system is completely secure.</p>

        <h6 class="fw-bold mt-3">5. Data Retention</h6>
        <p class="text-muted">Application records are retained for as long as needed for administrative purposes. You may request deletion of your account by contacting the administrator.</p>

        <h6 class="fw-bold mt-3">6. Cookies &amp; Sessions</h6>
        <p class="text-muted">OJAMS uses server-side sessions to maintain your login state. No third-party tracking cookies are used.</p>

        <h6 class="fw-bold mt-3">7. Your Rights</h6>
        <p class="text-muted">You have the right to access, correct, or request deletion of your personal data. Contact <a href="mailto:admin@ojams.com">admin@ojams.com</a> to exercise these rights.</p>

        <h6 class="fw-bold mt-3">8. Changes to This Policy</h6>
        <p class="text-muted">We may update this policy from time to time. Continued use of OJAMS after changes constitutes your acceptance of the revised policy.</p>
    </section>

    <div class="text-center">
        <a href="index.php" class="btn btn-outline-secondary me-2"><i class="bi bi-arrow-left me-1"></i>Back to Jobs</a>
        <a href="contact.php" class="btn btn-primary"><i class="bi bi-envelope me-1"></i>Contact Us</a>
    </div>
</div>
<?php include $basePath . 'layouts/footer.php'; ?>
