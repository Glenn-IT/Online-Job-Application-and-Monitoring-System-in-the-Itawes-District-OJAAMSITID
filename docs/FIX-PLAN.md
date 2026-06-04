# OJAMS Fix Plan
**Created:** 2026-06-02  
**Based on:** [SYSTEM-AUDIT.md](SYSTEM-AUDIT.md)

---

## How to Use This Document
Work through each phase in order. Complete all steps in a phase before moving to the next.  
Mark each step done by changing `[ ]` to `[x]`.

---

## Phase 1 — Critical Security Fixes
> These must be done first. They are active security vulnerabilities.

### Step 1 — Add CSRF Token Protection ✅
**Files edited:** `config/auth.php`, `layouts/header.php`, `assets/js/script.js`, all `handlers/*.php`, all fetch-using pages

1. [x] Added `generateCsrfToken()` and `validateCsrfToken()` to `config/auth.php`
2. [x] `layouts/header.php` calls `generateCsrfToken()` and emits `<meta name="csrf-token">` tag
3. [x] `assets/js/script.js` has `getCsrfToken()` helper that reads from the meta tag
4. [x] `handlers/jobs.php` — validates token, returns HTTP 403 on failure
5. [x] `handlers/applications.php` — validates token, returns HTTP 403 on failure
6. [x] `handlers/profile.php` — validates token, returns HTTP 403 on failure
7. [x] All `fetch()` payloads in `browse-jobs.php`, `manage-jobs.php`, `applications.php`, `my-applications.php`, `profile-settings.php` (user & admin) include `csrf_token: getCsrfToken()`

---

### Step 2 — Fix Session Fixation on Login ✅
**File edited:** `login.php`

1. [x] Added `session_regenerate_id(true);` immediately after successful `password_verify()`
2. [x] Added `unset($_SESSION['csrf_token']);` so a fresh CSRF token is issued for the new session ID

---

### Step 3 — Move Database Credentials to `.env` ✅
**Files created/edited:** `.env`, `.env.example`, `.gitignore`, `config/config.php`

1. [x] Created `.env` at the project root with all credentials
2. [x] Created `.gitignore` — excludes `.env`, `uploads/`, `logs/*.log`
3. [x] Wrote a lightweight `.env` parser directly in `config/config.php` (no Composer needed) — reads `KEY=VALUE` lines, skips comments, calls `putenv()` + populates `$_ENV`
4. [x] All `define()` constants now use `getenv()` with sensible fallbacks
5. [x] Created `.env.example` with empty values — safe to commit as a setup template
6. [x] Verified via `php -r` — all constants resolve correctly from `.env`

---

### Step 4 — Add Login Brute-Force Protection ✅
**Files edited:** `config/database.sql`, `login.php`

1. [x] Added `login_attempts (ip PK, attempts, last_attempt_at)` table to `config/database.sql`
2. [x] Created the table on the live DB via XAMPP MySQL
3. [x] `login.php` reads client IP (supports `X-Forwarded-For` proxies)
4. [x] Pre-login lockout check: if IP has ≥ 5 failed attempts in last 15 min → blocked with minutes-remaining message
5. [x] On failed login: `INSERT ... ON DUPLICATE KEY UPDATE attempts + 1` — remaining attempts shown in error
6. [x] On successful login: `DELETE FROM login_attempts WHERE ip = ?` — counter cleared

---

### Step 5 — Fix File Upload ✅
**File edited:** `modals/apply-job-modal.php`

1. [x] Removed the "Upload Passport Size ID" file input block — the backend never processed `$_FILES`, so users were silently losing any file they attached

---

### Step 6 — Validate Age Server-Side ✅
**File edited:** `handlers/applications.php`

1. [x] Removed `$age = (int)($body['age'] ?? 0)` — submitted value discarded completely
2. [x] Age now computed server-side: `(new DateTime())->diff(new DateTime($birthdate))->y`
3. [x] Stored as `null` when no birthdate is provided; integer years otherwise

---

## Phase 2 — Functional Bug Fixes

### Step 7 — Remove Dead Code (`edit-job-modal.php`) ✅
**File deleted:** `modals/edit-job-modal.php`

1. [x] Grep confirmed zero PHP includes reference `edit-job-modal`
2. [x] File deleted

---

### Step 8 — Fix `$basePath` Include Paths ✅
**File edited:** `pages/admin/reports.php`

1. [x] Audited all `$basePath` usage — pattern is consistent and correct across all pages
2. [x] `header.php` already uses `__DIR__`-based fallback for config includes
3. [x] Fixed real concrete bug: `reports.php` had `footer.php` included **twice** on the same line — removed the duplicate

---

### Step 9 — Add Input Length Validation ✅
**Files edited:** `modals/apply-job-modal.php`, `modals/add-job-modal.php`, `pages/user/profile-settings.php`, `pages/admin/profile-settings.php`, `handlers/jobs.php`, `handlers/applications.php`, `handlers/profile.php`

1. [x] Added `maxlength` to all form inputs matching DB column sizes (150, 20, 200, 500)
2. [x] `handlers/jobs.php` — `strlen()` checks on `title` and `company` (max 150)
3. [x] `handlers/applications.php` — `strlen()` checks on `full_name`, `email`, `contact`, all education fields
4. [x] `handlers/profile.php` — `strlen()` checks on `full_name`, `email`, `contact_number`

---

### Step 10 — Fix Forgot Password ✅
**File edited:** `login.php`

1. [x] Removed "Forgot your password?" trigger link
2. [x] Removed entire forgot password modal HTML block
3. [x] Removed `sendResetLink()` JS function and modal reset listener

---

