<!-- ============================================
     Modal: Approve Application & Schedule Interview (Admin & Staff)
     ============================================ -->
<div class="modal fade" id="approveAppModal" tabindex="-1" aria-labelledby="approveAppModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approveAppModalLabel">
                    <i class="bi bi-calendar2-check me-2"></i><span id="approveModalTitle">Approve &amp; Schedule Interview</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveAppForm" onsubmit="handleApproveSubmit(event)">
                <div class="modal-body py-3">
                    <input type="hidden" id="approveAppId" value="">
                    
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-3 border">
                        <div class="avatar bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:45px; height:45px; font-size:1.3rem;">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <div class="overflow-hidden">
                            <h6 class="fw-bold mb-0 text-dark text-truncate" id="approveApplicantName">—</h6>
                            <div class="text-muted small text-truncate">
                                <i class="bi bi-briefcase me-1"></i><span id="approveJobTitle">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="approveInterviewDate" class="form-label small fw-bold text-dark">
                            Interview Date &amp; Time <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" class="form-control" id="approveInterviewDate" required>
                        <div class="form-text small text-muted">
                            <i class="bi bi-clock me-1"></i>Set the scheduled interview date and time.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="approveInterviewNotes" class="form-label small fw-bold text-dark">
                            Interview Venue / Online Meeting Link / Instructions
                        </label>
                        <textarea class="form-control" id="approveInterviewNotes" rows="3" placeholder="e.g. On-site: Room 204 HR Office, Main Bldg. (Bring 2 valid IDs and resume) OR Google Meet link: https://meet.google.com/xyz-abc"></textarea>
                        <div class="form-text small text-muted">
                            <i class="bi bi-info-circle me-1"></i>This schedule and instructions will be emailed directly to the applicant and shown in their portal.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="approveSubmitBtn">
                        <i class="bi bi-send-check me-1"></i>Confirm &amp; Notify Candidate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
