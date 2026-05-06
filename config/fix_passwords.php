<?php
require_once __DIR__ . '/db.php';
$adminHash = password_hash('admin123',    PASSWORD_BCRYPT, ['cost' => 12]);
$userHash  = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
$pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?')->execute([$adminHash, 'admin@ojams.com']);
$pdo->prepare('UPDATE users SET password_hash = ? WHERE role = ?')->execute([$userHash, 'user']);
// Verify
$rows = $pdo->query("SELECT id, email, role FROM users")->fetchAll();
foreach ($rows as $r) {
    echo "Updated: [{$r['role']}] {$r['email']}" . PHP_EOL;
}
echo "Done. Admin password: admin123 | User password: password123" . PHP_EOL;
