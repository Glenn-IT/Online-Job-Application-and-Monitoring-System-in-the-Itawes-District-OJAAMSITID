# 📋 OJAMS — Online Job Application Monitoring System

> A frontend-focused PHP prototype for a Job Application Monitoring System built for student presentations, UI/UX demonstrations, and system walkthroughs — **using localStorage and sample data only, no database required**.

---

## 📸 Overview

OJAMS is a structured PHP prototype that simulates a complete Job Application Monitoring workflow with two roles:

| Role         | Access                                                                    |
| ------------ | ------------------------------------------------------------------------- |
| 👤 **User**  | Browse Jobs, Apply, Track Applications, Cancel Applications, Edit Profile |
| 🛠️ **Admin** | Dashboard, Manage Jobs (CRUD), Review Applications, Reports, Edit Profile |

---

## 🛠️ Technology Stack

- **PHP** — Modular file structure (MVC-like)
- **Bootstrap 5** — Via CDN for responsive UI
- **Bootstrap Icons** — Via CDN for iconography
- **Vanilla JavaScript** — For interactions, localStorage CRUD, and session management
- **localStorage** — Persistent client-side data storage (no database)
- **Sample Data** — PHP arrays as fallback / seed data

---

## 🗂️ Project Structure

```
OJAMS/
│
├── index.php                  # Entry point (redirects to login)
├── login.php                  # Login page with email/password authentication
├── register.php               # New user registration page
├── logout.php                 # Logout handler (clears session, redirects)
│
├── data/
│   └── sample-data.php        # All sample data arrays (seed data)
│
├── layouts/
│   ├── header.php             # HTML head & meta tags
│   ├── footer.php             # Scripts & closing tags
│   ├── navbar-user.php        # User navigation bar
│   ├── navbar-admin.php       # Admin top navigation bar
│   └── sidebar-admin.php      # Admin sidebar navigation
│
├── pages/
│   ├── user/
│   │   ├── browse-jobs.php        # Job listings with cards + search
│   │   ├── my-applications.php    # User's application history + cancel
│   │   └── profile-settings.php   # Profile view & edit + password change
│   │
│   └── admin/
│       ├── dashboard.php          # Stats overview & activity log
│       ├── manage-jobs.php        # Full CRUD for job postings
│       ├── applications.php       # Approve/Reject/View applications
│       ├── profile-settings.php   # Admin personal info & password update
│       └── reports.php            # Reports & analytics with visual charts
│
├── components/
│   ├── job-card.php           # Reusable job listing card
│   ├── stats-card.php         # Dashboard stat card
│   ├── application-row.php    # Application table row
│   └── table-header.php       # Reusable table header
│
├── modals/
│   ├── apply-job-modal.php        # Full application form (user)
│   ├── add-job-modal.php          # Admin: add new job
│   ├── edit-job-modal.php         # Admin: edit existing job
│   ├── view-application-modal.php # Admin: view applicant details
│   └── logout-modal.php           # Logout confirmation
│
├── assets/
│   ├── css/
│   │   └── style.css          # Custom styles
│   ├── js/
│   │   ├── storage.js         # localStorage CRUD manager
│   │   ├── app.js             # Core app logic & session helpers
│   │   ├── data.js            # Data initialization / seeding
│   │   └── script.js          # UI behaviors & interactions
│   └── images/
│       └── default-profile.svg
│
└── README.md
```

---

## 🚀 Getting Started

### Prerequisites

- **XAMPP** (or any PHP-enabled web server)
- A modern web browser

### Installation

1. Clone or copy this project into your web server's document root:
   ```
   C:\xampp\htdocs\OJAMS\
   ```
2. Start **Apache** in XAMPP.
3. Open your browser and navigate to:
   ```
   http://localhost/OJAMS/
   ```
4. You'll be redirected to the **Login Page**. Use the demo accounts below:

   | Role     | Email           | Password    |
   | -------- | --------------- | ----------- |
   | 🛠️ Admin | admin@ojams.com | admin123    |
   | 👤 User  | juan@email.com  | password123 |

---

## 📖 System Features

### 🔐 Authentication

| Feature                | Description                                                                                |
| ---------------------- | ------------------------------------------------------------------------------------------ |
| **Login**              | Email & password login with role-based redirection (Admin → Dashboard, User → Browse Jobs) |
| **Show/Hide Password** | Toggle password visibility on the login form                                               |
| **Forgot Password**    | Forgot Password modal with email input (prototype alert)                                   |
| **Register**           | New account registration form with validation (name, email, password, contact, address)    |
| **Logout**             | Confirmation modal before clearing session and redirecting to login                        |
| **Session Guard**      | Pages are protected — unauthenticated users are redirected to login automatically          |

---

### 👤 User Module

#### Browse Jobs

| Feature              | Description                                                                              |
| -------------------- | ---------------------------------------------------------------------------------------- |
| **Job Listings**     | View all available jobs displayed as cards (title, company, description, qualifications) |
| **Job Count Badge**  | Live count of total available job postings                                               |
| **Search Jobs**      | Real-time search bar filters jobs by title, company, or keyword                          |
| **Category Filter**  | Dropdown to filter jobs by category (IT, Design, Support)                                |
| **Job Status Badge** | Each card shows Open or Closed status                                                    |
| **Applicant Count**  | Each card displays how many applicants have applied                                      |
| **Apply Now**        | Opens a full application form modal for open positions                                   |
| **Already Applied**  | Apply button is replaced with a disabled "Already Applied" indicator if submitted        |
| **Closed Jobs**      | Apply button is disabled and shows "Closed" for non-open postings                        |

