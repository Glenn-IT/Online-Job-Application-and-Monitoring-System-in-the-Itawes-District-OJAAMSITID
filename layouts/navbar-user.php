<!-- ============================================
     User Navigation Bar
     ============================================ -->
<nav class="navbar navbar-expand-lg ojams-navbar user-bar sticky-top">
    <div class="container">

        <!-- Brand -->
        <a class="navbar-brand" href="<?php echo $basePath ?? ''; ?>index.php">
            <i class="bi bi-briefcase-fill me-2"></i>OJAMS
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#userNavbar"
                aria-controls="userNavbar" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="userNavbar">

            <!-- Main links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'browse-jobs' ? 'active' : ''; ?>"
                       href="<?php echo $basePath ?? ''; ?>pages/user/browse-jobs.php">
                        <i class="bi bi-search me-1"></i>Browse Jobs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'my-applications' ? 'active' : ''; ?>"
                       href="<?php echo $basePath ?? ''; ?>pages/user/my-applications.php">
                        <i class="bi bi-file-earmark-text me-1"></i>My Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage ?? '') === 'saved-jobs' ? 'active' : ''; ?>"
                       href="<?php echo $basePath ?? ''; ?>pages/user/saved-jobs.php">
                        <i class="bi bi-bookmark-heart me-1"></i>Saved Jobs
                    </a>
                </li>
            </ul>

            <!-- Right: user dropdown -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['ojams_user']['full_name'] ?? 'User') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text text-muted" style="font-size:0.75rem;">
                                <?= htmlspecialchars($_SESSION['ojams_user']['email'] ?? '') ?>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item <?php echo ($currentPage ?? '') === 'profile-settings' ? 'active' : ''; ?>"
                               href="<?php echo $basePath ?? ''; ?>pages/user/profile-settings.php">
                                <i class="bi bi-gear me-2"></i>Profile Settings
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
<?php include ($basePath ?? '') . 'modals/logout-modal.php'; ?>
