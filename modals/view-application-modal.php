<!-- ============================================
     Modal: View Application Details (Admin)
     ============================================ -->
<div class="modal fade" id="viewApplicationModal" tabindex="-1" aria-labelledby="viewApplicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewApplicationModalLabel">
                    <i class="bi bi-eye me-2"></i>Application Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Personal Information</h6>
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:45%;">Full Name</th>    <td id="viewAppName">—</td></tr>
                            <tr><th class="text-muted">Email</th>                            <td id="viewAppEmail">—</td></tr>
                            <tr><th class="text-muted">Contact</th>                          <td id="viewAppContact">—</td></tr>
                            <tr><th class="text-muted">Address</th>                          <td id="viewAppAddress">—</td></tr>
                            <tr><th class="text-muted">Birthdate</th>                        <td id="viewAppBirthdate">—</td></tr>
                            <tr><th class="text-muted">Age</th>                              <td id="viewAppAge">—</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Application Info</h6>
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:45%;">Job Title</th>    <td id="viewAppJobTitle">—</td></tr>
                            <tr><th class="text-muted">Company</th>                          <td id="viewAppCompany">—</td></tr>
                            <tr><th class="text-muted">Hiring Contact</th>                   <td id="viewAppContactPerson">—</td></tr>
                            <tr><th class="text-muted">Contact Phone</th>                    <td id="viewAppContactPhone">—</td></tr>
                            <tr><th class="text-muted">Date Applied</th>                     <td id="viewAppDate">—</td></tr>
                            <tr><th class="text-muted">Status</th>                           <td><span id="viewAppStatus" class="badge bg-secondary">—</span></td></tr>
                        </table>
                        <h6 class="fw-bold text-primary border-bottom pb-2 mt-3">Education</h6>
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:45%;">Elementary</th>   <td id="viewAppElemSchool">—</td></tr>
                            <tr><th class="text-muted">Junior HS</th>                        <td id="viewAppJhsSchool">—</td></tr>
                            <tr><th class="text-muted">Senior HS</th>                        <td id="viewAppShsSchool">—</td></tr>
                            <tr><th class="text-muted">College</th>                          <td id="viewAppCollege">—</td></tr>
                        </table>
                    </div>
                    <div class="col-12">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Skills &amp; Experience</h6>
                        <div class="mb-2"><strong>Skills:</strong> <span id="viewAppSkills">—</span></div>
                        <div><strong>Experience:</strong> <span id="viewAppExperience">—</span></div>
                    </div>

                    <!-- Interview Schedule (if approved) -->
                    <div class="col-12" id="viewAppInterviewRow" style="display:none;">
                        <div class="alert alert-success d-flex align-items-start gap-3 mb-0 border-success-subtle">
                            <i class="bi bi-calendar2-check-fill fs-3 text-success"></i>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading fw-bold mb-1 text-success">
                                    <i class="bi bi-calendar2-event me-1"></i>Scheduled Interview
                                </h6>
                                <p class="mb-1"><strong>Date &amp; Time:</strong> <span id="viewAppInterviewDate" class="badge bg-success fs-6">—</span></p>
                                <div id="viewAppInterviewNotesWrap" style="display:none;">
                                    <p class="mb-0 text-muted small"><strong>Instructions / Venue:</strong> <span id="viewAppInterviewNotes" class="text-dark">—</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resume -->
                    <div class="col-12" id="viewAppResumeRow" style="display:none;">
                        <h6 class="fw-bold text-primary border-bottom pb-2">Resume / CV</h6>
                        <div id="viewAppResume"></div>
                    </div>

                    <!-- Status History -->
                    <div class="col-12">
                        <h6 class="fw-bold text-primary border-bottom pb-2">
                            <i class="bi bi-clock-history me-1"></i>Status History
                        </h6>
                        <div id="viewAppHistory">
                            <p class="text-muted small fst-italic mb-0">
                                <i class="bi bi-info-circle me-1"></i>No status changes recorded yet.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
