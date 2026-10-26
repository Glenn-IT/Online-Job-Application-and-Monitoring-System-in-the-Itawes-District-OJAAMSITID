<?php
require_once __DIR__ . "/../../config/auth.php";
requireUser();

$basePath    = "../../";
$currentPage = "saved-jobs";
$pageTitle   = "OJAMS - Saved Drafts & Jobs";

$userId = (int)$_SESSION["ojams_user"]["id"];

// ── Fetch all saved jobs and application drafts for this user ─
$stmt = $pdo->prepare("
    SELECT sj.id AS saved_id, sj.saved_at, sj.draft_data, sj.updated_at AS draft_updated_at,
           j.id AS job_id, j.title, j.company, j.description, j.qualification,
           j.job_type, j.location, j.salary_range, j.status, j.deadline, j.date_posted,
           u.full_name AS poster_name,
           (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.user_id = ?) AS already_applied
    FROM saved_jobs sj
    JOIN jobs j ON j.id = sj.job_id
    LEFT JOIN users u ON u.id = j.created_by
    WHERE sj.user_id = ?
    ORDER BY sj.saved_at DESC
");
$stmt->execute([$userId, $userId]);
$savedJobs = $stmt->fetchAll();

$totalSaved  = count($savedJobs);
$totalDrafts = count(array_filter($savedJobs, fn($j) => !empty($j['draft_data'])));
$totalOpen   = count(array_filter($savedJobs, fn($j) => $j['status'] === 'Open'));

// Company monogram helpers
function getCompanyInitials(string $company): string {
    $words = preg_split('/\s+/', trim($company));
    if (empty($words) || $words[0] === '') return 'JB';
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($words[0], 0, 2));
}

function getCompanyPalette(string $company): array {
    $palettes = [
        ['bg' => '#eef2ff', 'color' => '#4338ca'],
        ['bg' => '#f0fdf4', 'color' => '#15803d'],
        ['bg' => '#f0f9ff', 'color' => '#0369a1'],
        ['bg' => '#fefce8', 'color' => '#a16207'],
        ['bg' => '#faf5ff', 'color' => '#7e22ce'],
        ['bg' => '#fff7ed', 'color' => '#c2410c'],
        ['bg' => '#fdf2f8', 'color' => '#be185d'],
    ];
    $idx = abs(crc32($company)) % count($palettes);
    return $palettes[$idx];
}

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

include $basePath . "layouts/header.php";
include $basePath . "layouts/navbar-user.php";
?>

<style>
.saved-hero {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
    color: #ffffff !important;
    border-radius: 16px;
    padding: 2.2rem 2rem;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}
.saved-hero::after {
    content: '';
    position: absolute;
    right: -40px; top: -40px;
    width: 220px; height: 220px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}
.saved-hero-badge {
    background: rgba(255, 255, 255, 0.15) !important;
    border: 1px solid rgba(255, 255, 255, 0.28) !important;
    color: #ffffff !important;
    border-radius: 50px;
    padding: 0.38rem 0.95rem;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    letter-spacing: 0.4px;
    margin-bottom: 0.75rem;
    backdrop-filter: blur(4px);
}
.saved-hero h2 {
    color: #ffffff !important;
    font-weight: 800 !important;
    font-size: 1.85rem;
    letter-spacing: -0.3px;
    margin-bottom: 0.5rem;
}
.saved-hero p {
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 0.98rem;
    line-height: 1.55;
    margin-bottom: 1.25rem;
}
.saved-stat-chip {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 50px;
    padding: 0.4rem 1rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: #ffffff !important;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    backdrop-filter: blur(4px);
}
.saved-stat-chip strong {
    color: #ffffff !important;
    font-weight: 800;
}
.saved-hero-btn {
    background: #fbbf24 !important;
    border: 1px solid #f59e0b !important;
    color: #1e1b4b !important;
    font-weight: 700 !important;
    padding: 0.65rem 1.4rem;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.saved-hero-btn:hover {
    background: #f59e0b !important;
    color: #0f172a !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(245, 158, 11, 0.45);
}
.saved-job-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(15,23,42,0.06);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
}
.saved-job-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(15,23,42,0.12);
}
.company-avatar-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.1rem;
    flex-shrink: 0;
}
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
.draft-callout {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-left: 4px solid #f59e0b;
    border-radius: 8px;
    padding: 0.65rem 0.85rem;
    font-size: 0.84rem;
}
.empty-saved {
    padding: 4rem 1rem;
    text-align: center;
    background: #ffffff;
    border-radius: 18px;
    border: 2px dashed #cbd5e1;
}
.empty-saved-icon {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: #eef2ff;
    color: #4f46e5;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem;
    margin: 0 auto 1.2rem;
}
</style>

