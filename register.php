<?php

$configPath = __DIR__ . '/config/auth.php';
require_once $configPath;

// Already logged in? Go to dashboard.
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/user/browse-jobs.php');
    exit;
}

$error   = '';
$success = '';

// ── Generate math CAPTCHA question ──────────────────────────
// Always refresh the question so each page load has a new one.
// On POST we validate against the stored answer, then regenerate.
function generateCaptcha(): void {
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['captcha_answer'] = $a + $b;
    $_SESSION['captcha_question'] = "What is {$a} + {$b}?";
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    generateCaptcha();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = trim($_POST['full_name'] ?? '');
    $email       = strtolower(trim($_POST['email'] ?? ''));
    $contact     = trim($_POST['contact']   ?? '');
    $birthdate   = trim($_POST['birthdate'] ?? '');
    $address     = trim($_POST['address']   ?? '');
    $elementary  = trim($_POST['elementary'] ?? '');
    $jhs         = trim($_POST['jhs']        ?? '');
    $shs         = trim($_POST['shs']        ?? '');
    $college     = trim($_POST['college']    ?? '');
    $skills      = trim($_POST['skills']     ?? '');
    $experience  = trim($_POST['experience'] ?? '');
    $password    = $_POST['password']  ?? '';
    $confirm     = $_POST['confirm']   ?? '';
    $sq_question = trim($_POST['security_question'] ?? '');
    $sq_answer   = trim($_POST['security_answer']   ?? '');
    $reg_role    = $_POST['reg_role'] ?? 'user';

    // ── Bot check 1: Honeypot ────────────────────────────────
    if (trim($_POST['website'] ?? '') !== '') {
        $success = 'Account created! Redirecting to login...';
        header('Refresh: 1.5; url=' . BASE_URL . '/login.php');
        generateCaptcha();
        goto render;
    }

    // ── Bot check 2: Math CAPTCHA ────────────────────────────
    $captchaInput  = (int)trim($_POST['captcha'] ?? '');
    $captchaAnswer = (int)($_SESSION['captcha_answer'] ?? -1);
    generateCaptcha(); // always regenerate after a submission attempt

    if ($captchaInput !== $captchaAnswer) {
        $error = 'Incorrect answer to the security question. Please try again.';
        goto render;
    }

    // Basic credentials validation
    if (!$full_name || !$email || !$contact || !$password || !$confirm) {
        $error = 'All primary account fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^\d{11}$/', $contact)) {
        $error = 'Contact number must be exactly 11 digits (numbers only, e.g. 09171234567).';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = 'Password must contain at least one special character (e.g. !@#$%^&*).';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($sq_question, SECURITY_QUESTIONS, true)) {
        $error = 'Please choose a security question from the list.';
    } elseif (mb_strlen($sq_answer) < 2) {
        $error = 'Security answer must be at least 2 characters.';
    } elseif (!in_array($reg_role, ['user', 'staff'], true)) {
        $error = 'Please choose a valid account type.';
    } elseif ($reg_role === 'user' && (!$birthdate || !$address || !$elementary || !$jhs || !$shs || !$college || !$skills || !$experience)) {
        $error = 'All applicant personal details, educational background, and skills are required to set up your profile.';
    } else {
        // Validate birthdate for applicants
        if ($birthdate !== '') {
            $bd = DateTime::createFromFormat('Y-m-d', $birthdate);
            if (!$bd || $bd->format('Y-m-d') !== $birthdate) {
                $error = 'Please enter a valid birthdate (YYYY-MM-DD).';
                goto render;
            }
            $now = new DateTime();
            $age = (int)$now->diff($bd)->y;
            if ($age < 18 || $age > 80) {
                $error = 'Applicants must be at least 18 years old and 80 years old or below to register.';
                goto render;
            }
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'That email address is already registered.';
        } else {
            // Insert new user
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $sqHash = password_hash(mb_strtolower($sq_answer), PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $isApproved = $reg_role === 'staff' ? 0 : 1;
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    role, full_name, email, password_hash, contact_number,
                    address, birthdate, elementary, jhs, shs, college,
                    skills, experience, security_question, security_answer_hash, is_approved
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reg_role, $full_name, $email, $hash, $contact,
                $address ?: null, $birthdate ?: null, $elementary ?: null, $jhs ?: null, $shs ?: null, $college ?: null,
                $skills ?: null, $experience ?: null, $sq_question, $sqHash, $isApproved
            ]);
            if ($reg_role === 'staff') {
                $success = 'Staff account created! An administrator must approve your account before you can sign in.';
            } else {
                $success = 'Account created successfully! All profile details have been saved. Redirecting to login...';
                header('Refresh: 1.8; url=' . BASE_URL . '/login.php');
            }
        }
    }
}

