<!-- ============================================
     Modal: Reject Application & Send Feedback (Admin & Staff)
     ============================================ -->
<div class="modal fade" id="rejectAppModal" tabindex="-1" aria-labelledby="rejectAppModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectAppModalLabel">
                    <i class="bi bi-x-circle me-2"></i><span id="rejectModalTitle">Reject Application &amp; Send Feedback</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectAppForm" onsubmit="handleRejectSubmit(event)">
                <div class="modal-body py-3">
                    <input type="hidden" id="rejectAppId" value="">
                    
                    <!-- Candidate & Job Summary Card -->
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-3 border">
                        <div class="avatar bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:45px; height:45px; font-size:1.3rem;">
                            <i class="bi bi-person-x-fill"></i>
                        </div>
                        <div class="overflow-hidden">
                            <h6 class="fw-bold mb-0 text-dark text-truncate" id="rejectApplicantName">—</h6>
                            <div class="text-muted small text-truncate">
                                <i class="bi bi-briefcase me-1"></i><span id="rejectJobTitle">—</span>
                            </div>
                        </div>
                    </div>

                    <!-- Primary Reason Dropdown -->
                    <div class="mb-3">
                        <label for="rejectReasonSelect" class="form-label small fw-bold text-dark">
                            Primary Reason for Rejection <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="rejectReasonSelect" required onchange="onRejectReasonChange(this)">
                            <option value="" disabled selected>-- Select a standard reason --</option>
                            <option value="Qualifications did not meet minimum job requirements">Qualifications did not match minimum job requirements</option>
                            <option value="Position has already been filled by another candidate">Position has already been filled by another candidate</option>
                            <option value="Incomplete required documentation / invalid credentials">Incomplete required documentation / invalid credentials</option>
                            <option value="Relevant work experience or skillset not aligned with position">Relevant work experience or skillset not aligned with position</option>
                            <option value="Candidate did not attend or pass the scheduled interview">Candidate did not attend or pass the scheduled interview</option>
                            <option value="other">Other / Custom feedback reason</option>
                        </select>
                        <div class="form-text small text-muted">
                            <i class="bi bi-tag me-1"></i>Select a categorized reason for tracking and reporting.
                        </div>
                    </div>

                    <!-- Custom Details / Feedback Textarea -->
                    <div class="mb-3">
                        <label for="rejectNotes" class="form-label small fw-bold text-dark">
                            Specific Feedback &amp; Advice for Candidate <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <textarea class="form-control" id="rejectNotes" rows="3" placeholder="Provide constructive feedback, encouragement, or specific details for this decision..."></textarea>
                        <div class="form-text small text-muted">
                            <i class="bi bi-envelope-check me-1"></i>This reason and feedback will be directly emailed and sent via SMS to the applicant.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger" id="rejectSubmitBtn">
                        <i class="bi bi-send-x me-1"></i>Confirm &amp; Send Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
