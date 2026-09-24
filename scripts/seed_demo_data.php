<?php
/**
 * OJAMS - Database Seeder for Capstone Demo
 * Generates:
 * - 10 Jobseeker Users (with localized Itawes District profiles & standard password)
 * - 10 Sample Jobs (7 Open, 3 Closed across various types and departments)
 * - Multiple Applications connecting the 10 users to these jobs
 * - 5 Mandatory Civil Service Documents in `resumes` for every application
 * - Application status history and activity logs
 */

require_once __DIR__ . '/../config/db.php';

echo "=== OJAMS Demonstration Data Seeder ===\n";

$pdo->beginTransaction();

try {
    // 1. Password Hash (Bcrypt for 'password123')
    $defaultPasswordHash = password_hash('password123', PASSWORD_BCRYPT);

    // 2. Prepare Sample Document in uploads/resumes
    $uploadDir = __DIR__ . '/../uploads/resumes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $existingPdf = $uploadDir . '11_12_1787590234_7a4a32b8c885.pdf';
    $demoDocName = 'demo_civil_service_doc.pdf';
    if (file_exists($existingPdf)) {
        copy($existingPdf, $uploadDir . $demoDocName);
    } else {
        // Fallback minimal valid PDF content
        $minimalPdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n200\n%%EOF";
        file_put_contents($uploadDir . $demoDocName, $minimalPdf);
    }
    $demoDocSize = filesize($uploadDir . $demoDocName);

    // 3. Define 10 Users
    $usersData = [
        [
            'full_name'      => 'Maria Lourdes Santos',
            'email'          => 'maria.santos@gmail.com',
            'contact_number' => '09171234561',
            'address'        => 'Brgy. Centro 1, Piat, Cagayan',
            'birthdate'      => '1999-04-12', // 27 y/o
        ],
        [
            'full_name'      => 'Juan Carlos Dela Cruz',
            'email'          => 'juan.delacruz@gmail.com',
            'contact_number' => '09171234562',
            'address'        => 'Brgy. Maguilling, Piat, Cagayan',
            'birthdate'      => '1998-08-25', // 28 y/o
        ],
        [
            'full_name'      => 'Carlos Miguel Reyes',
            'email'          => 'carlos.reyes@gmail.com',
            'contact_number' => '09281234563',
            'address'        => 'Brgy. Bagumbayan, Tuao, Cagayan',
            'birthdate'      => '1997-11-03', // 28 y/o
        ],
        [
            'full_name'      => 'Ana Patricia Garcia',
            'email'          => 'ana.garcia@gmail.com',
            'contact_number' => '09391234564',
            'address'        => 'Brgy. Poblacion, Solana, Cagayan',
            'birthdate'      => '2001-02-14', // 25 y/o
        ],
        [
            'full_name'      => 'Pedro Jose Mendoza',
            'email'          => 'pedro.mendoza@gmail.com',
            'contact_number' => '09451234565',
            'address'        => 'Brgy. Baung, Piat, Cagayan',
            'birthdate'      => '1996-07-30', // 30 y/o
        ],
        [
            'full_name'      => 'Elena Marie Torres',
            'email'          => 'elena.torres@gmail.com',
            'contact_number' => '09561234566',
            'address'        => 'Brgy. Minanga, Piat, Cagayan',
            'birthdate'      => '2000-09-18', // 26 y/o
        ],
        [
            'full_name'      => 'Mark Anthony Bautista',
            'email'          => 'mark.bautista@gmail.com',
            'contact_number' => '09181234567',
            'address'        => 'Brgy. Nambaran, Tuao, Cagayan',
            'birthdate'      => '1995-12-05', // 30 y/o
        ],
        [
            'full_name'      => 'Grace Anne Aquino',
            'email'          => 'grace.aquino@gmail.com',
            'contact_number' => '09291234568',
            'address'        => 'Brgy. Apayao, Piat, Cagayan',
            'birthdate'      => '2002-03-21', // 24 y/o
        ],
        [
            'full_name'      => 'Christian Paul Ramos',
            'email'          => 'christian.ramos@gmail.com',
            'contact_number' => '09381234569',
            'address'        => 'Brgy. Centro, Santo Niño, Cagayan',
            'birthdate'      => '1998-05-19', // 28 y/o
        ],
        [
            'full_name'      => 'Jessica Mae Flores',
            'email'          => 'jessica.flores@gmail.com',
            'contact_number' => '09491234570',
            'address'        => 'Brgy. Dungao, Tuao, Cagayan',
            'birthdate'      => '1999-10-10', // 26 y/o
        ],
    ];

    $createdUserIds = [];

    $userStmt = $pdo->prepare("
        INSERT INTO users (role, full_name, email, password_hash, contact_number, address, birthdate, is_active, is_approved, created_at)
        VALUES ('user', ?, ?, ?, ?, ?, ?, 1, 1, '2026-09-01 08:00:00')
        ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), contact_number=VALUES(contact_number), address=VALUES(address), is_active=1, is_approved=1
    ");

    $findUserStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");

    foreach ($usersData as $idx => $u) {
        $userStmt->execute([
            $u['full_name'],
            $u['email'],
            $defaultPasswordHash,
            $u['contact_number'],
            $u['address'],
            $u['birthdate']
        ]);
        $findUserStmt->execute([$u['email']]);
        $uid = (int)$findUserStmt->fetchColumn();
        $createdUserIds[$idx] = [
            'id'             => $uid,
            'full_name'      => $u['full_name'],
            'email'          => $u['email'],
            'contact'        => $u['contact_number'],
            'address'        => $u['address'],
            'birthdate'      => $u['birthdate'],
            'age'            => (int)date_diff(date_create($u['birthdate']), date_create('2026-09-24'))->format('%y'),
        ];
    }
    echo "✔ Inserted/Verified 10 Jobseeker Users (Password: password123)\n";

    // 4. Define 10 Sample Jobs (7 Open, 3 Closed)
    $jobsData = [
        // 1. OPEN - Full-time (LGU Piat)
        [
            'title'          => 'Administrative Aide IV (HR Records Clerk)',
            'company'        => 'Local Government Unit of Piat',
            'description'    => 'Maintains personnel filing systems, manages incoming and outgoing official correspondence, encodes administrative records, and assists the municipal HR department in civil service compliance documentation.',
            'qualification'  => 'Completion of 2 years in college or Bachelor’s degree; Career Service Subprofessional / First Level Eligibility; proficient in Microsoft Word & Excel; good verbal and written communication skills.',
            'date_posted'    => '2026-09-10',
            'location'       => 'Piat Municipal Hall, Piat, Cagayan',
            'job_type'       => 'Full-time',
            'salary_range'   => '₱16,000 – ₱18,500',
            'contact_person' => 'Atty. Roberto Guzman (HRMO V)',
            'contact_phone'  => '0917-555-0101',
            'status'         => 'Open',
            'deadline'       => '2026-11-30',
        ],
        // 2. OPEN - Full-time (Municipal IT)
        [
            'title'          => 'Municipal IT Support Specialist',
            'company'        => 'Management Information Systems - LGU Piat',
            'description'    => 'Administers municipal network infrastructure, provides hardware and software maintenance to municipal departments, oversees local government database backups, and supports local digital transformation initiatives.',
            'qualification'  => 'BS in Information Technology, Computer Science, or related course; Career Service Professional / Second Level Eligibility; hands-on knowledge in LAN/WLAN troubleshooting and basic systems administration.',
            'date_posted'    => '2026-09-12',
            'location'       => 'Piat, Cagayan',
            'job_type'       => 'Full-time',
            'salary_range'   => '₱24,000 – ₱28,000',
            'contact_person' => 'Engr. Daniel Ramirez',
            'contact_phone'  => '0917-555-0102',
            'status'         => 'Open',
            'deadline'       => '2026-12-15',
        ],
        // 3. OPEN - Contract (Treasury Tuao)
        [
            'title'          => 'Revenue Collection Clerk II',
            'company'        => 'Municipal Treasury Office - Tuao',
            'description'    => 'Assists the Municipal Treasurer in issuing official receipts, balancing daily revenue remittances, maintaining tax rolls, and processing real property and business tax assessment collections.',
            'qualification'  => 'Bachelor’s degree in Business Administration, Accounting, or Financial Management; CS Subprofessional or Professional eligibility; strong numerical accuracy and cash handling reliability.',
            'date_posted'    => '2026-09-14',
            'location'       => 'Tuao, Cagayan',
            'job_type'       => 'Contract',
            'salary_range'   => '₱18,500 – ₱21,000',
            'contact_person' => 'Ma. Luisa Carag (Municipal Treasurer)',
            'contact_phone'  => '0928-555-0201',
            'status'         => 'Open',
            'deadline'       => '2026-11-15',
        ],
        // 4. OPEN - Full-time (Agriculture Solana)
        [
            'title'          => 'Agricultural Technologist',
            'company'        => 'Municipal Agriculture Office - Solana',
            'description'    => 'Provides technical assistance and extension services to farming cooperatives in Solana and Itawes river plains; coordinates high-value crop production, seed distribution, and soil testing programs.',
            'qualification'  => 'BS in Agriculture, Agricultural Technology, or Agronomy; RA 1080 (Licensed Agriculturist) or CS Professional; experience in farmers field school facilitation preferred.',
            'date_posted'    => '2026-09-15',
            'location'       => 'Solana, Cagayan',
            'job_type'       => 'Full-time',
            'salary_range'   => '₱22,000 – ₱25,500',
            'contact_person' => 'Vicente Morales (Municipal Agriculturist)',
            'contact_phone'  => '0939-555-0301',
            'status'         => 'Open',
            'deadline'       => '2026-11-20',
        ],
        // 5. OPEN - Full-time (MDRRMO Piat)
        [
            'title'          => 'Disaster Risk Reduction & Rescue Officer',
            'company'        => 'MDRRMO - Piat',
            'description'    => 'Conducts hazard mapping, emergency dispatch coordination, flood monitoring along Chico River, and community disaster preparedness seminars across barangays in Piat.',
            'qualification'  => 'Bachelor’s degree; First Aid / BLS / EMT certification; CS Professional or appropriate civil service eligibility; physically fit with background in emergency response operations.',
            'date_posted'    => '2026-09-16',
            'location'       => 'Piat, Cagayan',
            'job_type'       => 'Full-time',
            'salary_range'   => '₱20,000 – ₱23,500',
            'contact_person' => 'Capt. Noel Balisi',
            'contact_phone'  => '0945-555-0401',
            'status'         => 'Open',
            'deadline'       => '2026-11-25',
        ],
        // 6. OPEN - Full-time (RHU Tuao)
        [
            'title'          => 'Rural Health Midwife II',
            'company'        => 'Rural Health Unit - Tuao',
            'description'    => 'Administers maternal and child health services, prenatal care, expanded program on immunization (EPI), and assists in municipal health programs across remote barangay health stations in Tuao.',
            'qualification'  => 'Graduate of Midwifery; RA 1080 (Registered Midwife); valid PRC License; at least 1 year of clinical or public health experience.',
            'date_posted'    => '2026-09-18',
            'location'       => 'Tuao, Cagayan',
            'job_type'       => 'Full-time',
            'salary_range'   => '₱19,000 – ₱22,000',
            'contact_person' => 'Dr. Carmela Taguba (MHO)',
            'contact_phone'  => '0956-555-0501',
            'status'         => 'Open',
            'deadline'       => '2026-12-05',
        ],
        // 7. OPEN - Contract (Tourism Piat)
        [
            'title'          => 'Community Tourism Development Coordinator',
            'company'        => 'Itawes Tourism & Heritage Council',
            'description'    => 'Coordinates pilgrim and tourist assistance for the Basilica Minore of Our Lady of Piat, prepares promotional itineraries, guides cultural visitor programs, and monitors local tourism statistics.',
            'qualification'  => 'Bachelor’s degree in Tourism, Hospitality, or Mass Communication; Career Service Subprofessional or Professional; pleasant interpersonal demeanor and knowledge of Itawes cultural history.',
            'date_posted'    => '2026-09-20',
            'location'       => 'Piat, Cagayan',
            'job_type'       => 'Contract',
            'salary_range'   => '₱17,500 – ₱20,500',
            'contact_person' => 'Cristina Pimentel',
            'contact_phone'  => '0918-555-0601',
            'status'         => 'Open',
            'deadline'       => '2026-11-10',
        ],
        // 8. CLOSED - Part-time (Civil Registry Piat)
        [
            'title'          => 'Data Encoder / Civil Registry Clerk',
            'company'        => 'Office of the Municipal Civil Registrar - Piat',
            'description'    => 'Encoded historical birth, marriage, and death registry documents into the digital civil registry archive system; verified certificate records and generated statistical reports.',
            'qualification'  => 'College level; minimum typing speed of 45 WPM; high attention to detail and accuracy with historical record transcriptions.',
            'date_posted'    => '2026-07-15',
            'location'       => 'Piat, Cagayan',
            'job_type'       => 'Part-time',
            'salary_range'   => '₱10,000 – ₱12,000',
            'contact_person' => 'Lourdes Macarubbo (MCR)',
            'contact_phone'  => '0929-555-0701',
            'status'         => 'Closed',
            'deadline'       => '2026-08-31',
        ],
        // 9. CLOSED - Full-time (Engineering Solana)
        [
            'title'          => 'Engineering Aide / CAD Draftsman',
            'company'        => 'Municipal Engineering Office - Solana',
            'description'    => 'Prepared detailed structural and architectural CAD plans for barangay road concreting, drainage improvements, and municipal multipurpose hall constructions.',
            'qualification'  => 'BS in Civil Engineering or Architecture; CS Professional / RA 1080; proficient in AutoCAD and drafting standards.',
            'date_posted'    => '2026-07-01',
            'location'       => 'Solana, Cagayan',
            'job_type'       => 'Full-time',
            'salary_range'   => '₱23,000 – ₱26,500',
            'contact_person' => 'Engr. Ferdinand Dayag',
            'contact_phone'  => '0938-555-0801',
            'status'         => 'Closed',
            'deadline'       => '2026-08-15',
        ],
        // 10. CLOSED - Contract (MENRO Tuao)
        [
            'title'          => 'LGU Environmental & Solid Waste Inspector',
            'company'        => 'MENRO - Tuao',
            'description'    => 'Conducted environmental monitoring, sanitary landfill inspections, tree planting compliance verification, and watershed conservation patrols along Tuao and surrounding forests.',
            'qualification'  => 'BS in Environmental Science, Forestry, or related degree; CS Subprofessional or Professional eligibility; capable of field fieldwork.',
            'date_posted'    => '2026-07-20',
            'location'       => 'Tuao, Cagayan',
            'job_type'       => 'Contract',
            'salary_range'   => '₱18,000 – ₱21,000',
            'contact_person' => 'Renato Cusipag (MENR Officer)',
            'contact_phone'  => '0949-555-0901',
            'status'         => 'Closed',
            'deadline'       => '2026-09-01',
        ],
    ];

    $jobStmt = $pdo->prepare("
        INSERT INTO jobs (title, company, description, qualification, date_posted, location, job_type, salary_range, contact_person, contact_phone, status, deadline, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");

    $createdJobIds = [];
    foreach ($jobsData as $idx => $j) {
        $jobStmt->execute([
            $j['title'],
            $j['company'],
            $j['description'],
            $j['qualification'],
            $j['date_posted'],
            $j['location'],
            $j['job_type'],
            $j['salary_range'],
            $j['contact_person'],
            $j['contact_phone'],
            $j['status'],
            $j['deadline'],
        ]);
        $createdJobIds[$idx] = (int)$pdo->lastInsertId();
    }
    echo "✔ Inserted 10 Jobs (7 Open, 3 Closed)\n";

    // 5. Educational and Experience profiles for applicants
    $eduProfiles = [
        [
            'elem' => 'Piat Central School (2011)',
            'jhs'  => 'Piat National High School (2015)',
            'shs'  => 'Piat National High School - HUMSS (2017)',
            'col'  => 'Cagayan State University - Piat Campus, BS Public Administration (2021)',
            'skills' => 'Office Administration, MS Word & Excel, Records Management, Civil Service Documentation',
            'exp'    => 'Administrative Intern at Municipal Hall of Piat (1 year)',
        ],
        [
            'elem' => 'Maguilling Elementary School (2010)',
            'jhs'  => 'Tuao Vocational and Technical School (2014)',
            'shs'  => 'Cagayan National High School - TVL (2016)',
            'col'  => 'Cagayan State University - Carig Campus, BS Information Technology (2020)',
            'skills' => 'Network Troubleshooting, Systems Administration, Hardware Repair, Database Management',
            'exp'    => 'Junior IT Technical Assistant at Itawes CyberHub (2 years)',
        ],
        [
            'elem' => 'Tuao Central School (2009)',
            'jhs'  => 'Tuao National High School (2013)',
            'shs'  => 'Saint Dominic Academy - ABM (2015)',
            'col'  => 'University of Saint Louis Tuguegarao, BS Accountancy (2019)',
            'skills' => 'Financial Accounting, Tax Assessment, Cash Collection, Spreadsheet Auditing',
            'exp'    => 'Billing & Collection Assistant at Tuao Rural Bank (2 years)',
        ],
        [
            'elem' => 'Solana South Central School (2013)',
            'jhs'  => 'Solana National High School (2017)',
            'shs'  => 'Solana Fresh Farms High School - Agri-Fishery Arts (2019)',
            'col'  => 'Cagayan State University - Piat Campus, BS Agriculture (2023)',
            'skills' => 'Soil Fertility Assessment, Seed Germination, Crop Disease Prevention, Farmer Field Schools',
            'exp'    => 'Field Extension Trainee at Solana Rice Research Station (1 year)',
        ],
        [
            'elem' => 'Baung Elementary School (2008)',
            'jhs'  => 'Piat National High School (2012)',
            'shs'  => 'Cagayan Valley High School - STEM (2014)',
            'col'  => 'Medical Colleges of Northern Philippines, BS Emergency Medical Services (2018)',
            'skills' => 'Emergency Medical Response, Water Rescue, BLS/CPR Certified, Hazard Mapping',
            'exp'    => 'Volunteer Responder at Piat Rescue 24/7 (3 years)',
        ],
        [
            'elem' => 'Minanga Elementary School (2012)',
            'jhs'  => 'Santo Niño High School (2016)',
            'shs'  => 'Piat High School - GAS (2018)',
            'col'  => 'Saint Paul University Philippines, BS Midwifery (2022)',
            'skills' => 'Maternal & Child Health Care, Immunization Administration, Vital Signs Monitoring',
            'exp'    => 'Clinical Midwife Trainee at Tuao District Hospital (18 months)',
        ],
        [
            'elem' => 'Nambaran Elementary School (2007)',
            'jhs'  => 'Tuao National High School (2011)',
            'shs'  => 'Saint Dominic Academy (2013)',
            'col'  => 'University of Saint Louis Tuguegarao, BS Tourism Management (2017)',
            'skills' => 'Tourism Operations, Event Coordination, Cultural Heritage Guiding, Customer Relations',
            'exp'    => 'Assistant Tour Officer at Itawes Heritage Center (2 years)',
        ],
        [
            'elem' => 'Apayao Elementary School (2014)',
            'jhs'  => 'Piat National High School (2018)',
            'shs'  => 'Piat National High School - ICT (2020)',
            'col'  => 'Cagayan State University - Piat Campus, BS Office Administration (2024)',
            'skills' => 'High-Speed Typing (55 WPM), Data Verification, Indexing, Record Scanning',
            'exp'    => 'Student Assistant at Piat Registrar Office (1 year)',
        ],
        [
            'elem' => 'Santo Niño Central School (2010)',
            'jhs'  => 'Santo Niño National High School (2014)',
            'shs'  => 'Cagayan National High School - STEM (2016)',
            'col'  => 'Cagayan State University - Carig Campus, BS Civil Engineering (2021)',
            'skills' => 'AutoCAD 2D/3D, Structural Blueprint Reading, Bill of Materials Estimation',
            'exp'    => 'Junior CAD Draftsman at Solana Construction & Supply (2 years)',
        ],
        [
            'elem' => 'Dungao Elementary School (2011)',
            'jhs'  => 'Tuao Vocational High School (2015)',
            'shs'  => 'Tuao National High School - STEM (2017)',
            'col'  => 'Isabela State University, BS Environmental Science (2022)',
            'skills' => 'Environmental Impact Assessment, Solid Waste Audit, Ecological Reforestation',
            'exp'    => 'Community Forest Ranger Assistant at MENRO Tuao (1 year)',
        ],
    ];

    // 6. Define Applications linking users to jobs
    // We will create 12 rich applications across various statuses:
    // Status distribution:
    // - Approved (with Scheduled Interview): 4
    // - Pending: 5
    // - Rejected: 3
    $applicationsData = [
        // App 1: Maria Santos applies to Job 1 (Administrative Aide) -> Approved with scheduled interview
        [
            'user_idx'        => 0,
            'job_idx'         => 0,
            'status'          => 'Approved',
            'interview_date'  => '2026-10-05 09:30:00',
            'interview_notes' => 'Please bring original copies of CS Form 212 and CSC Eligibility Certificate. Report to Conference Hall 2, 2nd Floor, Piat Municipal Hall.',
            'date_applied'    => '2026-09-12',
        ],
        // App 2: Juan Dela Cruz applies to Job 2 (Municipal IT Support) -> Approved with scheduled interview
        [
            'user_idx'        => 1,
            'job_idx'         => 1,
            'status'          => 'Approved',
            'interview_date'  => '2026-10-06 14:00:00',
            'interview_notes' => 'Technical examination followed by panel interview. Bring your own laptop or USB drive for practical network troubleshooting test.',
            'date_applied'    => '2026-09-13',
        ],
        // App 3: Carlos Reyes applies to Job 3 (Revenue Collection Clerk) -> Pending
        [
            'user_idx'        => 2,
            'job_idx'         => 2,
            'status'          => 'Pending',
            'interview_date'  => null,
            'interview_notes' => null,
            'date_applied'    => '2026-09-15',
        ],
        // App 4: Ana Garcia applies to Job 4 (Agricultural Technologist) -> Pending
        [
            'user_idx'        => 3,
            'job_idx'         => 3,
            'status'          => 'Pending',
            'interview_date'  => null,
            'interview_notes' => null,
            'date_applied'    => '2026-09-16',
        ],
        // App 5: Pedro Mendoza applies to Job 5 (Disaster Risk Reduction) -> Approved with scheduled interview
        [
            'user_idx'        => 4,
            'job_idx'         => 4,
            'status'          => 'Approved',
            'interview_date'  => '2026-10-08 10:00:00',
            'interview_notes' => 'Wear comfortable athletic attire for physical agility and basic life support demonstration at MDRRMO Operations Center.',
            'date_applied'    => '2026-09-17',
        ],
        // App 6: Elena Torres applies to Job 6 (Rural Health Midwife) -> Pending
        [
            'user_idx'        => 5,
            'job_idx'         => 5,
            'status'          => 'Pending',
            'interview_date'  => null,
            'interview_notes' => null,
            'date_applied'    => '2026-09-19',
        ],
        // App 7: Mark Bautista applies to Job 7 (Tourism Coordinator) -> Approved with scheduled interview
        [
            'user_idx'        => 6,
            'job_idx'         => 6,
            'status'          => 'Approved',
            'interview_date'  => '2026-10-09 11:00:00',
            'interview_notes' => 'Panel interview and 5-minute mock tourist briefing regarding Basilica Minore de Piat. Venue: Tourism Office, Piat.',
            'date_applied'    => '2026-09-21',
        ],
        // App 8: Grace Aquino applies to Job 8 (Closed - Data Encoder) -> Rejected
        [
            'user_idx'        => 7,
            'job_idx'         => 7,
            'status'          => 'Rejected',
            'interview_date'  => null,
            'interview_notes' => null,
            'date_applied'    => '2026-08-20',
        ],
        // App 9: Christian Ramos applies to Job 9 (Closed - Engineering Aide) -> Approved (Hired/Archived)
        [
            'user_idx'        => 8,
            'job_idx'         => 8,
            'status'          => 'Approved',
            'interview_date'  => '2026-08-28 09:00:00',
            'interview_notes' => 'Selection completed. Applicant officially appointed for Solana Municipal Hall extension drafting.',
            'date_applied'    => '2026-08-10',
        ],
        // App 10: Jessica Flores applies to Job 10 (Closed - Environmental Inspector) -> Rejected
        [
            'user_idx'        => 9,
            'job_idx'         => 9,
            'status'          => 'Rejected',
            'interview_date'  => null,
            'interview_notes' => null,
            'date_applied'    => '2026-08-25',
        ],
        // App 11: Maria Santos (User 0) also applied to Job 7 (Tourism Coordinator) -> Pending
        [
            'user_idx'        => 0,
            'job_idx'         => 6,
            'status'          => 'Pending',
            'interview_date'  => null,
            'interview_notes' => null,
            'date_applied'    => '2026-09-22',
        ],
        // App 12: Juan Dela Cruz (User 1) also applied to Job 4 (Agricultural Technologist) -> Pending
        [
            'user_idx'        => 1,
            'job_idx'         => 3,
            'status'          => 'Pending',
            'interview_date'  => null,
            'interview_notes' => null,
            'date_applied'    => '2026-09-23',
        ],
    ];

    $appStmt = $pdo->prepare("
        INSERT INTO applications
        (user_id, job_id, full_name, email, contact, address, birthdate, age,
         elementary, jhs, shs, college, skills, experience, status, interview_date, interview_notes, date_applied)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $resumeStmt = $pdo->prepare("
        INSERT INTO resumes (application_id, user_id, document_type, original_name, stored_name, file_size, mime_type)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $historyStmt = $pdo->prepare("
        INSERT INTO application_status_history (application_id, from_status, to_status, changed_by, changed_at)
        VALUES (?, ?, ?, 1, NOW())
    ");

    $activityStmt = $pdo->prepare("
        INSERT INTO activity_logs (action, status, performed_by, job_id, application_id, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $documentTypes = [
        'resume'             => 'Official_Resume_CV.pdf',
        'application_letter' => 'Formal_Application_Letter.pdf',
        'pds'                => 'Civil_Service_Form_212_PDS.pdf',
        'csc_eligib'         => 'CSC_Eligibility_Certificate.pdf',
        'tor'                => 'Official_Transcript_of_Records_TOR.pdf',
    ];

    foreach ($applicationsData as $app) {
        $u = $createdUserIds[$app['user_idx']];
        $jId = $createdJobIds[$app['job_idx']];
        $edu = $eduProfiles[$app['user_idx']];

        $appStmt->execute([
            $u['id'],
            $jId,
            $u['full_name'],
            $u['email'],
            $u['contact'],
            $u['address'],
            $u['birthdate'],
            $u['age'],
            $edu['elem'],
            $edu['jhs'],
            $edu['shs'],
            $edu['col'],
            $edu['skills'],
            $edu['exp'],
            $app['status'],
            $app['interview_date'],
            $app['interview_notes'],
            $app['date_applied'],
        ]);
        $appId = (int)$pdo->lastInsertId();

        // Attach all 5 mandatory documents for each application
        foreach ($documentTypes as $docKey => $origFileName) {
            $resumeStmt->execute([
                $appId,
                $u['id'],
                $docKey,
                $origFileName,
                $demoDocName,
                $demoDocSize,
                'application/pdf',
            ]);
        }

        // Add history and activity log
        $fromStatus = ($app['status'] !== 'Pending') ? 'Pending' : null;
        $historyStmt->execute([$appId, $fromStatus, $app['status']]);

        $actionDesc = "Application from {$u['full_name']} for \"{$jobsData[$app['job_idx']]['title']}\" is {$app['status']}";
        $activityStmt->execute([$actionDesc, $app['status'], 1, $jId, $appId]);
    }

    echo "✔ Inserted 12 Applications connecting the 10 users to the jobs with all 5 mandatory Civil Service documents each\n";

    $pdo->commit();
    echo "=========================================================\n";
    echo "SUCCESS: Seeding completed successfully!\n";
    echo "=========================================================\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR during seeding: " . $e->getMessage() . "\n";
    exit(1);
}
