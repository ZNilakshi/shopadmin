<?php
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$inProducts  = $currentDir === 'products';

function sidebarLink(string $href, string $icon, string $label, bool $active, string $badge = ''): void {
    $cls = $active ? 'nav-item active' : 'nav-item';
    echo "<a href=\"{$href}\" class=\"{$cls}\">";
    echo "<i class=\"bi bi-{$icon}\"></i>";
    echo "<span>{$label}</span>";
    if ($badge) echo "<span class=\"nav-badge\">{$badge}</span>";
    echo "</a>";
}

$pic = $_SESSION['user_picture'] ?? null;
$initials = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
?>
<div class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-shop-window"></i></div>
        <span class="brand-name">ShopAdmin</span>
        <button class="sidebar-close d-xl-none" id="sidebarClose">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>

        <?php sidebarLink(BASE_URL.'/dashboard.php', 'grid-1x2-fill', 'Dashboard',
            $currentFile === 'dashboard.php'); ?>

        <div class="nav-section-label">Inventory</div>

        <?php sidebarLink(BASE_URL.'/products/list.php', 'box-seam-fill', 'All Products',
            $inProducts && $currentFile === 'list.php'); ?>

        <?php sidebarLink(BASE_URL.'/products/add.php', 'plus-circle-fill', 'Add Product',
            $inProducts && $currentFile === 'add.php'); ?>

        <div class="nav-section-label">Account</div>

        <?php sidebarLink(BASE_URL.'/profile.php', 'person-circle', 'My Profile',
            $currentFile === 'profile.php'); ?>

        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
        <div class="nav-section-label">Admin</div>
        <a href="<?= BASE_URL ?>/api/products.php?action=export" class="nav-item" target="_blank">
            <i class="bi bi-file-earmark-spreadsheet"></i>
            <span>Export CSV</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- User footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <?php if ($pic): ?>
                <img src="<?= BASE_URL ?>/uploads/profiles/<?= htmlspecialchars($pic) ?>"
                     alt="Avatar" class="user-avatar">
            <?php else: ?>
                <div class="user-avatar-placeholder"><?= $initials ?></div>
            <?php endif; ?>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                <div class="user-role badge-role"><?= ucfirst($_SESSION['user_role'] ?? 'user') ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="logout-btn" title="Log out">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
