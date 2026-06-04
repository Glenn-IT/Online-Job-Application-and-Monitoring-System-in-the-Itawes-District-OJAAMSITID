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

// ── ACTION: clearLogs ────────────────────────────────────────
if ($action === 'clearLogs') {
    $days = max(1, (int)($body['days'] ?? 90));
    $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->execute([$days]);
    $deleted = $stmt->rowCount();

    // Log the cleanup itself
    $uid = $_SESSION['ojams_user']['id'];
    $pdo->prepare("INSERT INTO activity_logs (action, status, performed_by) VALUES (?, ?, ?)")
        ->execute(["Cleared {$deleted} activity log entries older than {$days} days", 'Deleted', $uid]);

    echo json_encode(['success' => true, 'message' => "Cleared {$deleted} log entries older than {$days} days."]);
    exit;
}

logError('admin.php: unknown action', ['action' => $action]);
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
