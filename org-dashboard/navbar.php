<?php
// Ensure session is already started in the calling page
if (!isset($_SESSION['user_id'])) {
    // If somehow called without a valid session, redirect
    header("Location: /index.php");
    exit();
}
?>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="logo">Org<span>Hub</span></div>
    <button class="close-sidebar" id="closeSidebar"><i class="fas fa-times"></i></button>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <!-- Use a helper function or inline check to set 'active' class -->
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : ''; ?>">
        <a href="members.php"><i class="fas fa-users"></i> Members</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>">
        <a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
        <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : ''; ?>">
        <a href="documents.php"><i class="fas fa-folder"></i> Documents</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
        <a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
      </li>
      <li class="logout"><a href="logout.php" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </nav>
  <div class="sidebar-footer">
    <p>© 2026 OrgHub</p>
  </div>
</aside>

<!-- Main Content (opening tag) -->
<main class="main-content">
  <header class="top-bar">
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
    <div class="user-info">
      <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
      <img src="https://placehold.co/40x40/2d3748/white?text=JD" alt="User avatar" class="avatar">
    </div>
  </header>