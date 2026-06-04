<?php

require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$pageTitle   = "OJAMS - Manage Jobs";
$basePath    = "../../";
$currentPage = "manage-jobs";

// ── Fetch all jobs from DB ──────────────────────────────────
$jobs = $pdo->query("SELECT * FROM jobs ORDER BY date_posted DESC")->fetchAll();

// Include header and admin navbar
include $basePath . 'layouts/header.php';
include $basePath . 'layouts/navbar-admin.php';
?>

<div class="admin-layout">
    <?php include $basePath . 'layouts/sidebar-admin.php'; ?>
    <main class="admin-main">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-kanban me-2 text-primary"></i>Manage Jobs
                    </h2>
                    <p class="text-muted mb-0">Create, edit, and manage job postings.</p>
                </div>
                <button class="btn btn-success" onclick="openAddJobModal()">
                    <i class="bi bi-plus-circle me-1"></i>Add New Job
                </button>
            </div>

            <!-- Jobs Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <?php
                            $columns = ['#', 'Job Title', 'Company', 'Date Posted', 'Status', 'Actions'];
                            include $basePath . 'components/table-header.php';
                            ?>
                            <tbody id="jobsTableBody">
                                <?php if (empty($jobs)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No jobs found. Add your first job post!</td></tr>
                                <?php else: ?>
                                <?php $count = 1; foreach ($jobs as $job): ?>
                                    <tr>
                                        <td><?= $count++ ?></td>
                                        <td>
                                            <i class="bi bi-briefcase me-1 text-primary"></i>
                                            <?= htmlspecialchars($job['title']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($job['company']) ?></td>
                                        <td><?= $job['date_posted'] ?></td>
                                        <td>
                                            <span class="badge <?= $job['status'] === 'Open' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $job['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-warning me-1"
                                                onclick="editJob(
                                                    this.dataset.title,
                                                    this.dataset.company,
                                                    this.dataset.description,
                                                    this.dataset.qualification,
                                                    this.dataset.date,
                                                    this.dataset.status,
                                                    <?= $job['id'] ?>)"
                                                data-title="<?= htmlspecialchars($job['title'], ENT_QUOTES) ?>"
                                                data-company="<?= htmlspecialchars($job['company'], ENT_QUOTES) ?>"
                                                data-description="<?= htmlspecialchars($job['description'], ENT_QUOTES) ?>"
                                                data-qualification="<?= htmlspecialchars($job['qualification'], ENT_QUOTES) ?>"
                                                data-date="<?= $job['date_posted'] ?>"
                                                data-status="<?= $job['status'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                onclick="deleteJob(<?= $job['id'] ?>)">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </main>
</div>

<!-- Modals -->
<?php include $basePath . 'modals/add-job-modal.php'; ?>

<script>
// ── Manage Jobs ──
const JOBS_HANDLER = '../../handlers/jobs.php';

// Override: open Add modal
function openAddJobModal() {
    _editingJobId = null;
    document.getElementById('addJobModalLabel').innerHTML =
        '<i class="bi bi-plus-circle me-2"></i>Add New Job Post';
    document.getElementById('addJobForm').reset();
    document.getElementById('jobDatePosted').value = new Date().toISOString().split('T')[0];
    new bootstrap.Modal(document.getElementById('addJobModal')).show();
};

// Override: open Edit modal
function editJob(title, company, description, qualification, datePosted, status, id) {
    _editingJobId = id;
    document.getElementById('addJobModalLabel').innerHTML =
        '<i class="bi bi-pencil-square me-2"></i>Edit Job Post';
    document.getElementById('jobTitle').value         = title;
    document.getElementById('jobCompany').value       = company;
    document.getElementById('jobDescription').value   = description;
    document.getElementById('jobQualification').value = qualification;
    document.getElementById('jobDatePosted').value    = datePosted;
    document.getElementById('jobStatus').value        = status;
    new bootstrap.Modal(document.getElementById('addJobModal')).show();
};

// Override: save (add or edit) via fetch
function saveJob() {
    const title         = document.getElementById('jobTitle')?.value.trim();
    const company       = document.getElementById('jobCompany')?.value.trim();
    const description   = document.getElementById('jobDescription')?.value.trim();
    const qualification = document.getElementById('jobQualification')?.value.trim();
    const date_posted   = document.getElementById('jobDatePosted')?.value;
    const status        = document.getElementById('jobStatus')?.value;
    if (!title || !company || !description || !qualification) {
        showToast('Please fill in all required fields.', 'warning');
        return;
    }
    const payload = _editingJobId
        ? { action: 'edit', id: _editingJobId, title, company, description, qualification, date_posted, status, csrf_token: getCsrfToken() }
        : { action: 'add',                     title, company, description, qualification, date_posted, status, csrf_token: getCsrfToken() };
    fetch(JOBS_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        bootstrap.Modal.getInstance(document.getElementById('addJobModal'))?.hide();
        if (res.success) {
            showToast(_editingJobId ? 'Job updated successfully!' : 'Job added successfully!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(() => showToast('Request failed. Please try again.', 'danger'));
};

// Override: delete via fetch
function deleteJob(id) {
    if (!confirm('Are you sure you want to delete this job post?')) return;
    fetch(JOBS_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: id, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.success ? 'Job deleted.' : res.message, res.success ? 'danger' : 'warning');
        if (res.success) setTimeout(() => location.reload(), 800);
    })
    .catch(() => showToast('Request failed. Please try again.', 'danger'));
};
</script>
<?php include $basePath . 'layouts/footer.php'; ?>