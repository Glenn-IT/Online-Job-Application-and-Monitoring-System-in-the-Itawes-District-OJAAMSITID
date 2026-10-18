<!-- ============================================
     Modal: Message Applicant & Request Documents (Admin & Staff)
     ============================================ -->
<div class="modal fade" id="messageApplicantModal" tabindex="-1" aria-labelledby="messageApplicantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-25 border-bottom border-warning-subtle text-dark">
                <h5 class="modal-title fw-bold" id="messageApplicantModalLabel">
                    <i class="bi bi-chat-left-dots-fill text-warning me-2"></i>Message Applicant &amp; Request Documents
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="messageApplicantForm" onsubmit="handleSendMessageSubmit(event)">
                <div class="modal-body py-3">
                    <input type="hidden" id="messageAppId" value="">
                    
                    <!-- Candidate & Job Summary Card -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar bg-warning-subtle text-warning-emphasis rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px; font-size:1.4rem;">
                                <i class="bi bi-person-lines-fill"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <h6 class="fw-bold mb-0 text-dark text-truncate" id="messageApplicantName">—</h6>
                                    <span class="badge bg-secondary-subtle text-secondary" id="messageAppStatus">—</span>
                                </div>
                                <div class="text-muted small text-truncate mb-1">
                                    <i class="bi bi-briefcase me-1"></i><span id="messageJobTitle">—</span>
                                </div>
                                <div class="d-flex gap-2 flex-wrap small">
                                    <span class="badge bg-white text-secondary border" id="messageApplicantEmailWrap">
                                        <i class="bi bi-envelope me-1 text-danger"></i><span id="messageApplicantEmail">—</span>
                                    </span>
                                    <span class="badge bg-white text-secondary border" id="messageApplicantContactWrap">
                                        <i class="bi bi-telephone me-1 text-success"></i><span id="messageApplicantContact">—</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Pre-fill Template Selector -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="messageTemplateSelect" class="form-label small fw-bold text-dark mb-0">
                                <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Quick Notice Template
                            </label>
                            <small class="text-muted">Auto-populates subject &amp; message</small>
                        </div>
                        <select class="form-select form-select-sm" id="messageTemplateSelect" onchange="onMessageTemplateChange(this.value)">
                            <option value="missing_docs" selected>Missing Required Documents Notice</option>
                            <option value="unclear_docs">Document Resubmission / File Unreadable</option>
                            <option value="general_update">General Application Update Request</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>

                    <!-- Missing / Requested Documents Checklist -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark d-flex justify-content-between align-items-center mb-1">
                            <span><i class="bi bi-check2-square text-primary me-1"></i>Select Missing / Requested Documents</span>
                            <span class="small fw-normal text-muted">Applicant will be prompted for these files</span>
                        </label>
                        <div class="border rounded-3 p-3 bg-white">
                            <div class="row g-2" id="messageDocsChecklist">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input msg-doc-check" type="checkbox" value="resume" id="docCheck_resume">
                                        <label class="form-check-label small text-dark" for="docCheck_resume">
                                            <strong>Resume / Curriculum Vitae</strong>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input msg-doc-check" type="checkbox" value="application_letter" id="docCheck_application_letter">
                                        <label class="form-check-label small text-dark" for="docCheck_application_letter">
                                            <strong>Application Letter</strong>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input msg-doc-check" type="checkbox" value="pds" id="docCheck_pds">
                                        <label class="form-check-label small text-dark" for="docCheck_pds">
                                            <strong>Personal Data Sheet (PDS)</strong>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input msg-doc-check" type="checkbox" value="csc_eligib" id="docCheck_csc_eligib">
                                        <label class="form-check-label small text-dark" for="docCheck_csc_eligib">
                                            <strong>Certificate of Eligibility</strong>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input msg-doc-check" type="checkbox" value="tor" id="docCheck_tor">
                                        <label class="form-check-label small text-dark" for="docCheck_tor">
                                            <strong>Transcript of Records (TOR)</strong>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input msg-doc-check" type="checkbox" value="diploma" id="docCheck_diploma">
                                        <label class="form-check-label small text-dark" for="docCheck_diploma">
                                            <strong>College Diploma / Certificate</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notice Subject -->
                    <div class="mb-3">
                        <label for="messageSubject" class="form-label small fw-bold text-dark">
                            Notice Subject <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="messageSubject" required
                               placeholder="e.g. Action Required: Missing Documents for Your Application"
                               value="Action Required: Missing Documents for Your Application">
                    </div>

                    <!-- Message Body / Instructions -->
                    <div class="mb-3">
                        <label for="messageBody" class="form-label small fw-bold text-dark">
                            Instructions &amp; Message for Applicant <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="messageBody" rows="4" required
                            placeholder="Specify instructions or additional details for the applicant..."></textarea>
                        <div class="form-text small text-muted">
                            <i class="bi bi-info-circle me-1"></i>A direct link to update their credentials will be automatically appended.
                        </div>
                    </div>

                    <!-- Delivery Channels -->
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="small fw-bold text-dark mb-2">
                            <i class="bi bi-broadcast me-1"></i>Notification Delivery Channels:
                        </div>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="messageSendEmail" checked>
                                <label class="form-check-label small" for="messageSendEmail">
                                    <i class="bi bi-envelope-at-fill text-danger me-1"></i>Send via Gmail (Email)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="messageSendSms" checked>
                                <label class="form-check-label small" for="messageSendSms">
                                    <i class="bi bi-chat-text-fill text-primary me-1"></i>Send via PhilSMS (SMS)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning fw-bold px-3" id="messageSubmitBtn">
                        <i class="bi bi-send-fill me-1"></i>Send Notice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
