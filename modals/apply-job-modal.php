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
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="appFullName" placeholder="e.g. Juan Dela Cruz" maxlength="150" required>
                        </div>
                        <div class="col-7 col-sm-8 col-md-3 mb-3">
                            <label class="form-label">Birthdate <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="appBirthdate" required>
                        </div>
                        <div class="col-5 col-sm-4 col-md-3 mb-3">
                            <label class="form-label">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="appAge" placeholder="Auto" readonly required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 col-md-8 mb-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="appAddress" placeholder="e.g. 123 Main St, Quezon City" maxlength="500" required>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
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
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Elementary <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="appElementary" placeholder="School name" maxlength="200" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Junior High School (JHS) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="appJhs" placeholder="School name" maxlength="200" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">Senior High School (SHS) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="appShs" placeholder="School name" maxlength="200" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">College <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="appCollege" placeholder="School name &amp; Course" maxlength="200" required>
                        </div>
                    </div>

                    <!-- ── Section: Additional Information ── -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4">
                        <i class="bi bi-info-circle me-1"></i>Additional Information
                    </h6>
                    <div class="mb-3">
                        <label class="form-label">Skills <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="appSkills" rows="3" placeholder="e.g. HTML, CSS, JavaScript, Teamwork, Communication" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Work Experience <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="appExperience" rows="3" placeholder="e.g. Intern at ABC Corp (2024-2025) or N/A if none" required></textarea>
                    </div>

                    <!-- ── Section: Documents & Requirements ── -->
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3 mt-4">
                        <h6 class="fw-bold text-primary mb-0">
                            <i class="bi bi-paperclip me-1"></i>Required Documents <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-2">All 5 Required</span>
                        </h6>
                        <span class="text-muted small">Max 5 MB per document</span>
                    </div>

                    <div class="row g-3">
                        <!-- 1. Resume / CV (Mandatory) -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="appResume">
                                <i class="bi bi-file-earmark-person me-1 text-primary"></i>Resume / Curriculum Vitae <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="appResume" name="resume"
                                   accept=".pdf,.doc,.docx" required>
                            <div class="form-text">
                                PDF, DOC, DOCX &bull; <strong class="text-danger">Required</strong>
                            </div>
                            <div id="resumeFileInfo" class="mt-2 d-none">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-file-earmark-check me-1"></i>
                                    <span id="resumeFileName"></span>
                                    <span id="resumeFileSize" class="ms-1 text-muted"></span>
                                </span>
                            </div>
                        </div>

                        <!-- 2. Application Letter (Mandatory) -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="appLetter">
                                <i class="bi bi-envelope-paper me-1 text-primary"></i>Application Letter <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="appLetter" name="application_letter"
                                   accept=".pdf,.doc,.docx" required>
                            <div class="form-text">
                                PDF, DOC, DOCX &bull; <strong class="text-danger">Required</strong>
                            </div>
                            <div id="appLetterFileInfo" class="mt-2 d-none">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-file-earmark-check me-1"></i>
                                    <span id="appLetterFileName"></span>
                                    <span id="appLetterFileSize" class="ms-1 text-muted"></span>
                                </span>
                            </div>
                        </div>

                        <!-- 3. Personal Data Sheet (PDS) (Mandatory) -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="appPds">
                                <i class="bi bi-card-checklist me-1 text-primary"></i>Personal Data Sheet (PDS) <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="appPds" name="pds"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <div class="form-text">
                                CS Form 212 &bull; PDF, Word, JPG, PNG &bull; <strong class="text-danger">Required</strong>
                            </div>
                            <div id="pdsFileInfo" class="mt-2 d-none">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-file-earmark-check me-1"></i>
                                    <span id="pdsFileName"></span>
                                    <span id="pdsFileSize" class="ms-1 text-muted"></span>
                                </span>
                            </div>
                        </div>

                        <!-- 4. CSC Eligibility (Mandatory) -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="appCsc">
                                <i class="bi bi-award me-1 text-primary"></i>Certificate of CSC Eligibility <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="appCsc" name="csc_eligib"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <div class="form-text">
                                Career Service / PRC / RA 1080 &bull; PDF, JPG, PNG &bull; <strong class="text-danger">Required</strong>
                            </div>
                            <div id="cscFileInfo" class="mt-2 d-none">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-file-earmark-check me-1"></i>
                                    <span id="cscFileName"></span>
                                    <span id="cscFileSize" class="ms-1 text-muted"></span>
                                </span>
                            </div>
                        </div>

                        <!-- 5. Transcript of Records (TOR) (Mandatory) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="appTor">
                                <i class="bi bi-journal-bookmark me-1 text-primary"></i>Transcript of Records (TOR) <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="appTor" name="tor"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <div class="form-text">
                                Official College / Academic TOR &bull; PDF, Word, JPG, PNG &bull; <strong class="text-danger">Required</strong>
                            </div>
                            <div id="torFileInfo" class="mt-2 d-none">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-file-earmark-check me-1"></i>
                                    <span id="torFileName"></span>
                                    <span id="torFileSize" class="ms-1 text-muted"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer d-flex flex-column-reverse flex-sm-row gap-2">
                <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary w-100 w-sm-auto" id="submitAppBtn" onclick="submitApplication()">
                    <i class="bi bi-send me-1"></i>Submit Application
                </button>
            </div>
        </div>
    </div>
</div>
