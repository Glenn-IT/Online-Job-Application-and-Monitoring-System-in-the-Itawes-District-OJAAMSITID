# OJAMS — Online Job Application and Monitoring System in the Itawes District

A role-based PHP web application for managing job postings and applications, backed by a MySQL database.

---

## Tech Stack

| Layer      | Technology |
|------------|-----------|
| Backend    | PHP 7.4+ (no framework) |
| Database   | MySQL 5.7+ / MariaDB 10.3+ via PDO |
| Frontend   | Bootstrap 5.3, Bootstrap Icons, Vanilla JS |
| Server     | XAMPP / Apache (local development) |

---

## Roles

| Role | Access |
|------|--------|
| **Admin** | Dashboard, Manage Jobs (CRUD), Review Applications, Reports, Profile |
| **User**  | Browse Jobs, Apply, Track Applications, Cancel, Profile |

---

## Getting Started

### Requirements
- XAMPP (Apache + MySQL)
- PHP 7.4+

### Setup

1. Copy the project into XAMPP's document root:
   ```
   C:\xampp\htdocs\OJAMS\
   ```

2. Start **Apache** and **MySQL** in XAMPP.

3. Import the database schema:
   - Open **phpMyAdmin** → Import → select `config/database.sql`
   - Or run via CLI: `mysql -u root < config/database.sql`

4. Copy the environment file and configure it:
   ```
   cp .env.example .env
   ```
   Edit `.env` if your MySQL credentials differ from the defaults:
   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=ojams_db
   DB_USER=root
   DB_PASS=
   BASE_URL=http://localhost/OJAMS
   ```

5. Open your browser:
   ```
   http://localhost/OJAMS/
   ```

---

## Demo Accounts

| Role  | Email           | Password    |
|-------|-----------------|-------------|
| Admin | admin@ojams.com | admin123    |
| User  | juan@email.com  | password123 |

Additional user accounts: `maria@email.com`, `carlos@email.com`, `ana@email.com`, `pedro@email.com` — all use `password123`.

---

## Project Structure

```
OJAMS/
├── index.php                  Entry point — redirects to login
├── login.php                  Authentication with brute-force protection
├── register.php               New user registration
├── logout.php                 Session cleanup and redirect
│
├── config/
│   ├── config.php             App constants — reads from .env
│   ├── db.php                 PDO connection + session init
│   ├── auth.php               Session helpers, auth guards, CSRF functions
│   └── database.sql           Full schema + seed data
│
├── handlers/                  JSON API endpoints (fetch() targets)
│   ├── jobs.php               Add / edit / delete jobs (admin only)
│   ├── applications.php       Apply / cancel / approve / reject
│   └── profile.php            Update profile info / change password
│
├── pages/
│   ├── user/
│   │   ├── browse-jobs.php        Job listings, search, apply modal
│   │   ├── my-applications.php    Application history, cancel, view
│   │   └── profile-settings.php   Profile edit and password change
│   └── admin/
│       ├── dashboard.php          Stats cards and activity log
│       ├── manage-jobs.php        Job CRUD table
│       ├── applications.php       Approve / reject applications
│       ├── reports.php            Applicants per job, monthly stats
│       └── profile-settings.php   Admin profile edit
│
├── layouts/                   Shared HTML fragments
│   ├── header.php
│   ├── footer.php
│   ├── navbar-user.php
│   ├── navbar-admin.php
│   └── sidebar-admin.php
│
├── modals/                    Bootstrap modal dialogs
│   ├── apply-job-modal.php
│   ├── add-job-modal.php
│   ├── view-application-modal.php
│   └── logout-modal.php
│
├── components/                Reusable PHP partials
│   ├── job-card.php
│   ├── stats-card.php
│   ├── application-row.php
│   └── table-header.php
│
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   └── images/default-profile.svg
│
├── docs/
│   ├── SYSTEM-AUDIT.md        Full system audit findings
│   └── FIX-PLAN.md            Step-by-step fix plan with progress
│
├── .env                       Local credentials (not committed)
├── .env.example               Template for .env setup
└── .gitignore
```

---

## Security Features (v2)

- CSRF token on every state-changing request
- Session ID regenerated on login (session fixation protection)
- Brute-force protection — 5 failed attempts triggers 15-minute lockout
- Passwords hashed with bcrypt (cost 12)
- All DB queries use prepared statements
- Credentials stored in `.env`, excluded from git

---

## Database

Four tables: `users`, `jobs`, `applications`, `activity_logs`, `login_attempts`.
See `config/database.sql` for full schema and seed data.

---

## License

Educational / academic project.
Made by Glenard Pagurayan &copy; 2026