<div class="container py-4">

    <!-- ── Hero Section ──────────────────────────────────────── -->
    <div class="saved-hero">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <div class="saved-hero-badge">
                    <i class="bi bi-bookmark-star-fill text-warning"></i>
                    <span>Applicant Workspace</span>
                </div>
                <h2>Saved Jobs &amp; Draft Applications</h2>
                <p style="max-width: 580px;">
                    Review your bookmarked opportunities or pick up right where you left off on pending application drafts.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="saved-stat-chip">
                        <i class="bi bi-bookmark-check-fill text-warning"></i>
                        <strong><?= $totalSaved ?></strong> Saved Opportunities
                    </span>
                    <span class="saved-stat-chip">
                        <i class="bi bi-pencil-square text-info"></i>
                        <strong><?= $totalDrafts ?></strong> Drafts In Progress
                    </span>
                    <span class="saved-stat-chip">
                        <i class="bi bi-briefcase-fill text-success"></i>
                        <strong><?= $totalOpen ?></strong> Open Positions
                    </span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="browse-jobs.php" class="saved-hero-btn">
                    <i class="bi bi-search"></i>Explore More Jobs
                </a>
            </div>
        </div>
    </div>

    <!-- ── Filter Tabs & Status Notice ────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="btn-group" role="group" aria-label="Filter saved jobs">
            <button type="button" class="btn btn-outline-primary btn-sm active" onclick="filterSaved('all', this)">
                All (<?= $totalSaved ?>)
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterSaved('draft', this)">
                <i class="bi bi-pencil-square me-1"></i>Drafts (<?= $totalDrafts ?>)
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterSaved('bookmarked', this)">
                <i class="bi bi-bookmark me-1"></i>Bookmarked (<?= $totalSaved - $totalDrafts ?>)
            </button>
        </div>
        <div class="text-muted small">
            <i class="bi bi-info-circle me-1"></i>Drafts automatically clear once you successfully submit your application.
        </div>
    </div>

    <?php if (empty($savedJobs)): ?>
    <!-- ── Empty State ────────────────────────────────────────── -->
    <div class="empty-saved shadow-sm">
        <div class="empty-saved-icon">
            <i class="bi bi-bookmark-x"></i>
        </div>
        <h4 class="fw-bold text-dark mb-2">No Saved Jobs or Drafts Yet</h4>
        <p class="text-muted mb-4" style="max-width: 440px; margin: 0 auto;">
            When browsing jobs, click <strong>Save as Draft</strong> during application or bookmark any opening to review and complete later.
        </p>
        <a href="browse-jobs.php" class="btn btn-primary px-4 py-2 fw-semibold">
            <i class="bi bi-search me-1"></i>Browse Available Jobs
        </a>
    </div>

    <?php else: ?>
    <!-- ── Saved Jobs Grid ────────────────────────────────────── -->
    <div class="row g-4" id="savedJobsGrid">
        <?php foreach ($savedJobs as $item): 
            $hasDraft   = !empty($item['draft_data']);
            $isApplied  = (int)$item['already_applied'] > 0;
            $isOpen     = $item['status'] === 'Open';
            $pal        = getCompanyPalette($item['company']);
            $initials   = getCompanyInitials($item['company']);
            $jobType    = $item['job_type'] ?: 'Full-time';
            $location   = $item['location'] ?: 'Piat / Itawes District';
            $salary     = $item['salary_range'] ?: 'Standard Salary';
            $deadline   = !empty($item['deadline']) ? date('M j, Y', strtotime($item['deadline'])) : 'Open until filled';
            $filterType = $hasDraft ? 'draft' : 'bookmarked';
        ?>
        <div class="col-md-6 col-lg-4 saved-item" data-filter="<?= $filterType ?>">
            <div class="card saved-job-card h-100 bg-white">
                <div class="card-body p-4 d-flex flex-column">
                    <!-- Top: Monogram & Company -->
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="company-avatar-box" style="background: <?= $pal['bg'] ?>; color: <?= $pal['color'] ?>;">
                            <?= $initials ?>
                        </div>
                        <div class="overflow-hidden">
                            <span class="text-muted small text-truncate d-block fw-semibold"><?= htmlspecialchars($item['company']) ?></span>
                            <h5 class="fw-bold text-dark text-truncate mb-0" title="<?= htmlspecialchars($item['title']) ?>">
                                <?= htmlspecialchars($item['title']) ?>
                            </h5>
                        </div>
                    </div>

                    <!-- Badges -->
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="tag-pill <?= getJobTypeClass($jobType) ?>">
                            <i class="bi bi-clock-history"></i><?= htmlspecialchars($jobType) ?>
                        </span>
                        <span class="tag-pill tag-location">
                            <i class="bi bi-geo-alt"></i><?= htmlspecialchars($location) ?>
                        </span>
                        <span class="tag-pill tag-salary">
                            <i class="bi bi-cash-stack"></i><?= htmlspecialchars($salary) ?>
                        </span>
                    </div>

                    <!-- Status Banner -->
                    <?php if ($hasDraft): ?>
                    <div class="draft-callout mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-warning-emphasis"><i class="bi bi-pencil-square me-1"></i>Draft Application Saved</strong>
                            <span class="badge bg-warning text-dark" style="font-size:0.68rem;">Incomplete</span>
                        </div>
                        <div class="small text-muted">
                            Saved on <?= date('M j, Y &bull; g:i A', strtotime($item['draft_updated_at'] ?: $item['saved_at'])) ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="p-2.5 rounded-3 bg-light border mb-3 small text-muted">
                        <i class="bi bi-bookmark-check text-primary me-1"></i>Saved to review on <?= date('M j, Y', strtotime($item['saved_at'])) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Requirements snippet -->
                    <div class="mb-3 flex-grow-1">
                        <div class="small fw-semibold text-primary mb-1">
                            <i class="bi bi-mortarboard me-1"></i>Key Requirements:
                        </div>
                        <div class="small text-muted" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            <?= htmlspecialchars($item['qualification']) ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top small text-muted">
                        <span><i class="bi bi-calendar-event me-1"></i>Deadline: <?= htmlspecialchars($deadline) ?></span>
                        <span class="badge <?= $isOpen ? 'bg-success' : 'bg-secondary' ?>"><?= $item['status'] ?></span>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex flex-column gap-2 mt-auto">
                        <?php if ($isApplied): ?>
                            <span class="btn btn-success btn-sm disabled w-100 py-2">
                                <i class="bi bi-check-circle-fill me-1"></i>Application Submitted
                            </span>
                        <?php elseif (!$isOpen): ?>
                            <button class="btn btn-secondary btn-sm w-100 disabled py-2">Position Closed</button>
                        <?php elseif ($hasDraft): ?>
                            <button type="button" class="btn btn-warning text-white fw-bold btn-sm w-100 py-2 shadow-sm"
                                    onclick="continueDraft(<?= $item['job_id'] ?>, '<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['company'], ENT_QUOTES) ?>')">
                                <i class="bi bi-play-circle-fill me-1"></i>Continue Application
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary btn-sm fw-semibold w-100 py-2"
                                    onclick="openApplyModal(<?= $item['job_id'] ?>, '<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($item['company'], ENT_QUOTES) ?>')">
                                <i class="bi bi-send me-1"></i>Apply Now
                            </button>
                        <?php endif; ?>

                        <div class="d-flex gap-2">
                            <a href="job-detail.php?id=<?= $item['job_id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                <i class="bi bi-eye me-1"></i>View Details
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-sm px-3"
                                    title="Remove from saved"
                                    onclick="removeSavedJob(<?= $item['job_id'] ?>, this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- ── Include Standard Apply Job Modal ───────────────────── -->
