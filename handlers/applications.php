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
    $fullName   = trim($body['full_name']  ?? '');
    $email      = trim($body['email']      ?? '');
    $contact    = trim($body['contact']    ?? '');
    $address    = trim($body['address']    ?? '');
    $birthdate  = $body['birthdate']       ?? null;
    $age        = (int)($body['age']       ?? 0);
    $elementary = trim($body['elementary'] ?? '');
    $jhs        = trim($body['jhs']        ?? '');
    $shs        = trim($body['shs']        ?? '');
    $college    = trim($body['college']    ?? '');
    $skills     = trim($body['skills']     ?? '');
    $experience = trim($body['experience'] ?? '');

    if (!$fullName || !$email) {
        echo json_encode(['success' => false, 'message' => 'Full name and email are required.']);
        exit;
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
    $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?")->execute([$status, $appId]);
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
    echo json_encode(['success' => true, 'data' => $app]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
