<?php


require_once __DIR__ . '/../config/auth.php';
requireAdmin();   // Must be a logged-in admin — hard stop otherwise

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Decode JSON body
$body   = json_decode(file_get_contents('php://input'), true);
$action = $body['action'] ?? '';

// CSRF check
if (!validateCsrfToken($body['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing CSRF token.']);
    exit;
}

// Rate limit: 30 requests per minute per IP
rateLimit('jobs', 30, 60);

// ── Helper: log activity ────────────────────────────────────
function logActivity(PDO $pdo, string $action, string $status, ?int $jobId = null, ?int $appId = null): void {
    $userId = $_SESSION['ojams_user']['id'] ?? null;
    $stmt   = $pdo->prepare(
        "INSERT INTO activity_logs (action, status, performed_by, job_id, application_id) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$action, $status, $userId, $jobId, $appId]);
}

// ── Helper: validate required string fields ─────────────────
function requireFields(array $data, array $fields): ?string {
    foreach ($fields as $f) {
        if (empty(trim($data[$f] ?? ''))) {
            return "Field '{$f}' is required.";
        }
    }
    return null;
}

// ════════════════════════════════════════════════════════════
// ACTION: add
// ════════════════════════════════════════════════════════════
if ($action === 'add') {

    $err = requireFields($body, ['title', 'company', 'description', 'qualification']);
    if ($err) { echo json_encode(['success' => false, 'message' => $err]); exit; }

    $title         = strip_tags(trim($body['title']));
    $company       = strip_tags(trim($body['company']));
    $description   = strip_tags(trim($body['description']));
    $qualification = strip_tags(trim($body['qualification']));
    $location      = strip_tags(trim($body['location']    ?? '')) ?: null;
    $jobType       = in_array($body['job_type'] ?? '', ['Full-time','Part-time','Contract','Internship','Freelance'])
                        ? $body['job_type'] : null;
    $salaryRange   = strip_tags(trim($body['salary_range'] ?? '')) ?: null;
    $date_posted   = $body['date_posted'] ?: date('Y-m-d');
    $status        = in_array($body['status'] ?? '', ['Open', 'Closed']) ? $body['status'] : 'Open';
    $deadline      = !empty($body['deadline']) ? $body['deadline'] : null;
    $created_by    = $_SESSION['ojams_user']['id'];

    if (strlen($title) > 150)      { echo json_encode(['success' => false, 'message' => 'Job title must be 150 characters or fewer.']); exit; }
    if (strlen($company) > 150)    { echo json_encode(['success' => false, 'message' => 'Company name must be 150 characters or fewer.']); exit; }
    if ($location && strlen($location) > 150) { echo json_encode(['success' => false, 'message' => 'Location must be 150 characters or fewer.']); exit; }

    $stmt = $pdo->prepare("
        INSERT INTO jobs (title, company, description, qualification, location, job_type, salary_range, date_posted, status, deadline, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $company, $description, $qualification, $location, $jobType, $salaryRange, $date_posted, $status, $deadline, $created_by]);
    $newId = $pdo->lastInsertId();

    logActivity($pdo, "New job posted: \"{$title}\" at {$company}", 'Created', (int)$newId);

    echo json_encode(['success' => true, 'message' => 'Job added successfully.', 'id' => $newId]);
    exit;
}

// ════════════════════════════════════════════════════════════
// ACTION: edit
// ════════════════════════════════════════════════════════════
if ($action === 'edit') {

    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid job ID.']); exit; }

    $err = requireFields($body, ['title', 'company', 'description', 'qualification']);
    if ($err) { echo json_encode(['success' => false, 'message' => $err]); exit; }

    // Confirm job exists
    $check = $pdo->prepare("SELECT id, title FROM jobs WHERE id = ?");
    $check->execute([$id]);
    $existing = $check->fetch();
    if (!$existing) { echo json_encode(['success' => false, 'message' => 'Job not found.']); exit; }

    $title         = strip_tags(trim($body['title']));
    $company       = strip_tags(trim($body['company']));
    $description   = strip_tags(trim($body['description']));
    $qualification = strip_tags(trim($body['qualification']));
    $location      = strip_tags(trim($body['location']    ?? '')) ?: null;
    $jobType       = in_array($body['job_type'] ?? '', ['Full-time','Part-time','Contract','Internship','Freelance'])
                        ? $body['job_type'] : null;
    $salaryRange   = strip_tags(trim($body['salary_range'] ?? '')) ?: null;
    $date_posted   = $body['date_posted'] ?: date('Y-m-d');
    $status        = in_array($body['status'] ?? '', ['Open', 'Closed']) ? $body['status'] : 'Open';
    $deadline      = !empty($body['deadline']) ? $body['deadline'] : null;

    if (strlen($title) > 150)      { echo json_encode(['success' => false, 'message' => 'Job title must be 150 characters or fewer.']); exit; }
    if (strlen($company) > 150)    { echo json_encode(['success' => false, 'message' => 'Company name must be 150 characters or fewer.']); exit; }
    if ($location && strlen($location) > 150) { echo json_encode(['success' => false, 'message' => 'Location must be 150 characters or fewer.']); exit; }

    $stmt = $pdo->prepare("
        UPDATE jobs
        SET title = ?, company = ?, description = ?, qualification = ?,
            location = ?, job_type = ?, salary_range = ?,
            date_posted = ?, status = ?, deadline = ?
        WHERE id = ?
    ");
    $stmt->execute([$title, $company, $description, $qualification, $location, $jobType, $salaryRange, $date_posted, $status, $deadline, $id]);

    logActivity($pdo, "Job updated: \"{$title}\"", 'Updated', $id);

    echo json_encode(['success' => true, 'message' => 'Job updated successfully.']);
    exit;
}

// ════════════════════════════════════════════════════════════
// ACTION: delete
// ════════════════════════════════════════════════════════════
if ($action === 'delete') {

    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid job ID.']); exit; }

    // Confirm job exists
    $check = $pdo->prepare("SELECT id, title FROM jobs WHERE id = ?");
    $check->execute([$id]);
    $existing = $check->fetch();
    if (!$existing) { echo json_encode(['success' => false, 'message' => 'Job not found.']); exit; }

    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->execute([$id]);

    logActivity($pdo, "Job deleted: \"{$existing['title']}\"", 'Deleted', $id);

    echo json_encode(['success' => true, 'message' => 'Job deleted successfully.']);
    exit;
}

// ── ACTION: bulkDelete ───────────────────────────────────────
if ($action === 'bulkDelete') {
    $ids = array_filter(array_map('intval', (array)($body['ids'] ?? [])));
    if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'No jobs selected.']); exit; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM jobs WHERE id IN ({$placeholders})")->execute(array_values($ids));
    logActivity($pdo, "Bulk deleted " . count($ids) . " job post(s)", 'Deleted');
    echo json_encode(['success' => true, 'message' => count($ids) . " job(s) deleted."]);
    exit;
}

// Unknown action
logError('jobs.php: unknown action', ['action' => $action]);
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
