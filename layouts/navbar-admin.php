<!-- ============================================
     Admin Navigation Bar
     ============================================ -->
<nav class="navbar ojams-navbar admin-bar sticky-top">
    <div class="container-fluid d-flex align-items-center gap-2">

        <!-- Mobile: sidebar toggle -->
        <button class="sidebar-toggle-btn d-lg-none"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#adminSidebarOffcanvas"
                aria-controls="adminSidebarOffcanvas"
                title="Open navigation">
            <i class="bi bi-list"></i>
        </button>

        <!-- Brand -->
        <a class="navbar-brand me-auto" href="<?php echo $basePath ?? ''; ?>pages/admin/dashboard.php">
            <i class="bi bi-briefcase-fill me-2"></i>OJAMS
            <span class="brand-badge ms-1">ADMIN</span>
        </a>

        <!-- Admin user dropdown -->
        <div class="dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
               href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="d-none d-sm-inline text-white-50" style="font-size:0.78rem;">
                    <i class="bi bi-shield-lock-fill me-1"></i><?= htmlspecialchars($_SESSION['ojams_user']['full_name'] ?? 'Administrator') ?>
                </span>
                <i class="bi bi-person-circle d-sm-none" style="font-size:1.2rem;color:#fff;"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <span class="dropdown-item-text text-muted" style="font-size:0.75rem;">
                        Signed in as <strong><?= htmlspecialchars($_SESSION['ojams_user']['full_name'] ?? '') ?></strong>
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="<?php echo $basePath ?? ''; ?>pages/admin/profile-settings.php">
                        <i class="bi bi-person-gear me-2"></i>Profile Settings
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>

    </div>
</nav>
<?php include ($basePath ?? '') . 'modals/logout-modal.php'; ?>