## Phase 3 — Code Quality

### Step 11 — Update README.md ✅
**File rewritten:** `README.md`

1. [x] Full rewrite — removed all localStorage / "no database" references
2. [x] Documents MySQL setup, `.env` configuration, demo accounts, project structure, and security features

---

### Step 12 — Add Application-Level Error Logging ✅
**Files created/edited:** `logs/.gitkeep`, `config/config.php`, `config/auth.php`, `config/db.php`, all handlers

1. [x] Created `logs/` directory with `.gitkeep`
2. [x] Added `LOG_FILE` constant to `config/config.php`
3. [x] Added `logError(string $message, array $context = [])` to `config/auth.php` — writes timestamped entries to `logs/app.log`
4. [x] `config/db.php` — logs connection failure to file before showing safe error page
5. [x] All three handlers log unknown actions with `logError()`

---

### Step 13 — Add Input Sanitization in Handlers ✅
**Files edited:** `handlers/jobs.php`, `handlers/applications.php`

1. [x] `handlers/jobs.php` — `strip_tags(trim())` on `title`, `company`, `description`, `qualification` (both add and edit actions)
2. [x] `handlers/applications.php` — `strip_tags(trim())` on `full_name`, `contact`, `address`, all education fields, `skills`, `experience`
3. [x] `htmlspecialchars()` output escaping retained on all pages — defense in depth maintained

---

### Step 14 — Remove Console Log from Production JS ✅
**File edited:** `assets/js/script.js`

1. [x] Removed the `DOMContentLoaded` listener that logged `"OJAMS – DB-backed UI loaded"`

---

### Step 15 — Restrict Dev Utility Files ✅
**Files edited:** `config/fix_passwords.php`, `config/check.php`

1. [x] Both files now return HTTP 403 immediately if accessed via browser — CLI only

---

## Phase 4 — Polish & Scalability

### Step 16 — Add Pagination to Applications Table (Admin) ✅
**File rewritten:** `pages/admin/applications.php`

1. [x] `?page=N` query param — clamped to valid range
2. [x] `LIMIT 15 OFFSET` added to both filtered and unfiltered queries
3. [x] Total count query runs separately per filter state
4. [x] Bootstrap pagination bar rendered in `card-footer` — shows page range, prev/next, page numbers
5. [x] `?status=` filter preserved in all pagination links
6. [x] Row numbers continue from correct offset (e.g. page 2 starts at #16)

---

### Step 17 — Preserve Browse Jobs Filter State ✅
**File edited:** `pages/user/browse-jobs.php`

1. [x] PHP applies `WHERE (title LIKE ? OR company LIKE ?) AND status = ?` from `$_GET['search']` and `$_GET['status']`
2. [x] Search input and status dropdown pre-filled from GET params on page load
3. [x] "Clear filters" `×` link shown when any filter is active
4. [x] `filterJobs()` still does instant DOM filtering for immediate feedback
5. [x] `history.replaceState()` (debounced 400ms) pushes filter state to URL so it survives reload

---

### Step 18 — Add Reports Pagination / Aggregation Limit ✅
**File edited:** `pages/admin/reports.php`

1. [x] Monthly report: added `LIMIT 12` — shows last 12 months only
2. [x] Per-job report: added `LIMIT 20` — shows top 20 jobs by applicant count

---

### Step 19 — Add Activity Log Cleanup ✅
**Files created/edited:** `handlers/admin.php`, `pages/admin/dashboard.php`

1. [x] Created `handlers/admin.php` — CSRF-protected, admin-only handler for utility actions
2. [x] `action: 'clearLogs'` deletes rows older than N days (default 90), logs the cleanup itself
3. [x] "Clear Logs >90 Days" button added to dashboard activity card header
4. [x] `clearOldLogs()` JS function added to dashboard with confirm dialog

---

### Step 20 — Update Progress.md ✅
**File rewritten:** `Progress.md`

1. [x] Full rewrite — tracks all completed features, all 19 bug fixes, and remaining future enhancements
2. [x] Status updated to 100% — all phases complete

---

## Quick Reference — File Index

| Fix | Files Touched |
|-----|---------------|
| Step 1 — CSRF | `config/auth.php`, all `handlers/`, all modals, all pages with forms |
| Step 2 — Session | `login.php` |
| Step 3 — .env | `.env`, `.gitignore`, `config/config.php` |
| Step 4 — Brute Force | `login.php`, `config/database.sql` |
| Step 5 — File Upload | `modals/apply-job-modal.php`, `handlers/applications.php` |
| Step 6 — Age Validation | `handlers/applications.php` |
| Step 7 — Dead Code | `modals/edit-job-modal.php` (delete) |
| Step 8 — $basePath | `layouts/navbar-admin.php`, affected pages |
| Step 9 — Length Validation | All modal forms, all `handlers/` |
| Step 10 — Forgot Password | `login.php` |
| Step 11 — README | `README.md` |
| Step 12 — Error Logging | `config/config.php`, `config/auth.php`, `config/db.php`, `handlers/` |
| Step 13 — Sanitization | `handlers/applications.php`, `handlers/jobs.php` |
| Step 14 — Console Log | `assets/js/script.js` |
| Step 15 — Dev Utilities | `config/fix_passwords.php`, `config/check.php` |
| Step 16 — Pagination (Apps) | `pages/admin/applications.php` |
| Step 17 — Filter State | `pages/user/browse-jobs.php` |
| Step 18 — Reports Limit | `pages/admin/reports.php` |
| Step 19 — Log Cleanup | `pages/admin/dashboard.php`, new handler action |
| Step 20 — Progress.md | `Progress.md` |
