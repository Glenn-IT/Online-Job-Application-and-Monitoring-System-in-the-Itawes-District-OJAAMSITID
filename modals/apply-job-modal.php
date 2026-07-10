<!-- ============================================
     Modal: Apply for Job
     Contains the full application form
     ============================================ -->
<div class="modal fade" id="applyJobModal" tabindex="-1" aria-labelledby="applyJobModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="applyJobModalLabel">
                    <i class="bi bi-send me-2"></i>Job Application Form
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <!-- Job Info Banner -->
                <div class="alert alert-info">
                    <strong>Applying for:</strong>
                    <span id="applyJobTitle">—</span> at
                    <span id="applyJobCompany">—</span>
                </div>

                <form id="applicationForm">
                    <!-- ── Section: Personal Information ── -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-person me-1"></i>Personal Information
                    </h6>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="appFullName" placeholder="e.g. Juan Dela Cruz" maxlength="150" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Birthdate <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="appBirthdate" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="appAge" placeholder="e.g. 25" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="appAddress" placeholder="e.g. 123 Main St, Quezon City" maxlength="500" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="appContact" placeholder="e.g. 09171234567"
                                   inputmode="numeric" maxlength="11" pattern="\d{11}"
                                   oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)" required>
                        </div>
                    </div>

                    <!-- ── Section: Educational Attainment ── -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4">
                        <i class="bi bi-mortarboard me-1"></i>Educational Attainment
                    </h6>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Elementary</label>
                            <input type="text" class="form-control" id="appElementary" placeholder="School name" maxlength="200">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Junior High School (JHS)</label>
                            <input type="text" class="form-control" id="appJhs" placeholder="School name" maxlength="200">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Senior High School (SHS)</label>
                            <input type="text" class="form-control" id="appShs" placeholder="School name" maxlength="200">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">College</label>
                            <input type="text" class="form-control" id="appCollege" placeholder="School name &amp; Course" maxlength="200">
                        </div>
                    </div>

                    <!-- ── Section: Additional Information ── -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4">
                        <i class="bi bi-info-circle me-1"></i>Additional Information
                    </h6>
                    <div class="mb-3">
                        <label class="form-label">Skills</label>
                        <textarea class="form-control" id="appSkills" rows="3" placeholder="e.g. HTML, CSS, JavaScript, Teamwork, Communication"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Work Experience</label>
                        <textarea class="form-control" id="appExperience" rows="3" placeholder="e.g. Intern at ABC Corp (2024-2025)"></textarea>
                    </div>

                    <!-- ── Section: Resume / CV Upload ── -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4">
                        <i class="bi bi-paperclip me-1"></i>Resume / CV
                    </h6>
                    <div class="mb-3">
                        <label class="form-label" for="appResume">
                            Upload Resume <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <input type="file" class="form-control" id="appResume" name="resume"
                               accept=".pdf,.doc,.docx">
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>
                            Accepted formats: PDF, DOC, DOCX &mdash; Max size: 5 MB
                        </div>
                        <div id="resumeFileInfo" class="mt-2 d-none">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                <i class="bi bi-file-earmark-check me-1"></i>
                                <span id="resumeFileName"></span>
                                <span id="resumeFileSize" class="ms-1 text-muted"></span>
                            </span>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="submitAppBtn" onclick="submitApplication()">
                    <i class="bi bi-send me-1"></i>Submit Application
                </button>
            </div>
        </div>
    </div>
</div>
