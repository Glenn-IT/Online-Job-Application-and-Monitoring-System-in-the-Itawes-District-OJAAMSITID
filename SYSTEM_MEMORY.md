# OJAMS System Memory & Architecture Dependency Hub
<!-- Last Updated: 2026-09-14 | System Status: Active | Tech Stack: PHP 8+, MySQL/PDO, Tailwind/Bootstrap CSS, Vanilla JS -->

## 1. System Core Purpose & Golden Rule

### The Golden Rule of Change Synchronization:
> **"NO ISOLATED CHANGES"**
> Whenever any function, parameter, database schema, API handler, session variable, UI component, or modal is added, updated, renamed, or refactored, **ALL connected and dependent files across the entire system MUST be updated synchronously in the exact same task.** 
> Never leave orphan endpoints, stale database column references, mismatching modal form fields, or broken JavaScript event listeners.

---

## 2. Refactoring & Modification Synchronization Protocol

Before and during any code refactor or feature update, execute the following 4-step synchronization cycle:

```
[ Step 1: Trace & Map ] ──> [ Step 2: Database & Core ] ──> [ Step 3: Handlers & APIs ]
                                                                       │
[ Step 4: Verification ] <── [ Step 3b: Modals & Views ] <─────────────┘
```

### Step 1: Pre-Change Impact Analysis (Search & Trace)
- Run `grep_search` across the entire codebase for the function name, parameter, database column, or action key being changed.
- Identify all call sites, backend handlers, frontend templates, modal forms, and JS fetch calls.
- Consult the **Feature-to-File Dependency Matrix** below to ensure no connected file is missed.

### Step 2: Synchronous Full-Stack Execution
Update all connected layers together:
1. **Database Layer:** `config/database.sql` and any migration/alter scripts.
2. **Core Config / Helpers:** `config/config.php`, `config/db.php`, `config/auth.php`, `config/mailer.php`, `config/sms.php`.
3. **Backend Action Handlers:** `handlers/*.php` (validate inputs, update response shapes).
4. **Presentation Pages & Layouts:** `pages/admin/*`, `pages/staff/*`, `pages/user/*`, `layouts/*`.
5. **Interactive Components & Modals:** `components/*`, `modals/*`.
6. **Frontend Scripts:** `assets/js/script.js` (sync form payloads, DOM IDs, event handlers, response parsing).

### Step 3: Post-Change Verification & Consistency Check
- Ensure all CSRF tokens (`csrf_token`) are properly handled.
- Verify role permissions (`checkAdmin()`, `checkStaff()`, `checkAuth()`).
- Verify rate limits and JSON response schemas `{ "success": bool, "message": string, ... }`.
- Confirm responsive UI states and error/success alerts.

### Step 4: System Memory Update
- If new endpoints, parameters, tables, or file relationships are created or altered, update this `SYSTEM_MEMORY.md` document immediately.

---

## 3. Feature-to-File Dependency Matrix

Use this matrix to identify all connected files whenever modifying a feature:

### 3.1 Authentication, Sessions & User Security
*Responsibilities: Login, registration, password hashing, password reset tokens, role elevation, session guards, account lockouts.*

| Component / Layer | Connected Files |
| :--- | :--- |
| **Config & Security Core** | `config/config.php`, `config/auth.php`, `config/db.php` |
| **Public Auth Pages** | `login.php`, `register.php`, `logout.php`, `forgot-password.php`, `reset-password.php` |
| **Backend Handlers** | `handlers/profile.php` (`changePassword`), `handlers/admin.php` (`deactivateUser`, `reactivateUser`, `changeRole`) |
| **Layout Guards** | `layouts/header.php`, `layouts/navbar-admin.php`, `layouts/navbar-staff.php`, `layouts/navbar-user.php` |
| **Session Keys** | `$_SESSION['user_id']`, `$_SESSION['role']` (`admin`, `staff`, `user`), `$_SESSION['full_name']`, `$_SESSION['email']`, `$_SESSION['csrf_token']` |
| **Database Tables** | `users`, `password_resets`, `login_attempts`, `activity_logs` |
| **Modals** | `modals/logout-modal.php` |