<?php include $basePath . "modals/apply-job-modal.php"; ?>

<script>
const SAVED_JOBS_HANDLER   = "../../handlers/saved-jobs.php";
const APPLICATIONS_HANDLER = "../../handlers/applications.php";

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || "<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>";
}

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

function filterSaved(type, btn) {
    document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const items = document.querySelectorAll('.saved-item');
    items.forEach(el => {
        if (type === 'all' || el.dataset.filter === type) {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    });
}

function removeSavedJob(jobId, btn) {
    showConfirmModal({
        title: "Remove Saved Job",
        message: "Are you sure you want to remove this job and any saved application draft from your list?",
        confirmBtnText: "Remove",
        confirmBtnClass: "btn-danger",
        onConfirm: () => {
            btnLoading(btn, true, "");

            fetch(SAVED_JOBS_HANDLER, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'remove', job_id: jobId, csrf_token: getCsrfToken() })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    showToast(res.message, 'success');
                    const cardCol = btn.closest('.saved-item');
                    cardCol?.remove();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(res.message || 'Failed to remove job.', 'danger');
                }
            })
            .catch(() => showToast('Request error.', 'danger'))
            .finally(() => btnLoading(btn, false));
        }
    });
}

// ── Open Apply Modal (Fresh or From Profile + Draft Restore) ───
function openApplyModal(jobId, title, company) {
    document.getElementById("applyJobTitle").textContent   = title;
    document.getElementById("applyJobCompany").textContent = company;
    document.getElementById("applicationForm").reset();
    if (typeof clearCertificateRows === 'function') clearCertificateRows('additionalCertsContainer');
    document.getElementById("applicationForm").dataset.jobId = jobId;
    clearAllFieldErrors("applicationForm");
    ['resume', 'appLetter', 'pds', 'csc', 'tor', 'diploma'].forEach(k => {
        document.getElementById(k + "FileInfo")?.classList.add("d-none");
    });

    const sess = <?php 
        $uStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $uData = $uStmt->fetch() ?: $_SESSION["ojams_user"];
        echo json_encode([
            "full_name"      => $uData["full_name"],
            "contact_number" => $uData["contact_number"] ?? "",
            "address"        => $uData["address"] ?? "",
            "birthdate"      => $uData["birthdate"] ?? "",
            "elementary"     => $uData["elementary"] ?? "",
            "jhs"            => $uData["jhs"] ?? "",
            "shs"            => $uData["shs"] ?? "",
            "college"        => $uData["college"] ?? "",
            "skills"         => $uData["skills"] ?? "",
            "experience"     => $uData["experience"] ?? "",
        ]);
    ?>;
    const setV = (id, v) => { const el = document.getElementById(id); if (el) el.value = v || ""; };
    setV("appFullName",   sess.full_name);
    setV("appContact",    sess.contact_number);
    setV("appAddress",    sess.address);
    setV("appBirthdate",  sess.birthdate);
    setV("appElementary", sess.elementary);
    setV("appJhs",        sess.jhs);
    setV("appShs",        sess.shs);
    setV("appCollege",    sess.college);
    setV("appSkills",     sess.skills);
    setV("appExperience", sess.experience);

    const ageEl = document.getElementById("appAge");
    if (sess.birthdate && ageEl) {
        ageEl.value = computeAge(sess.birthdate);
    }

    // Check if a saved draft exists for this job and restore it
    fetch(SAVED_JOBS_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_draft', job_id: jobId, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.has_draft && res.draft) {
            const d = res.draft;
            const setVal = (id, v) => { const el = document.getElementById(id); if (el && v !== undefined && v !== null && v !== "") el.value = v; };
            if (d.full_name)   setVal("appFullName",   d.full_name);
            if (d.birthdate) {
                setVal("appBirthdate", d.birthdate);
                if (ageEl) ageEl.value = computeAge(d.birthdate);
            }
            if (d.address)     setVal("appAddress",    d.address);
            if (d.contact)     setVal("appContact",    d.contact);
            if (d.elementary)  setVal("appElementary", d.elementary);
            if (d.jhs)         setVal("appJhs",        d.jhs);
            if (d.shs)         setVal("appShs",        d.shs);
            if (d.college)     setVal("appCollege",    d.college);
            if (d.skills)      setVal("appSkills",     d.skills);
            if (d.experience)  setVal("appExperience", d.experience);
            showToast("Draft restored! You can continue editing your application.", "info");
        }
    })
    .catch(() => {});

    bootstrap.Modal.getOrCreateInstance(document.getElementById("applyJobModal")).show();
}

