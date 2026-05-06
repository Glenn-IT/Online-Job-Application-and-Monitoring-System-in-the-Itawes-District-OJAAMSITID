<?php


// ── Job Listings ──────────────────────────────
$jobs = [
    [
        "id"            => 1,
        "title"         => "Web Developer",
        "company"       => "ABC Technologies",
        "description"   => "Develop and maintain company websites using modern frameworks and technologies.",
        "qualification" => "HTML, CSS, JavaScript, PHP",
        "date_posted"   => "2026-03-20",
        "status"        => "Open",
        "applicants"    => 2
    ],
    [
        "id"            => 2,
        "title"         => "IT Support Specialist",
        "company"       => "TechFix Solutions",
        "description"   => "Provide technical support to end-users and troubleshoot hardware/software issues.",
        "qualification" => "Basic networking and troubleshooting",
        "date_posted"   => "2026-03-18",
        "status"        => "Open",
        "applicants"    => 1
    ],
    [
        "id"            => 3,
        "title"         => "Data Analyst",
        "company"       => "DataWorks Inc.",
        "description"   => "Analyze datasets and generate actionable insights for business stakeholders.",
        "qualification" => "Excel, SQL, Python, Statistics",
        "date_posted"   => "2026-03-15",
        "status"        => "Closed",
        "applicants"    => 4
    ],
    [
        "id"            => 4,
        "title"         => "Graphic Designer",
        "company"       => "Creative Minds Studio",
        "description"   => "Design marketing materials, social media assets, and brand collateral.",
        "qualification" => "Adobe Photoshop, Illustrator, Figma",
        "date_posted"   => "2026-03-22",
        "status"        => "Open",
        "applicants"    => 3
    ],
    [
        "id"            => 5,
        "title"         => "Network Administrator",
        "company"       => "ConnectPH Corp.",
        "description"   => "Manage and monitor corporate network infrastructure and security.",
        "qualification" => "CCNA, Network Security, Linux",
        "date_posted"   => "2026-03-10",
        "status"        => "Open",
        "applicants"    => 5
    ],
    [
        "id"            => 6,
        "title"         => "Mobile App Developer",
        "company"       => "AppForge PH",
        "description"   => "Build and maintain cross-platform mobile applications for clients.",
        "qualification" => "Flutter, React Native, Dart",
        "date_posted"   => "2026-03-24",
        "status"        => "Open",
        "applicants"    => 0
    ]
];

// ── Applications ──────────────────────────────
$applications = [
    [
        "id"           => 1,
        "name"         => "Juan Dela Cruz",
        "email"        => "juan@email.com",
        "job_title"    => "Web Developer",
        "company"      => "ABC Technologies",
        "date_applied" => "2026-03-22",
        "status"       => "Pending"
    ],
    [
        "id"           => 2,
        "name"         => "Maria Santos",
        "email"        => "maria@email.com",
        "job_title"    => "IT Support Specialist",
        "company"      => "TechFix Solutions",
        "date_applied" => "2026-03-21",
        "status"       => "Approved"
    ],
    [
        "id"           => 3,
        "name"         => "Carlos Reyes",
        "email"        => "carlos@email.com",
        "job_title"    => "Data Analyst",
        "company"      => "DataWorks Inc.",
        "date_applied" => "2026-03-20",
        "status"       => "Rejected"
    ],
    [
        "id"           => 4,
        "name"         => "Ana Garcia",
        "email"        => "ana@email.com",
        "job_title"    => "Graphic Designer",
        "company"      => "Creative Minds Studio",
        "date_applied" => "2026-03-23",
        "status"       => "Pending"
    ],
    [
        "id"           => 5,
        "name"         => "Pedro Mendoza",
        "email"        => "pedro@email.com",
        "job_title"    => "Web Developer",
        "company"      => "ABC Technologies",
        "date_applied" => "2026-03-24",
        "status"       => "Pending"
    ]
];

// ── Dashboard Statistics ──────────────────────
$stats = [
    "total_jobs"            => count($jobs),
    "total_applicants"      => count($applications),
    "pending_applications"  => count(array_filter($applications, fn($a) => $a['status'] === 'Pending')),
    "approved_applications" => count(array_filter($applications, fn($a) => $a['status'] === 'Approved')),
    "rejected_applications" => count(array_filter($applications, fn($a) => $a['status'] === 'Rejected'))
];

// ── Activity History (Admin Dashboard) ────────
$activity_history = [
    [
        "date"   => "2026-03-24",
        "time"   => "02:15 PM",
        "action" => "New application received from Pedro Mendoza",
        "status" => "New"
    ],
    [
        "date"   => "2026-03-23",
        "time"   => "10:30 AM",
        "action" => "New application received from Ana Garcia",
        "status" => "New"
    ],
    [
        "date"   => "2026-03-22",
        "time"   => "09:00 AM",
        "action" => "Job post 'Mobile App Developer' created",
        "status" => "Created"
    ],
    [
        "date"   => "2026-03-21",
        "time"   => "03:45 PM",
        "action" => "Application of Maria Santos approved",
        "status" => "Approved"
    ],
    [
        "date"   => "2026-03-20",
        "time"   => "11:20 AM",
        "action" => "Application of Carlos Reyes rejected",
        "status" => "Rejected"
    ]
];

// ── User Profile (Sample logged-in user) ──────
$user_profile = [
    "full_name"      => "Juan Dela Cruz",
    "email"          => "juan.delacruz@email.com",
    "contact_number" => "09171234567",
    "password"       => "••••••••••",
    "address"        => "123 Main St, Quezon City, Metro Manila",
    "birthdate"      => "1998-05-15",
    "age"            => 27
];

// ── Applicants per Job (Reports) ──────────────
$applicants_per_job = [
    ["job_title" => "Web Developer",         "applicants" => 2],
    ["job_title" => "IT Support Specialist",  "applicants" => 1],
    ["job_title" => "Data Analyst",           "applicants" => 1],
    ["job_title" => "Graphic Designer",       "applicants" => 1],
    ["job_title" => "Network Administrator",  "applicants" => 0],
    ["job_title" => "Mobile App Developer",   "applicants" => 0]
];

// ── Monthly Application Report (Reports) ─────
$monthly_report = [
    ["month" => "January 2026",  "applications" => 3],
    ["month" => "February 2026", "applications" => 7],
    ["month" => "March 2026",    "applications" => 5]
];
