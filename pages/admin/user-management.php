<?php
require_once __DIR__ . '/../../config/auth.php';
requireAdmin();

$pageTitle   = 'OJAMS - User Management';
$basePath    = '../../';
$currentPage = 'user-management';

// ── Pagination ───────────────────────────────────────────────
$perPage    = PER_PAGE_ADMIN;
$page       = max(1, (int)($_GET['page'] ?? 1));
$roleFilter = $_GET['role'] ?? 'All';
$allowed    = ['All', 'admin', 'staff', 'user'];
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
    SELECT id, role, full_name, email, contact_number, birthdate, is_active, is_approved, created_at
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
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group" role="group">
                    <?php foreach (['All', 'admin', 'staff', 'user'] as $r):
                        $active  = $roleFilter === $r ? 'active' : '';
                        $variant = $r === 'admin' ? 'danger' : ($r === 'staff' ? 'warning' : ($r === 'user' ? 'primary' : 'secondary'));
                    ?>
                    <a href="?role=<?php echo $r; ?>"
                       class="btn btn-outline-<?php echo $variant; ?> btn-sm <?php echo $active; ?>">
                        <?php echo ucfirst($r); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill me-1"></i>Register Account
                </button>
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
                                $isSelf     = ((int)$u['id'] === $selfId);
                                $isActive   = (int)($u['is_active'] ?? 1);
                                $isApproved = (int)($u['is_approved'] ?? 1);
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
                                        <span class="badge <?php echo $u['role'] === 'admin' ? 'bg-danger' : ($u['role'] === 'staff' ? 'bg-warning text-dark' : 'bg-primary'); ?>">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!$isApproved): ?>
                                        <span class="badge bg-warning text-dark">Pending Approval</span>
                                        <?php else: ?>
                                        <span class="badge <?php echo $isActive ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <?php endif; ?>
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
                                        <?php if (!$isApproved): ?>
                                        <!-- Approve pending staff account -->
                                        <button class="btn btn-sm btn-success me-1"
                                            onclick="approveUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>')"
                                            title="Approve this account so they can log in">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </button>
                                        <?php endif; ?>

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

<!-- ── Register Account Modal (admin-created user/staff/admin) ─ -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="addUserForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Register Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="addUserAlert" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label class="form-label d-block">Account Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="au_role" id="auRoleUser" value="user" checked>
                                <label class="btn btn-outline-primary w-100 btn-sm" for="auRoleUser">Applicant</label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="au_role" id="auRoleStaff" value="staff">
                                <label class="btn btn-outline-warning w-100 btn-sm" for="auRoleStaff">Staff</label>
                            </div>
                        </div>
                        <div class="form-text text-muted">Accounts created here are approved and active immediately.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="auFullName">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="auFullName" placeholder="Full name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="auEmail">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="auEmail" placeholder="name@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="auContact">Contact Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="auContact" placeholder="e.g. 09171234567"
                               inputmode="numeric" maxlength="11" pattern="\d{11}"
                               oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="auPassword">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="auPassword" minlength="8"
                                   placeholder="Min 8 chars, uppercase, number, symbol" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAuPass('auPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="auConfirm">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="auConfirm" placeholder="Repeat password" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAuPass('auConfirm', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="auSecQuestion">Security Question <span class="text-danger">*</span></label>
                        <select class="form-select" id="auSecQuestion" required>
                            <option value="" disabled selected>Choose a question…</option>
                            <?php foreach (SECURITY_QUESTIONS as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label" for="auSecAnswer">Security Answer <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="auSecAnswer" maxlength="150" autocomplete="off" placeholder="Answer" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i>Create Account
                    </button>
                </div>
            </form>
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
    roleBadge.className    = 'badge mt-1 ' + (u.role === 'admin' ? 'bg-danger' : (u.role === 'staff' ? 'bg-warning text-dark' : 'bg-primary'));
    const statusBadge = document.getElementById('vuStatusBadge');
    const active   = parseInt(u.is_active ?? 1);
    const approved = parseInt(u.is_approved ?? 1);
    if (!approved) {
        statusBadge.textContent = 'Pending Approval';
        statusBadge.className   = 'badge ms-1 mt-1 bg-warning text-dark';
    } else {
        statusBadge.textContent = active ? 'Active' : 'Inactive';
        statusBadge.className   = 'badge ms-1 mt-1 ' + (active ? 'bg-success' : 'bg-secondary');
    }
}

function approveUser(id, name) {
    showConfirmModal({
        title: "Approve Account",
        message: `Approve ${name}'s account? They will be able to log in immediately.`,
        confirmBtnText: "Yes, Approve Account",
        confirmBtnClass: "btn-success",
        icon: "bi-check-circle-fill",
        onConfirm: () => {
            fetch(ADMIN_HANDLER, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'approveUser', id: id, csrf_token: getCsrfToken() })
            })
            .then(r => r.json())
            .then(res => {
                showToast(res.message, res.success ? 'success' : 'danger');
                if (res.success) setTimeout(() => location.reload(), 900);
            })
            .catch(() => showToast('Request failed.', 'danger'));
        }
    });
}

function toggleUserStatus(id, action, name) {
    const isDeactivate = action === 'deactivate';
    const label = isDeactivate ? 'deactivate' : 'reactivate';
    const apiAction = isDeactivate ? 'deactivateUser' : 'reactivateUser';
    showConfirmModal({
        title: isDeactivate ? "Deactivate Account" : "Reactivate Account",
        message: `Are you sure you want to ${label} ${name}'s account?`,
        confirmBtnText: isDeactivate ? "Yes, Deactivate" : "Yes, Reactivate",
        confirmBtnClass: isDeactivate ? "btn-danger" : "btn-success",
        icon: isDeactivate ? "bi-person-slash" : "bi-person-check",
        onConfirm: () => {
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
    });
}

function toggleAuPass(id, btn) {
    const inp  = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') { inp.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else                         { inp.type = 'password'; icon.className = 'bi bi-eye'; }
}

document.getElementById('addUserForm')?.addEventListener('submit', function (e) {
    e.preventDefault();

    const alertBox = document.getElementById('addUserAlert');
    alertBox.classList.add('d-none');

    const payload = {
        action: 'addUser',
        role: document.querySelector('input[name="au_role"]:checked')?.value || 'user',
        full_name: document.getElementById('auFullName').value.trim(),
        email: document.getElementById('auEmail').value.trim(),
        contact: document.getElementById('auContact').value.trim(),
        password: document.getElementById('auPassword').value,
        confirm: document.getElementById('auConfirm').value,
        security_question: document.getElementById('auSecQuestion').value,
        security_answer: document.getElementById('auSecAnswer').value.trim(),
        csrf_token: getCsrfToken()
    };

    if (payload.password !== payload.confirm) {
        alertBox.textContent = 'Passwords do not match.';
        alertBox.classList.remove('d-none');
        showToast('Passwords do not match.', 'danger');
        return;
    }

    const btn = this.querySelector('[type="submit"]');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Creating…';

    fetch(ADMIN_HANDLER, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message, 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            alertBox.textContent = res.message;
            alertBox.classList.remove('d-none');
            showToast(res.message || 'Failed to create account.', 'danger');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(() => {
        alertBox.textContent = 'Request failed.';
        alertBox.classList.remove('d-none');
        showToast('Request failed. Please try again.', 'danger');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
});
</script>
<?php include $basePath . 'layouts/footer.php'; ?>
