# OJAMS — Capstone Defense Presentation Walkthrough & Panelist Demonstration Guide
<!-- System: Online Job Application and Monitoring System in the Itawes District (OJAMS) -->
<!-- Target Audience: Capstone Panelists, Advisers, and Evaluators -->

---

## 🧭 Executive Summary & Timing Strategy

| Phase | Section | Recommended Duration | Primary Interface |
| :--- | :--- | :--- | :--- |
| **Phase 1** | Project Rationale & Problem Context | 1.5 mins | Title Slide / [index.php](file:///C:/xampp/htdocs/OJAMS/index.php) |
| **Phase 2** | Architecture, RBAC & Security Baseline | 1.0 min | [SYSTEM_MEMORY.md](file:///C:/xampp/htdocs/OJAMS/SYSTEM_MEMORY.md) |
| **Phase 3** | Public Portal & Municipal Identity | 1.0 min | [index.php](file:///C:/xampp/htdocs/OJAMS/index.php) |
| **Phase 4** | Applicant Registration & 18+ Civil Service Age Trapping | 1.0 min | [register.php](file:///C:/xampp/htdocs/OJAMS/register.php) |
| **Phase 5** | Job Discovery, Search & Dynamic Filter Chips | 1.0 min | [pages/user/browse-jobs.php](file:///C:/xampp/htdocs/OJAMS/pages/user/browse-jobs.php) |
| **Phase 6** | Job Details & Automated Deadline Alert | 1.0 min | [pages/user/job-detail.php](file:///C:/xampp/htdocs/OJAMS/pages/user/job-detail.php) |
| **Phase 7** | Application Submission & 5 Civil Service Documents | 2.0 mins | [modals/apply-job-modal.php](file:///C:/xampp/htdocs/OJAMS/modals/apply-job-modal.php) |
| **Phase 8** | Real-time Applicant Tracking Dashboard | 1.0 min | [pages/user/my-applications.php](file:///C:/xampp/htdocs/OJAMS/pages/user/my-applications.php) |
| **Phase 9** | Staff Operations & HR Queue Triage | 1.0 min | [pages/staff/dashboard.php](file:///C:/xampp/htdocs/OJAMS/pages/staff/dashboard.php) |
| **Phase 10** | Candidate Dossier & Document Inspection | 1.5 mins | [modals/view-application-modal.php](file:///C:/xampp/htdocs/OJAMS/modals/view-application-modal.php) |
| **Phase 11** | Interview Scheduling & Dual Notifications (Email + SMS) | 1.5 mins | [modals/schedule-interview-modal.php](file:///C:/xampp/htdocs/OJAMS/modals/schedule-interview-modal.php) |
| **Phase 12** | Administrator Command Center & User Governance | 1.0 min | [pages/admin/user-management.php](file:///C:/xampp/htdocs/OJAMS/pages/admin/user-management.php) |
| **Phase 13** | Analytical Reports & Multi-format Export (PDF/CSV/Excel) | 1.0 min | [pages/admin/reports.php](file:///C:/xampp/htdocs/OJAMS/pages/admin/reports.php) |
| **Phase 14** | Audit Trail & Transition to Panel Q&A | 0.5 min | [pages/admin/dashboard.php](file:///C:/xampp/htdocs/OJAMS/pages/admin/dashboard.php) |
| **Total** | **Full System Presentation** | **~15.0 mins** | — |

---

## 🛠️ Pre-Defense Staging & Credentials Setup

Before starting the defense presentation, prepare your presentation workstation:

1. **Browser Setup**:
   * **Window 1 (Main Browser):** Logged in as **Staff** or **Admin**.
   * **Window 2 (Incognito / Private Window):** Ready for the **Jobseeker** live demo. This eliminates logging in and out between role transitions.
2. **Standard Accounts**:
   * **Admin Account:** `admin@ojams.com` | Password: `password123`
   * **All 10 Sample Jobseekers:** Password is uniform: `password123`
3. **Database Seed Script**:
   * Available at [`scripts/seed_demo_data.php`](file:///C:/xampp/htdocs/OJAMS/scripts/seed_demo_data.php). Run via `php scripts/seed_demo_data.php` at any time to re-populate fresh demo data.

### 👥 The 10 Seeded Jobseekers & Live Applications
All jobseekers are pre-configured with realistic Itawes District profiles, legal civil service ages (18+), and complete 5-document dossiers in [`uploads/resumes/`](file:///C:/xampp/htdocs/OJAMS/uploads/resumes/):

| # | Applicant Name | Email (`password123`) | Location | Applied Position | Status |
| :- | :--- | :--- | :--- | :--- | :--- |
| 1 | **Maria Lourdes Santos** | `maria.santos@gmail.com` | Piat | Administrative Aide IV | **Approved** (Interview: Oct 5, 9:30 AM) |
| 2 | **Juan Carlos Dela Cruz** | `juan.delacruz@gmail.com` | Piat | Municipal IT Support | **Approved** (Interview: Oct 6, 2:00 PM) |
| 3 | **Carlos Miguel Reyes** | `carlos.reyes@gmail.com` | Tuao | Revenue Collection Clerk | **Pending** (Ready for live review) |
| 4 | **Ana Patricia Garcia** | `ana.garcia@gmail.com` | Solana | Agricultural Technologist | **Pending** (Ready for live review) |
| 5 | **Pedro Jose Mendoza** | `pedro.mendoza@gmail.com` | Piat | Disaster Risk Reduction Officer | **Approved** (Interview: Oct 8, 10:00 AM) |
| 6 | **Elena Marie Torres** | `elena.torres@gmail.com` | Piat | Rural Health Midwife II | **Pending** (Ready for live review) |
| 7 | **Mark Anthony Bautista** | `mark.bautista@gmail.com` | Tuao | Community Tourism Coordinator | **Approved** (Interview: Oct 9, 11:00 AM) |
| 8 | **Grace Anne Aquino** | `grace.aquino@gmail.com` | Piat | Data Encoder (Closed Job) | **Rejected** (Historical record) |
| 9 | **Christian Paul Ramos** | `christian.ramos@gmail.com` | Sto. Niño | Engineering Aide (Closed Job) | **Approved** (Appointed / Archived) |
| 10 | **Jessica Mae Flores** | `jessica.flores@gmail.com` | Tuao | Environmental Inspector (Closed) | **Rejected** (Historical record) |

### 💼 The 10 Seeded Jobs (7 Open & 3 Closed)

| # | Job Title | Office / Entity | Type | Location | Status | Closing Date |
| :- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | **Administrative Aide IV** | LGU Piat | Full-time | Piat | **Open** | Nov 30, 2026 |
| 2 | **Municipal IT Support** | MIS - LGU Piat | Full-time | Piat | **Open** | Dec 15, 2026 |
| 3 | **Revenue Collection Clerk II** | Municipal Treasury | Contract | Tuao | **Open** | Nov 15, 2026 |
| 4 | **Agricultural Technologist** | Agriculture Office | Full-time | Solana | **Open** | Nov 20, 2026 |
| 5 | **Disaster Risk Reduction Aide** | MDRRMO Piat | Full-time | Piat | **Open** | Nov 25, 2026 |
| 6 | **Rural Health Midwife II** | Rural Health Unit | Full-time | Tuao | **Open** | Dec 05, 2026 |
| 7 | **Community Tourism Coordinator** | Tourism Council | Contract | Piat | **Open** | Nov 10, 2026 |
| 8 | **Data Encoder / Civil Registry** | Civil Registrar | Part-time | Piat | **Closed** | Aug 31, 2026 |
| 9 | **Engineering Aide / CAD** | Engineering Office | Full-time | Solana | **Closed** | Aug 15, 2026 |
| 10 | **Environmental Inspector** | MENRO Tuao | Contract | Tuao | **Closed** | Sep 01, 2026 |

---

## 🎬 Step-by-Step Presentation Script (From First to Last)

---

### Step 1: Opening & Local Problem Statement
* **Screen Display:** Slide Deck or [index.php](file:///C:/xampp/htdocs/OJAMS/index.php) hero section
* **Estimated Time:** 1.5 minutes
* **Screen Action:** Present the title slide with the official municipal emblem of Piat ([img/Piat-Logo.png](file:///C:/xampp/htdocs/OJAMS/img/Piat-Logo.png)).
* **🗣️ Verbal Script:**
  > *"Good morning, honorable panel members, advisers, and guests. Today, we are proud to present **OJAMS — the Online Job Application and Monitoring System in the Itawes District**.*
  >
  > *In rural district municipalities such as Piat, Tuao, and neighboring communities, recruitment and employment facilitation traditionally rely on paper forms, bulletin boards, and in-person submissions. This introduces major logistical barriers: applicants incur costly travel to submit dossiers, files get misplaced, and municipal HR staff face difficulties tracking hundreds of applications and broadcasting interview schedules.*
  >
  > *OJAMS solves this by establishing a centralized, secure, and mobile-friendly employment monitoring system specifically tailored to Local Government Unit (LGU) and Civil Service standards."*

---

### Step 2: System Architecture & Security Baseline
* **Screen Display:** Architecture Diagram or [SYSTEM_MEMORY.md](file:///C:/xampp/htdocs/OJAMS/SYSTEM_MEMORY.md)
* **Estimated Time:** 1.0 minute
* **Screen Action:** Briefly highlight the technical foundation.
* **🗣️ Verbal Script:**
  > *"Architecturally, OJAMS is built on modern PHP 8+, MySQL using PDO, responsive CSS, and vanilla JavaScript.
  >
  > *Security and data integrity are enforced across every endpoint:
  > 1. **Role-Based Access Control (RBAC):** Strict separation among `Admin`, `Staff`, and `User` (Jobseeker) roles defined in [config/auth.php](file:///C:/xampp/htdocs/OJAMS/config/auth.php).
  > 2. **SQL Injection Defense:** 100% prepared parameterized queries via [config/db.php](file:///C:/xampp/htdocs/OJAMS/config/db.php).
  > 3. **CSRF Protection:** Cryptographic CSRF tokens on every form and AJAX transaction.
  > 4. **Dual-Channel Notification Engine:** PHPMailer SMTP for transactional emails, and an integrated SMS gateway for rural applicants who may lack active internet data."*

---

### Step 3: Public Landing Page & Municipal Identity
* **Screen Display:** [index.php](file:///C:/xampp/htdocs/OJAMS/index.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:** Scroll through the landing page: Hero section, quick search bar, featured municipal vacancies, and footer with municipal seal.
* **🗣️ Verbal Script:**
  > *"Starting at our public gateway, jobseekers and residents are greeted by the official municipal seal of Piat. The public portal allows immediate search and filtering of active vacancies without requiring registration upfront, promoting transparency in local government recruitment."*

---

### Step 4: Jobseeker Registration & 18+ Civil Service Age Trapping
* **Screen Display:** [register.php](file:///C:/xampp/htdocs/OJAMS/register.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Open registration form.
  2. Input a test birthdate that makes the candidate under 18 years old.
  3. Show the dynamic validation prompt preventing submission.
  4. Correct the date to a valid legal working age (18+) and register or proceed to login.
* **🗣️ Verbal Script:**
  > *"To ensure compliance with Philippine Civil Service and labor regulations, OJAMS implements strict age trapping. The system enforces that candidates must be at least 18 years old. This is validated on the front-end datepicker and enforced server-side in [handlers/auth.php](file:///C:/xampp/htdocs/OJAMS/handlers/auth.php) to protect system integrity."*

---

### Step 5: Job Discovery, Search & Dynamic Filter Chips
* **Screen Display:** [pages/user/browse-jobs.php](file:///C:/xampp/htdocs/OJAMS/pages/user/browse-jobs.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Demonstrate the search bar (filter by title or department).
  2. Click the quick-filter pills (*Full-time*, *Part-time*, *Contract*, *Internship*).
  3. Point out company monograms and color-coded tag pills.
* **🗣️ Verbal Script:**
  > *"Inside the applicant portal, candidates have a modern discovery view. With interactive quick-filter chips, candidates can filter between Full-time civil service roles, contractual assignments, or internships in one click."*

---

### Step 6: Job Details & Automated Closing Deadline Alerts
* **Screen Display:** [pages/user/job-detail.php](file:///C:/xampp/htdocs/OJAMS/pages/user/job-detail.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Open a specific job listing.
  2. Highlight the **6-box quick highlight tile grid**: *Job Type, Salary Range, Location, Date Posted, Closing Deadline, and Total Current Applicants*.
  3. Show the mobile-sticky action bar ([assets/css/style.css](file:///C:/xampp/htdocs/OJAMS/assets/css/style.css)).
* **🗣️ Verbal Script:**
  > *"On the job detail screen, applicants see essential decision metrics: duties, qualification standards, and salary. Crucially, OJAMS features an automated deadline checker that disables applications once the closing date expires, preventing late submissions."*

---

### Step 7: Application Submission & 5 Mandatory Civil Service Documents
* **Screen Display:** [modals/apply-job-modal.php](file:///C:/xampp/htdocs/OJAMS/modals/apply-job-modal.php)
* **Estimated Time:** 2.0 minutes
* **Screen Action:**
  1. Click **"Apply Now"** to trigger the modal.
  2. Show educational background fields (Elementary, JHS, SHS, College).
  3. Highlight the **5 Mandatory Civil Service Document Upload Fields**:
     * 📄 **Resume / CV**
     * 📝 **Application Letter**
     * 📑 **Personal Data Sheet (PDS - CS Form 212)**
     * 🏅 **Certificate of CSC Eligibility**
     * 🎓 **Transcript of Records (TOR)**
  4. Attach the sample files and click **"Submit Application"**.
* **🗣️ Verbal Script:**
  > *"Unlike commercial job portals, OJAMS is designed for public sector compliance. In accordance with Civil Service Commission guidelines, all 5 mandatory documents must be uploaded: Resume, Application Letter, Personal Data Sheet CS Form 212, CSC Eligibility Certificate, and Transcript of Records.
  >
  > *Files are validated for allowable MIME extensions (PDF, DOC, DOCX, JPG, PNG), verified against a 5 MB ceiling per file, sanitized, and stored securely in [uploads/resumes/](file:///C:/xampp/htdocs/OJAMS/uploads/resumes/)."*

---

### Step 8: Real-Time Applicant Tracking Dashboard
* **Screen Display:** [pages/user/my-applications.php](file:///C:/xampp/htdocs/OJAMS/pages/user/my-applications.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Show the newly submitted application marked with a blue `Pending` badge.
  2. Click to preview uploaded documents.
  3. Mention that applicants can update documents while the status is still *Pending*.
* **🗣️ Verbal Script:**
  > *"Applicants no longer need to call or visit the municipal hall to check if their papers were received. The applicant dashboard provides real-time tracking across all lifecycle states: Pending, Under Review, Interview Scheduled, Hired, or Rejected."*

---

### Step 9: Staff Portal & HR Operations Dashboard
* **Screen Display:** Switch to the Staff window: [pages/staff/dashboard.php](file:///C:/xampp/htdocs/OJAMS/pages/staff/dashboard.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Point out the staff KPI metric cards: *Active Postings, Total Applicants, Pending Reviews, and Scheduled Interviews for Today*.
  2. Briefly show [pages/staff/manage-jobs.php](file:///C:/xampp/htdocs/OJAMS/pages/staff/manage-jobs.php) where staff can post, edit, or close vacancies.
* **🗣️ Verbal Script:**
  > *"Now switching to the operational view of the Municipal HR Staff. The staff dashboard provides an instant triage center showing active recruitment workloads and pending candidate submissions."*

---

### Step 10: Candidate Dossier & Document Inspection
* **Screen Display:** [pages/staff/applications.php](file:///C:/xampp/htdocs/OJAMS/pages/staff/applications.php) & [modals/view-application-modal.php](file:///C:/xampp/htdocs/OJAMS/modals/view-application-modal.php)
* **Estimated Time:** 1.5 minutes
* **Screen Action:**
  1. Locate the test applicant's submission.
  2. Click **"View Details"** to open the candidate dossier modal.
  3. Show the applicant's educational background, skills, and contact information.
  4. Click on the document view links to inspect the 5 uploaded civil service files.
  5. Change the application status from `Pending` to `Under Review`.
* **🗣️ Verbal Script:**
  > *"HR staff can evaluate the complete candidate dossier without opening separate email inboxes or paper folders. With a single click, staff can verify credentials and inspect all 5 uploaded documents directly within the system."*

---

### Step 11: Interview Scheduling & Dual Notifications (Email + SMS)
* **Screen Display:** [modals/schedule-interview-modal.php](file:///C:/xampp/htdocs/OJAMS/modals/schedule-interview-modal.php)
* **Estimated Time:** 1.5 minutes
* **Screen Action:**
  1. Click **"Schedule Interview"**.
  2. Pick the interview date, time, venue/meeting link, and notes.
  3. Click **"Confirm Schedule"**.
  4. Explain the backend notification triggers in [config/mailer.php](file:///C:/xampp/htdocs/OJAMS/config/mailer.php) and [config/sms.php](file:///C:/xampp/htdocs/OJAMS/config/sms.php).
* **🗣️ Verbal Script:**
  > *"When staff schedules an interview, OJAMS automatically triggers our dual notification pipeline:
  > 1. An official email invitation is dispatched via PHPMailer / Gmail SMTP with full interview details.
  > 2. Simultaneously, an SMS alert is transmitted to the candidate's registered mobile number.
  >
  > *This dual-channel approach ensures that even candidates traveling through remote barangays with spotty mobile data still receive instant notifications."*

---

### Step 12: Administrator Command Center & User Governance
* **Screen Display:** Switch to Admin window: [pages/admin/dashboard.php](file:///C:/xampp/htdocs/OJAMS/pages/admin/dashboard.php) & [pages/admin/user-management.php](file:///C:/xampp/htdocs/OJAMS/pages/admin/user-management.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Show district-wide analytics and hiring metrics.
  2. Navigate to User Management.
  3. Point out the staff approval control (`is_approved`) and account deactivation toggles.
* **🗣️ Verbal Script:**
  > *"The Administrator portal handles institutional governance. Admins maintain complete authority over user accounts. For security, newly registered staff accounts require explicit administrative approval before they can access internal records."*

---

### Step 13: Reports Generation & Multi-Format Export
* **Screen Display:** [pages/admin/reports.php](file:///C:/xampp/htdocs/OJAMS/pages/admin/reports.php) & [pages/admin/export-report.php](file:///C:/xampp/htdocs/OJAMS/pages/admin/export-report.php)
* **Estimated Time:** 1.0 minute
* **Screen Action:**
  1. Demonstrate the date range and status filters.
  2. Show the dynamic summary tables.
  3. Click the export options (**PDF**, **Excel**, **CSV**).
* **🗣️ Verbal Script:**
  > *"For LGU compliance and Civil Service audits, OJAMS generates comprehensive recruitment and placement reports. These reports can be filtered by date, department, or hiring outcome and exported instantly to PDF, Excel, or CSV formats."*

---

### Step 14: Audit Trail, Conclusion & Transition to Q&A
* **Screen Display:** [pages/admin/dashboard.php](file:///C:/xampp/htdocs/OJAMS/pages/admin/dashboard.php) (Recent Activity Log section)
* **Estimated Time:** 0.5 minute
* **Screen Action:**
  1. Scroll to the Activity Log displaying timestamps, user IDs, actions performed, and IP references stored in `activity_logs`.
  2. Face the panel for closing remarks.
* **🗣️ Verbal Script:**
  > *"Every administrative action, status change, and job update is immutably recorded in the system audit log for total accountability.
  >
  > *In conclusion, OJAMS delivers an efficient, transparent, and legally compliant recruitment solution tailored specifically to the Itawes District.
  >
  > *Thank you very much, honorable panelists. We are now eager to take your questions."*

---

## 🛡️ Capstone Defense Panelist Q&A Cheat Sheet

| Question | Recommended Answer |
| :--- | :--- |
| **Q1: Why didn't you just recommend an off-the-shelf portal like JobStreet or LinkedIn?** | *"Commercial platforms cater to private corporate firms. They lack compliance with Philippine Civil Service and LGU recruitment standards (e.g., CS Form 212 PDS, CSC Eligibility Certificates). Furthermore, OJAMS includes localized SMS notifications for rural district candidates who don't have continuous mobile data."* |
| **Q2: How do you prevent unauthorized users from viewing confidential candidate documents?** | *"All document endpoints verify session authenticity, active status, and role elevation via `checkAuth()`, `checkStaff()`, and `checkAdmin()` in [config/auth.php](file:///C:/xampp/htdocs/OJAMS/config/auth.php). Direct unauthorized access to uploads is restricted."* |
| **Q3: What prevents SQL injection and cross-site scripting (XSS)?** | *"We strictly follow defensive coding: 100% of SQL operations use PDO prepared statements with parameter binding ([config/db.php](file:///C:/xampp/htdocs/OJAMS/config/db.php)), all output rendering is sanitized with `htmlspecialchars()`, and all forms enforce CSRF verification tokens."* |
| **Q4: What happens if an applicant uploads a corrupted or malicious file?** | *"Uploads in [handlers/applications.php](file:///C:/xampp/htdocs/OJAMS/handlers/applications.php) are verified against strict extension whitelists (`pdf`, `doc`, `docx`, `jpg`, `png`), validated for MIME type, capped at 5 MB per file, and stored under uniquely hashed filenames to prevent code execution."* |
| **Q5: Can an applicant apply multiple times for the exact same job?** | *"No. The database enforces application uniqueness per job for an applicant, and [handlers/applications.php](file:///C:/xampp/htdocs/OJAMS/handlers/applications.php) verifies existing submissions before allowing a new application to be processed."* |

---

## 💡 Pro-Tips for Defense Day
1. **Split-Screen Strategy:** Keep the Staff window on one side and the Applicant window on the other to show instant real-time status transitions.
2. **Confidence in Local Context:** Emphasize that the system is built for the **Itawes District** (Municipality of Piat, Tuao, etc.) and aligns with Philippine Civil Service procedures.
3. **Backup Plan:** Have offline PDF copies of the report exports and sample screenshots saved locally in case the projection screen or local network faces temporary hiccups.
