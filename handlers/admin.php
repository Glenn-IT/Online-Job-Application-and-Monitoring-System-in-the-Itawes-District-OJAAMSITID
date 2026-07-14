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

// ── ACTION: approveUser ──────────────────────────────────────
// Staff accounts register unapproved and cannot log in until an
// admin approves them here.
if ($action === 'approveUser') {
    $targetId = (int)($body['id'] ?? 0);
    if ($targetId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid user ID.']); exit; }

    $check = $pdo->prepare("SELECT id, full_name, is_approved FROM users WHERE id = ?");
    $check->execute([$targetId]);
    $target = $check->fetch();
    if (!$target) { echo json_encode(['success' => false, 'message' => 'User not found.']); exit; }
    if ((int)$target['is_approved'] === 1) {
        echo json_encode(['success' => false, 'message' => "{$target['full_name']} is already approved."]); exit;
    }

    $pdo->prepare("UPDATE users SET is_approved = 1 WHERE id = ?")->execute([$targetId]);
    $uid = $_SESSION['ojams_user']['id'];
    $pdo->prepare("INSERT INTO activity_logs (action, status, performed_by) VALUES (?, ?, ?)")
        ->execute(["Staff account approved: {$target['full_name']}", 'Updated', $uid]);
    echo json_encode(['success' => true, 'message' => "{$target['full_name']}'s account has been approved. They can now log in."]);
    exit;
}

// ── ACTION: changeRole ───────────────────────────────────────
if ($action === 'changeRole') {
    $targetId = (int)($body['id']   ?? 0);
    $newRole  = $body['role'] ?? '';
    $selfId   = $_SESSION['ojams_user']['id'];
    if ($targetId <= 0 || !in_array($newRole, ['admin', 'staff', 'user'])) {
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

// ── ACTION: addUser ──────────────────────────────────────────
// Admin-created accounts (applicant, staff, or admin) are approved
// and active immediately — no self-registration approval wait.
if ($action === 'addUser') {
    $role        = $body['role'] ?? 'user';
    $full_name   = trim($body['full_name'] ?? '');
    $email       = strtolower(trim($body['email'] ?? ''));
    $contact     = trim($body['contact'] ?? '');
    $password    = $body['password'] ?? '';
    $confirm     = $body['confirm'] ?? '';
    $sq_question = trim($body['security_question'] ?? '');
    $sq_answer   = trim($body['security_answer'] ?? '');

    if (!in_array($role, ['staff', 'user'], true)) {
        echo json_encode(['success' => false, 'message' => 'Please choose a valid account type.']); exit;
    }
    if (!$full_name || !$email || !$contact || !$password || !$confirm) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']); exit;
    }
    if (!preg_match('/^\d{11}$/', $contact)) {
        echo json_encode(['success' => false, 'message' => 'Contact number must be exactly 11 digits.']); exit;
    }
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters and include an uppercase letter, a number, and a special character.']); exit;
    }
    if ($password !== $confirm) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']); exit;
    }
    if (!in_array($sq_question, SECURITY_QUESTIONS, true)) {
        echo json_encode(['success' => false, 'message' => 'Please choose a security question from the list.']); exit;
    }
    if (mb_strlen($sq_answer) < 2) {
        echo json_encode(['success' => false, 'message' => 'Security answer must be at least 2 characters.']); exit;
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'That email address is already registered.']); exit;
    }

    $hash   = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $sqHash = password_hash(mb_strtolower($sq_answer), PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

    $stmt = $pdo->prepare("
        INSERT INTO users (role, full_name, email, password_hash, contact_number, security_question, security_answer_hash, is_active, is_approved)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)
    ");
    $stmt->execute([$role, $full_name, $email, $hash, $contact, $sq_question, $sqHash]);

    $uid = $_SESSION['ojams_user']['id'];
    $pdo->prepare("INSERT INTO activity_logs (action, status, performed_by) VALUES (?, ?, ?)")
        ->execute(["Registered new {$role} account: {$full_name}", 'Created', $uid]);

    echo json_encode(['success' => true, 'message' => "{$full_name}'s {$role} account has been created."]);
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
