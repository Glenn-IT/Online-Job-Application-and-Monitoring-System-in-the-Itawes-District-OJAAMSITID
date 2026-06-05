<?php
require_once __DIR__ . '/../config/auth.php';
requireUser();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

if (!validateCsrfToken($body['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
    exit;
}

rateLimit('saved_jobs', 30, 60);

$userId = $_SESSION['ojams_user']['id'];
$action = $body['action'] ?? '';

// ── ACTION: toggle ───────────────────────────────────────────
if ($action === 'toggle') {
    $jobId = (int)($body['job_id'] ?? 0);
    if ($jobId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid job ID.']);
        exit;
    }
    $job = $pdo->prepare("SELECT id FROM jobs WHERE id = ?");
    $job->execute([$jobId]);
    if (!$job->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Job not found.']);
        exit;
    }
    $check = $pdo->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $check->execute([$userId, $jobId]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?")->execute([$userId, $jobId]);
        echo json_encode(['success' => true, 'saved' => false, 'message' => 'Job removed from saved list.']);
    } else {
        $pdo->prepare("INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)")->execute([$userId, $jobId]);
        echo json_encode(['success' => true, 'saved' => true, 'message' => 'Job saved to your list.']);
    }
    exit;
}

logError('saved-jobs.php: unknown action', ['action' => $action]);
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
