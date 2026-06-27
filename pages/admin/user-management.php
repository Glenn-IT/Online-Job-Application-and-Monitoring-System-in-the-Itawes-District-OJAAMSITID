<?php
require_once __DIR__ . '/../../components/under-construction.php';
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$pageTitle   = 'OJAMS - User Management';
$basePath    = '../../';
$currentPage = 'user-management';

// ── Pagination ───────────────────────────────────────────────
$perPage    = PER_PAGE_ADMIN;
$page       = max(1, (int)($_GET['page'] ?? 1));
$roleFilter = $_GET['role'] ?? 'All';
$allowed    = ['All', 'admin', 'user'];
if (!in_array($roleFilter, $allowed)) $roleFilter = 'All';

$where  = $roleFilter !== 'All' ? 'WHERE role = ?' : '';
$params = $roleFilter !== 'All' ? [$roleFilter] : [];

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users {$where}");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT id, role, full_name, email, contact_number, birthdate, is_active, created_at
    FROM users {$where}
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$users = $stmt->fetchAll();

$selfId = $_SESSION['ojams_user']['id'];

include $basePath . 'layouts/header.php';
include $basePath . 'layouts/navbar-admin.php';
?>
<div class="admin-layout">
    <?php include $basePath . 'layouts/sidebar-admin.php'; ?>
    <main class="admin-main">

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-people-fill me-2 text-primary"></i>User Management
                </h2>
                <p class="text-muted mb-0">View, manage roles, and deactivate user accounts.
                    <span class="small">(<?php echo $total; ?> total)</span>
                </p>
            </div>
            <div class="btn-group" role="group">
                <?php foreach (['All', 'admin', 'user'] as $r):
                    $active  = $roleFilter === $r ? 'active' : '';
                    $variant = $r === 'admin' ? 'danger' : ($r === 'user' ? 'primary' : 'secondary');
                ?>
                <a href="?role=<?php echo $r; ?>"
                   class="btn btn-outline-<?php echo $variant; ?> btn-sm <?php echo $active; ?>">
                    <?php echo ucfirst($r); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <?php
                        $columns = ['#', 'Name', 'Email', 'Contact', 'Role', 'Status', 'Joined', 'Actions'];
                        include $basePath . 'components/table-header.php';
                        ?>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bi bi-people display-5 text-muted d-block mb-3"></i>
                                        <p class="text-muted mb-0">No users match the current filter. Try clearing the search or changing the role filter.</p>
                                    </td>
                                </tr>
                            <?php else: $rowNum = $offset + 1; foreach ($users as $u):
                                $isSelf   = ((int)$u['id'] === $selfId);
                                $isActive = (int)($u['is_active'] ?? 1);
                            ?>
                                <tr class="<?php echo !$isActive ? 'table-secondary text-muted' : ''; ?>">
                                    <td><?php echo $rowNum++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                        <?php if ($isSelf): ?>
                                            <span class="badge bg-info ms-1" title="This is you">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['contact_number'] ?? '—'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $isActive ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($u['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <!-- View Details -->
                                        <button class="btn btn-sm btn-outline-primary me-1"
                                            onclick="viewUser(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES); ?>)"
                                            data-bs-toggle="modal" data-bs-target="#viewUserModal"
                                            title="View details">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <?php if (!$isSelf): ?>
                                        <!-- Toggle Role -->
                                        <button class="btn btn-sm btn-outline-warning me-1"
                                            onclick="changeRole(<?php echo $u['id']; ?>, '<?php echo $u['role'] === 'admin' ? 'user' : 'admin'; ?>', '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>')"
                                            title="Change to <?php echo $u['role'] === 'admin' ? 'User' : 'Admin'; ?>">
                                            <i class="bi bi-arrow-left-right"></i>
                                            <?php echo $u['role'] === 'admin' ? 'Make User' : 'Make Admin'; ?>
                                        </button>

                                        <!-- Deactivate / Reactivate -->
                                        <?php if ($isActive): ?>
                                        <button class="btn btn-sm btn-outline-danger"
                                            onclick="toggleUserStatus(<?php echo $u['id']; ?>, 'deactivate', '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>')"
                                            title="Deactivate account">
                                            <i class="bi bi-person-slash"></i> Deactivate
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-outline-success"
                                            onclick="toggleUserStatus(<?php echo $u['id']; ?>, 'reactivate', '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>')"
                                            title="Reactivate account">
                                            <i class="bi bi-person-check"></i> Reactivate
                                        </button>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <span class="text-muted small fst-italic">Your account</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $perPage, $total); ?> of <?php echo $total; ?>
                </small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?role=<?php echo $roleFilter; ?>&page=<?php echo $page - 1; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++):
                            if (!($p === 1 || $p === $totalPages || abs($p - $page) <= 2)) continue;
                        ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?role=<?php echo $roleFilter; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?role=<?php echo $roleFilter; ?>&page=<?php echo $page + 1; ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- ── View User Modal ──────────────────────────────────────── -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-lines-fill me-2"></i>User Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 text-center mb-2">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold"
                             style="width:64px;height:64px;font-size:1.5rem;" id="vuInitials">--</div>
                        <h6 class="mt-2 mb-0 fw-bold" id="vuName">—</h6>
                        <span id="vuRoleBadge" class="badge mt-1">—</span>
                        <span id="vuStatusBadge" class="badge ms-1 mt-1">—</span>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Email</div>
                        <div class="fw-semibold" id="vuEmail">—</div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Contact</div>
                        <div class="fw-semibold" id="vuContact">—</div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Birthdate</div>
                        <div class="fw-semibold" id="vuBirthdate">—</div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Date Joined</div>
                        <div class="fw-semibold" id="vuJoined">—</div>
                    </div>
                    <div class="col-12">
                        <div class="small text-muted">User ID</div>
                        <div class="fw-semibold text-muted" id="vuId">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const ADMIN_HANDLER = '../../handlers/admin.php';