*Synchronization Check when modifying Auth:*
- If changing session keys or roles, update `config/auth.php` (`checkAuth()`, `checkAdmin()`, `checkStaff()`), all page guards at the top of `pages/admin/*.php`, `pages/staff/*.php`, `pages/user/*.php`, and navbar display logic.

---

### 3.1b System Branding, Official Logo & Visual Identity
*Responsibilities: Official emblem and municipal seal of the Municipality of Piat across all portals, auth screens, and navigations.*

| Component / Layer | Connected Files |
| :--- | :--- |
| **Official Logo Asset** | `img/Piat-Logo.png` |
| **Shared Favicon** | `layouts/header.php`, `index.php`, `login.php`, `register.php`, `forgot-password.php`, `reset-password.php` |
| **Navbars** | `layouts/navbar-admin.php`, `layouts/navbar-staff.php`, `layouts/navbar-user.php` (`.brand-logo`); `index.php` (streamlined with direct `Log In` & `Register` actions on desktop & mobile) |
| **Authentication Screens** | `login.php`, `register.php`, `forgot-password.php`, `reset-password.php` (`.auth-hero` emblem & `.auth-card-header`) |
| **Dashboards** | `pages/admin/dashboard.php`, `pages/staff/dashboard.php` (page header emblem) |
| **Public Portal** | `index.php` (hero section badge & footer seal), `pages/user/browse-jobs.php` (hero badge) |
| **Styles** | `assets/css/style.css` (`.brand-logo` sizing, aspect-ratio, drop shadow, hover transition) |

---

### 3.2 Job Postings & Opportunity Management
*Responsibilities: Creating, editing, closing, deleting jobs, categorizing, salary ranges, deadlines, search & filters.*

| Component / Layer | Connected Files |
| :--- | :--- |
| **Backend Handler** | `handlers/jobs.php` (Actions: `add`, `edit`, `delete`, `bulkDelete`) |
| **Admin Management** | `pages/admin/manage-jobs.php`, `pages/admin/dashboard.php` |
| **Staff Management** | `pages/staff/manage-jobs.php`, `pages/staff/dashboard.php` |
| **User Job Portal** | `pages/user/browse-jobs.php`, `pages/user/job-detail.php`, `index.php` |
| **Modals & UI** | `modals/add-job-modal.php` (used for both Add & Edit modes), `components/job-card.php` |
| **Frontend Script & Styling** | `assets/css/style.css` (`.browse-hero-card`, `.browse-search-box`, `.filter-pills-scroll`, `.filter-chip`, `.job-card-modern`, `.company-monogram`, `.badge-tag`, `.detail-stat-box`) |
| **Database Tables** | `jobs` |

*Modern Applicant Job Browsing Architecture:*
1. **Hero & Unified Search**: `pages/user/browse-jobs.php` features a modern discovery hero card with unified keyword/company/location search, status selector, and responsive action button.
2. **Scrollable Quick-Filter Chips**: Dynamic pills for All Jobs, Full-time, Part-time, Contract, Internship, and Freelance.
3. **Company Monogram & Soft Badges**: Deterministic hashing generates distinctive initials and soft pastel badges (`tag-fulltime`, `tag-parttime`, `tag-contract`, `tag-internship`, `tag-freelance`, `tag-salary`, `tag-location`).
4. **Direct Applicant Flow**: Saved jobs / bookmarking feature was retired from the applicant interface in favor of direct browsing and streamlined 1-tap application.
5. **Job Detail Highlights**: `pages/user/job-detail.php` includes a 6-box quick highlight tile grid (`.detail-stat-box`) displaying Job Type, Salary, Location, Date Posted, Deadline (with expiration alert), and Total Applicants.
6. **Mobile Ergonomics & Sticky Action Bar**: `pages/user/browse-jobs.php` features responsive input stacking (`col-12 col-sm-6 col-md-3`), compact card padding, and touch targets >=44px. `pages/user/job-detail.php` features a fixed glassmorphic bottom bar (`.mobile-sticky-apply-bar`) for 1-tap applications on mobile without vertical scrolling, plus 3-column overview metrics on `pages/user/my-applications.php`.

