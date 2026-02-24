<?php
// navbar.php
// Ensure session is started in the calling page
?>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="logo">Org<span>Hub</span></div>
    <button class="close-sidebar" id="closeSidebar"><i class="fas fa-times"></i></button>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : ''; ?>">
        <a href="members.php"><i class="fas fa-users"></i> Members</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'document_tracking.php' ? 'active' : ''; ?>">
        <a href="document_tracking.php"><i class="fas fa-file-alt"></i> Document Tracking</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
        <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
      </li>
      <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'archive.php' ? 'active' : ''; ?>">
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
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


    <!-- Page Content (to be filled by each page) -->
    <main class="main-content">