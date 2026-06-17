# OJAMS Version Control & Presentation Plan

This document outlines the week-by-week versioned rollout of the Online Job Application Management System (OJAMS).
Each version corresponds to a GitHub release tag. Pages not yet presented display an "Under Construction" placeholder.

---

## Rollout Schedule

### Week 1 — v1.01 | Authentication
**Present:** Login & Register
**Under Construction:** All other pages

- `login.php` — Login page
- `register.php` — Register page

---

### Week 2 — v1.02 | Admin Dashboard
**Present:** Login, Register + Admin Dashboard (layout/shell, no live data yet)
**Under Construction:** All remaining pages

- `pages/admin/dashboard.php` — Admin dashboard shell

---

### Week 3 — v1.03 | User Management
**Present:** All previous + User Management
**Under Construction:** All remaining pages

- `pages/admin/user-management.php` — View, add, edit, delete users

---

### Week 4 — v1.04 | Job Management (Admin)
**Present:** All previous + Manage Jobs
**Under Construction:** All remaining pages

- `pages/admin/manage-jobs.php` — Admin posts, edits, and deletes job listings

---

### Week 5 — v1.05 | Job Browsing (User)
**Present:** All previous + Browse Jobs & Job Detail
**Under Construction:** All remaining pages

- `pages/user/browse-jobs.php` — User views available job listings
- `pages/user/job-detail.php` — User views full job details

---

### Week 6 — v1.06 | Job Applications
**Present:** All previous + Apply for Jobs, My Applications, Admin Applications view
**Under Construction:** All remaining pages

- `pages/user/my-applications.php` — User tracks their submitted applications
- `modals/apply-job-modal.php` — Apply for a job modal
- `pages/admin/applications.php` — Admin views and manages all applications
- `modals/view-application-modal.php` — Admin views application details

---

### Week 7 — v1.07 | Saved Jobs
**Present:** All previous + Saved Jobs
**Under Construction:** All remaining pages

- `pages/user/saved-jobs.php` — User bookmarks/saves job listings

---

### Week 8 — v1.08 | Reports & Export
**Present:** All previous + Reports and Export
**Under Construction:** All remaining pages

- `pages/admin/reports.php` — Admin views application/job analytics
- `pages/admin/export-report.php` — Admin exports report data

---

### Week 9 — v1.09 | Profile Settings
**Present:** All previous + Profile Settings (both roles)
**Under Construction:** All remaining pages

- `pages/admin/profile-settings.php` — Admin profile settings
- `pages/user/profile-settings.php` — User profile settings

---

### Week 10 — v1.10 | Final Polish & Full System Demo
**Present:** Complete system — all pages live

- `forgot-password.php` — Forgot password page
- `reset-password.php` — Reset password page
- Full system walkthrough and final presentation

---

## GitHub Release Tags

| Version | Tag        | Branch/Commit |
|---------|------------|---------------|
| v1.01   | `v1.01`    |               |
| v1.02   | `v1.02`    |               |
| v1.03   | `v1.03`    |               |
| v1.04   | `v1.04`    |               |
| v1.05   | `v1.05`    |               |
| v1.06   | `v1.06`    |               |
| v1.07   | `v1.07`    |               |
| v1.08   | `v1.08`    |               |
| v1.09   | `v1.09`    |               |
| v1.10   | `v1.10`    |               |

> Fill in the commit hash or branch name after each GitHub release is published.

---

## How to Push a Version to GitHub (Step-by-Step)

Do this at the end of each week when you are ready to tag a version for presentation.

### Step 1 — Stage and commit your changes
```bash
git add .
git commit -m "feat: Week 1 - Login and Register (v1.01)"
```

### Step 2 — Push your commits to GitHub
```bash
git push origin main
```

### Step 3 — Create a version tag
```bash
git tag v1.01
```

### Step 4 — Push the tag to GitHub
```bash
git push origin v1.01
```

### Step 5 — Create a GitHub Release (optional but recommended)
1. Go to your GitHub repository
2. Click **Releases** → **Draft a new release**
3. Under **Choose a tag**, select `v1.01`
4. Add a title e.g. `Week 1 - Authentication`
5. Add a short description of what was presented
6. Click **Publish release**

> Repeat Steps 1–5 every week with the new version number (v1.02, v1.03, etc.)

---

## What Happens When You Add or Refactor Something on Main?

**Short answer:** Old version tags are NOT affected. They stay as snapshots forever.

Here is how it works:

```
main branch (keeps growing with new commits)
│
├── commit A  ← tagged as v1.01 (Login & Register)
├── commit B
├── commit C  ← tagged as v1.02 (Dashboard added)
├── commit D  ← tagged as v1.03 (User Management added)
│
└── (future commits keep going...)
```

- A **tag** is a permanent bookmark to a specific commit.
- If you fix a bug or refactor code on `main`, it goes into new commits — old tags are untouched.
- Anyone (your adviser, panel) can checkout an old version anytime using:
  ```bash
  git checkout v1.01
  ```
- To go back to the latest code:
  ```bash
  git checkout main
  ```

### If you refactor something that affects a previous version's feature:
- You do **not** need to update the old tag.
- Just note it in the release description (e.g., "Login page refactored for better UI — original version was v1.01").
- The old tag still shows what was presented at that time. That is the point of tagging.

---

## Under Construction Page Strategy

Pages not yet part of the current version will show:

```
🚧 This page is under construction. Please check back in a future version.
```

No code deletion needed — content is gated by version, so all code remains intact throughout development.
