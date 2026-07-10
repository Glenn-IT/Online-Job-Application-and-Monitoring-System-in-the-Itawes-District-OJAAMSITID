# Staff Role — Draft Plan (not yet implemented)

> Status: **DRAFT**. The `staff` role exists in the system (registration, approval gate,
> login) but staff currently see the same pages as applicants. This document drafts what
> the staff module should become, based on the existing OJAMS structure.

## What already works (implemented 2026-07-10)

- `users.role` enum extended to `('admin','staff','user')`.
- `users.is_approved` flag added — staff register with `0`, applicants with `1`.
- Register page has a "Register as" picker (Applicant / Staff).
- Login blocks unapproved accounts with an "awaiting administrator approval" message
  (correct credentials are not counted toward the lockout).
- Admin → User Management shows a **Pending Approval** badge and an **Approve** button
  (`approveUser` action in `handlers/admin.php`), plus a Staff role filter.
- Temporary: `isUser()` in `config/auth.php` treats approved staff as applicants so they
  can log in and use the site. `isStaff()` helper already exists for when the module ships.

## Proposed staff capabilities

Staff sit between admin and applicant: they help process applications but cannot
manage users or system settings.

| Capability                                   | Admin | Staff | Applicant |
|----------------------------------------------|:-----:|:-----:|:---------:|
| Browse/apply for jobs                        |  —    |  —    |    ✅     |
| View applications                            |  ✅   |  ✅   | own only  |
| Approve / reject applications                |  ✅   |  ✅   |    —      |
| Download applicant resumes                   |  ✅   |  ✅   |    —      |
| Create / edit job posts                      |  ✅   |  ✅   |    —      |
| Delete job posts                             |  ✅   |  —    |    —      |
| User management (roles, deactivate, approve) |  ✅   |  —    |    —      |
| Activity logs / clear logs                   |  ✅   |  —    |    —      |
| Dashboard stats                              |  ✅   | read-only | —     |

## Implementation sketch (when we build it)

1. **Guards** (`config/auth.php`)
   - Revert `isUser()` to `role === 'user'` only.
   - Add `requireStaff()` and/or `requireStaffOrAdmin()` mirroring `requireAdmin()`.

2. **Pages** — new `pages/staff/` directory reusing admin components:
   - `dashboard.php` — read-only stats (reuse `components/stats-card.php`).
   - `applications.php` — same table as admin's, reusing `components/application-row.php`
     and `modals/view-application-modal.php`.
   - `manage-jobs.php` — admin's job table minus the Delete button
     (reuse `modals/add-job-modal.php`).
   - `profile-settings.php` — copy of admin's (or share one page keyed by role).
   - New `layouts/sidebar-staff.php` / reuse `navbar-admin.php` with fewer links.

3. **Handlers** — authorize per action instead of per file:
   - `handlers/applications.php` (status changes) and `handlers/jobs.php`
     (add/edit): allow `admin` OR `staff`; keep delete/user-management admin-only.
   - Log staff actions to `activity_logs` exactly like admin actions (already keyed
     by `performed_by`).

4. **Login routing** (`login.php`)
   - `admin` → admin dashboard, `staff` → staff dashboard, `user` → browse jobs.

5. **Optional hardening**
   - Notify admin (dashboard counter) when staff registrations are pending.
   - Email/notice to the staff member when approved.
