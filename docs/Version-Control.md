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
- `index.php` — indexi page

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

| Version | Tag     | Commit                                     |
| ------- | ------- | ------------------------------------------ |
| v1.01   | `v1.01` | `1e51cccba0249d3b5aa5e20432ebf4a2d177dd08` |
| v1.02   | `v1.02` | `a42bfdd1f486dc6bfb42d0604a9eb775c687550b` |
| v1.03   | `v1.03` | `3d1d46d6dcec3144d32eafca3659f97d0eaa0a5f` |
| v1.04   | `v1.04` | `73ab789ce50842a15769b24b09faffc51ff3dab9` |
| v1.05   | `v1.05` | `af09c37f13a3d6f66f6677f2fe52d0819cee59f6` |
| v1.06   | `v1.06` | `57d95b677696672e0391a91cfc8fccf654747562` |
| v1.07   | `v1.07` | `be77342d1eaa8b6e71144210bb9726e4b8b779e1` |
| v1.08   | `v1.08` | `20691803c8bf8676264b7421a098e445ac7fdf4c` |
| v1.09   | `v1.09` | `8a6f878936722c3316a249354f9045121b722eff` |
| v1.10   | `v1.10` | `b69d2b404ddca723bd7f63bc1a95c1484555259d` |

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

## What To Do When Your Prof Requests Changes

### The Situation

- You already presented **v1.01** (e.g., Login & Register)
- Your prof wants changes or new content added to Login/Register
- Your next presentation is **v1.02** which should include v1.01 content + the new feature

### Best Approach: Fix on `main`, then re-tag v1.02

**Step 1 — Make the changes on main**

```bash
git checkout main
# edit the affected page (e.g., login.php or register.php)
git add .
git commit -m "feat: update login page per prof feedback"
git push origin main
```

**Step 2 — Delete the old v1.02 tag and re-create it on the latest commit**

```bash
# delete old v1.02 tag locally
git tag -d v1.02

# delete old v1.02 tag on GitHub
git push origin :refs/tags/v1.02

# create new v1.02 tag pointing to the latest commit
git tag v1.02
git push origin v1.02
```

Now v1.02 includes both the prof's requested fix AND the new feature for that week.

### Why this works

- `main` always has the latest and most correct code
- The old v1.01 tag stays as the original snapshot — what was shown before feedback
- The updated v1.02 tag now reflects the improved version you will present next week
- No extra branches, no complicated history

### Rule of Thumb

| Scenario                                                         | What to do                                             |
| ---------------------------------------------------------------- | ------------------------------------------------------ |
| Prof gives feedback before your next presentation                | Fix on `main`, re-tag the upcoming version             |
| Prof gives feedback after you already presented the next version | Fix on `main`, the fix lives in the next tag naturally |
| Never                                                            | Edit directly on an old tag                            |

---

## Under Construction Page Strategy

Pages not yet part of the current version will show:

```
🚧 This page is under construction. Please check back in a future version.
```

No code deletion needed — content is gated by version, so all code remains intact throughout development.