#### Application Form (Modal)

| Feature                    | Description                                                            |
| -------------------------- | ---------------------------------------------------------------------- |
| **Personal Info**          | Full name, email, contact number, address, birthdate, age              |
| **Educational Background** | Fields for Elementary, JHS, SHS, and College with school name and year |
| **Skills & Experience**    | Text areas for skills and work experience                              |
| **Submit Application**     | Saves application to localStorage and logs activity                    |

#### My Applications

| Feature                | Description                                                                        |
| ---------------------- | ---------------------------------------------------------------------------------- |
| **Application Table**  | Lists all submitted applications with job title, company, date applied, and status |
| **Status Badges**      | Color-coded badges: Pending (yellow), Approved (green), Rejected (red)             |
| **View Application**   | Opens a modal showing full details of a specific application                       |
| **Cancel Application** | Cancel button (Pending only) with confirmation modal; removes from localStorage    |
| **Summary Cards**      | Shows total, pending, and approved application counts at a glance                  |

#### Profile Settings (User)

| Feature              | Description                                                                     |
| -------------------- | ------------------------------------------------------------------------------- |
| **View Profile**     | Displays full name, email, contact number, address, birthdate (read-only)       |
| **Edit Mode Toggle** | "Edit Profile" button switches between view and edit mode                       |
| **Update Info**      | Editable fields for full name, email, contact number, address, birthdate        |
| **Change Password**  | Current password verification before setting a new password                     |
| **Save/Cancel**      | Save persists changes to localStorage; Cancel discards and reverts to view mode |

---

### 🛡️ Admin Module

#### Dashboard

| Feature             | Description                                                                                |
| ------------------- | ------------------------------------------------------------------------------------------ |
| **Stats Overview**  | Four stat cards: Total Job Posts, Total Applicants, Pending Applications, Approved         |
| **Live Stats**      | Counts are dynamically recalculated from localStorage on every page load                   |
| **Activity Log**    | Timestamped table of recent system events (new applications, job creations, approvals)     |
| **Activity Badges** | Color-coded status labels: New (blue), Created (primary), Approved (green), Rejected (red) |

#### Manage Jobs

| Feature           | Description                                                                                |
| ----------------- | ------------------------------------------------------------------------------------------ |
| **Jobs Table**    | Lists all jobs with title, company, date posted, and status                                |
| **Add New Job**   | Modal form to create a new job (title, company, description, qualifications, date, status) |
| **Edit Job**      | Pre-filled edit modal; updates job details in localStorage                                 |
| **Delete Job**    | Removes a job from localStorage with confirmation                                          |
| **Status Toggle** | Job status displayed as Open (green) or Closed (grey) badge                                |

#### Applications

| Feature                | Description                                                                                 |
| ---------------------- | ------------------------------------------------------------------------------------------- |
| **Applications Table** | Lists all applicants with name, job title, date applied, and status                         |
| **Filter by Status**   | Filter buttons: All, Pending, Approved, Rejected — instantly updates the table              |
| **Approve**            | Sets application status to Approved and logs activity; button disables after action         |
| **Reject**             | Sets application status to Rejected and logs activity; button disables after action         |
| **View Details**       | Opens a modal showing full applicant info (personal details, education, skills, experience) |

#### Reports & Monitoring

| Feature                | Description                                                                    |
| ---------------------- | ------------------------------------------------------------------------------ |
| **Applicants per Job** | Table with job-wise applicant count and a Bootstrap progress bar visualization |
| **Monthly Report**     | Table showing total applications per month with a progress bar visualization   |
| **Live Calculation**   | Report data is computed dynamically from localStorage on page load             |
| **Chart Placeholder**  | Dedicated section for a future Chart.js integration                            |
| **Download Report**    | Button to trigger a report download (prototype alert)                          |

#### Admin Profile Settings

| Feature                  | Description                                                          |
| ------------------------ | -------------------------------------------------------------------- |
| **Profile Overview**     | Displays admin name, email, role badge, and last updated timestamp   |
| **Update Personal Info** | Editable fields for first name, last name, email, and contact number |
| **Change Password**      | Current password confirmation required before setting a new password |
| **Save Changes**         | Persists updates to localStorage with a success alert                |

---

## 💡 Design Principles

- ✅ Clean, modular PHP structure (MVC-like separation)
- ✅ Reusable components (`job-card`, `stats-card`, `table-header`, etc.)
- ✅ PHP `include` for layouts — no duplicate HTML
- ✅ Responsive design with Bootstrap 5
- ✅ localStorage-based persistence — no database needed
- ✅ Role-based session guards on every protected page
- ✅ Descriptive comments in every file
- ✅ Clear naming conventions

---

## ⚠️ Disclaimer

This is a **prototype** for academic/demonstration purposes only. No real authentication, database, or backend logic is implemented. All data is stored in the browser's localStorage and will reset if cleared.

---

## 📄 License

This project is for educational purposes.
This is made by Glenard Pagurayan

---

&copy; 2026 OJAMS — Online Job Application Monitoring System
