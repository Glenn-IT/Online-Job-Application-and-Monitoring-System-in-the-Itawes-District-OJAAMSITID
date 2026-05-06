# 🔄 OJAMS — Backend Migration Plan

### localStorage → PHP + MySQL

> **Purpose:** Step-by-step plan for converting the OJAMS localStorage prototype into a real backend-driven system using PHP and MySQL.
> **Date Created:** May 6, 2026
> **Author:** Glenard Pagurayan

---

## 🔍 Phase 0 — localStorage Audit

### Files & What They Do

| File                   | Role                                                                                                                                                                         | Status                                                                              |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| `assets/js/storage.js` | **The core localStorage engine** — contains `Session`, `Users`, `Jobs`, `Applications`, `ActivityLog` CRUD, `seedStorage()`, and auth guards (`requireAdmin`, `requireUser`) | ❌ Must be fully replaced by PHP backend                                            |
| `assets/js/script.js`  | UI behaviors — calls `Session.get()`, `Applications.create()`, `Jobs.findById()`, `Users.*`, etc.                                                                            | ⚠️ Needs heavy refactoring — JS CRUD calls replaced with `fetch()` to PHP endpoints |
| `assets/js/data.js`    | Static dummy JS arrays (`jobs`, `myApplications`, `adminApplications`) — only used as fallback/UI seed in old single-file prototype                                          | ✅ Safe to delete entirely — never used by the current multi-page build             |
| `assets/js/app.js`     | Old single-page app logic (sidebar builder, role toggle, `buildLayout`, `renderJobCards`) — **not used by current multi-page PHP structure**                                 | ✅ Safe to delete entirely                                                          |
| `data/sample-data.php` | PHP arrays as seed/fallback data for server-side rendering                                                                                                                   | ⚠️ Keep temporarily as reference; delete after DB seeding is done                   |

---

### What `storage.js` Controls Right Now (Key Dependencies)

| Concern                 | localStorage Function                              | What Replaces It                          |
| ----------------------- | -------------------------------------------------- | ----------------------------------------- |
| Session / Auth Guard    | `Session.get()`, `requireAdmin()`, `requireUser()` | PHP `$_SESSION`, PHP page-top auth guards |
| Login                   | `Users.authenticate()` → `Session.set()`           | PHP login form → `$_SESSION`              |
| Register                | `Users.create()`                                   | PHP `INSERT INTO users`                   |
| Apply for Job           | `Applications.create()`                            | PHP `INSERT INTO applications`            |
| Cancel Application      | `Applications.delete()`                            | PHP `DELETE` / status update              |
| Approve / Reject        | `Applications.updateStatus()`                      | PHP `UPDATE applications SET status`      |
| Add / Edit / Delete Job | `Jobs.create/update/delete()`                      | PHP job CRUD endpoints                    |
| Profile Update          | `Users.update()`                                   | PHP `UPDATE users`                        |
| Activity Log            | `logActivity()`                                    | PHP `INSERT INTO activity_logs`           |
| Dashboard Stats         | Computed from localStorage arrays                  | PHP `SELECT COUNT(*)` queries             |

---

### Action Plan for Each File

| File                   | Action                                                                                 | Reason                                                                                                                       |
| ---------------------- | -------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `storage.js`           | **Replace entirely** in a later phase                                                  | Every function in it maps 1:1 to a PHP+MySQL operation                                                                       |
| `script.js`            | **Keep UI/modal/toast utilities**; strip localStorage-calling functions phase by phase | Contains non-storage helpers (`statusBadgeClass`, `showToast`, `toggleEditProfile`, `toggleLoginPass`) that are still useful |
| `data.js`              | **Delete now** — it's orphaned                                                         | Not included anywhere in the current multi-page build                                                                        |
| `app.js`               | **Delete now** — it's orphaned                                                         | Single-page-app leftover; multi-page PHP build doesn't use it                                                                |
| `data/sample-data.php` | **Keep for now, mark as legacy**                                                       | Useful as reference when writing DB seed SQL; delete after Phase 2                                                           |

> ⚠️ **Do NOT remove `storage.js` yet.** The current live UI depends on it for session guards and all CRUD. It will be surgically replaced phase by phase starting in Phase 3.

---

## 🗃️ Recommended Database Structure

```sql
-- Core tables only, no over-engineering

users
  id, role (enum: admin/user), full_name, email,
  password_hash, contact_number, address, birthdate,
  created_at, updated_at

jobs
  id, title, company, description, qualification,
  date_posted, status (enum: Open/Closed),
  created_by (FK → users.id), created_at, updated_at

applications
  id, user_id (FK → users.id), job_id (FK → jobs.id),
  full_name, email, contact, address, birthdate, age,
  elementary, jhs, shs, college,   ← denormalized for simplicity
  skills, experience,
  status (enum: Pending/Approved/Rejected),
  date_applied, updated_at

activity_logs
  id, action, status, performed_by (FK → users.id, nullable),
  created_at
```