*Synchronization Check when modifying Job fields (e.g. adding new field `employment_type` or `department`):*
1. Add field to `config/database.sql` (`jobs` table).
2. Update `handlers/jobs.php` in `add` and `edit` action validators & SQL statements.
3. Update `modals/add-job-modal.php` to include form inputs with proper `id` and `name`.
4. Update `pages/admin/manage-jobs.php` and `pages/staff/manage-jobs.php` data attributes and table columns.
5. Update `pages/user/browse-jobs.php`, `pages/user/job-detail.php`, and `components/job-card.php` display.
6. Update `assets/js/script.js` populate modal script for edit triggers.

---

### 3.3 Application Workflow & Status Tracking
*Responsibilities: Applying for jobs, uploading resumes, status transitions (Pending -> Under Review -> Interview Scheduled -> Hired / Rejected), notes, scheduling interviews.*

| Component / Layer | Connected Files |
| :--- | :--- |
| **Backend Handler** | `handlers/applications.php` (Actions: `apply`, `cancel`, `updateStatus`, `scheduleInterview`, `getDetails`, `bulkUpdateStatus`, `bulkDelete`) |
| **User Pages** | `pages/user/my-applications.php`, `pages/user/job-detail.php` |
| **Admin & Staff Pages** | `pages/admin/applications.php`, `pages/staff/applications.php`, `pages/admin/dashboard.php`, `pages/staff/dashboard.php` |
| **Modals** | `modals/apply-job-modal.php`, `modals/view-application-modal.php`, `modals/schedule-interview-modal.php` |
| **Components** | `components/application-row.php`, `components/stats-card.php` |
| **Notifications Bridge** | `config/mailer.php` (Email notifications), `config/sms.php` (SMS alerts) |
| **Upload Directory** | `uploads/resumes/` |
| **Database Tables** | `applications`, `application_history`, `interviews` |

*Synchronization Check when modifying Application status or fields:*
1. If status enum/values change: sync `handlers/applications.php`, status badges in `pages/admin/applications.php`, `pages/staff/applications.php`, `pages/user/my-applications.php`, and `components/application-row.php`.
2. If application fields change (e.g., portfolio URL): sync `modals/apply-job-modal.php`, `handlers/applications.php` (`apply` action), `modals/view-application-modal.php`, and `config/database.sql`.
3. If interview scheduling changes: sync `modals/schedule-interview-modal.php`, `handlers/applications.php` (`scheduleInterview`), and notification templates in `config/mailer.php` & `config/sms.php`.
4. **Application Validation & 18+ Age Trapping**: All fields in the application submission (`full_name`, `birthdate`, `address`, `contact`, `elementary`, `jhs`, `shs`, `college`, `skills`, `experience`, and `resume` file) are strictly required. Age trapping enforces `age >= 18` and `<= 80` (rejects `< 18` in datepicker `max` attribute, live JavaScript change listener, client submit validation, and server-side `handlers/applications.php`). Both `pages/user/browse-jobs.php` and `pages/user/job-detail.php` must stay in sync with `modals/apply-job-modal.php`.

---

### 3.4 User Profiles & Candidate Dossier
*Responsibilities: Personal details, educational background, skills, contact info, avatar uploads, account status.*

| Component / Layer | Connected Files |
| :--- | :--- |
| **Backend Handler** | `handlers/profile.php` (`updateInfo`, `changePassword`, `uploadAvatar`) |
| **Profile Settings Pages** | `pages/user/profile-settings.php`, `pages/admin/profile-settings.php`, `pages/staff/profile-settings.php` |
| **Admin Management** | `pages/admin/user-management.php`, `handlers/admin.php` |
| **Upload Directory** | `uploads/avatars/`, `uploads/resumes/` |
| **Database Tables** | `users` (or `user_profiles`) |
| **Navbars** | Avatar & user name rendering in `layouts/navbar-admin.php`, `layouts/navbar-staff.php`, `layouts/navbar-user.php` |

*Synchronization Check when modifying User Profile fields:*
1. Update database table schema in `config/database.sql`.
2. Update validator and update queries in `handlers/profile.php`.
3. Update inputs in `pages/user/profile-settings.php` and `pages/admin/profile-settings.php`.
4. Update display in `pages/admin/user-management.php` and `modals/view-application-modal.php`.

---

### 3.5 Communications: Gmail SMTP Mailer & SMS Service
*Responsibilities: Dispatching transactional emails and SMS notifications for application alerts, password resets, interview invites.*