function viewUser(u) {
    const initials = (u.full_name || '?').split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
    document.getElementById('vuInitials').textContent  = initials;
    document.getElementById('vuName').textContent      = u.full_name  || '—';
    document.getElementById('vuEmail').textContent     = u.email      || '—';
    document.getElementById('vuContact').textContent   = u.contact_number || '—';
    document.getElementById('vuBirthdate').textContent = u.birthdate  || '—';
    document.getElementById('vuJoined').textContent    = u.created_at ? u.created_at.substring(0,10) : '—';
    document.getElementById('vuId').textContent        = '#' + u.id;
    const roleBadge = document.getElementById('vuRoleBadge');
    roleBadge.textContent  = u.role.charAt(0).toUpperCase() + u.role.slice(1);
    roleBadge.className    = 'badge mt-1 ' + (u.role === 'admin' ? 'bg-danger' : 'bg-primary');
    const statusBadge = document.getElementById('vuStatusBadge');
    const active = parseInt(u.is_active ?? 1);
    statusBadge.textContent = active ? 'Active' : 'Inactive';
    statusBadge.className   = 'badge ms-1 mt-1 ' + (active ? 'bg-success' : 'bg-secondary');
}

function toggleUserStatus(id, action, name) {
    const label = action === 'deactivate' ? 'deactivate' : 'reactivate';
    if (!confirm(`Are you sure you want to ${label} ${name}'s account?`)) return;
    const apiAction = action === 'deactivate' ? 'deactivateUser' : 'reactivateUser';
    fetch(ADMIN_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: apiAction, id: id, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 900);
    })
    .catch(() => showToast('Request failed.', 'danger'));
}

function changeRole(id, newRole, name) {
    if (!confirm(`Change ${name}'s role to ${newRole}?`)) return;
    fetch(ADMIN_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'changeRole', id: id, role: newRole, csrf_token: getCsrfToken() })
    })
    .then(r => r.json())
    .then(res => {
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 900);
    })
    .catch(() => showToast('Request failed.', 'danger'));
}
</script>
<?php include $basePath . 'layouts/footer.php'; ?>
