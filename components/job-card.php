<!-- ============================================
     Component: Job Card
     Usage: Pass $job array to render a single card
     ============================================ -->
<div class="col-md-6 col-lg-4 mb-4">
    <div class="card h-100 shadow-sm border-0 job-card">
        <div class="card-body d-flex flex-column">
            <!-- Job Title -->
            <h5 class="card-title fw-bold text-primary">
                <i class="bi bi-briefcase me-2"></i><?php echo $job['title']; ?>
            </h5>

            <!-- Company -->
            <h6 class="card-subtitle mb-2 text-muted">
                <i class="bi bi-building me-1"></i><?php echo $job['company']; ?>
            </h6>

            <!-- Description -->
            <p class="card-text flex-grow-1">
                <?php echo $job['description']; ?>
            </p>

            <!-- Qualification -->
            <p class="card-text mb-1">
                <small class="text-muted">
                    <i class="bi bi-mortarboard me-1"></i>
                    <strong>Qualifications:</strong> <?php echo $job['qualification']; ?>
                </small>
            </p>

            <!-- Hiring Contact Person -->
            <?php if (!empty($job['contact_person']) || !empty($job['contact_phone'])): ?>
                <p class="card-text mb-2">
                    <small class="text-dark">
                        <i class="bi bi-person-lines-fill me-1 text-primary"></i>
                        <strong>Hiring Contact:</strong>
                        <?php echo htmlspecialchars($job['contact_person'] ?? 'Recruiter'); ?>
                        <?php if (!empty($job['contact_phone'])): ?>
                            <span class="ms-1 fw-semibold text-primary"><i class="bi bi-telephone-fill ms-1 me-1"></i><?php echo htmlspecialchars($job['contact_phone']); ?></span>
                        <?php endif; ?>
                    </small>
                </p>
            <?php endif; ?>

            <!-- Date Posted, Poster & Status -->
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                <small class="text-muted">
                    <i class="bi bi-calendar-event me-1"></i>Posted: <?php echo $job['date_posted']; ?>
                    <?php if (!empty($job['poster_name'])): ?>
                        <span class="ms-1 text-secondary">&bull; By <strong><?php echo htmlspecialchars($job['poster_name']); ?></strong> (<?php echo ucfirst($job['poster_role'] ?? 'Admin'); ?>)</span>
                    <?php endif; ?>
                </small>
                <span class="badge <?php echo $job['status'] === 'Open' ? 'bg-success' : 'bg-secondary'; ?>">
                    <?php echo $job['status']; ?>
                </span>
            </div>

            <!-- Applicant Count -->
            <div class="d-flex align-items-center mb-3">
                <span class="badge bg-light text-dark border me-2">
                    <i class="bi bi-people-fill text-primary me-1"></i>
                    <?php echo $job['applicants'] ?? 0; ?> <?php echo ($job['applicants'] ?? 0) == 1 ? 'Applicant' : 'Applicants'; ?>
                </span>
            </div>

            <!-- Apply Button -->
            <?php if ($job['status'] === 'Open'): ?>
                <button class="btn btn-primary w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#applyJobModal"
                        onclick="setApplyJob(<?php echo $job['id']; ?>, '<?php echo addslashes($job['title']); ?>', '<?php echo addslashes($job['company']); ?>')">
                    <i class="bi bi-send me-1"></i>Apply Now
                </button>
            <?php else: ?>
                <button class="btn btn-secondary w-100" disabled>
                    <i class="bi bi-lock me-1"></i>Closed
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
