<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Detect multipart (file upload) vs JSON request
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

// Rate limit: 30 requests per minute per IP
rateLimit('applications', 30, 60);

// Helper: log activity
function logActivity(PDO $pdo, string $action, string $status): void {
    $uid  = $_SESSION['ojams_user']['id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (action, status, performed_by) VALUES (?, ?, ?)");
    $stmt->execute([$action, $status, $uid]);
}

// ── ACTION: apply ────────────────────────────────────────────
if ($action === 'apply') {
    if (!isUser()) {
        echo json_encode(['success' => false, 'message' => 'Only users can apply for jobs.']);
        exit;
    }
    $jobId = (int)($body['job_id'] ?? 0);
    if ($jobId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid job ID.']);
        exit;
    }
    // Check job exists and is open
    $jStmt = $pdo->prepare("SELECT id, title, company, status FROM jobs WHERE id = ?");
    $jStmt->execute([$jobId]);
    $job = $jStmt->fetch();
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not found.']);
        exit;
    }
    if ($job['status'] !== 'Open') {
        echo json_encode(['success' => false, 'message' => 'This job is no longer accepting applications.']);
        exit;
    }
    // Check duplicate
    $dup = $pdo->prepare("SELECT id FROM applications WHERE user_id = ? AND job_id = ?");
    $dup->execute([$userId, $jobId]);
    if ($dup->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already applied for this job.']);
        exit;
    }
    $fullName   = strip_tags(trim($body['full_name']  ?? ''));
    $email      = trim($body['email']      ?? '');
    $contact    = strip_tags(trim($body['contact']    ?? ''));
    $address    = strip_tags(trim($body['address']    ?? ''));
    $birthdate  = $body['birthdate']       ?? null;
    // Always compute age server-side — never trust the submitted value
    $age = null;
    if ($birthdate) {
        $age = (int)(new DateTime())->diff(new DateTime($birthdate))->y;
    }
    $elementary = strip_tags(trim($body['elementary'] ?? ''));
    $jhs        = strip_tags(trim($body['jhs']        ?? ''));
    $shs        = strip_tags(trim($body['shs']        ?? ''));
    $college    = strip_tags(trim($body['college']    ?? ''));
    $skills     = strip_tags(trim($body['skills']     ?? ''));
    $experience = strip_tags(trim($body['experience'] ?? ''));

    if (!$fullName || !$email) {
        echo json_encode(['success' => false, 'message' => 'Full name and email are required.']);
        exit;
    }
    if (strlen($fullName) > 150)    { echo json_encode(['success' => false, 'message' => 'Full name must be 150 characters or fewer.']); exit; }
    if (strlen($email) > 150)       { echo json_encode(['success' => false, 'message' => 'Email must be 150 characters or fewer.']); exit; }
    if (strlen($contact) > 20)      { echo json_encode(['success' => false, 'message' => 'Contact number must be 20 characters or fewer.']); exit; }
    if (strlen($elementary) > 200)  { echo json_encode(['success' => false, 'message' => 'Elementary school name must be 200 characters or fewer.']); exit; }
    if (strlen($jhs) > 200)         { echo json_encode(['success' => false, 'message' => 'JHS school name must be 200 characters or fewer.']); exit; }
    if (strlen($shs) > 200)         { echo json_encode(['success' => false, 'message' => 'SHS school name must be 200 characters or fewer.']); exit; }
    if (strlen($college) > 200)     { echo json_encode(['success' => false, 'message' => 'College name must be 200 characters or fewer.']); exit; }

    // ── Resume / CV upload (optional) ───────────────────────
    $uploadedFile  = null; // holds validated file info pre-insert
    $resumeError   = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['resume'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File upload failed (code ' . $file['error'] . ').']);
            exit;
        }
        // Size limit: 5 MB
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Resume must be 5 MB or smaller.']);
            exit;
        }
        // MIME validation (real content check, not just extension)
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMimes, true)) {
            echo json_encode(['success' => false, 'message' => 'Only PDF, DOC, and DOCX files are allowed.']);
            exit;
        }
        // Extension cross-check
        $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'doc', 'docx'];
        if (!in_array($ext, $allowedExts, true)) {
            echo json_encode(['success' => false, 'message' => 'Only PDF, DOC, and DOCX files are allowed.']);
            exit;
        }
        $uploadedFile = [
            'tmp'      => $file['tmp_name'],
            'original' => basename($file['name']),
            'size'     => $file['size'],
            'mime'     => $mime,
            'ext'      => $ext,
        ];
    }

    $stmt = $pdo->prepare("
        INSERT INTO applications
        (user_id, job_id, full_name, email, contact, address, birthdate, age,
         elementary, jhs, shs, college, skills, experience, status, date_applied)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', CURDATE())
    ");
    $stmt->execute([
        $userId, $jobId, $fullName, $email, $contact, $address,
        $birthdate ?: null, $age ?: null,
        $elementary, $jhs, $shs, $college, $skills, $experience
    ]);
    $newAppId = (int)$pdo->lastInsertId();

    // ── Move uploaded file and record it ────────────────────
    if ($uploadedFile) {
        $uploadDir = __DIR__ . '/../uploads/resumes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $storedName = $userId . '_' . $jobId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $uploadedFile['ext'];
        if (move_uploaded_file($uploadedFile['tmp'], $uploadDir . $storedName)) {
            $pdo->prepare("
                INSERT INTO resumes (application_id, user_id, original_name, stored_name, file_size, mime_type)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                $newAppId, $userId,
                $uploadedFile['original'], $storedName,
                $uploadedFile['size'], $uploadedFile['mime'],
            ]);
        }
    }

    $user = getCurrentUser();
    logActivity($pdo, "New application received from {$user['full_name']} for \"{$job['title']}\"", 'New');
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully.']);
    exit;
}