// ── Continue Application from Saved Draft ──────────────────────
function continueDraft(jobId, title, company) {
    openApplyModal(jobId, title, company);
}

// ── Save Application as Draft Handler ──────────────────────────
function saveApplicationDraft() {
    const form = document.getElementById("applicationForm");
    const jobId = form ? form.dataset.jobId : null;
    if (!jobId) { showToast("No job selected.", "danger"); return; }

    const g = (id) => document.getElementById(id) ? document.getElementById(id).value.trim() : "";
    const draftData = {
        full_name:   g("appFullName"),
        birthdate:   document.getElementById("appBirthdate")?.value || "",
        age:         g("appAge"),
        address:     g("appAddress"),
        contact:     g("appContact"),
        elementary:  g("appElementary"),
        jhs:         g("appJhs"),
        shs:         g("appShs"),
        college:     g("appCollege"),
        skills:      g("appSkills"),
        experience:  g("appExperience")
    };

    const draftBtn = document.getElementById("saveDraftAppBtn");
    btnLoading(draftBtn, true, "Saving Draft…");

    fetch(SAVED_JOBS_HANDLER, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            action: "save_draft",
            job_id: jobId,
            draft_data: draftData,
            csrf_token: getCsrfToken()
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message, "success");
            const modalEl = document.getElementById("applyJobModal");
            bootstrap.Modal.getInstance(modalEl)?.hide();
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(res.message || "Failed to save draft.", "danger");
        }
    })
    .catch(() => showToast("Error saving application draft.", "danger"))
    .finally(() => btnLoading(draftBtn, false));
}

