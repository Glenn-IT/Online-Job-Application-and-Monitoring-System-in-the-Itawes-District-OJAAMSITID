# OJAMS System Audit
**Date:** 2026-06-02  
**Audited By:** Claude Code  
**Version:** 2.0.0 (MySQL Backend)  
**Status:** 81% Complete — Ongoing Development

---

## Table of Contents
1. [System Overview](#system-overview)
2. [Folder Structure](#folder-structure)
3. [Frontend](#frontend)
4. [Backend](#backend)
5. [Database](#database)
6. [Fix & Bug Checklist](#fix--bug-checklist)

---

## System Overview

OJAMS (Online Job Application and Monitoring System in the Itawes District) is a role-based web application for managing job postings and applications. It supports two roles — **Admin** and **User** — with a session-based authentication system backed by a MySQL database.

**Tech Stack:**
- PHP 7+ (no framework)
- MySQL via PDO
- Bootstrap 5.3.3 (CDN)
- Bootstrap Icons 1.11.3 (CDN)
- Vanilla JavaScript (no jQuery or frontend framework)
- XAMPP / Apache (local development)

**Architecture Pattern:** MVC-like  
- `config/` — Models & configuration  
- `pages/` — Views  
- `handlers/` — Controllers (JSON API endpoints)  
- `layouts/`, `modals/`, `components/` — Reusable UI fragments

---

## Folder Structure

```
OJAMS/
├── index.php                        Entry point — redirects to login
├── login.php                        Authentication, email/password form
├── register.php                     User registration
├── logout.php                       Session cleanup & redirect
│
├── config/
│   ├── config.php                   App constants: DB credentials, timezone, bcrypt cost
│   ├── db.php                       PDO connection + session initialization
│   ├── auth.php                     Session helpers, auth guards (requireAdmin/requireUser)
│   ├── check.php                    Quick DB diagnostic test
│   ├── database.sql                 Schema + seed data
│   └── fix_passwords.php            Dev utility: resets demo passwords
│
├── pages/
│   ├── user/
│   │   ├── browse-jobs.php          Job listings, search/filter, apply modal
│   │   ├── my-applications.php      User's own applications, cancel/view
│   │   └── profile-settings.php     Profile view/edit, password change
│   └── admin/
│       ├── dashboard.php            Stats cards + activity log
│       ├── manage-jobs.php          Job CRUD (add/edit/delete)
│       ├── applications.php         Approve / reject applications
│       ├── reports.php              Applicants per job, monthly stats
│       └── profile-settings.php     Admin profile edit
│
├── handlers/
│   ├── jobs.php                     API: add / edit / delete jobs (admin only)
│   ├── applications.php             API: apply / cancel / approve / reject
│   └── profile.php                  API: updateInfo / changePassword
│
├── layouts/
│   ├── header.php                   HTML head, CDN links, meta tags
│   ├── footer.php                   Closing tags, modal includes, Bootstrap JS
│   ├── navbar-admin.php             Admin top navigation + user dropdown
│   ├── navbar-user.php              User top navigation
│   └── sidebar-admin.php            Admin left sidebar navigation
│
├── modals/
│   ├── apply-job-modal.php          Full job application form (5 sections)
│   ├── add-job-modal.php            Unified add/edit job form (admin)
│   ├── edit-job-modal.php           DEAD CODE — not included anywhere
│   ├── view-application-modal.php   View applicant details (admin)
│   └── logout-modal.php             Logout confirmation dialog
│
├── components/
│   ├── job-card.php                 Reusable job listing card
│   ├── stats-card.php               Dashboard stat card template
│   ├── application-row.php          Table row for applications
│   └── table-header.php             Table header columns
│
├── assets/
│   ├── css/style.css                Custom styles (~100 lines)
│   ├── js/script.js                 Global JS helpers (showToast, statusBadgeClass)
│   └── images/default-profile.svg  Profile placeholder
│
├── docs/
│   ├── Backend Migration Plan.md    Migration notes (v1 localStorage → v2 MySQL)
│   └── SYSTEM-AUDIT.md             This file
│
├── README.md                        OUTDATED — still references localStorage
└── Progress.md                      81% progress tracker
```

**Total:** ~34 PHP files · 1 SQL · 1 CSS · 1 JS · ~3,500 PHP LOC

---

## Frontend

### Pages & Purpose

| Page | Role | Description |
|------|------|-------------|
| `login.php` | Public | Email/password login, demo hints, forgot password (prototype) |
| `register.php` | Public | Registration, auto-assigns `user` role |
| `pages/user/browse-jobs.php` | User | Card grid of open jobs, client-side search/filter, apply modal |
| `pages/user/my-applications.php` | User | Table of own applications, cancel Pending, view details |
| `pages/user/profile-settings.php` | User | View/edit profile, change password |
| `pages/admin/dashboard.php` | Admin | Stats cards (users/jobs/apps/pending), recent activity log |
| `pages/admin/manage-jobs.php` | Admin | CRUD table for job postings |
| `pages/admin/applications.php` | Admin | All applications, approve/reject, view details |
| `pages/admin/reports.php` | Admin | Applicants per job (progress bars), monthly application chart |
| `pages/admin/profile-settings.php` | Admin | Admin profile edit, password change |

### CSS — `assets/css/style.css`
- Sidebar: sticky positioning with hover highlights
- Job cards: hover animation (`translateY -4px`, box-shadow)
- Stats cards: hover animation
- Tables: uppercase headers, vertical-align middle
- Cards: border-radius 12px throughout
- Responsive via Bootstrap grid

### JavaScript — `assets/js/script.js` (Global Helpers)
| Function | Purpose |
|----------|---------|
| `showToast(msg, type)` | Fixed bottom-right toast, color-coded by success/danger/warning |
| `statusBadgeClass(status)` | Returns Bootstrap badge class from status string |
| `_setText(id, val)` / `_setVal(id, val)` | DOM helpers |
| `setApplyJob(id, title, company)` | Sets current job context before opening apply modal |
| `comingSoon()` | Placeholder alert for unimplemented features |

**Page-Specific JS (Embedded):**

| Page | Functions |
|------|-----------|
| `browse-jobs.php` | `filterJobs()`, `openApplyModal()`, `submitApplication()`, age auto-compute |
| `manage-jobs.php` | `openAddJobModal()`, `editJob()`, `saveJob()`, `deleteJob()` |
| `applications.php` | `updateAppStatus()`, `viewAppDetails()` |
| `my-applications.php` | `viewMyApp()`, `confirmCancel()`, `cancelApplication()` |
| `profile-settings.php` | `toggleEditProfile()`, `saveProfile()`, `savePassword()` |

### Libraries Used
- Bootstrap 5.3.3 (CDN — no local copy)
- Bootstrap Icons 1.11.3 (CDN)
- No jQuery, no build tools, no npm

---

## Backend

### Authentication Flow
1. `login.php` — validates email/password via `password_verify()` (bcrypt)
2. Sets `$_SESSION['ojams_user']` with: `id, role, full_name, email, contact_number, address, birthdate`
3. Role-based redirect: `admin` → `dashboard.php` · `user` → `browse-jobs.php`
4. Guards: `requireAdmin()`, `requireUser()`, `requireLogin()` in `config/auth.php`

### API Endpoints — `handlers/`

All handlers accept `POST` with JSON body and return JSON responses.

**`handlers/jobs.php`** (Admin only)

| Action | Description | Returns |
|--------|-------------|---------|
| `add` | Insert new job | `{success, id, message}` |
| `edit` | Update existing job | `{success, message}` |
| `delete` | Delete job by ID | `{success, message}` |

**`handlers/applications.php`** (Mixed roles)

| Action | Role | Description |
|--------|------|-------------|
| `apply` | User | Submit application — checks job open, no duplicate |
| `cancel` | User | Delete own Pending application |
| `updateStatus` | Admin | Set status to Approved or Rejected |
| `getDetails` | Admin | Return full applicant record with job info |

**`handlers/profile.php`** (Any authenticated)

| Action | Description |
|--------|-------------|
| `updateInfo` | Update name, email, contact, address, birthdate — refreshes session |
| `changePassword` | Verify current password, hash + save new password |

### Security Measures (Current)
- Prepared statements everywhere (no SQL injection risk)
- `password_hash()` / `password_verify()` with bcrypt cost 12
- `htmlspecialchars()` with `ENT_QUOTES` on all output
- Role guards on all admin endpoints
- Duplicate application prevention (UNIQUE key + query check)

---

## Database

**Database:** `ojams_db` · Charset: `utf8mb4_unicode_ci`

### Table: `users`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | Auto-increment |
| `role` | ENUM('admin','user') | Default: `user` |
| `full_name` | VARCHAR(150) NOT NULL | |
| `email` | VARCHAR(150) UNIQUE NOT NULL | |
| `password_hash` | VARCHAR(255) NOT NULL | bcrypt |
| `contact_number` | VARCHAR(20) | Nullable |
| `address` | TEXT | Nullable |
| `birthdate` | DATE | Nullable |
| `created_at` | DATETIME | Default: CURRENT_TIMESTAMP |
| `updated_at` | DATETIME | Auto-updates on change |

Seed accounts:
- `admin@ojams.com` / `admin123`
- `juan@email.com`, `maria@email.com`, `carlos@email.com`, `ana@email.com`, `pedro@email.com` — all `password123`

### Table: `jobs`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `title` | VARCHAR(150) NOT NULL | |
| `company` | VARCHAR(150) NOT NULL | |
| `description` | TEXT NOT NULL | |
| `qualification` | TEXT NOT NULL | |
| `date_posted` | DATE NOT NULL | Defaults to CURDATE() |
| `status` | ENUM('Open','Closed') | Default: `Open` |
| `created_by` | FK → users.id | ON DELETE SET NULL |

### Table: `applications`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `user_id` | FK → users.id | ON DELETE CASCADE |
| `job_id` | FK → jobs.id | ON DELETE CASCADE |
| `full_name`, `email`, `contact`, `address`, `birthdate`, `age` | Various | Snapshot at apply time |
| `elementary`, `jhs`, `shs`, `college` | VARCHAR(200) | Denormalized education |
| `skills`, `experience` | TEXT | |
| `status` | ENUM('Pending','Approved','Rejected') | Default: `Pending` |
| `date_applied` | DATE NOT NULL | |
| UNIQUE KEY | `(user_id, job_id)` | Prevents duplicate applications |

### Table: `activity_logs`
| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `action` | VARCHAR(255) NOT NULL | Human-readable log message |
| `status` | VARCHAR(50) NOT NULL | New / Created / Updated / Deleted / Approved / Rejected / Cancelled |
| `performed_by` | FK → users.id | Nullable (ON DELETE SET NULL) |
| `created_at` | DATETIME | |

### Entity Relationships
```
users ──< jobs           (created_by — one admin creates many jobs)
users ──< applications   (user_id — one user submits many applications, CASCADE)
jobs  ──< applications   (job_id  — one job receives many applications, CASCADE)
users ──< activity_logs  (performed_by — nullable, SET NULL on delete)
```

---

## Fix & Bug Checklist

### 🔴 Critical (Security / Data Integrity)

- [ ] **CSRF Protection Missing** — All `handlers/*.php` accept POST with no token validation. An attacker can craft cross-site requests to trigger admin actions (job create/delete, status updates). Add CSRF token to all forms and validate server-side.

- [ ] **No Session Regeneration on Login** — `login.php` never calls `session_regenerate_id(true)` after authenticating. Enables session fixation attacks. Add immediately after setting `$_SESSION['ojams_user']`.

- [ ] **Hardcoded Database Credentials** — `config/config.php` stores `DB_USER`, `DB_PASS` in plain PHP. If committed to version control, credentials are exposed. Move to `.env` file and add `.env` to `.gitignore`.

- [ ] **No Brute-Force Protection on Login** — No attempt tracking, lockout, or CAPTCHA on `login.php`. Vulnerable to password spraying. Implement failed attempt counter in session or DB with temporary lockout.

- [ ] **File Upload UI Without Backend Handler** — `apply-job-modal.php` has a "Passport Size ID" file input, but `handlers/applications.php` never processes `$_FILES`. Files are silently dropped. Either remove the input or implement full file upload handling with type/size validation and secure storage.

- [ ] **Age Field Not Validated Server-Side** — `age` is auto-computed by JavaScript but the field is editable by the user. The backend stores whatever is submitted without verifying it matches `birthdate`. Recalculate age from `birthdate` in the handler; ignore submitted age.

### 🟠 High (Functional Bugs / UX)

- [ ] **Forgot Password is Non-Functional** — Modal in `login.php` shows a fake success toast; no email is sent. Users may believe a reset email was sent. Either remove the feature or integrate a mailer (e.g., PHPMailer with SMTP).

- [ ] **`edit-job-modal.php` is Dead Code** — File exists in `modals/` but is never included. `manage-jobs.php` uses `add-job-modal.php` for both add and edit. Delete the file to avoid confusion.

- [ ] **`$basePath` Variable Used Without Consistent Definition** — `layouts/navbar-admin.php` references `$basePath ?? ''` for include paths. If a page does not define `$basePath` before including the layout, paths silently degrade. Use `__DIR__` relative paths instead.

- [ ] **No Input Length Validation** — Most form fields lack `maxlength` HTML attributes and backend length checks. A user can submit a 50,000-character name string. Add `maxlength` to inputs and validate lengths in handlers against the DB column sizes (e.g., `VARCHAR(150)`).

- [ ] **Browse Jobs Filter State Lost on Reload** — `filterJobs()` is client-side only; search text and filters are wiped on page reload. Consider persisting state via URL query parameters (`?search=developer&status=Open`).

### 🟡 Medium (Code Quality / Maintainability)

- [ ] **README.md is Outdated** — Still references `localStorage` and "no database required" (v1 design). Update to describe the current MySQL architecture, setup steps, and demo credentials.

- [ ] **No Error Logging** — There is no `error_log()`, no log file, and no debug mode flag. Exceptions are caught only at the PDO level. Add application-level logging to a file or integrate a simple logger for production diagnostics.

- [ ] **No Input Sanitization Beyond Output Escaping** — `htmlspecialchars()` at output prevents XSS, but `skills`, `experience`, and `description` fields accept raw HTML tags that are stored in the DB. Consider `strip_tags()` or an allowlist sanitizer on input in handlers.

- [ ] **Reports Page Has No Pagination or Limit** — `reports.php` queries all applications with no `LIMIT`. Will degrade performance at scale. Add pagination or aggregate-only queries.

- [ ] **`console.log` Left in Production Code** — `assets/js/script.js` logs `"OJAMS – DB-backed UI loaded"` on every page load. Remove before production deployment.

- [ ] **Activity Logs Lack a Cleanup Strategy** — `activity_logs` grows unbounded. Add a scheduled purge or an admin UI to clear old logs beyond a retention window (e.g., 90 days).

### 🟢 Low (Nice-to-Have / Polish)

- [ ] **No Pagination on Applications Table** — Admin `applications.php` renders all records at once. Should paginate or implement server-side search for scalability.

- [ ] **No Pagination on Browse Jobs** — `browse-jobs.php` renders all jobs. Fine for small datasets; needs pagination at scale.

- [ ] **No Unit or Integration Tests** — Zero test coverage. Consider adding PHPUnit for handler logic and Selenium or Playwright for E2E flows.

- [ ] **`fix_passwords.php` Should Not Be Web-Accessible** — `config/fix_passwords.php` is a dev utility reachable via browser. Move to a CLI-only script or protect with IP restrictions.

- [ ] **`check.php` Should Not Be Web-Accessible in Production** — `config/check.php` exposes DB stats and user counts. Remove or restrict in production.

- [ ] **`Progress.md` Needs Updating** — Only shows a summary line. Should track completed features, in-progress items, and remaining work.

- [ ] **Bootstrap and Icons Loaded from CDN Only** — No local fallback if CDN is unavailable. For production, consider hosting locally or using a lockfile (subresource integrity hashes are already ideal here).

---

## Summary Scorecard

| Area | Status | Key Issue |
|------|--------|-----------|
| **Authentication** | Functional but insecure | No session regeneration, no CSRF |
| **Authorization** | Working | Role guards implemented |
| **API / Handlers** | Functional | No CSRF, no rate limiting |
| **Database** | Clean schema | Credentials hardcoded |
| **Frontend / UI** | Mostly complete | Dead code (edit-job-modal), filter state lost |
| **Security** | Weak | Missing CSRF, CSRF, brute-force, file upload |
| **Error Handling** | Minimal | No app-level logging |
| **Code Quality** | Good structure | Some dead code, outdated README |
| **Testing** | None | No tests of any kind |
| **Documentation** | Outdated | README references v1 design |
