# Staff Role — Specification & Implementation Status

> Status: **IMPLEMENTED**. The `staff` role is fully integrated into OJAMS. Approved staff accounts log directly into the dedicated Staff Portal (`/pages/staff/`) to handle job recruitment & applicant evaluations.

## Implemented Features & Architecture

- `users.role` enum supports `('admin','staff','user')`.
- `users.is_approved` flag added — staff register with `0`, applicants with `1`.
- Registration page features a "Register as" picker (Applicant / Staff).
- Login blocks unapproved accounts with an "awaiting administrator approval" message.
- Admin → User Management shows a **Pending Approval** badge and an **Approve** button (`approveUser` action in `handlers/admin.php`).
- Login routing directs approved Staff accounts automatically to `pages/staff/dashboard.php`.

## Capability Matrix

| Capability                                   | Admin | Staff (Evaluator / HR) | Applicant |
|----------------------------------------------|:-----:|:---------------------:|:---------:|
| Browse/apply for jobs                        |  —    |          —            |    ✅     |
| View applications                            |  ✅   |          ✅           | own only  |
| Approve / reject applications                |  ✅   |          ✅           |    —      |
| Download applicant resumes                   |  ✅   |          ✅           |    —      |
| Create / edit job posts                      |  ✅   |          ✅           |    —      |
| Delete job posts                             |  ✅   |          —            |    —      |
| User management (roles, deactivate, approve) |  ✅   |          —            |    —      |
| Activity logs / clear logs                   |  ✅   |          —            |    —      |
| Dashboard stats                              |  ✅   |  Operational Stats    |    —      |

## Staff Module Structure

1. **Guards (`config/auth.php`)**
   - `isUser()` checks `role === 'user'`.
   - `isStaff()` checks `role === 'staff'`.
   - `requireStaff()` & `requireStaffOrAdmin()` guard staff-accessible pages and APIs.

2. **Pages (`pages/staff/`)**
   - `dashboard.php` — Candidate evaluation metrics & applicant chart distribution.
   - `applications.php` — Search, filter, view details, download resumes, and approve/reject applications.
   - `manage-jobs.php` — Create and edit job listings, toggle Open/Closed status (delete restricted to Admin).
   - `profile-settings.php` — Staff user details, security question, and password updates.

3. **Layouts (`layouts/`)**
   - `sidebar-staff.php` — Staff navigation panel with Staff Officer avatar badge.
   - `navbar-staff.php` — Top navigation with "STAFF" brand badge.

4. **Handlers (`handlers/`)**
   - `handlers/applications.php` & `handlers/jobs.php` authorize `requireStaffOrAdmin()`. Deletions remain Admin-only.
