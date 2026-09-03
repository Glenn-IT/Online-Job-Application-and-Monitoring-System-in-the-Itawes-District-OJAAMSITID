# OJAMS — Fix Checklist

**Audited:** 2026-06-05  
**System:** Online Job Application and Monitoring System (OJAMS)  
**Total Items:** 58 | **Completed:** 50 / 58

---

## Legend

- 🔴 **High** — Core functionality missing or broken
- 🟡 **Medium** — Incomplete UX or partial feature
- 🟢 **Low** — Code quality, performance, or nice-to-have

---

## Phase 1 — Critical Fixes (Do These First) 🔴

- [x] **Forgot Password flow** — users who forget their password have no recovery option (`login.php` + new handler)
- [x] **Rate limiting on API endpoints** — handlers accept unlimited requests per second (`handlers/*.php`)
- [x] **HTTPS enforcement** — add `.htaccess` redirect before any production deployment
- [x] **404 / Error page** — invalid routes currently show raw PHP errors or a blank screen
- [x] **Email notifications** — automated Gmail SMTP notifications for application submission, status evaluation (Approved/Rejected), and staff account approvals (`config/mailer.php`, `handlers/applications.php`, `handlers/admin.php`)
- [x] **SMS notifications (PhilSMS)** — automated SMS notifications for application submission, status evaluation (Approved/Rejected), and scheduled interviews (`config/sms.php`, `handlers/applications.php`)
- [x] **User Management page** — admins cannot view, edit, or deactivate user accounts (new `pages/admin/user-management.php`)
- [x] **Job Detail page** — users only see truncated cards; no full detail view (new `pages/user/job-detail.php`)
- [x] **Resume / CV upload** — applicants cannot attach documents to their application (`modals/apply-job-modal.php`)
- [x] **`resumes` / `user_files` table** — no database infrastructure to store uploaded files (`config/database.sql`)

---

## Phase 2 — Missing Features 🟡

- [x] **Application Deadline field** — add `deadline` column to `jobs` table and show it on job cards
- [x] **Job Applicant Count badge** — show number of applicants per job in Manage Jobs table (`pages/admin/manage-jobs.php`)
- [x] **Bulk Actions** — approve all / reject all / delete selected on applications and jobs tables
- [x] **Job Search / Filter in admin** — filter by title, company, status, date in Manage Jobs (`pages/admin/manage-jobs.php`)
- [x] **Application Search in admin** — server-side search on admin applications page (`pages/admin/applications.php`)
- [x] **Export to CSV / PDF** — replace `window.print()` with real export in reports (`pages/admin/reports.php`)
- [x] **Real Charts in Reports** — replace placeholder text with actual Chart.js charts (`pages/admin/reports.php`)
- [x] **Application Status History** — log and display when a status changed (e.g., Pending → Approved)
- [x] **Activity Log Filter / Search** — dashboard log limited to 20 items with no search or date filter (`pages/admin/dashboard.php`)
- [x] **`salary_range`, `job_type`, `location` columns** — add to `jobs` table for richer job listings
- [x] **Attempt throttling on password change** — unlimited attempts currently allowed (`handlers/profile.php`)
- [x] **Timezone set globally** — PHP and MySQL may produce mismatched timestamps (`config/config.php`)

---

## Phase 3 — UI / UX Improvements 🟡

- [x] **Loading state on submit buttons** — buttons should disable and show a spinner while awaiting server response
- [x] **Field-level error highlighting** — mark the specific invalid field instead of only showing a toast message
- [x] **Pagination for Browse Jobs** — all jobs load at once; add pagination for 100+ job scenarios (`pages/user/browse-jobs.php`)
- [x] **Sorting on job and application tables** — sort by date, title, company, status, applicant count
- [x] **Mobile sidebar responsiveness** — verify and fix admin sidebar collapse on small screens (`layouts/sidebar-admin.php`)

---

## Phase 4 — Validation Fixes 🟡

- [x] **Case-insensitive email uniqueness** — `test@email.com` and `TEST@EMAIL.COM` should be treated as duplicates (`register.php`, `handlers/profile.php`)
- [x] **Password strength requirements** — enforce uppercase, number, and special character on register (`register.php`)
- [x] **Phone number format validation** — `contact_number` currently accepts any string including letters (`handlers/profile.php`, `handlers/applications.php`)
- [x] **Birthdate reasonableness check** — reject future dates and unrealistic ages like 1900 (accept range: 16–80 years old)
- [x] **Frontend form validation before submit** — catch required fields client-side before sending a fetch request

---

## Phase 5 — Database Improvements 🟡

- [x] **Indexes on filtered columns** — add indexes on `status`, `date_posted`, `email` for faster queries (`config/database.sql`)
- [x] **Composite indexes** — add on `applications(user_id, job_id)`, `jobs(status, date_posted)`
- [x] **Application status history table** — store changelog of status transitions instead of only current status
- [x] **`activity_logs` foreign keys** — add `job_id` and `application_id` columns to link logs to specific records
- [x] **Applicant count query optimization** — replace per-job loop query with a single `GROUP BY` join (`pages/user/browse-jobs.php`)

---

## Phase 6 — Low Priority / Polish 🟢

- [x] **Password strength indicator on Register** — already exists on profile page, add it to `register.php`
- [x] **Empty-state CTAs** — replace "No data" with helpful buttons like "Add your first job posting"
- [x] **"Save Job" / Bookmark feature** — let users save jobs to review later
- [x] **Remove dead `comingSoon()` function** — unused code in `assets/js/script.js`
- [x] **Move hardcoded constants to config** — `$perPage`, `MAX_ATTEMPTS`, `LOCKOUT_MINUTES` should be in `config/config.php`
- [x] **`profile_photo` / avatar column** — add to `users` table for profile pictures
- [x] **Query result caching** — reduce repeated DB hits for static data (acceptable now, needed at scale)
- [x] **About / Help / FAQ page** — informational page for new visitors
- [x] **Contact Us / Support page** — let users reach the admin for help
- [x] **Terms of Service / Privacy Policy page** — required for any public deployment
- [x] **API documentation** — document all `handlers/*.php` endpoints
- [x] **ER diagram** — visual database schema diagram
- [x] **Deployment guide** — Apache vhost setup, environment config, HTTPS setup
- [x] **User guide / onboarding tutorial** — walkthrough for new applicants and admins

---

## Progress Tracker

| Phase                      | Items  | Done   | Remaining |
| -------------------------- | ------ | ------ | --------- |
| Phase 1 — Critical         | 10     | 10     | 0         |
| Phase 2 — Missing Features | 12     | 12     | 0         |
| Phase 3 — UI / UX          | 5      | 5      | 0         |
| Phase 4 — Validation       | 5      | 5      | 0         |
| Phase 5 — Database         | 5      | 5      | 0         |
| Phase 6 — Polish           | 14     | 14     | 0         |
| **Total**                  | **58** | **58** | **0**     |