// ── Submit Application Handler ─────────────────────────────────
function submitApplication() {
    const form  = document.getElementById("applicationForm");
    const jobId = form ? form.dataset.jobId : null;
    if (!jobId) { showToast("No job selected.", "danger"); return; }

    const g = (id) => document.getElementById(id) ? document.getElementById(id).value.trim() : "";
    clearAllFieldErrors("applicationForm");
    let valid = true;

    if (!g("appFullName")) { showFieldError("appFullName", "Full name is required."); valid = false; }
    if (!g("appAddress"))  { showFieldError("appAddress",  "Address is required.");   valid = false; }
    const contactVal = g("appContact");
    if (!contactVal) {
        showFieldError("appContact", "Contact number is required."); valid = false;
    } else if (!/^\d{11}$/.test(contactVal)) {
        showFieldError("appContact", "Contact number must be exactly 11 digits."); valid = false;
    }

    const bdVal = document.getElementById("appBirthdate")?.value;
    if (!bdVal) {
        showFieldError("appBirthdate", "Birthdate is required."); valid = false;
    } else {
        const age = computeAge(bdVal);
        if (age === "" || age < 18) {
            showFieldError("appBirthdate", "Applicants must be at least 18 years old to apply."); valid = false;
        } else if (age > 80) {
            showFieldError("appBirthdate", "Age must be 80 years old or below."); valid = false;
        }
    }

    if (!g("appElementary")) { showFieldError("appElementary", "Elementary school is required."); valid = false; }
    if (!g("appJhs"))        { showFieldError("appJhs", "Junior High School is required."); valid = false; }
    if (!g("appShs"))        { showFieldError("appShs", "Senior High School is required."); valid = false; }
    if (!g("appCollege"))    { showFieldError("appCollege", "College education is required."); valid = false; }
    if (!g("appSkills"))     { showFieldError("appSkills", "Skills are required."); valid = false; }
    if (!g("appExperience")) { showFieldError("appExperience", "Work experience is required."); valid = false; }

    const docs = ['appResume', 'appLetter', 'appPds', 'appCsc', 'appTor', 'appDiploma'];
    docs.forEach(id => {
        const inp = document.getElementById(id);
        if (!inp || !inp.files || !inp.files[0]) {
            showFieldError(id, "This document is required.");
            valid = false;
        }
    });

    if (!valid) {
        showToast("Please complete all required fields and upload all 6 required documents.", "warning");
        return;
    }

    const submitBtn = document.getElementById("submitAppBtn");
    btnLoading(submitBtn, true, "Submitting…");

    bootstrap.Modal.getInstance(document.getElementById("applyJobModal"))?.hide();
    showLoadingModal("Submitting application…");

    const fd = new FormData();
    fd.append("action", "apply");
    fd.append("job_id", jobId);
    fd.append("csrf_token", getCsrfToken());
    fd.append("full_name", g("appFullName"));
    fd.append("email", "<?php echo htmlspecialchars($_SESSION['ojams_user']['email'] ?? '', ENT_QUOTES); ?>");
    fd.append("contact", contactVal);
    fd.append("address", g("appAddress"));
    fd.append("birthdate", bdVal);
    fd.append("age", computeAge(bdVal));
    fd.append("elementary", g("appElementary"));
    fd.append("jhs", g("appJhs"));
    fd.append("shs", g("appShs"));
    fd.append("college", g("appCollege"));
    fd.append("skills", g("appSkills"));
    fd.append("experience", g("appExperience"));

    fd.append("resume", document.getElementById("appResume").files[0]);
    fd.append("application_letter", document.getElementById("appLetter").files[0]);
    fd.append("pds", document.getElementById("appPds").files[0]);
    fd.append("csc_eligib", document.getElementById("appCsc").files[0]);
    fd.append("tor", document.getElementById("appTor").files[0]);
    fd.append("diploma", document.getElementById("appDiploma").files[0]);

    // Append Additional Certificates
    const certRows = document.querySelectorAll("#additionalCertsContainer .cert-upload-row");
    certRows.forEach(row => {
        const fileInput = row.querySelector(".cert-file-input");
        const titleInput = row.querySelector(".cert-title-input");
        if (fileInput && fileInput.files && fileInput.files[0]) {
            fd.append("certificates[]", fileInput.files[0]);
            fd.append("cert_titles[]", titleInput ? titleInput.value.trim() : "");
        }
    });

    fetch(APPLICATIONS_HANDLER, {
        method: "POST",
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        hideLoadingModal();
        showToast(res.message, res.success ? "success" : "danger");
        if (res.success) {
            setTimeout(() => location.reload(), 1200);
        }
    })
    .catch(() => {
        hideLoadingModal();
        showToast("Request error during submission.", "danger");
    })
    .finally(() => btnLoading(submitBtn, false));
}