> **On normalization:** Education fields are kept flat inside `applications` (not a separate table). Given the scope of this system, a separate `education` table would add complexity without real benefit.

---

## ✅ Backend Implementation Checklist

---

### ✅ PHASE 1 — Database Planning _(DONE)_

- **Objective:** Design and create the MySQL database with all tables and seed data.
- **Files created:** `config/database.sql` (schema + seeds)
- **Tables:** `users`, `jobs`, `applications`, `activity_logs`
- **Outcome:** Database is ready; demo accounts and sample jobs are seeded.

---

### ✅ PHASE 2 — Core Configuration _(DONE)_

- **Objective:** Create the PHP database connection and shared config file used by all backend files.
- **Files created:** `config/config.php`, `config/db.php`, `config/auth.php` (stub), `config/seed.php` (one-time hash generator)
- **Files updated:** `layouts/header.php` (auto-includes db.php), `index.php`
- **Tables:** None (connection only)
- **Outcome:** All PHP pages can connect to MySQL via `$pdo`; session is started automatically.

---

### ✅ PHASE 3 — Authentication Backend _(DONE)_

- **Objective:** Replace localStorage login/register/logout/session guards with real PHP sessions.
- **Files updated:** `login.php`, `register.php`, `logout.php`
- **Files updated:** `config/auth.php` (guards activated), all 8 protected pages (PHP guard added at top)
- **Files updated:** `layouts/navbar-admin.php`, `layouts/navbar-user.php` (session-driven username)
- **Tables:** `users`
- **Outcome:** Real login/logout with `$_SESSION`; auth guards work server-side; `storage.js` session functions are now dead code.

---

### ✅ PHASE 4 — Job Management Backend _(DONE)_

- **Objective:** Replace localStorage job CRUD with real MySQL operations.
- **Files created:** `handlers/jobs.php` (add/edit/delete POST handler, JSON responses)
- **Files updated:** `pages/admin/manage-jobs.php` (DB query replaces sample-data; fetch() replaces localStorage CRUD)
- **Tables:** `jobs`, `activity_logs`
- **Outcome:** Admin can add, edit, and delete jobs; all changes persist to MySQL and are logged.

---

### PHASE 5 — Job Application Backend

- **Objective:** Replace localStorage application submission, cancellation, and status updates.
- **Files to update:** `pages/user/browse-jobs.php`, `pages/user/my-applications.php`, `pages/admin/applications.php`
- **Files to create:** `handlers/applications.php`
- **Tables:** `applications`, `activity_logs`
- **Outcome:** Users can apply and cancel; admins can approve/reject — all persisted in MySQL.

---

### PHASE 6 — User Profile Backend

- **Objective:** Replace localStorage profile update and password change with real DB updates.
- **Files to update:** `pages/user/profile-settings.php`, `pages/admin/profile-settings.php`
- **Files to create:** `handlers/profile.php`
- **Tables:** `users`
- **Outcome:** Profile changes and password updates persist to the database.

---

### PHASE 7 — Admin Dashboard Backend

- **Objective:** Replace localStorage-computed stats and activity log with live SQL queries.
- **Files to update:** `pages/admin/dashboard.php`
- **Tables:** `jobs`, `applications`, `activity_logs`, `users`
- **Outcome:** Dashboard stats and activity log are real-time from MySQL.

---

### PHASE 8 — Reports Backend

- **Objective:** Replace localStorage-computed report tables with SQL aggregate queries.
- **Files to update:** `pages/admin/reports.php`
- **Tables:** `applications`, `jobs`
- **Outcome:** Applicants-per-job and monthly report tables pull from real data.

---

### PHASE 9 — Validation & Security Cleanup

- **Objective:** Harden the system before final presentation.
- **Files to update:** All `handlers/*.php`, `login.php`, `register.php`
- **Tasks:**
  - PDO prepared statements everywhere
  - `password_hash()` / `password_verify()` for all passwords
  - Input sanitization on all form submissions
  - Remove `data.js`, `app.js`
  - Remove `storage.js` and `data/sample-data.php`
- **Outcome:** No raw SQL, no plaintext passwords, no orphaned localStorage code remaining.

---

## 📁 Final Expected Folder Additions

```
OJAMS/
├── config/
│   ├── db.php              # PDO connection
│   ├── config.php          # App-wide constants
│   ├── auth.php            # PHP session guard helpers
│   └── database.sql        # Schema + seed data
│
└── handlers/
    ├── jobs.php            # Add / Edit / Delete job (POST)
    ├── applications.php    # Submit / Cancel / Approve / Reject (POST)
    └── profile.php         # Update profile / Change password (POST)
```

---

## ⚠️ Important Reminders

- Never modify the Bootstrap UI layout during migration.
- Always test each phase independently before moving to the next.
- Keep `storage.js` alive until Phase 3 is fully confirmed working.
- Use PDO with prepared statements — **never** raw `mysqli_query` with user input.
- Passwords must use `password_hash(PASSWORD_BCRYPT)` — never store plaintext.

---

_Stop after each phase and confirm before proceeding to the next._