// ── ACTION: cancel ───────────────────────────────────────────
if ($action === 'cancel') {
    $appId = (int)($body['id'] ?? 0);
    if ($appId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid application ID.']);
        exit;
    }
    $check = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $check->execute([$appId, $userId]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Application not found or cannot be cancelled.']);
        exit;
    }
    $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$appId]);
    $user = getCurrentUser();
    logActivity($pdo, "Application cancelled by {$user['full_name']}", 'Cancelled');
    echo json_encode(['success' => true, 'message' => 'Application cancelled.']);
    exit;
}

// ── ACTION: updateStatus (admin only) ────────────────────────
if ($action === 'updateStatus') {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }
    $appId  = (int)($body['id'] ?? 0);
    $status = $body['status'] ?? '';
    if ($appId <= 0 || !in_array($status, ['Approved', 'Rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }
    $check = $pdo->prepare("
        SELECT a.id, a.full_name, j.title
        FROM applications a
        JOIN jobs j ON j.id = a.job_id
        WHERE a.id = ?
    ");
    $check->execute([$appId]);
    $app = $check->fetch();
    if (!$app) {
        echo json_encode(['success' => false, 'message' => 'Application not found.']);
        exit;
    }
    // Capture old status before overwriting
    $oldRow = $pdo->prepare("SELECT status FROM applications WHERE id = ?");
    $oldRow->execute([$appId]);
    $oldStatus = $oldRow->fetchColumn();

    $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$status, $appId]);

    // Log the transition
    $pdo->prepare("
        INSERT INTO application_status_history (application_id, from_status, to_status, changed_by)
        VALUES (?, ?, ?, ?)
    ")->execute([$appId, $oldStatus ?: null, $status, $_SESSION['ojams_user']['id']]);

    logActivity($pdo, "Application of {$app['full_name']} marked as {$status} for \"{$app['title']}\"", $status);
    echo json_encode(['success' => true, 'message' => "Application {$status}."]);
    exit;
}

// ── ACTION: getDetails (admin only) ──────────────────────────
if ($action === 'getDetails') {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }
    $appId = (int)($body['id'] ?? 0);
    $stmt  = $pdo->prepare("
        SELECT a.*, j.title as job_title, j.company
        FROM applications a
        JOIN jobs j ON j.id = a.job_id
        WHERE a.id = ?
    ");
    $stmt->execute([$appId]);
    $app = $stmt->fetch();
    if (!$app) {
        echo json_encode(['success' => false, 'message' => 'Application not found.']);
        exit;
    }

    // Fetch status change history
    $histStmt = $pdo->prepare("
        SELECT h.from_status, h.to_status,
               DATE_FORMAT(h.changed_at, '%M %d, %Y %h:%i %p') AS changed_at,
               u.full_name AS changed_by
        FROM application_status_history h
        LEFT JOIN users u ON u.id = h.changed_by
        WHERE h.application_id = ?
        ORDER BY h.changed_at ASC
    ");
    $histStmt->execute([$appId]);
    $history = $histStmt->fetchAll();

    // Check if a resume was uploaded
    $resumeStmt = $pdo->prepare("SELECT original_name, stored_name FROM resumes WHERE application_id = ? LIMIT 1");
    $resumeStmt->execute([$appId]);
    $resume = $resumeStmt->fetch();

    echo json_encode(['success' => true, 'data' => $app, 'history' => $history, 'resume' => $resume ?: null]);
    exit;
}

// ── ACTION: bulkUpdateStatus (admin only) ────────────────────
if ($action === 'bulkUpdateStatus') {
    if (!isAdmin()) { echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit; }
    $ids    = array_filter(array_map('intval', (array)($body['ids'] ?? [])));
    $status = $body['status'] ?? '';
    if (empty($ids) || !in_array($status, ['Approved', 'Rejected'], true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']); exit;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Capture old statuses before overwriting
    $oldStmt = $pdo->prepare("SELECT id, status FROM applications WHERE id IN ({$placeholders})");
    $oldStmt->execute(array_values($ids));
    $oldStatuses = $oldStmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => status]

    $pdo->prepare("UPDATE applications SET status = ? WHERE id IN ({$placeholders})")
        ->execute(array_merge([$status], array_values($ids)));

    // Log a history row per application
    $adminId  = $_SESSION['ojams_user']['id'];
    $histStmt = $pdo->prepare("
        INSERT INTO application_status_history (application_id, from_status, to_status, changed_by)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($ids as $id) {
        $histStmt->execute([$id, $oldStatuses[$id] ?? null, $status, $adminId]);
    }

    logActivity($pdo, "Bulk marked " . count($ids) . " application(s) as {$status}", $status);
    echo json_encode(['success' => true, 'message' => count($ids) . " application(s) marked as {$status}."]);
    exit;
}

// ── ACTION: bulkDelete (admin only) ──────────────────────────
if ($action === 'bulkDelete') {
    if (!isAdmin()) { echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit; }
    $ids = array_filter(array_map('intval', (array)($body['ids'] ?? [])));
    if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'No applications selected.']); exit; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM applications WHERE id IN ({$placeholders})")->execute(array_values($ids));
    logActivity($pdo, "Bulk deleted " . count($ids) . " application(s)", 'Deleted');
    echo json_encode(['success' => true, 'message' => count($ids) . " application(s) deleted."]);
    exit;
}

logError('applications.php: unknown action', ['action' => $action]);
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
