<?php
// topbar.php
// Opens its own DB connection — never relies on the parent page's $conn.

$_tb_user = [];
if (isset($_SESSION['user_id'])) {
    $_tb_id   = (int) $_SESSION['user_id'];
    $_tb_conn = @mysqli_connect("localhost", "root", "", "cig_system");
    if ($_tb_conn) {
        $_tb_r = mysqli_query($_tb_conn, "SELECT org_name, org_code, logo_path FROM users WHERE user_id = $_tb_id LIMIT 1");
        if ($_tb_r) $_tb_user = mysqli_fetch_assoc($_tb_r) ?: [];
        mysqli_close($_tb_conn);
    }
}

$_tb_org_code  = htmlspecialchars($_tb_user['org_code'] ?? ($_SESSION['org_code'] ?? 'Admin'));
$_tb_org_name  = htmlspecialchars($_tb_user['org_name'] ?? ($_SESSION['org_name'] ?? ''));
$_tb_logo_path = $_tb_user['logo_path'] ?? null;
$_tb_initials  = strtoupper(substr($_tb_user['org_name'] ?? $_tb_org_code, 0, 2));

$_tb_logo_url = null;
if ($_tb_logo_path) {
    $abs = dirname(dirname(__DIR__)) . '/' . ltrim($_tb_logo_path, './');
    if (file_exists($abs)) {
        $_tb_logo_url = '../../' . ltrim($_tb_logo_path, './');
    }
}
?>
<header class="top-bar">

    <div class="user-info">

        <!-- Notification Bell -->
        <div class="notification-bell" id="notificationBell">
            <i class="fas fa-bell"></i>
            <span class="badge" id="notificationBadge">3</span>
        </div>

        <!-- Notification Dropdown Panel -->
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="dropdown-header">
                <h3>Notifications</h3>
                <button class="mark-read" id="markAllRead">Mark all as read</button>
            </div>
            <div class="notification-list" id="notificationList">
                <!-- Notifications inserted by JavaScript -->
            </div>
            <div class="dropdown-footer">
                <a href="notifications.php">View all notifications</a>
            </div>
        </div>

        <!-- Org Code label -->
        <span class="user-name" id="topbarOrgCode"><?= $_tb_org_code ?></span>

        <!-- Avatar: links to settings.php, shows logo or initials -->
        <a href="settings.php"
           class="avatar-link"
           id="topbarAvatarLink"
           title="<?= $_tb_org_name ?> · Go to Settings">
            <?php if ($_tb_logo_url): ?>
                <img src="<?= htmlspecialchars($_tb_logo_url) ?>"
                     alt="<?= $_tb_org_name ?>"
                     class="avatar"
                     id="topbarAvatarImg">
            <?php else: ?>
                <div class="avatar avatar-initials" id="topbarAvatarInitials">
                    <?= $_tb_initials ?>
                </div>
            <?php endif; ?>
        </a>
    </div>
</header>

<!-- Notification Details Modal -->
<div id="notificationModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" id="closeNotificationModal">&times;</span>
        <h2><i class="fas fa-bell"></i> Notification Details</h2>
        <div id="notificationModalBody" class="modal-body">
            <!-- Dynamic content inserted here -->
        </div>
    </div>
</div>

<!-- NOTE: notifications.js is loaded by the parent page, not here -->