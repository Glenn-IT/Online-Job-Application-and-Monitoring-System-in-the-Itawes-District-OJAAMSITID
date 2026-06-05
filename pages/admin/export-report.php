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

// ── Print / PDF view ──────────────────────────────────────────
if ($type === 'print') {
    // Fetch both datasets
    $perJob = $pdo->query("
        SELECT j.title AS job_title, j.company, j.status AS job_status,
               j.date_posted, COUNT(a.id) AS applicants
        FROM jobs j
        LEFT JOIN applications a ON a.job_id = j.id
        GROUP BY j.id
        ORDER BY applicants DESC
        LIMIT 20
    ")->fetchAll();

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

    $generatedAt = date('F d, Y \a\t h:i A');
    $adminName   = htmlspecialchars($_SESSION['ojams_user']['full_name'] ?? 'Administrator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJAMS Report — <?php echo date('Y-m-d'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1e293b; background: #fff; padding: 30px; }
        h1 { font-size: 20px; font-weight: 700; color: #4338ca; margin-bottom: 4px; }
        .meta { color: #64748b; font-size: 11px; margin-bottom: 24px; }
        h2 { font-size: 14px; font-weight: 700; border-bottom: 2px solid #4338ca; padding-bottom: 6px; margin: 24px 0 12px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #4338ca; color: #fff; padding: 7px 10px; text-align: left; font-size: 11px; font-weight: 600; }
        td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        tr:nth-child(even) td { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 10px; font-weight: 600; }
        .badge-open    { background: #dcfce7; color: #166534; }
        .badge-closed  { background: #f1f5f9; color: #475569; }
        .badge-approved{ background: #dcfce7; color: #166534; }
        .badge-rejected{ background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .bar-wrap { background: #e2e8f0; border-radius: 4px; height: 10px; }
        .bar-fill  { background: #4338ca; border-radius: 4px; height: 10px; display: inline-block; }
        .footer    { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 10px; }
        @media print {
            body { padding: 15px; }
            .no-print { display: none !important; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <!-- Print toolbar (hidden on actual print) -->
    <div class="no-print" style="margin-bottom:20px; display:flex; gap:10px;">
        <button onclick="window.print()" style="background:#4338ca;color:#fff;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:12px;">
            🖨 Print / Save as PDF
        </button>
        <button onclick="window.close()" style="background:#f1f5f9;color:#475569;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:12px;">
            ✕ Close
        </button>
    </div>

    <h1>OJAMS — System Report</h1>
    <p class="meta">Generated: <?php echo $generatedAt; ?> &nbsp;|&nbsp; By: <?php echo $adminName; ?></p>

    <!-- Section 1: Applicants per Job -->
    <h2>Total Applicants per Job</h2>
    <table>
        <thead>
            <tr><th>#</th><th>Job Title</th><th>Company</th><th>Status</th><th>Posted</th><th>Applicants</th><th style="width:22%;">Bar</th></tr>
        </thead>
        <tbody>
            <?php if (empty($perJob)): ?>
            <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:16px;">No data.</td></tr>
            <?php else:
            $maxA = max(array_column($perJob, 'applicants')) ?: 1;
            foreach ($perJob as $i => $r):
                $pct = round(($r['applicants'] / $maxA) * 100);
            ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($r['job_title']); ?></td>
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
    <h2>Monthly Application Report (Last 12 Months)</h2>
    <table>
        <thead>
            <tr><th>#</th><th>Month</th><th>Total</th><th>Approved</th><th>Rejected</th><th>Pending</th></tr>
        </thead>
        <tbody>
            <?php if (empty($monthly)): ?>
            <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:16px;">No data.</td></tr>
            <?php else: foreach ($monthly as $i => $r): ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($r['month']); ?></td>
                <td><span class="badge" style="background:#e0e7ff;color:#3730a3;"><?php echo $r['total']; ?></span></td>
                <td><span class="badge badge-approved"><?php echo $r['approved']; ?></span></td>
                <td><span class="badge badge-rejected"><?php echo $r['rejected']; ?></span></td>
                <td><span class="badge badge-pending"><?php echo $r['pending']; ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="footer">
        OJAMS &mdash; Online Job Application and Monitoring System &mdash; <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
        &nbsp;|&nbsp; Printed: <?php echo $generatedAt; ?>
    </div>

    <script>
        // Auto-open print dialog after a short delay for rendering
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
