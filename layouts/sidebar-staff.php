<?php
$staffName = htmlspecialchars($_SESSION['ojams_user']['full_name'] ?? 'Staff');
$initials  = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $staffName), 0, 2))));

$links = [
    ['page' => 'dashboard',        'icon' => 'bi-speedometer2',        'label' => 'Dashboard',        'href' => 'pages/staff/dashboard.php'],
    ['page' => 'manage-jobs',      'icon' => 'bi-kanban',              'label' => 'Manage Jobs',      'href' => 'pages/staff/manage-jobs.php'],
    ['page' => 'applications',     'icon' => 'bi-file-earmark-person', 'label' => 'Applications',     'href' => 'pages/staff/applications.php'],
    ['page' => 'profile-settings', 'icon' => 'bi-person-gear',         'label' => 'Profile Settings', 'href' => 'pages/staff/profile-settings.php'],
];

function renderStaffSidebarLinks(array $links, string $basePath, string $currentPage): string {
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
$navLinksHtml = renderStaffSidebarLinks($links, $bp, $currentPage);
?>

<!-- ════════════════════════════════════════════
     STAFF SIDEBAR
     ════════════════════════════════════════════ -->
<div class="sidebar-wrapper">
    <!-- User Info -->
    <div class="sidebar-user-info">
        <div class="sidebar-avatar bg-warning text-dark"><?= $initials ?></div>
        <div>
            <div class="user-name"><?= $staffName ?></div>
            <div class="user-role">Staff Officer</div>
        </div>
    </div>

    <!-- Nav label -->
    <div class="sidebar-label">Staff Menu</div>

    <!-- Navigation -->
    <ul class="sidebar-nav">
        <?= $navLinksHtml ?>
    </ul>

    <!-- Footer -->
    <div class="sidebar-footer">
        <span class="sidebar-version">OJAMS Staff &bull; v1.0</span>
    </div>
</div>
