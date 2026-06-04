<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';
$userId = $_SESSION['ojams_user']['id'];

// CSRF check
if (!validateCsrfToken($body['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
    exit;
}

// ── ACTION: updateInfo ───────────────────────────────────────
if ($action === 'updateInfo') {
    $fullName = trim($body['full_name']      ?? '');
    $email    = trim($body['email']          ?? '');
    $contact  = trim($body['contact_number'] ?? '');
    $address  = trim($body['address']        ?? '');
    $birthdate = $body['birthdate']          ?? null;

    if (!$fullName || !$email) {
        echo json_encode(['success' => false, 'message' => 'Full name and email are required.']);
        exit;
    }
    if (strlen($fullName) > 150) { echo json_encode(['success' => false, 'message' => 'Full name must be 150 characters or fewer.']); exit; }
    if (strlen($email) > 150)    { echo json_encode(['success' => false, 'message' => 'Email must be 150 characters or fewer.']); exit; }
    if (strlen($contact) > 20)   { echo json_encode(['success' => false, 'message' => 'Contact number must be 20 characters or fewer.']); exit; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }
    // Check email uniqueness (excluding self)
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->execute([$email, $userId]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'That email is already in use by another account.']);
        exit;
    }
    $stmt = $pdo->prepare("
        UPDATE users
        SET full_name = ?, email = ?, contact_number = ?, address = ?, birthdate = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $fullName, $email, $contact ?: null,
        $address  ?: null, $birthdate ?: null,
        $userId
    ]);
    // Refresh session
    $_SESSION['ojams_user']['full_name']      = $fullName;
    $_SESSION['ojams_user']['email']          = $email;
    $_SESSION['ojams_user']['contact_number'] = $contact;
    $_SESSION['ojams_user']['address']        = $address;
    $_SESSION['ojams_user']['birthdate']      = $birthdate;
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    exit;
}

// ── ACTION: changePassword ───────────────────────────────────
if ($action === 'changePassword') {
    $current = $body['current_password'] ?? '';
    $newPw   = $body['new_password']     ?? '';
    $confirm = $body['confirm_password'] ?? '';

    if (!$current || !$newPw || !$confirm) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
        exit;
    }
    if (strlen($newPw) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
        exit;
    }
    if ($newPw !== $confirm) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
        exit;
    }
    // Verify current password
    $row = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $row->execute([$userId]);
    $user = $row->fetch();
    if (!$user || !password_verify($current, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }
    $newHash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $userId]);
    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
    exit;
}

logError('profile.php: unknown action', ['action' => $action]);
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
