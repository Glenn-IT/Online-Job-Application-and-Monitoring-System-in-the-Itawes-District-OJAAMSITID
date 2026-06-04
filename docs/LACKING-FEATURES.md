# OJAMS — Lacking Features & Gaps

**Audited:** 2026-06-05  
**System:** Online Job Application and Monitoring System (OJAMS)  
**Total Issues Found:** 70

---

## Priority Legend
- 🔴 **High** — Core functionality missing or broken
- 🟡 **Medium** — Incomplete UX or partial feature
- 🟢 **Low** — Code quality, performance, or nice-to-have

---

## 1. Placeholder / Stub Features

| # | File | Issue | Priority |
|---|------|-------|----------|
| 1 | `pages/admin/reports.php` | Chart section is a placeholder — shows static text "A visual chart (e.g., Chart.js) would be rendered here in the full implementation." No actual chart is rendered. | 🟡 |
| 2 | `assets/js/script.js` | `comingSoon()` function exists but is never called by any current page — dead code. | 🟢 |
| 3 | `pages/admin/reports.php` | "Download Report" button only calls `window.print()`. No real CSV or PDF export. | 🟡 |

---

## 2. Missing Core Features

| # | Area | Issue | Priority |
|---|------|-------|----------|
| 4 | User | No dedicated **Job Detail Page** (`/job-detail.php?id=X`). Users only see truncated cards in browse view. | 🔴 |
| 5 | User | No **Resume / CV upload** on application form or profile. Applicants cannot attach documents. | 🔴 |
| 6 | User | No **Email Notifications** — applicants are never notified when their application is approved or rejected. | 🔴 |
| 7 | User | No **Forgot Password** flow. Users who forget their password have no recovery option. | 🔴 |
| 8 | Admin | No **User Management Page** (`pages/admin/user-management.php`). Admins cannot view, edit, or deactivate user accounts. | 🔴 |
| 9 | Admin | No **Bulk Actions** on applications or jobs tables (approve all, reject all, delete selected). | 🟡 |
| 10 | Admin | No **Job Search / Filter** in Manage Jobs page. Admin cannot search by title, company, status, or date. | 🟡 |
| 11 | Admin | No **Application Search** in admin applications page (server-side). Only client-side status filter exists. | 🟡 |
| 12 | Admin | No **Export to CSV or PDF** for reports. Printing via browser is the only current option. | 🟡 |
| 13 | Admin | No **Application Status History**. Cannot see when a status changed (e.g., Pending → Approved). | 🟡 |
| 14 | Admin | No **Activity Log Filter/Search**. Dashboard log shows only 20 items with no search, date filter, or pagination. | 🟡 |
| 15 | Admin | No **Job Applicant Count Badge** on Manage Jobs table. Admin cannot see at a glance how many applied per job. | 🟡 |
| 16 | User | No **"Save Job" / Bookmark** feature. Users cannot save jobs to review later. | 🟢 |
| 17 | User | No **Application Deadline** on jobs. Jobs show no end date — unclear when applications close. | 🟡 |
| 18 | Public | No **404 / Error Page**. Invalid routes show raw PHP or blank screen. | 🔴 |

---

## 3. Incomplete UI / UX

| # | File | Issue | Priority |
|---|------|-------|----------|
| 19 | All pages with forms | No **loading state on submit buttons** — buttons don't disable or show a spinner while awaiting server response. | 🟡 |
| 20 | All modals | No **field-level error highlighting** — only generic toast messages shown on validation failure. Specific fields are not marked. | 🟡 |
| 21 | `pages/admin/dashboard.php`, `manage-jobs.php` | No **empty-state CTAs** — "No data" text shown but no action button (e.g., "Add your first job posting"). | 🟢 |
| 22 | `pages/user/browse-jobs.php` | No **pagination for browse jobs**. All jobs load at once. Could be slow with 100+ jobs. | 🟡 |
| 23 | `layouts/sidebar-admin.php` | Admin sidebar **mobile responsiveness unclear** — offcanvas exists but collapsing behavior on small screens is not fully tested. | 🟡 |
| 24 | `register.php` | No **password strength indicator** on register form, unlike the profile change-password page. | 🟢 |
| 25 | All job listings | No **sorting** on job tables (by date, title, company, applicant count, status). | 🟡 |
| 26 | `pages/admin/applications.php` | Cannot **sort applicants** by date, name, or any column. Only status filter exists. | 🟡 |

---

## 4. Missing Validation

