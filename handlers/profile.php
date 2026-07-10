<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isMultipart = str_contains($contentType, 'multipart/form-data');

if ($isMultipart) {
    $body = $_POST;
} else {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
}

$action = $body['action'] ?? '';
$userId = $_SESSION['ojams_user']['id'];

// CSRF check
if (!validateCsrfToken($body['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
    exit;
}

// Rate limit: 20 requests per minute per IP
rateLimit('profile', 20, 60);

// ── ACTION: uploadAvatar ─────────────────────────────────────
if ($action === 'uploadAvatar') {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
        exit;
    }
    $file = $_FILES['avatar'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload failed (code ' . $file['error'] . ').']);
        exit;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Avatar must be 2 MB or smaller.']);
        exit;
    }
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMimes, true)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, and WebP images are allowed.']);
        exit;
    }
    $ext       = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$mime];
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $prev = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $prev->execute([$userId]);
    $oldPhoto = $prev->fetchColumn();
    if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
        unlink($uploadDir . $oldPhoto);
    }

    $stored = $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $stored)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
        exit;
    }
    $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?")->execute([$stored, $userId]);
    $_SESSION['ojams_user']['profile_photo'] = $stored;
    $url = BASE_URL . '/uploads/avatars/' . $stored;
    echo json_encode(['success' => true, 'message' => 'Avatar updated.', 'url' => $url]);
    exit;
}

// ── ACTION: updateInfo ───────────────────────────────────────
if ($action === 'updateInfo') {
    $fullName = trim($body['full_name']      ?? '');
    $email    = strtolower(trim($body['email'] ?? ''));
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
    if ($contact !== '' && !preg_match('/^\d{11}$/', $contact)) {
        echo json_encode(['success' => false, 'message' => 'Contact number must be exactly 11 digits (numbers only).']);
        exit;
    }
    if ($birthdate) {
        $bd = DateTime::createFromFormat('Y-m-d', $birthdate);
        if (!$bd || $bd->format('Y-m-d') !== $birthdate) {
            echo json_encode(['success' => false, 'message' => 'Invalid birthdate format.']);
            exit;
        }
        $now = new DateTime();
        if ($bd >= $now) {
            echo json_encode(['success' => false, 'message' => 'Birthdate cannot be a future date.']);
            exit;
        }
        $age = (int)$now->diff($bd)->y;
        if ($age < 16 || $age > 80) {
            echo json_encode(['success' => false, 'message' => 'Age must be between 16 and 80 years.']);
            exit;
        }
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }
    $check = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id != ?");
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
    // ── Throttle: uses constants from config/config.php ──────
    $pwMaxAttempts = PW_MAX_ATTEMPTS;
    $pwLockoutSecs = PW_LOCKOUT_SECONDS;
    $pwAttempts    = (int)($_SESSION['pw_attempts']     ?? 0);
    $pwLockedUtil  = (int)($_SESSION['pw_locked_until'] ?? 0);

    if ($pwLockedUtil > time()) {
        $retryAfter = $pwLockedUtil - time();
        echo json_encode([
            'success'     => false,
            'locked'      => true,
            'retry_after' => $retryAfter,
            'message'     => "Too many failed attempts. Please wait {$retryAfter} second(s).",
        ]);
        exit;
    }

    // Lock window expired — reset counter
    if ($pwLockedUtil > 0 && $pwLockedUtil <= time()) {
        $_SESSION['pw_attempts']     = 0;
        $_SESSION['pw_locked_until'] = 0;
        $pwAttempts = 0;
    }

    $current = $body['current_password'] ?? '';
    $newPw   = $body['new_password']     ?? '';
    $confirm = $body['confirm_password'] ?? '';

    if (!$current || !$newPw || !$confirm) {
        echo json_encode(['success' => false, 'locked' => false, 'message' => 'All password fields are required.']);
        exit;
    }
    if (strlen($newPw) < 8) {
        echo json_encode(['success' => false, 'locked' => false, 'message' => 'New password must be at least 8 characters.']);
        exit;
    }
    if (!preg_match('/[A-Z]/', $newPw)) {
        echo json_encode(['success' => false, 'locked' => false, 'message' => 'New password must contain at least one uppercase letter.']);
        exit;
    }
    if (!preg_match('/[0-9]/', $newPw)) {
        echo json_encode(['success' => false, 'locked' => false, 'message' => 'New password must contain at least one number.']);
        exit;
    }
    if (!preg_match('/[^A-Za-z0-9]/', $newPw)) {
        echo json_encode(['success' => false, 'locked' => false, 'message' => 'New password must contain at least one special character.']);
        exit;
    }
    if ($newPw !== $confirm) {
        echo json_encode(['success' => false, 'locked' => false, 'message' => 'New passwords do not match.']);
        exit;
    }

    $row = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $row->execute([$userId]);
    $user = $row->fetch();

    if (!$user || !password_verify($current, $user['password_hash'])) {
        $pwAttempts++;
        $_SESSION['pw_attempts'] = $pwAttempts;

        if ($pwAttempts >= $pwMaxAttempts) {
            $_SESSION['pw_locked_until'] = time() + $pwLockoutSecs;
            echo json_encode([
                'success'     => false,
                'locked'      => true,
                'retry_after' => $pwLockoutSecs,
                'message'     => "Too many failed attempts. Please wait {$pwLockoutSecs} seconds.",
            ]);
        } else {
            $left = $pwMaxAttempts - $pwAttempts;
            echo json_encode([
                'success' => false,
                'locked'  => false,
                'message' => "Current password is incorrect. {$left} attempt(s) remaining before lockout.",
            ]);
        }
        exit;
    }

    $_SESSION['pw_attempts']     = 0;
    $_SESSION['pw_locked_until'] = 0;
    $newHash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $userId]);
    echo json_encode(['success' => true, 'locked' => false, 'message' => 'Password changed successfully.']);
    exit;
}

// ── ACTION: updateSecurityQuestion ───────────────────────────
if ($action === 'updateSecurityQuestion') {
    $question = trim($body['question'] ?? '');
    $answer   = trim($body['answer']   ?? '');
    $current  = $body['current_password'] ?? '';

    if (!$current) {
        echo json_encode(['success' => false, 'message' => 'Your current password is required to change the security question.']);
        exit;
    }
    if (!in_array($question, SECURITY_QUESTIONS, true)) {
        echo json_encode(['success' => false, 'message' => 'Please choose a security question from the list.']);
        exit;
    }
    if (mb_strlen($answer) < 2) {
        echo json_encode(['success' => false, 'message' => 'Security answer must be at least 2 characters.']);
        exit;
    }

    $row = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $row->execute([$userId]);
    $user = $row->fetch();
    if (!$user || !password_verify($current, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    // Answer is normalized (trimmed, lowercased) to match forgot-password verification
    $sqHash = password_hash(mb_strtolower($answer), PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $pdo->prepare("UPDATE users SET security_question = ?, security_answer_hash = ? WHERE id = ?")
        ->execute([$question, $sqHash, $userId]);
    $_SESSION['ojams_user']['security_question'] = $question;
    echo json_encode(['success' => true, 'message' => 'Security question updated.', 'question' => $question]);
    exit;
}

logError('profile.php: unknown action', ['action' => $action]);
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
