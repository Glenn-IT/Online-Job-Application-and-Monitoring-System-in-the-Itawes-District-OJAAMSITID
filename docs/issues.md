# OJAMS Issues & Feature Tracking

## [RESOLVED] Add Pending Function on Action (Admin & Staff)

### Request:
`http://localhost/OJAMS/pages/admin/applications.php`:
> "can you adda a pending function on the action"

### Implementation Summary:
1. **Backend Handler (`handlers/applications.php`)**:
   - Updated `updateStatus` and `bulkUpdateStatus` to validate and accept `'Pending'`.
   - Clears `interview_date = NULL, interview_notes = NULL, rejection_reason = NULL` upon reverting an application to `Pending`.
   - Records transition in `application_status_history` and logs event in `activity_logs`.
   - Dispatches email and SMS status alerts informing candidate their application is under pending review.

2. **Email Bridge (`config/mailer.php`)**:
   - Added support for `Pending` status in `sendApplicationStatusEmail()` with amber "Pending Review ⏳" badge (`#d97706`), branded content, and tracking button.

3. **SMS Gateway Bridge (`config/sms.php`)**:
   - Added support for `Pending` status in `sendApplicationStatusSms()` notifying candidate via PhilSMS.

4. **Admin Applications Page (`pages/admin/applications.php`)**:
   - Added a **Pending** action button (`<i class="bi bi-arrow-counterclockwise"></i> Pending`) to table rows for any application whose status is not currently `Pending`.
   - Restyled the Notice button as `btn-outline-info` for clear visual distinction across actions (*Approve = Green, Reject = Red, Pending = Warning/Amber, Notice = Cyan/Info, View = Blue*).
   - Added `setAppPending(id, applicantName)` with confirmation modal and feedback toast.
   - Added `Set Pending All` to the bulk selection toolbar and updated `bulkAppAction('Pending')`.

5. **Staff Applications Page (`pages/staff/applications.php`)**:
   - Implemented matching **Pending** action button on table rows, `setAppPending()`, bulk selection `bulkPendingBtn`, and `bulkUpdate('Pending')`.

6. **View Application Modal (`modals/view-application-modal.php`)**:
   - Added a **Set to Pending** button (`#viewAppSetPendingBtn`) in the modal footer so reviewers can revert an application to Pending directly while viewing applicant credentials.

7. **System Memory Synchronized (`SYSTEM_MEMORY.md`)**:
   - Documented Point 8 in Section 3.3 for the Application Revert to Pending Architecture.