| # | File | Issue | Priority |
|---|------|-------|----------|
| 27 | `register.php`, `handlers/profile.php` | No **case-insensitive email uniqueness check** — `test@email.com` and `TEST@EMAIL.COM` would be treated as two separate accounts. | 🟡 |
| 28 | `register.php` | No **password strength requirements** — only minimum 6 characters enforced. No uppercase, number, or special character requirement. | 🟡 |
| 29 | All profile/application forms | No **phone number format validation** — `contact_number` accepts any string including letters and symbols. | 🟡 |
| 30 | `handlers/applications.php`, `handlers/profile.php` | No **birthdate reasonableness check** — accepts future dates and dates from 1900. Should validate for realistic age range (e.g., 16–80). | 🟡 |
| 31 | All modals | **Frontend form validation before submit** is minimal — some required fields not caught client-side before fetch is sent. | 🟢 |

---

## 5. Security Gaps

| # | File | Issue | Priority |
|---|------|-------|----------|
| 32 | All handler files | No **rate limiting on API endpoints** — a user can submit 1000 requests per second with no throttling. | 🔴 |
| 33 | `register.php` | No **CAPTCHA or bot protection** on registration — bot accounts can be mass-registered. | 🔴 |
| 34 | `handlers/profile.php` | No **attempt throttling on password change** — unlimited attempts allowed with no lockout. | 🟡 |
| 35 | All pages | No **HTTPS enforcement** via `.htaccess`. If deployed to production, HTTP traffic is unencrypted. | 🔴 |

---

## 6. Database Gaps

| # | Table / File | Issue | Priority |
|---|------|-------|----------|
| 36 | `jobs` table | No `deadline` / `application_deadline` column — cannot set when a job stops accepting applications. | 🟡 |
| 37 | (missing table) | No `resumes` or `user_files` table — no infrastructure to store uploaded CV/resume files. | 🔴 |
| 38 | `activity_logs` table | Logs record actions but **no foreign key to the affected record** (no `job_id`, `application_id`). Hard to trace what was changed. | 🟡 |
| 39 | (missing table) | No **application status history table** — only the current status is stored, not a changelog. | 🟡 |
| 40 | `users` table | No `profile_photo` or `avatar` column — users cannot upload a profile picture. | 🟢 |
| 41 | `config/database.sql` | No **indexes on filtered columns** (`status`, `date_posted`, `email`) — queries will slow down at scale. | 🟡 |
| 42 | `jobs` table | No `salary_range`, `job_type` (full-time, part-time, contract), or `location` columns — job cards lack key job-listing information. | 🟡 |

---

## 7. Missing Pages / Routes

| # | Issue | Priority |
|---|-------|----------|
| 43 | No **404 error page** | 🔴 |
| 44 | No **Job Detail page** (public/user route for a single job) | 🔴 |
| 45 | No **User Management page** (admin) | 🔴 |
| 46 | No **About / Help / FAQ page** | 🟢 |
| 47 | No **Contact Us / Support page** | 🟢 |
| 48 | No **Terms of Service / Privacy Policy page** | 🟢 |

---

## 8. Performance Issues

| # | File | Issue | Priority |
|---|------|-------|----------|
| 49 | `pages/user/browse-jobs.php` | **Applicant count queried in a loop** — should use a `GROUP BY` join in the main query instead of a separate query per job. | 🟡 |
| 50 | `config/database.sql` | Missing **composite indexes** on `applications(user_id, job_id)`, `jobs(status, date_posted)`, etc. | 🟡 |
| 51 | All pages | No **query result caching** — every page reload hits the database fresh. Acceptable now, but won't scale. | 🟢 |

---

## 9. Configuration / Hardcoded Values

| # | File | Issue | Priority |
|---|------|-------|----------|
| 52 | `pages/admin/applications.php` | `$perPage = 15` is hardcoded — should be in `config/config.php` as a named constant. | 🟢 |
| 53 | `config/auth.php` | `MAX_ATTEMPTS = 5` and `LOCKOUT_MINUTES = 15` are hardcoded — no way to adjust without editing source. | 🟢 |
| 54 | All files | **Timezone not set globally** in `config/config.php`. PHP and MySQL may use different timezones causing timestamp mismatches. | 🟡 |

---

## 10. Documentation Gaps

| # | Issue | Priority |
|---|-------|----------|
| 55 | No **API documentation** for handler endpoints (`handlers/*.php`) | 🟢 |
| 56 | No **ER diagram** for the database schema | 🟢 |
| 57 | No **deployment guide** for production setup (Apache vhost, environment config, HTTPS) | 🟡 |
| 58 | No **user guide or onboarding tutorial** for new applicants or admins | 🟢 |

---

## Summary

| Priority | Count |
|----------|-------|
| 🔴 High  | 14    |
| 🟡 Medium | 31   |
| 🟢 Low   | 13    |
| **Total** | **58** |

### Top 5 to Fix First
1. **Forgot Password** — users with no recovery option will abandon the system
2. **Rate Limiting on API Endpoints** — critical security gap
3. **HTTPS Enforcement** — must-have before any production deployment
4. **404 Error Page** — currently exposes raw PHP errors
5. **Email Notifications** — applicants have no feedback loop without this
