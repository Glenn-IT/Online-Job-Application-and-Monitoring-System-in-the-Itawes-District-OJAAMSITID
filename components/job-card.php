<!-- ============================================
     Component: Modern Job Card
     Usage: Pass $job array to render a single card
     ============================================ -->
<?php
$cName     = $job['company'] ?? 'Company';
$words     = preg_split('/\s+/', trim($cName));
$initials  = (count($words) >= 2 && $words[0] !== '') ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)) : strtoupper(substr($cName, 0, 2));
$palettes  = [
    ['bg' => '#eef2ff', 'color' => '#4338ca'],
    ['bg' => '#f0fdf4', 'color' => '#15803d'],
    ['bg' => '#f0f9ff', 'color' => '#0369a1'],
    ['bg' => '#fefce8', 'color' => '#a16207'],
    ['bg' => '#faf5ff', 'color' => '#7e22ce'],
    ['bg' => '#fff1f2', 'color' => '#be123c'],
];
$palette   = $palettes[abs(crc32($cName)) % count($palettes)];
$jtSlug    = strtolower(str_replace(['-', ' '], '', $job['job_type'] ?? ''));
$isOpen    = ($job['status'] ?? 'Open') === 'Open';
$jobId     = (int)($job['id'] ?? 0);
$cnt       = (int)($job['applicants'] ?? 0);
?>
<div class="col-12 col-md-6 col-lg-4 mb-4">
    <div class="job-card-modern h-100 p-3 p-sm-4 shadow-sm">
        
        <!-- Header: Monogram & Company -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <div class="company-monogram" style="background: <?= $palette['bg'] ?>; color: <?= $palette['color'] ?>;">
                    <?= htmlspecialchars($initials) ?>
                </div>
                <div class="min-w-0">
                    <div class="text-muted small fw-semibold text-truncate" title="<?= htmlspecialchars($cName) ?>">
                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($cName) ?>
                    </div>
                    <div class="text-muted" style="font-size: 0.74rem;">
                        <i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($job['date_posted'] ?? '') ?>
                    </div>
                </div>
            </div>
            <span class="badge <?= $isOpen ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?>">
                <?= $isOpen ? 'Open' : 'Closed' ?>
            </span>
        </div>

        <!-- Title -->
        <h5 class="fw-bold mb-2" style="font-size: 1.05rem; line-height: 1.35;">
            <a href="job-detail.php?id=<?= $jobId ?>" class="text-decoration-none text-dark hover-primary text-truncate d-block" title="<?= htmlspecialchars($job['title'] ?? '') ?>">
                <?= htmlspecialchars($job['title'] ?? '') ?>
            </a>
        </h5>

        <!-- Soft Badges -->
        <div class="d-flex flex-wrap gap-1 mb-3">
            <?php if (!empty($job['job_type'])): ?>
            <span class="badge-tag tag-<?= $jtSlug ?>">
                <i class="bi bi-briefcase"></i><?= htmlspecialchars($job['job_type']) ?>
            </span>
            <?php endif; ?>
            <?php if (!empty($job['salary_range'])): ?>
            <span class="badge-tag tag-salary">
                <i class="bi bi-cash"></i><?= htmlspecialchars($job['salary_range']) ?>
            </span>
            <?php endif; ?>
            <?php if (!empty($job['location'])): ?>
            <span class="badge-tag tag-location text-truncate" style="max-width: 160px;" title="<?= htmlspecialchars($job['location']) ?>">
                <i class="bi bi-geo-alt"></i><?= htmlspecialchars($job['location']) ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.55;">
            <?= htmlspecialchars($job['description'] ?? '') ?>
        </p>

        <!-- Requirements -->
        <div class="mb-3 text-truncate" style="font-size: 0.78rem;">
            <span class="text-muted">
                <i class="bi bi-mortarboard me-1 text-primary"></i><strong>Req:</strong> <?= htmlspecialchars($job['qualification'] ?? '') ?>
            </span>
        </div>

        <!-- Meta -->
        <div class="pt-2 border-top d-flex justify-content-between align-items-center mb-3 text-muted" style="font-size: 0.78rem;">
            <span>
                <i class="bi bi-people-fill text-primary me-1"></i><strong><?= $cnt ?></strong> applicant<?= $cnt !== 1 ? 's' : ''; ?>
            </span>
            <?php if (!empty($job['deadline'])): ?>
            <?php $isPast = strtotime($job['deadline']) < strtotime('today'); ?>
            <span class="<?= $isPast ? 'text-danger fw-semibold' : 'text-muted' ?>">
                <i class="bi bi-calendar-x me-1"></i><?= $isPast ? 'Expired' : date('M d', strtotime($job['deadline'])) ?>
            </span>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex gap-2 pt-1 mt-auto">
            <a href="job-detail.php?id=<?= $jobId ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                <i class="bi bi-eye me-1"></i>Details
            </a>
            <?php if ($isOpen): ?>
                <button class="btn btn-primary btn-sm flex-grow-1"
                        data-bs-toggle="modal"
                        data-bs-target="#applyJobModal"
                        onclick="setApplyJob(<?= $jobId ?>, '<?= addslashes($job['title'] ?? '') ?>', '<?= addslashes($cName) ?>')">
                    <i class="bi bi-send me-1"></i>Apply
                </button>
            <?php else: ?>
                <button class="btn btn-secondary btn-sm flex-grow-1" disabled>
                    <i class="bi bi-lock me-1"></i>Closed
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
