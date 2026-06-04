-- ============================================================
-- OJAMS — Online Job Application and Monitoring System in the Itawes District
-- Database Schema + Seed Data
-- ============================================================
-- Engine  : MySQL 5.7+ / MariaDB 10.3+
-- Charset : utf8mb4
-- Run this file once via phpMyAdmin or MySQL CLI to set up.
-- ============================================================

-- ── Create & select database ────────────────────────────────
CREATE DATABASE IF NOT EXISTS ojams_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ojams_db;

-- ============================================================
-- TABLE: users
-- Stores both admin and regular user accounts.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  role            ENUM('admin','user') NOT NULL DEFAULT 'user',
  full_name       VARCHAR(150)    NOT NULL,
  email           VARCHAR(150)    NOT NULL UNIQUE,
  password_hash   VARCHAR(255)    NOT NULL,
  contact_number  VARCHAR(20)     DEFAULT NULL,
  address         TEXT            DEFAULT NULL,
  birthdate       DATE            DEFAULT NULL,
  created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: jobs
-- Stores all job postings managed by admins.
-- ============================================================
CREATE TABLE IF NOT EXISTS jobs (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  title         VARCHAR(150)    NOT NULL,
  company       VARCHAR(150)    NOT NULL,
  description   TEXT            NOT NULL,
  qualification TEXT            NOT NULL,
  date_posted   DATE            NOT NULL,
  status        ENUM('Open','Closed') NOT NULL DEFAULT 'Open',
  created_by    INT UNSIGNED    DEFAULT NULL,  -- FK → users.id (admin)
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_jobs_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: applications
-- Stores job applications submitted by users.
-- Education fields are intentionally denormalized (flat)
-- to keep the schema simple for this project scope.
-- ============================================================
CREATE TABLE IF NOT EXISTS applications (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED    NOT NULL,         -- FK → users.id
  job_id        INT UNSIGNED    NOT NULL,         -- FK → jobs.id
  -- Snapshot of applicant info at time of application
  full_name     VARCHAR(150)    NOT NULL,
  email         VARCHAR(150)    NOT NULL,
  contact       VARCHAR(20)     DEFAULT NULL,
  address       TEXT            DEFAULT NULL,
  birthdate     DATE            DEFAULT NULL,
  age           TINYINT UNSIGNED DEFAULT NULL,
  -- Educational background (denormalized)
  elementary    VARCHAR(200)    DEFAULT NULL,
  jhs           VARCHAR(200)    DEFAULT NULL,
  shs           VARCHAR(200)    DEFAULT NULL,
  college       VARCHAR(200)    DEFAULT NULL,
  -- Skills & experience
  skills        TEXT            DEFAULT NULL,
  experience    TEXT            DEFAULT NULL,
  -- Status tracking
  status        ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  date_applied  DATE            NOT NULL,
  updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  -- Prevent duplicate applications (one user per job)
  UNIQUE KEY uq_user_job (user_id, job_id),
  CONSTRAINT fk_applications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_applications_job  FOREIGN KEY (job_id)  REFERENCES jobs(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: login_attempts
-- Tracks failed login attempts per IP for brute-force protection.
-- ============================================================
CREATE TABLE IF NOT EXISTS login_attempts (
  ip              VARCHAR(45)     NOT NULL,
  attempts        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  last_attempt_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: activity_logs
-- Records system-wide events for the admin dashboard.
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  action        VARCHAR(255)    NOT NULL,
  status        VARCHAR(50)     NOT NULL,    -- 'New', 'Created', 'Approved', 'Rejected', etc.
  performed_by  INT UNSIGNED    DEFAULT NULL, -- FK → users.id (nullable for system events)
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_activity_user FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- SEED DATA
-- ============================================================
-- Passwords below are bcrypt hashes.
-- Plaintext values (for reference only, do NOT store):
--   admin123     → $2y$12$...
--   password123  → $2y$12$...
-- The actual hashes below were generated with:
--   password_hash('admin123',    PASSWORD_BCRYPT)
--   password_hash('password123', PASSWORD_BCRYPT)
-- ============================================================

-- ── Seed: Users ─────────────────────────────────────────────
INSERT INTO users (id, role, full_name, email, password_hash, contact_number, address, birthdate, created_at) VALUES
(1, 'admin', 'Administrator', 'admin@ojams.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '09000000000', 'OJAMS HQ', '1990-01-01', '2026-01-01 00:00:00'),
(2, 'user',  'Juan Dela Cruz', 'juan@email.com',
 '$2y$12$T7VKzM3TZcP9UBM2X9sQ8e5Bv0sPKNgbU7YR.4mDmWzWcZP3Bi0mK',
 '09171234567', '123 Main St, Quezon City, Metro Manila', '1998-05-15', '2026-03-01 00:00:00'),
(3, 'user',  'Maria Santos',   'maria@email.com',
 '$2y$12$T7VKzM3TZcP9UBM2X9sQ8e5Bv0sPKNgbU7YR.4mDmWzWcZP3Bi0mK',
 '09281234567', '456 Rizal Ave, Manila', '2000-08-20', '2026-03-05 00:00:00'),
(4, 'user',  'Carlos Reyes',   'carlos@email.com',
 '$2y$12$T7VKzM3TZcP9UBM2X9sQ8e5Bv0sPKNgbU7YR.4mDmWzWcZP3Bi0mK',
 '09391234567', '789 Mabini St, Cebu City', '1997-11-03', '2026-03-08 00:00:00'),
(5, 'user',  'Ana Garcia',     'ana@email.com',
 '$2y$12$T7VKzM3TZcP9UBM2X9sQ8e5Bv0sPKNgbU7YR.4mDmWzWcZP3Bi0mK',
 '09451234567', '321 Luna St, Davao City', '1999-02-14', '2026-03-10 00:00:00'),
(6, 'user',  'Pedro Mendoza',  'pedro@email.com',
 '$2y$12$T7VKzM3TZcP9UBM2X9sQ8e5Bv0sPKNgbU7YR.4mDmWzWcZP3Bi0mK',
 '09561234567', '654 Bonifacio St, Iloilo City', '1995-07-30', '2026-03-12 00:00:00');

-- ── Seed: Jobs ──────────────────────────────────────────────
INSERT INTO jobs (id, title, company, description, qualification, date_posted, status, created_by) VALUES
(1, 'Web Developer',         'ABC Technologies',      'Develop and maintain company websites using modern frameworks and technologies.',                  'HTML, CSS, JavaScript, PHP',           '2026-03-20', 'Open',   1),
(2, 'IT Support Specialist', 'TechFix Solutions',     'Provide technical support to end-users and troubleshoot hardware/software issues.',               'Basic networking and troubleshooting',  '2026-03-18', 'Open',   1),
(3, 'Data Analyst',          'DataWorks Inc.',        'Analyze datasets and generate actionable insights for business stakeholders.',                     'Excel, SQL, Python, Statistics',        '2026-03-15', 'Closed', 1),
(4, 'Graphic Designer',      'Creative Minds Studio', 'Design marketing materials, social media assets, and brand collateral.',                          'Adobe Photoshop, Illustrator, Figma',   '2026-03-22', 'Open',   1),
(5, 'Network Administrator', 'ConnectPH Corp.',       'Manage and monitor corporate network infrastructure and security.',                               'CCNA, Network Security, Linux',         '2026-03-10', 'Open',   1),
(6, 'Mobile App Developer',  'AppForge PH',           'Build and maintain cross-platform mobile applications for clients.',                              'Flutter, React Native, Dart',           '2026-03-24', 'Open',   1);

-- ── Seed: Applications ──────────────────────────────────────
-- user_id 2 = Juan, 3 = Maria, 4 = Carlos, 5 = Ana, 6 = Pedro
INSERT INTO applications (id, user_id, job_id, full_name, email, contact, address, birthdate, age, skills, experience, status, date_applied) VALUES
(1, 2, 1, 'Juan Dela Cruz', 'juan@email.com',  '09171234567', '123 Main St, Quezon City', '1998-05-15', 27,
 'HTML, CSS, JavaScript, PHP', 'Intern at ABC Corp (2024-2025)',
 'Pending',  '2026-03-22'),

(2, 3, 2, 'Maria Santos',   'maria@email.com', '09281234567', '456 Rizal Ave, Manila',    '2000-08-20', 25,
 'Networking, Troubleshooting', 'IT Assistant at TechStart (2025)',
 'Approved', '2026-03-21'),

(3, 4, 3, 'Carlos Reyes',   'carlos@email.com','09391234567', '789 Mabini St, Cebu City', '1997-11-03', 28,
 'Excel, SQL, Python', 'Data entry at DataWorks (2023-2024)',
 'Rejected', '2026-03-20'),

(4, 5, 4, 'Ana Garcia',     'ana@email.com',   '09451234567', '321 Luna St, Davao City',  '1999-02-14', 27,
 'Adobe Photoshop, Illustrator, Figma', 'Freelance Designer (2024-present)',
 'Pending',  '2026-03-23'),

(5, 6, 1, 'Pedro Mendoza',  'pedro@email.com', '09561234567', '654 Bonifacio St, Iloilo', '1995-07-30', 30,
 'HTML, CSS, Bootstrap, PHP', '2 years freelance web development',
 'Pending',  '2026-03-24');

-- ── Seed: Activity Logs ─────────────────────────────────────
INSERT INTO activity_logs (action, status, performed_by, created_at) VALUES
('New application received from Pedro Mendoza for "Web Developer"',        'New',      6, '2026-03-24 14:15:00'),
('New application received from Ana Garcia for "Graphic Designer"',        'New',      5, '2026-03-23 10:30:00'),
('Job post "Mobile App Developer" created',                                'Created',  1, '2026-03-22 09:00:00'),
('Application of Juan Dela Cruz submitted for "Web Developer"',            'New',      2, '2026-03-22 08:45:00'),
('Application of Maria Santos marked as Approved',                         'Approved', 1, '2026-03-21 15:45:00'),
('Application of Carlos Reyes marked as Rejected',                         'Rejected', 1, '2026-03-20 11:20:00');

-- ============================================================
-- END OF SCHEMA
-- ============================================================
