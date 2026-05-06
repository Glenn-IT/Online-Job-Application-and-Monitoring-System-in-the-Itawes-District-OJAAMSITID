<?php
require_once __DIR__ . '/db.php';
echo "DB: CONNECTED\n";
$rows = $pdo->query("SELECT role, email FROM users ORDER BY role")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['role'] . ': ' . $r['email'] . "\n";
}
$jobs  = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$apps  = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$logs  = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
echo "Jobs: $jobs | Applications: $apps | Activity logs: $logs\n";
