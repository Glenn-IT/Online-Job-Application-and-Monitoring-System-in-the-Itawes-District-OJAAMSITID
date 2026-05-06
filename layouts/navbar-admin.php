<!-- ============================================
     Admin Navigation Bar (Top Bar)
     ============================================ -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="<?php echo $basePath ?? ''; ?>pages/admin/dashboard.php">
            <i class="bi bi-briefcase-fill me-2"></i>OJAMS <small class="text-warning">Admin</small>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav Links -->
        <div class="collapse navbar-collapse" id="adminNavbar">
            <!-- Spacer -->
            <ul class="navbar-nav me-auto"></ul>

            <!-- Right Side: Admin Info & Logout -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-lock-fill me-1"></i><?= htmlspecialchars($_SESSION['ojams_user']['full_name'] ?? 'Administrator') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
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
                </li>
            </ul>
        </div>
    </div>
</nav>
