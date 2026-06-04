<?php
$adminName = htmlspecialchars($_SESSION['ojams_user']['full_name'] ?? 'Admin');
$initials  = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $adminName), 0, 2))));

$links = [
    ['page' => 'dashboard',       'icon' => 'bi-speedometer2',        'label' => 'Dashboard',        'href' => 'pages/admin/dashboard.php'],
    ['page' => 'manage-jobs',     'icon' => 'bi-kanban',              'label' => 'Manage Jobs',       'href' => 'pages/admin/manage-jobs.php'],
    ['page' => 'applications',    'icon' => 'bi-file-earmark-person', 'label' => 'Applications',     'href' => 'pages/admin/applications.php'],
    ['page' => 'reports',         'icon' => 'bi-graph-up',            'label' => 'Reports',          'href' => 'pages/admin/reports.php'],
    ['page' => 'profile-settings','icon' => 'bi-person-gear',         'label' => 'Profile Settings', 'href' => 'pages/admin/profile-settings.php'],
];

function renderSidebarLinks(array $links, string $basePath, string $currentPage): string {
    $html = '';
    foreach ($links as $link) {
        $active = ($currentPage === $link['page']) ? ' active' : '';
        $html .= '<li class="nav-item">'
               . '<a class="nav-link' . $active . '" href="' . $basePath . $link['href'] . '">'
               . '<i class="bi ' . $link['icon'] . '"></i>' . htmlspecialchars($link['label'])
               . '</a></li>';
    }
    return $html;
}

$bp          = $basePath ?? '';
$currentPage = $currentPage ?? '';
$navLinksHtml = renderSidebarLinks($links, $bp, $currentPage);
?>

<!-- ════════════════════════════════════════════
     DESKTOP SIDEBAR (hidden on mobile)
     ════════════════════════════════════════════ -->
<div class="sidebar-wrapper d-none d-lg-flex">
    <!-- User Info -->
    <div class="sidebar-user-info">
        <div class="sidebar-avatar"><?= $initials ?></div>
        <div>
            <div class="user-name"><?= $adminName ?></div>
            <div class="user-role">Administrator</div>
        </div>
    </div>

    <!-- Nav label -->
    <div class="sidebar-label">Main Menu</div>

    <!-- Navigation -->
    <ul class="sidebar-nav">
        <?= $navLinksHtml ?>
    </ul>

    <!-- Footer -->
    <div class="sidebar-footer">
        <span class="sidebar-version">OJAMS Admin &bull; v1.0</span>
    </div>
</div>


<!-- ════════════════════════════════════════════
     MOBILE OFFCANVAS SIDEBAR (hidden on desktop)
     ════════════════════════════════════════════ -->
<div class="offcanvas sidebar-offcanvas d-lg-none"
     tabindex="-1"
     id="adminSidebarOffcanvas"
     aria-labelledby="adminSidebarOffcanvasLabel">

    <div class="offcanvas-header">
        <span class="offcanvas-title" id="adminSidebarOffcanvasLabel">
            <i class="bi bi-briefcase-fill me-2"></i>OJAMS
        </span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <!-- User Info -->
        <div class="sidebar-user-info mb-3">
            <div class="sidebar-avatar"><?= $initials ?></div>
            <div>
                <div class="user-name"><?= $adminName ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>

        <div class="sidebar-label">Main Menu</div>

        <ul class="sidebar-nav">
            <?= $navLinksHtml ?>
        </ul>

        <div class="sidebar-footer mt-3">
            <span class="sidebar-version">OJAMS Admin &bull; v1.0</span>
        </div>
    </div>
</div>
