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
        echo json_encode(['success' => true, 'saved' => true, 'message' => 'Job saved to your list!']);
    }
    exit;
}

// ── ACTION: save_draft ────────────────────────────────────────
if ($action === 'save_draft') {
    $jobId = (int)($body['job_id'] ?? 0);
    if ($jobId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid job ID.']);
        exit;
    }
    $job = $pdo->prepare("SELECT id, title, company FROM jobs WHERE id = ?");
    $job->execute([$jobId]);
    if (!$job->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Job not found.']);
        exit;
    }
    $draftData = $body['draft_data'] ?? [];
    if (is_array($draftData)) {
        $draftJson = json_encode($draftData);
    } elseif (is_string($draftData)) {
        $draftJson = $draftData;
    } else {
        $draftJson = null;
    }

    $check = $pdo->prepare("SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $check->execute([$userId, $jobId]);
    if ($check->fetch()) {
        $pdo->prepare("UPDATE saved_jobs SET draft_data = ?, updated_at = NOW() WHERE user_id = ? AND job_id = ?")
            ->execute([$draftJson, $userId, $jobId]);
    } else {
        $pdo->prepare("INSERT INTO saved_jobs (user_id, job_id, draft_data, updated_at) VALUES (?, ?, ?, NOW())")
            ->execute([$userId, $jobId, $draftJson]);
    }

    echo json_encode([
        'success' => true,
        'saved'   => true,
        'message' => 'Application draft saved! You can continue applying anytime from Saved Jobs.'
    ]);
    exit;
}

// ── ACTION: get_draft ─────────────────────────────────────────
if ($action === 'get_draft') {
    $jobId = (int)($body['job_id'] ?? 0);
    if ($jobId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid job ID.']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT draft_data, updated_at FROM saved_jobs WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$userId, $jobId]);
    $row = $stmt->fetch();
    if ($row && !empty($row['draft_data'])) {
        $data = json_decode($row['draft_data'], true);
        echo json_encode([
            'success'    => true,
            'has_draft'  => true,
            'draft'      => $data,
            'updated_at' => $row['updated_at']
        ]);
    } else {
        echo json_encode(['success' => true, 'has_draft' => false, 'draft' => null]);
    }
    exit;
}

// ── ACTION: remove ────────────────────────────────────────────
if ($action === 'remove') {
    $jobId = (int)($body['job_id'] ?? 0);
    if ($jobId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid job ID.']);
        exit;
    }
    $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?")->execute([$userId, $jobId]);
    echo json_encode(['success' => true, 'message' => 'Job removed from saved list.']);
    exit;
}

logError('saved-jobs.php: unknown action', ['action' => $action]);
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
