<?php
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$type = $_GET['type'] ?? '';

// ── CSV: Applicants per Job ───────────────────────────────────
if ($type === 'applicants_csv') {
    $rows = $pdo->query("
        SELECT j.title AS job_title, j.company, j.status AS job_status,
               j.date_posted, COUNT(a.id) AS applicants
        FROM jobs j
        LEFT JOIN applications a ON a.job_id = j.id
        GROUP BY j.id
        ORDER BY applicants DESC
    ")->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ojams-applicants-per-job-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['#', 'Job Title', 'Company', 'Status', 'Date Posted', 'Total Applicants']);
    foreach ($rows as $i => $row) {
        fputcsv($out, [
            $i + 1,
            $row['job_title'],
            $row['company'],
            $row['job_status'],
            $row['date_posted'],
            $row['applicants'],
        ]);
    }
    fclose($out);
    exit;
}

// ── CSV: Monthly Application Report ─────────────────────────
if ($type === 'monthly_csv') {
    $rows = $pdo->query("
        SELECT
            DATE_FORMAT(date_applied, '%M %Y') AS month,
            COUNT(*)                           AS total,
            SUM(status = 'Approved')           AS approved,
            SUM(status = 'Rejected')           AS rejected,
            SUM(status = 'Pending')            AS pending
        FROM applications
        GROUP BY DATE_FORMAT(date_applied, '%Y-%m')
        ORDER BY MIN(date_applied) DESC
    ")->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ojams-monthly-report-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['#', 'Month', 'Total Applications', 'Approved', 'Rejected', 'Pending']);
    foreach ($rows as $i => $row) {
        fputcsv($out, [
            $i + 1,
            $row['month'],
            $row['total'],
            $row['approved'],
            $row['rejected'],
            $row['pending'],
        ]);
    }
    fclose($out);
    exit;
}

// ── CSV: Applications List by Status ────────────────────────
if ($type === 'applications_csv') {
    $statusFilter = $_GET['status'] ?? 'All';
    $search       = trim($_GET['search'] ?? '');
    $dateFrom     = $_GET['date_from'] ?? '';
    $dateTo       = $_GET['date_to'] ?? '';

    $where  = [];
    $params = [];

    if ($statusFilter !== 'All' && in_array($statusFilter, ['Approved', 'Rejected', 'Pending'], true)) {
        $where[]  = "a.status = ?";
        $params[] = $statusFilter;
    }
    if ($search !== '') {
        $where[]  = "(a.full_name LIKE ? OR a.email LIKE ? OR a.contact LIKE ? OR j.title LIKE ? OR j.company LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($dateFrom !== '') {
        $where[]  = "a.date_applied >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo !== '') {
        $where[]  = "a.date_applied <= ?";
        $params[] = $dateTo;
    }
    $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

    $stmt = $pdo->prepare("
        SELECT a.*, j.title AS job_title, j.company
        FROM applications a
        JOIN jobs j ON j.id = a.job_id
        {$whereSQL}
        ORDER BY a.date_applied DESC, a.id DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $fileSlug = strtolower($statusFilter === 'All' ? 'all-applications' : "applications-{$statusFilter}");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ojams-' . $fileSlug . '-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        '#',
        'Applicant Name',
        'Email',
        'Contact Number',
        'Address',
        'Age',
        'Job Title',
        'Company',
        'Status',
        'Date Applied',
        'Interview Schedule',
        'Interview Notes',
        'College / Education',
        'Skills',
        'Experience'
    ]);

    foreach ($rows as $i => $row) {
        fputcsv($out, [
            $i + 1,
            $row['full_name'],
            $row['email'],
            $row['contact'],
            $row['address'],
            $row['age'],
            $row['job_title'],
            $row['company'],
            $row['status'],
            $row['date_applied'],
            $row['interview_date'] ?? '',
            $row['interview_notes'] ?? '',
            $row['college'] ?? '',
            $row['skills'] ?? '',
            $row['experience'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ── Print: Applications Status Report (Dedicated) ───────────
if ($type === 'print_applications') {
    $statusFilter = $_GET['status'] ?? 'All';
    $search       = trim($_GET['search'] ?? '');
    $dateFrom     = $_GET['date_from'] ?? '';
    $dateTo       = $_GET['date_to'] ?? '';

    $where  = [];
    $params = [];

    if ($statusFilter !== 'All' && in_array($statusFilter, ['Approved', 'Rejected', 'Pending'], true)) {
        $where[]  = "a.status = ?";
        $params[] = $statusFilter;
    }
    if ($search !== '') {
        $where[]  = "(a.full_name LIKE ? OR a.email LIKE ? OR a.contact LIKE ? OR j.title LIKE ? OR j.company LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($dateFrom !== '') {
        $where[]  = "a.date_applied >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo !== '') {
        $where[]  = "a.date_applied <= ?";
        $params[] = $dateTo;
    }
    $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

    // Overall summary counts
    $stmtSummary = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'Approved') AS approved,
            SUM(status = 'Rejected') AS rejected,
            SUM(status = 'Pending')  AS pending
        FROM applications
    ");
    $appSummary = $stmtSummary->fetch() ?: ['total' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0];

    // Fetch filtered list
    $stmt = $pdo->prepare("
        SELECT a.*, j.title AS job_title, j.company
        FROM applications a
        JOIN jobs j ON j.id = a.job_id
        {$whereSQL}
        ORDER BY a.date_applied DESC, a.id DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $generatedAt = date('F d, Y \a\t h:i A');
    $adminName   = htmlspecialchars($_SESSION['ojams_user']['full_name'] ?? 'Administrator');
    $reportTitle = $statusFilter === 'All' ? 'All Applications Report' : ucfirst(strtolower($statusFilter)) . ' Applications Report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJAMS — <?php echo htmlspecialchars($reportTitle); ?> — <?php echo date('Y-m-d'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11.5px; color: #1e293b; background: #fff; padding: 25px; }
        .header-wrap { border-bottom: 2px solid #4338ca; padding-bottom: 12px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: flex-start; }
        h1 { font-size: 18px; font-weight: 700; color: #4338ca; margin-bottom: 3px; }
        .sub-header { font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; }
        .meta { color: #64748b; font-size: 10.5px; }
        .kpi-container { display: flex; gap: 12px; margin-bottom: 18px; }
        .kpi-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; background: #f8fafc; }
        .kpi-box.kpi-approved { border-left: 4px solid #16a34a; }
        .kpi-box.kpi-pending  { border-left: 4px solid #d97706; }
        .kpi-box.kpi-rejected { border-left: 4px solid #dc2626; }
        .kpi-box.kpi-total    { border-left: 4px solid #4338ca; }
        .kpi-label { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600; }
        .kpi-value { font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
        th { background: #4338ca; color: #fff; padding: 7px 8px; text-align: left; font-size: 10.5px; font-weight: 600; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) td { background: #fbfcfe; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9.5px; font-weight: 600; text-transform: uppercase; }
        .badge-approved { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-pending  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .interview-info { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 4px 6px; font-size: 10px; color: #166534; }
        .signatures { display: flex; justify-content: space-between; margin-top: 35px; padding-top: 20px; }
        .sig-block { width: 220px; text-align: center; }
        .sig-line { border-top: 1px solid #0f172a; margin-top: 40px; padding-top: 4px; font-weight: 600; font-size: 11px; }
        .sig-title { font-size: 10px; color: #64748b; }
        .footer { margin-top: 25px; padding-top: 8px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 9.5px; display: flex; justify-content: space-between; }
        @media print {
            body { padding: 15px; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <!-- Print Toolbar -->
    <div class="no-print" style="margin-bottom:18px; display:flex; gap:10px;">
        <button onclick="window.print()" style="background:#4338ca;color:#fff;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">
            🖨 Print / Save as PDF
        </button>
        <button onclick="window.close()" style="background:#f1f5f9;color:#475569;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:12px;">
            ✕ Close
        </button>
    </div>

    <!-- Document Header -->
    <div class="header-wrap">
        <div>
            <h1>Online Job Application and Monitoring System</h1>
            <div class="sub-header"><?php echo htmlspecialchars($reportTitle); ?> (Itawes District)</div>
            <div class="meta">
                Status Filter: <strong><?php echo htmlspecialchars($statusFilter); ?></strong>
                <?php if ($dateFrom || $dateTo): ?>
                    &nbsp;|&nbsp; Date Range: <?php echo htmlspecialchars($dateFrom ?: 'Beginning'); ?> to <?php echo htmlspecialchars($dateTo ?: 'Present'); ?>
                <?php endif; ?>
                <?php if ($search): ?>
                    &nbsp;|&nbsp; Search: "<?php echo htmlspecialchars($search); ?>"
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div class="meta"><strong>Date Generated:</strong> <?php echo $generatedAt; ?></div>
            <div class="meta"><strong>Generated By:</strong> <?php echo $adminName; ?></div>
            <div class="meta"><strong>Total Records:</strong> <?php echo count($rows); ?></div>
        </div>
    </div>

    <!-- Summary KPI metrics -->
    <div class="kpi-container">
        <div class="kpi-box kpi-total">
            <div class="kpi-label">Total Applications</div>
            <div class="kpi-value"><?php echo number_format((int)$appSummary['total']); ?></div>
        </div>
        <div class="kpi-box kpi-approved">
            <div class="kpi-label">Approved</div>
            <div class="kpi-value"><?php echo number_format((int)$appSummary['approved']); ?></div>
        </div>
        <div class="kpi-box kpi-pending">
            <div class="kpi-label">Pending</div>
            <div class="kpi-value"><?php echo number_format((int)$appSummary['pending']); ?></div>
        </div>
        <div class="kpi-box kpi-rejected">
            <div class="kpi-label">Rejected</div>
            <div class="kpi-value"><?php echo number_format((int)$appSummary['rejected']); ?></div>
        </div>
    </div>

    <!-- Applications Table -->
    <table>
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:20%;">Applicant Name &amp; Contact</th>
                <th style="width:24%;">Job Title &amp; Company</th>
                <th style="width:11%;">Date Applied</th>
                <th style="width:11%;">Status</th>
                <th style="width:30%;">Interview Schedule / Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#94a3b8;padding:24px;">
                        No applications found matching the selected filter criteria.
                    </td>
                </tr>
            <?php else: foreach ($rows as $i => $r):
                $badgeCls = match($r['status']) {
                    'Approved' => 'badge-approved',
                    'Rejected' => 'badge-rejected',
                    'Pending'  => 'badge-pending',
                    default    => ''
                };
            ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td>
                        <strong style="color:#0f172a;"><?php echo htmlspecialchars($r['full_name']); ?></strong>
                        <div style="color:#64748b; font-size:10px;">
                            <?php echo htmlspecialchars($r['email']); ?>
                            <?php if (!empty($r['contact'])): ?>
                                &bull; <?php echo htmlspecialchars($r['contact']); ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <strong style="color:#4338ca;"><?php echo htmlspecialchars($r['job_title']); ?></strong>
                        <div style="color:#64748b; font-size:10px;"><?php echo htmlspecialchars($r['company']); ?></div>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($r['date_applied'])); ?></td>
                    <td><span class="badge <?php echo $badgeCls; ?>"><?php echo $r['status']; ?></span></td>
                    <td>
                        <?php if ($r['status'] === 'Approved' && !empty($r['interview_date'])): ?>
                            <div class="interview-info">
                                <strong>📅 <?php echo date('M d, Y h:i A', strtotime($r['interview_date'])); ?></strong>
                                <?php if (!empty($r['interview_notes'])): ?>
                                    <div style="font-size:9.5px; color:#334155; margin-top:2px;">
                                        <em><?php echo htmlspecialchars($r['interview_notes']); ?></em>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($r['status'] === 'Approved'): ?>
                            <span style="color:#64748b;font-style:italic;">Schedule not yet assigned</span>
                        <?php elseif ($r['status'] === 'Rejected'): ?>
                            <span style="color:#94a3b8;">Application Closed</span>
                        <?php else: ?>
                            <span style="color:#b45309;font-style:italic;">Pending Review</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Signature block for official validation -->
    <div class="signatures">
        <div class="sig-block">
            <div class="sig-line"><?php echo $adminName; ?></div>
            <div class="sig-title">System Administrator / Prepared By</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">PESO Officer / Department Head</div>
            <div class="sig-title">Noted &amp; Verified By</div>
        </div>
    </div>

    <div class="footer">
        <div>OJAMS &mdash; Online Job Application and Monitoring System &mdash; Itawes District</div>
        <div>Page 1 of 1 &nbsp;|&nbsp; Generated on <?php echo $generatedAt; ?></div>
    </div>

    <script>
        setTimeout(() => window.print(), 400);
    </script>
</body>
</html>
<?php
    exit;
}

// ── Print: Comprehensive Full System Report ──────────────────
if ($type === 'print') {
    // 1. Applicants per job
    $perJob = $pdo->query("
        SELECT j.title AS job_title, j.company, j.status AS job_status,
               j.date_posted, COUNT(a.id) AS applicants
        FROM jobs j
        LEFT JOIN applications a ON a.job_id = j.id
        GROUP BY j.id
        ORDER BY applicants DESC
        LIMIT 20
    ")->fetchAll();

    // 2. Monthly trend
    $monthly = $pdo->query("
        SELECT
            DATE_FORMAT(date_applied, '%M %Y') AS month,
            COUNT(*)                           AS total,
            SUM(status = 'Approved')           AS approved,
            SUM(status = 'Rejected')           AS rejected,
            SUM(status = 'Pending')            AS pending
        FROM applications
        GROUP BY DATE_FORMAT(date_applied, '%Y-%m')
        ORDER BY MIN(date_applied) DESC
        LIMIT 12
    ")->fetchAll();

    // 3. Applications list breakdown
    $applicationsList = $pdo->query("
        SELECT a.*, j.title AS job_title, j.company
        FROM applications a
        JOIN jobs j ON j.id = a.job_id
        ORDER BY a.date_applied DESC, a.id DESC
        LIMIT 50
    ")->fetchAll();

    // Summary stats
    $stmtSummary = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(status = 'Approved') AS approved,
            SUM(status = 'Rejected') AS rejected,
            SUM(status = 'Pending')  AS pending
        FROM applications
    ");
    $appSummary = $stmtSummary->fetch() ?: ['total' => 0, 'approved' => 0, 'rejected' => 0, 'pending' => 0];

    $generatedAt = date('F d, Y \a\t h:i A');
    $adminName   = htmlspecialchars($_SESSION['ojams_user']['full_name'] ?? 'Administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJAMS — Comprehensive System Report — <?php echo date('Y-m-d'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11.5px; color: #1e293b; background: #fff; padding: 25px; }
        h1 { font-size: 19px; font-weight: 700; color: #4338ca; margin-bottom: 3px; }
        .meta { color: #64748b; font-size: 10.5px; margin-bottom: 18px; }
        h2 { font-size: 13.5px; font-weight: 700; border-bottom: 2px solid #4338ca; padding-bottom: 5px; margin: 22px 0 10px; color: #1e293b; }
        .kpi-container { display: flex; gap: 12px; margin-bottom: 16px; }
        .kpi-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; background: #f8fafc; }
        .kpi-box.kpi-approved { border-left: 4px solid #16a34a; }
        .kpi-box.kpi-pending  { border-left: 4px solid #d97706; }
        .kpi-box.kpi-rejected { border-left: 4px solid #dc2626; }
        .kpi-box.kpi-total    { border-left: 4px solid #4338ca; }
        .kpi-label { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600; }
        .kpi-value { font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11px; }
        th { background: #4338ca; color: #fff; padding: 6px 8px; text-align: left; font-size: 10.5px; font-weight: 600; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) td { background: #fbfcfe; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9.5px; font-weight: 600; text-transform: uppercase; }
        .badge-open    { background: #dcfce7; color: #166534; }
        .badge-closed  { background: #f1f5f9; color: #475569; }
        .badge-approved{ background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-rejected{ background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .bar-wrap { background: #e2e8f0; border-radius: 4px; height: 10px; }
        .bar-fill  { background: #4338ca; border-radius: 4px; height: 10px; display: inline-block; }
        .interview-info { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 3px 5px; font-size: 9.5px; color: #166534; }
        .signatures { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 15px; }
        .sig-block { width: 220px; text-align: center; }
        .sig-line { border-top: 1px solid #0f172a; margin-top: 35px; padding-top: 4px; font-weight: 600; font-size: 11px; }
        .sig-title { font-size: 10px; color: #64748b; }
        .footer { margin-top: 25px; padding-top: 8px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 9.5px; }
        @media print {
            body { padding: 15px; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    <!-- Print toolbar -->
    <div class="no-print" style="margin-bottom:18px; display:flex; gap:10px;">
        <button onclick="window.print()" style="background:#4338ca;color:#fff;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">
            🖨 Print / Save as PDF
        </button>
        <button onclick="window.close()" style="background:#f1f5f9;color:#475569;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:12px;">
            ✕ Close
        </button>
    </div>

    <h1>OJAMS — Comprehensive System Report</h1>
    <p class="meta">Generated: <?php echo $generatedAt; ?> &nbsp;|&nbsp; Prepared By: <?php echo $adminName; ?></p>

    <!-- Overall KPI Cards -->
    <div class="kpi-container">
        <div class="kpi-box kpi-total">
            <div class="kpi-label">Total Applications</div>
            <div class="kpi-value"><?php echo number_format((int)$appSummary['total']); ?></div>
        </div>
        <div class="kpi-box kpi-approved">
            <div class="kpi-label">Approved</div>
            <div class="kpi-value"><?php echo number_format((int)$appSummary['approved']); ?></div>
        </div>
        <div class="kpi-box kpi-pending">
            <div class="kpi-label">Pending</div>
            <div class="kpi-value"><?php echo number_format((int)$appSummary['pending']); ?></div>
        </div>
        <div class="kpi-box kpi-rejected">
            <div class="kpi-label">Rejected</div>
            <div class="kpi-value"><?php echo number_format((int)$appSummary['rejected']); ?></div>
        </div>
    </div>

    <!-- Section 1: Applicants per Job -->
    <h2>1. Total Applicants per Job Post</h2>
    <table>
        <thead>
            <tr><th>#</th><th>Job Title</th><th>Company</th><th>Status</th><th>Posted</th><th>Applicants</th><th style="width:22%;">Ratio</th></tr>
        </thead>
        <tbody>
            <?php if (empty($perJob)): ?>
                <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:16px;">No job postings found.</td></tr>
            <?php else:
                $maxA = max(array_column($perJob, 'applicants')) ?: 1;
                foreach ($perJob as $i => $r):
                    $pct = round(($r['applicants'] / $maxA) * 100);
            ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><strong><?php echo htmlspecialchars($r['job_title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($r['company']); ?></td>
                    <td><span class="badge badge-<?php echo strtolower($r['job_status']); ?>"><?php echo $r['job_status']; ?></span></td>
                    <td><?php echo $r['date_posted']; ?></td>
                    <td><strong><?php echo $r['applicants']; ?></strong></td>
                    <td><div class="bar-wrap"><div class="bar-fill" style="width:<?php echo $pct; ?>%;"></div></div></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Section 2: Monthly Report -->
    <h2>2. Monthly Application Trends (Last 12 Months)</h2>
    <table>
        <thead>
            <tr><th>#</th><th>Month</th><th>Total</th><th>Approved</th><th>Rejected</th><th>Pending</th></tr>
        </thead>
        <tbody>
            <?php if (empty($monthly)): ?>
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:16px;">No applications submitted yet.</td></tr>
            <?php else: foreach ($monthly as $i => $r): ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td><strong><?php echo htmlspecialchars($r['month']); ?></strong></td>
                    <td><span class="badge" style="background:#e0e7ff;color:#3730a3;"><?php echo $r['total']; ?></span></td>
                    <td><span class="badge badge-approved"><?php echo $r['approved']; ?></span></td>
                    <td><span class="badge badge-rejected"><?php echo $r['rejected']; ?></span></td>
                    <td><span class="badge badge-pending"><?php echo $r['pending']; ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Section 3: Applications List (Approved, Rejected, Pending) -->
    <h2 class="page-break">3. Detailed Applications List (Approved / Rejected / Pending)</h2>
    <table>
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:20%;">Applicant &amp; Contact</th>
                <th style="width:24%;">Job Applied</th>
                <th style="width:11%;">Date Applied</th>
                <th style="width:11%;">Status</th>
                <th style="width:30%;">Interview Schedule / Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($applicationsList)): ?>
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:16px;">No applications recorded.</td></tr>
            <?php else: foreach ($applicationsList as $i => $app):
                $badgeCls = match($app['status']) {
                    'Approved' => 'badge-approved',
                    'Rejected' => 'badge-rejected',
                    'Pending'  => 'badge-pending',
                    default    => ''
                };
            ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td>
                        <strong style="color:#0f172a;"><?php echo htmlspecialchars($app['full_name']); ?></strong>
                        <div style="color:#64748b; font-size:10px;">
                            <?php echo htmlspecialchars($app['email']); ?>
                            <?php if (!empty($app['contact'])): ?> &bull; <?php echo htmlspecialchars($app['contact']); ?><?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <strong style="color:#4338ca;"><?php echo htmlspecialchars($app['job_title']); ?></strong>
                        <div style="color:#64748b; font-size:10px;"><?php echo htmlspecialchars($app['company']); ?></div>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($app['date_applied'])); ?></td>
                    <td><span class="badge <?php echo $badgeCls; ?>"><?php echo $app['status']; ?></span></td>
                    <td>
                        <?php if ($app['status'] === 'Approved' && !empty($app['interview_date'])): ?>
                            <div class="interview-info">
                                <strong>📅 <?php echo date('M d, Y h:i A', strtotime($app['interview_date'])); ?></strong>
                                <?php if (!empty($app['interview_notes'])): ?>
                                    <div style="font-size:9.5px; color:#334155; margin-top:2px;">
                                        <em><?php echo htmlspecialchars($app['interview_notes']); ?></em>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($app['status'] === 'Approved'): ?>
                            <span style="color:#64748b;font-style:italic;">Schedule not set</span>
                        <?php elseif ($app['status'] === 'Rejected'): ?>
                            <span style="color:#94a3b8;">Application Closed</span>
                        <?php else: ?>
                            <span style="color:#b45309;font-style:italic;">Pending Review</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Signature block for official validation -->
    <div class="signatures">
        <div class="sig-block">
            <div class="sig-line"><?php echo $adminName; ?></div>
            <div class="sig-title">System Administrator / Prepared By</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">PESO Officer / Department Head</div>
            <div class="sig-title">Noted &amp; Verified By</div>
        </div>
    </div>

    <div class="footer">
        OJAMS &mdash; Online Job Application and Monitoring System &mdash; <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
        &nbsp;|&nbsp; Printed: <?php echo $generatedAt; ?>
    </div>

    <script>
        setTimeout(() => window.print(), 400);
    </script>
</body>
</html>
<?php
    exit;
}

// Unknown type — redirect back
header('Location: ' . BASE_URL . '/pages/admin/reports.php');
exit;