| Component / Layer | Connected Files |
| :--- | :--- |
| **Mailer Engine** | `config/mailer.php` (PHPMailer wrapper, HTML templates, Gmail SMTP config) |
| **SMS Engine** | `config/sms.php` (SMS gateway integration, template generator) |
| **Configuration** | `.env`, `.env.example`, `config/config.php` |
| **Call Sites** | `handlers/applications.php` (Application submit, Status change, Interview scheduled), `forgot-password.php` (Reset token link) |

*Synchronization Check when updating Notification hooks:*
1. Ensure both Email (`config/mailer.php`) and SMS (`config/sms.php`) triggers receive correct variables (e.g., `$applicantEmail`, `$applicantName`, `$jobTitle`, `$interviewDate`, `$interviewNotes`).
2. Verify fallback error logging to `logs/` without crashing the main HTTP JSON response.

---

### 3.6 Reports, Statistics & Exports
*Responsibilities: Metric calculations, charts, PDF/CSV/Excel exports.*

| Component / Layer | Connected Files |
| :--- | :--- |
| **Admin Reports** | `pages/admin/reports.php`, `pages/admin/export-report.php` |
| **Admin & Staff Dashboards** | `pages/admin/dashboard.php`, `pages/staff/dashboard.php` |
| **Components** | `components/stats-card.php` |
| **Database Queries** | Aggregate queries across `applications`, `jobs`, `users` |

*Synchronization Check when modifying Data Models:*
- Verify metrics calculations in `pages/admin/dashboard.php`, `pages/staff/dashboard.php`, and `pages/admin/reports.php` reflect the altered status names or column definitions.

---

### 3.7 Layouts, Global Navigation & Assets
*Responsibilities: Header metadata, global JS helpers, modal launchers, mobile menus, CSRF inject.*

| Component / Layer | Connected Files |
| :--- | :--- |
| **Layouts** | `layouts/header.php`, `layouts/footer.php`, `layouts/sidebar-admin.php`, `layouts/sidebar-staff.php` |
| **Global JS** | `assets/js/script.js` |
| **Global CSS** | `assets/css/*` |

*Synchronization Check when modifying Layouts or Scripts:*
- Ensure common IDs, classes, and modal attributes (e.g., `data-modal-target`, `data-job-id`, `data-app-id`) match across all views.
- **Mobile Responsiveness Standards**:
  - `navbar-user.php`: Must expand dynamically with `min-height: var(--navbar-h); height: auto;` in `assets/css/style.css` without height clipping.
  - Modals (`apply-job-modal.php`, `view-application-modal.php`): Must use responsive grid columns (`col-12 col-md-6`, `col-7 col-sm-8 col-md-3`, `col-5 col-sm-4 col-md-3`), compact padding, and full-width touch-friendly buttons on small viewports.
  - Tables & Cards (`my-applications.php`, `browse-jobs.php`): Use `.table-responsive` with `.text-nowrap` on actions and compact 3-column summary cards on mobile (`col-4`).
  - Inputs: Sized at 16px minimum on screens `< 768px` to prevent iOS Safari auto-zoom.

---

## 4. Standard Request/Response & Code Patterns

### 4.1 JSON API Response Format
All `handlers/*.php` must strictly return JSON with standard keys:
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {}
}
```
In case of error:
```json
{
  "success": false,
  "message": "Error description for UI display.",
  "errors": []
}
```

### 4.2 CSRF Protection Standard
Every POST request MUST include a valid CSRF token:
- In forms: `<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">`
- In AJAX/Fetch: Include `"csrf_token": document.querySelector('meta[name="csrf-token"]')?.content || ...`
- In Handlers: Validated via `validateCSRFToken($_POST['csrf_token'] ?? '')` or `validateCSRFToken($input['csrf_token'] ?? '')`.

---

## 5. Maintenance Checklist for AI & Developers

Whenever you are asked to make any change:
1. [ ] **Search:** Run `grep_search` to find all files referencing the target code.
2. [ ] **Matrix Check:** Review the relevant section in Section 3 of this document.
3. [ ] **Refactor:** Modify all dependent files together.
4. [ ] **Lint/Syntax:** Verify syntax and prevent breaking changes.
5. [ ] **Document:** Update `SYSTEM_MEMORY.md` if signatures, actions, or schemas were altered.
