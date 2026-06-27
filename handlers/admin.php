<?php
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';

// CSRF check
if (!validateCsrfToken($body['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
    exit;
}

// Rate limit: 20 requests per minute per IP
rateLimit('admin', 20, 60);

// ── ACTION: deactivateUser ───────────────────────────────────
if ($action === 'deactivateUser') {
    $targetId = (int)($body['id'] ?? 0);
    $selfId   = $_SESSION['ojams_user']['id'];
    if ($targetId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid user ID.']); exit; }
    if ($targetId === $selfId) { echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account.']); exit; }

    $check = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $check->execute([$targetId]);
    $target = $check->fetch();
    if (!$target) { echo json_encode(['success' => false, 'message' => 'User not found.']); exit; }

    $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?")->execute([$targetId]);
    $uid = $selfId;
    $pdo->prepare("INSERT INTO activity_logs (action, status, performed_by) VALUES (?, ?, ?)")
        ->execute(["User account deactivated: {$target['full_name']}", 'Updated', $uid]);
    echo json_encode(['success' => true, 'message' => "{$target['full_name']} has been deactivated."]);
    exit;
}

// ── ACTION: reactivateUser ───────────────────────────────────
if ($action === 'reactivateUser') {
    $targetId = (int)($body['id'] ?? 0);
    if ($targetId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid user ID.']); exit; }

    $check = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $check->execute([$targetId]);
    $target = $check->fetch();
    if (!$target) { echo json_encode(['success' => false, 'message' => 'User not found.']); exit; }

    $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?")->execute([$targetId]);
    $uid = $_SESSION['ojams_user']['id'];
    $pdo->prepare("INSERT INTO activity_logs (action, status, performed_by) VALUES (?, ?, ?)")
        ->execute(["User account reactivated: {$target['full_name']}", 'Updated', $uid]);
    echo json_encode(['success' => true, 'message' => "{$target['full_name']} has been reactivated."]);
    exit;
}

// ── ACTION: changeRole ───────────────────────────────────────
if ($action === 'changeRole') {
    $targetId = (int)($body['id']   ?? 0);
    $newRole  = $body['role'] ?? '';
    $selfId   = $_SESSION['ojams_user']['id'];
    if ($targetId <= 0 || !in_array($newRole, ['admin', 'user'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']); exit;
    }
    if ($targetId === $selfId) {
        echo json_encode(['success' => false, 'message' => 'You cannot change your own role.']); exit;
    }

    $check = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $check->execute([$targetId]);
    $target = $check->fetch();
    if (!$target) { echo json_encode(['success' => false, 'message' => 'User not found.']); exit; }

    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $targetId]);
    $uid = $selfId;
    $pdo->prepare("INSERT INTO activity_logs (action, status, performed_by) VALUES (?, ?, ?)")
        ->execute(["User role changed to {$newRole}: {$target['full_name']}", 'Updated', $uid]);
    echo json_encode(['success' => true, 'message' => "{$target['full_name']}'s role changed to {$newRole}."]);
    exit;
}

// ── ACTION: clearLogs ────────────────────────────────────────
if ($action === 'clearLogs') {
    $days = max(1, (int)($body['days'] ?? 90));
    $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->execute([$days]);
    $deleted = $stmt->rowCount();

    $uid = $_SESSION['ojams_user']['id'];
    $pdo->prepare("INSERT INTO activity_logs (action, status, performed_by) VALUES (?, ?, ?)")
        ->execute(["Cleared {$deleted} activity log entries older than {$days} days", 'Deleted', $uid]);

    echo json_encode(['success' => true, 'message' => "Cleared {$deleted} log entries older than {$days} days."]);
    exit;
}

logError('admin.php: unknown action', ['action' => $action]);
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
