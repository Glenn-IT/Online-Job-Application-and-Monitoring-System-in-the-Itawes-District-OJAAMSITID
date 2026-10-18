# OJAMS Issues & Feature Tracking

## [RESOLVED] Missing Documents & Applicant Notice Messaging Feature (Admin & Staff)

### Request:
Admin side on `http://localhost/OJAMS/pages/admin/applications.php`:
> "can you add a feature that the admin can message the applicant if he is missing a Required documents or needed an update add a SMS function and gmail on that ok"

### Implementation Summary:
1. **Email Bridge (`config/mailer.php`)**:
   - Added `sendMissingDocsNoticeEmail()` with branded HTML template, amber "Action Required" badge, dynamic checklist of missing documents, admin instructions/notes, and direct CTA button linking the applicant to `pages/user/my-applications.php` to update their documents.

2. **SMS Gateway (`config/sms.php`)**:
   - Added `sendMissingDocsNoticeSms()` using PhilSMS API with normalized Philippine phone number formatting, candidate name, missing document list summary, custom admin instructions, and prompt to log in and upload credentials.

3. **Backend Handler (`handlers/applications.php`)**:
   - Implemented `notifyApplicant` action accessible to Admin and Staff.
   - Validates CSRF token, application ID, non-empty instructions, and channel options (`send_email`, `send_sms`).
   - Maps document keys to readable names, dispatches Gmail email and/or PhilSMS SMS notifications, and logs the action to `activity_logs`.

4. **Message Modal (`modals/message-applicant-modal.php`)**:
   - Implemented `#messageApplicantModal` with candidate summary dossier, quick notice template dropdown (Missing Required Documents, Unclear/Illegible Scans, General Update, Custom), checkboxes for all 6 required documents (`resume`, `application_letter`, `pds`, `csc_eligib`, `tor`, `diploma`), subject, instructions textarea, and Email/SMS channel toggles.

5. **Document Viewer Integration (`modals/view-application-modal.php`)**:
   - Added "Request Docs" quick button in the document header so reviewers inspecting uploaded files can launch the message modal with missing documents automatically pre-checked.

6. **Admin & Staff Frontends (`pages/admin/applications.php` & `pages/staff/applications.php`)**:
   - Added table row "Notice" action button with modal launcher.
   - Wired JS handlers: `openMessageApplicantModal()`, `openMessageModalFromDetails()`, `onMessageTemplateChange()`, and `handleSendMessageSubmit()`.

7. **System Architecture Synchronized (`SYSTEM_MEMORY.md`)**:
   - Synchronized Feature-to-File Matrix, communications bridge, and multi-document notice architecture.