render:

$pageTitle = "OJAMS - Register";
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
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            font-size: 0.9rem;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            color: #1e293b;
        }
        .auth-page { min-height: 100vh; display: flex; }
        .auth-hero {
            flex: 1;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        .auth-hero::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .auth-hero::after {
            content: '';
            position: absolute;
            bottom: -100px; left: -60px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,0.03);
        }
        .auth-hero-icon {
            width: 80px; height: 80px;
            border-radius: 22px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        .auth-hero h1 { color: #fff; font-size: 2.2rem; font-weight: 900; letter-spacing: -0.5px; margin-bottom: 0.75rem; }
        .auth-hero > p { color: rgba(255,255,255,0.65); font-size: 1rem; max-width: 320px; text-align: center; line-height: 1.6; }
        .auth-hero-features { list-style: none; padding: 0; margin: 2rem 0 0; text-align: left; width: 100%; max-width: 300px; }
        .auth-hero-features li { color: rgba(255,255,255,0.75); font-size: 0.875rem; padding: 0.45rem 0; display: flex; align-items: center; gap: 0.75rem; }
        .auth-hero-features li i { color: #a5b4fc; font-size: 1rem; width: 18px; flex-shrink: 0; }
        .auth-form-panel {
            width: 620px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 2.5rem 2rem;
            background: #ffffff;
            overflow-y: auto;
        }
        .auth-card { width: 100%; max-width: 560px; }
        .auth-card-header { text-align: center; margin-bottom: 1.8rem; }
        .auth-card-header h2 { font-size: 1.6rem; font-weight: 800; color: #1e293b; margin-bottom: 0.3rem; }
        .auth-card-header p { color: #64748b; font-size: 0.875rem; }
        .form-section-title {
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4f46e5;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.4rem;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .auth-footer-link { text-align: center; margin-top: 1.5rem; font-size: 0.845rem; color: #64748b; }
        .auth-footer-link a { color: #4f46e5; font-weight: 600; text-decoration: none; }
        .auth-footer-link a:hover { text-decoration: underline; }
        .back-to-jobs { text-align: center; margin-top: 0.75rem; font-size: 0.82rem; }
        .back-to-jobs a { color: #64748b; text-decoration: none; }
        .back-to-jobs a:hover { color: #4f46e5; }
        .copyright-note { font-size: 0.72rem; color: #64748b; text-align: center; margin-top: 1.5rem; }
        @media (max-width: 767.98px) {
            .auth-hero { display: none; }
            .auth-form-panel { width: 100%; padding: 1.5rem; min-height: 100vh; }
        }
    </style>
</head>
<body>

<div class="auth-page">

    <!-- Left: Hero Panel -->
    <div class="auth-hero">
        <div class="mb-3 text-center">
            <img src="img/Piat-Logo.png" alt="Piat Logo" style="width: 88px; height: 88px; object-fit: contain; filter: drop-shadow(0 4px 14px rgba(0,0,0,0.35));">
        </div>
        <h1>Join OJAMS</h1>
        <p>Create your free account and start exploring hundreds of job opportunities today.</p>
        <ul class="auth-hero-features">
            <li><i class="bi bi-lightning-charge-fill"></i> Quick &amp; easy registration</li>
            <li><i class="bi bi-search-heart"></i> Browse curated job listings</li>
            <li><i class="bi bi-graph-up-arrow"></i> Track all your applications</li>
            <li><i class="bi bi-bell-fill"></i> Get notified on status updates</li>
        </ul>
    </div>

    <!-- Right: Form Panel -->
    <div class="auth-form-panel">
        <div class="auth-card">

            <div class="auth-card-header">
                <div class="text-center mb-3">
                    <img src="img/Piat-Logo.png" alt="Piat Logo" style="width: 64px; height: 64px; object-fit: contain; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.12));">
                </div>
                <h2>Create account</h2>
                <p>Fill in the details below to get started</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php elseif ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <form id="registerForm" method="post" action="register.php">
                <!-- Honeypot: hidden from humans via CSS; bots fill it in -->
                <div style="position:absolute;left:-9999px;top:-9999px;opacity:0;" aria-hidden="true" tabindex="-1">
                    <label for="website">Leave this blank</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>

                <!-- Account type (staff role is temporary — module drafted in docs/STAFF-ROLE-DRAFT.md) -->
                <div class="mb-3">
                    <label class="form-label d-block">Register as <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="reg_role" id="roleApplicant" value="user"
                                   <?= (($_POST['reg_role'] ?? 'user') === 'user') ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary w-100" for="roleApplicant">
                                <i class="bi bi-person-badge me-1"></i>Applicant
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="reg_role" id="roleStaff" value="staff"
                                   <?= (($_POST['reg_role'] ?? '') === 'staff') ? 'checked' : '' ?>>
                            <label class="btn btn-outline-warning w-100" for="roleStaff">
                                <i class="bi bi-person-workspace me-1"></i>Staff
                            </label>
                        </div>
                    </div>
                    <div class="form-text text-muted" id="roleHint">Applicants can browse and apply for jobs right away.</div>
                </div>

                <!-- ── SECTION: Personal Information ── -->
                <div class="form-section-title">
                    <i class="bi bi-person-vcard"></i>Personal Information
                </div>

                <div class="mb-3">
                    <label class="form-label" for="regName">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" name="full_name" id="regName"
                               placeholder="e.g. Juan Dela Cruz"
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-7">
                        <label class="form-label" for="regEmail">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" name="email" id="regEmail"
                                   placeholder="you@example.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label" for="regContact">Contact Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                            <input type="tel" class="form-control" name="contact" id="regContact"
                                   placeholder="09171234567"
                                   inputmode="numeric" maxlength="11" pattern="\d{11}"
                                   oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)"
                                   value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-8">
                        <label class="form-label" for="regBirthdate">Birthdate <span class="text-danger req-star">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                            <input type="date" class="form-control" name="birthdate" id="regBirthdate"
                                   value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>" required>
                        </div>
                        <div class="form-text text-muted">Must be at least 18 years old.</div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label" for="regAge">Age</label>
                        <input type="text" class="form-control text-center fw-bold bg-light" id="regAge" placeholder="Auto" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="regAddress">Residential Address <span class="text-danger req-star">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" class="form-control" name="address" id="regAddress"
                               placeholder="e.g. Centro 1, Piat, Cagayan"
                               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- ── SECTION: Educational Attainment & Background (for Applicants) ── -->
                <div id="applicantBackgroundSection">
                    <div class="form-section-title">
                        <i class="bi bi-mortarboard"></i>Educational Attainment
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="regElementary">Elementary School <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="elementary" id="regElementary"
                                   placeholder="Elementary school name" maxlength="200"
                                   value="<?= htmlspecialchars($_POST['elementary'] ?? '') ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="regJhs">Junior High School (JHS) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="jhs" id="regJhs"
                                   placeholder="JHS school name" maxlength="200"
                                   value="<?= htmlspecialchars($_POST['jhs'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="regShs">Senior High School (SHS) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="shs" id="regShs"
                                   placeholder="SHS school name" maxlength="200"
                                   value="<?= htmlspecialchars($_POST['shs'] ?? '') ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="regCollege">College / Vocational &amp; Degree <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="college" id="regCollege"
                                   placeholder="College &amp; Course / Degree" maxlength="200"
                                   value="<?= htmlspecialchars($_POST['college'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-section-title">
                        <i class="bi bi-tools"></i>Skills &amp; Experience
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="regSkills">Skills &amp; Proficiencies <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="skills" id="regSkills" rows="2"
                                  placeholder="e.g. IT Skills, MS Office, Civil Engineering, Communication, Driving" required><?= htmlspecialchars($_POST['skills'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="regExperience">Work Experience <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="experience" id="regExperience" rows="2"
                                  placeholder="e.g. 2 years Administrative Aide at LGU Piat or N/A if fresh graduate" required><?= htmlspecialchars($_POST['experience'] ?? '') ?></textarea>
                        <div class="form-text text-muted">Enter "N/A" or "None" if you are a fresh graduate without prior work experience.</div>
                    </div>
                </div>

                <!-- ── SECTION: Account Security & Verification ── -->
                <div class="form-section-title">
                    <i class="bi bi-shield-lock"></i>Account Security
                </div>

                <div class="mb-3">
                    <label class="form-label" for="regPassword">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" name="password" id="regPassword"
                               placeholder="Min 8 chars, uppercase, number, symbol" required minlength="8"
                               oninput="checkRegPasswordStrength()">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleRegPass()">
                            <i class="bi bi-eye" id="regEyeIcon"></i>
                        </button>
                    </div>
                    <div class="progress mt-2" style="height:5px;">
                        <div class="progress-bar" id="regPwStrengthBar" role="progressbar" style="width:0%"></div>
                    </div>
                    <small class="text-muted" id="regPwStrengthText"></small>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="regConfirm">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" name="confirm" id="regConfirm"
                               placeholder="Repeat your password" required>
                    </div>
                </div>

                <!-- Security Question (used for Forgot Password) -->
                <div class="mb-3">
                    <label class="form-label" for="regSecQuestion">Security Question <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-patch-question"></i></span>
                        <select class="form-select" name="security_question" id="regSecQuestion" required>
                            <option value="" disabled <?= empty($_POST['security_question']) ? 'selected' : '' ?>>Choose a question…</option>
                            <?php foreach (SECURITY_QUESTIONS as $q): ?>
                            <option value="<?= htmlspecialchars($q) ?>" <?= (($_POST['security_question'] ?? '') === $q) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($q) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-text text-muted">You'll answer this if you ever forget your password.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="regSecAnswer">Security Answer <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-chat-left-text"></i></span>
                        <input type="text" class="form-control" name="security_answer" id="regSecAnswer"
                               placeholder="Your answer" maxlength="150" autocomplete="off"
                               value="<?= htmlspecialchars($_POST['security_answer'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Math CAPTCHA -->
                <div class="mb-4">
                    <label class="form-label" for="captcha">
                        <i class="bi bi-shield-check me-1 text-primary"></i>
                        Security Check <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text fw-semibold text-primary">
                            <?php echo htmlspecialchars($_SESSION['captcha_question'] ?? 'What is 1 + 1?'); ?>
                        </span>
                        <input type="number" class="form-control" name="captcha" id="captcha"
                               placeholder="Your answer" required autocomplete="off">
                    </div>
                    <div class="form-text text-muted">Answer the simple math question to prove you're human.</div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </div>
            </form>

            <div class="auth-footer-link">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>

            <div class="back-to-jobs">
                <a href="index.php"><i class="bi bi-arrow-left me-1"></i>Back to Job Listings</a>
            </div>

            <div class="copyright-note">&copy; 2026 OJAMS &mdash; Prototype Version</div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function computeAge(birthdateStr) {
    if (!birthdateStr) return '';
    const b = new Date(birthdateStr);
    if (isNaN(b.getTime())) return '';
    const now = new Date();
    let age = now.getFullYear() - b.getFullYear();
    const m = now.getMonth() - b.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < b.getDate())) {
        age--;
    }
    return age >= 0 ? age : '';
}

const bdInput = document.getElementById('regBirthdate');
const ageInput = document.getElementById('regAge');
if (bdInput) {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 18);
    bdInput.max = d.toISOString().split('T')[0];

    const updateAgeDisplay = () => {
        const age = computeAge(bdInput.value);
        if (ageInput) ageInput.value = age !== '' ? age + ' yrs' : '';
    };

    bdInput.addEventListener('change', updateAgeDisplay);
    bdInput.addEventListener('input', updateAgeDisplay);
    if (bdInput.value) updateAgeDisplay();
}

// Explain what each account type means as the user switches & toggle background fields
const roleHints = {
    user:  'Applicants can browse and apply for jobs right away. Profile details auto-populate when applying!',
    staff: 'Staff accounts need administrator approval before you can sign in.'
};
const applicantSection = document.getElementById('applicantBackgroundSection');
document.querySelectorAll('input[name="reg_role"]').forEach(r => {
    r.addEventListener('change', () => {
        const hint = document.getElementById('roleHint');
        if (hint) hint.textContent = roleHints[r.value] || '';
        if (applicantSection) {
            applicantSection.style.display = (r.value === 'staff') ? 'none' : 'block';
        }
        document.querySelectorAll('.req-star').forEach(s => {
            s.style.display = (r.value === 'staff') ? 'none' : 'inline';
        });
    });
});

// Security: passwords cannot be copied out of, or pasted into, these fields.
// Forces the user to actually retype the password in Confirm Password.
['regPassword', 'regConfirm'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    ['copy', 'cut', 'paste', 'drop'].forEach(evt =>
        el.addEventListener(evt, e => e.preventDefault())
    );
});

function toggleRegPass() {
    const inp  = document.getElementById('regPassword');
    const icon = document.getElementById('regEyeIcon');
    if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else                         { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}

function checkRegPasswordStrength() {
    const pw  = document.getElementById('regPassword')?.value || '';
    const bar = document.getElementById('regPwStrengthBar');
    const txt = document.getElementById('regPwStrengthText');
    if (!bar || !txt) return;
    let strength = 0;
    if (pw.length >= 8)  strength++;
    if (pw.length >= 12) strength++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) strength++;
    if (/\d/.test(pw))   strength++;
    if (/[^A-Za-z0-9]/.test(pw)) strength++;
    const levels = [
        { pct: '0%',   cls: '',           label: '' },
        { pct: '25%',  cls: 'bg-danger',  label: 'Weak' },
        { pct: '50%',  cls: 'bg-warning', label: 'Fair' },
        { pct: '75%',  cls: 'bg-info',    label: 'Good' },
        { pct: '100%', cls: 'bg-success', label: 'Strong' }
    ];
    const lvl = levels[Math.min(strength, 4)];
    bar.style.width = lvl.pct;
    bar.className   = 'progress-bar ' + lvl.cls;
    txt.textContent = lvl.label;
}

document.getElementById('registerForm')?.addEventListener('submit', function (e) {
    this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    this.querySelectorAll('.invalid-feedback.d-block').forEach(el => el.remove());

    const showErr = (id, msg) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('is-invalid');
        const wrapper = el.closest('.input-group') || el;
        const fb = document.createElement('div');
        fb.className = 'invalid-feedback d-block';
        fb.textContent = msg;
        wrapper.insertAdjacentElement('afterend', fb);
        el.addEventListener('input', () => { el.classList.remove('is-invalid'); fb.remove(); }, { once: true });
    };

    const isApplicant = document.getElementById('roleApplicant')?.checked;
    const name        = document.getElementById('regName')?.value.trim();
    const email       = document.getElementById('regEmail')?.value.trim();
    const contact     = document.getElementById('regContact')?.value.trim();
    const birthdate   = document.getElementById('regBirthdate')?.value.trim();
    const address     = document.getElementById('regAddress')?.value.trim();
    const elementary  = document.getElementById('regElementary')?.value.trim();
    const jhs         = document.getElementById('regJhs')?.value.trim();
    const shs         = document.getElementById('regShs')?.value.trim();
    const college     = document.getElementById('regCollege')?.value.trim();
    const skills      = document.getElementById('regSkills')?.value.trim();
    const experience  = document.getElementById('regExperience')?.value.trim();
    const pw          = document.getElementById('regPassword')?.value;
    const confirm     = document.getElementById('regConfirm')?.value;
    let valid = true;

    if (!name)    { showErr('regName',     'Full name is required.');                    valid = false; }
    if (!email)   { showErr('regEmail',    'Email address is required.');                valid = false; }
    if (!contact) { showErr('regContact',  'Contact number is required.');               valid = false; }
    else if (!/^\d{11}$/.test(contact)) { showErr('regContact', 'Contact number must be exactly 11 digits.'); valid = false; }

    if (isApplicant) {
        if (!birthdate) {
            showErr('regBirthdate', 'Birthdate is required.');
            valid = false;
        } else {
            const age = computeAge(birthdate);
            if (age === '' || age < 18) {
                showErr('regBirthdate', 'Applicants must be at least 18 years old to register.');
                valid = false;
            } else if (age > 80) {
                showErr('regBirthdate', 'Age must be 80 years old or below.');
                valid = false;
            }
        }
        if (!address)    { showErr('regAddress',    'Residential address is required.');           valid = false; }
        if (!elementary) { showErr('regElementary', 'Elementary school is required.');             valid = false; }
        if (!jhs)        { showErr('regJhs',        'Junior High School is required.');            valid = false; }
        if (!shs)        { showErr('regShs',        'Senior High School is required.');            valid = false; }
        if (!college)    { showErr('regCollege',    'College / Degree information is required.');  valid = false; }
        if (!skills)     { showErr('regSkills',     'Please enter your skills.');                 valid = false; }
        if (!experience) { showErr('regExperience', 'Please enter your work experience or N/A.'); valid = false; }
    }

    if (!pw) { showErr('regPassword', 'Password is required.'); valid = false; }
    else if (pw.length < 8)              { showErr('regPassword', 'Password must be at least 8 characters.'); valid = false; }
    else if (!/[A-Z]/.test(pw))         { showErr('regPassword', 'Password must contain at least one uppercase letter.'); valid = false; }
    else if (!/[0-9]/.test(pw))         { showErr('regPassword', 'Password must contain at least one number.'); valid = false; }
    else if (!/[^A-Za-z0-9]/.test(pw)) { showErr('regPassword', 'Password must contain at least one special character.'); valid = false; }
    if (pw && confirm && pw !== confirm) { showErr('regConfirm', 'Passwords do not match.'); valid = false; }

    const secQ = document.getElementById('regSecQuestion')?.value;
    const secA = document.getElementById('regSecAnswer')?.value.trim();
    if (!secQ) { showErr('regSecQuestion', 'Please choose a security question.'); valid = false; }
    if (!secA) { showErr('regSecAnswer', 'Security answer is required.'); valid = false; }
    else if (secA.length < 2) { showErr('regSecAnswer', 'Security answer must be at least 2 characters.'); valid = false; }

    if (!valid) { e.preventDefault(); return; }

    const btn = this.querySelector('[type="submit"]');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Creating account…';
        btn.disabled = true;
    }
});
</script>
</body>
</html>
