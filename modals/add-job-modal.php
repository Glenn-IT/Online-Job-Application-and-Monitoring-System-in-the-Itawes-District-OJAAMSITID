<!-- ============================================
     Modal: Add / Edit Job (Admin) – Unified
     ============================================ -->
<div class="modal fade" id="addJobModal" tabindex="-1" aria-labelledby="addJobModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addJobModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Add New Job Post
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <form id="addJobForm">
                    <div class="mb-3">
                        <label class="form-label">Job Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="jobTitle" placeholder="e.g. Web Developer" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="jobCompany" placeholder="e.g. ABC Technologies" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" rows="3" id="jobDescription" placeholder="Job description..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Qualifications <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="jobQualification" placeholder="e.g. HTML, CSS, JavaScript" maxlength="500" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date Posted</label>
                            <input type="date" class="form-control" id="jobDatePosted">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="jobStatus">
                                <option value="Open" selected>Open</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" onclick="saveJob()">
                    <i class="bi bi-save me-1"></i>Save Job
                </button>
            </div>
        </div>
    </div>
</div>