document.addEventListener("DOMContentLoaded", function () {
    const bdEl = document.getElementById("appBirthdate");
    const ageEl = document.getElementById("appAge");
    if (bdEl) {
        const d = new Date();
        d.setFullYear(d.getFullYear() - 18);
        bdEl.max = d.toISOString().split("T")[0];

        bdEl.addEventListener("change", function () {
            clearFieldError("appBirthdate");
            const age = computeAge(this.value);
            if (ageEl) ageEl.value = age;
            if (this.value) {
                if (age === "" || age < 18) {
                    showFieldError("appBirthdate", "Applicants must be at least 18 years old to apply.");
                } else if (age > 80) {
                    showFieldError("appBirthdate", "Age must be 80 years old or below.");
                }
            }
        });
    }

    const docPreviews = [
        { input: 'appResume', info: 'resumeFileInfo', name: 'resumeFileName', size: 'resumeFileSize' },
        { input: 'appLetter', info: 'appLetterFileInfo', name: 'appLetterFileName', size: 'appLetterFileSize' },
        { input: 'appPds', info: 'pdsFileInfo', name: 'pdsFileName', size: 'pdsFileSize' },
        { input: 'appCsc', info: 'cscFileInfo', name: 'cscFileName', size: 'cscFileSize' },
        { input: 'appTor', info: 'torFileInfo', name: 'torFileName', size: 'torFileSize' },
        { input: 'appDiploma', info: 'diplomaFileInfo', name: 'diplomaFileName', size: 'diplomaFileSize' }
    ];
    docPreviews.forEach(p => {
        const inp = document.getElementById(p.input);
        if (!inp) return;
        inp.addEventListener('change', function () {
            clearFieldError(p.input);
            const infoEl = document.getElementById(p.info);
            const nameEl = document.getElementById(p.name);
            const sizeEl = document.getElementById(p.size);
            if (this.files && this.files[0]) {
                const f = this.files[0];
                if (nameEl) nameEl.textContent = f.name;
                if (sizeEl) sizeEl.textContent = '(' + (f.size / (1024 * 1024)).toFixed(2) + ' MB)';
                if (infoEl) infoEl.classList.remove('d-none');
            } else {
                if (infoEl) infoEl.classList.add('d-none');
            }
        });
    });
});
</script>

<?php include $basePath . "layouts/footer.php"; ?>
