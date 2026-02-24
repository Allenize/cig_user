<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Announcements · Organization</title>
  <!-- Google Fonts - Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Shared styles -->
  <link rel="stylesheet" href="shared.css">
  <!-- Announcements specific styles -->
  <link rel="stylesheet" href="announcements.css">
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="logo">Org<span>Hub</span></div>
        <button class="close-sidebar" id="closeSidebar"><i class="fas fa-times"></i></button>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li><a href="members.php"><i class="fas fa-users"></i> Members</a></li>
          <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
          <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
          <li><a href="documents.php"><i class="fas fa-folder"></i> Documents</a></li>
          <li class="active"><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
          <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
          <li class="logout"><a href="logout.php" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </nav>
      <div class="sidebar-footer">
        <p>© 2026 OrgHub</p>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <header class="top-bar">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <div class="user-info">
          <span class="user-name">John Doe</span>
          <img src="https://placehold.co/40x40/2d3748/white?text=JD" alt="User avatar" class="avatar">
        </div>
      </header>

      <div class="content">
        <div class="page-header">
          <h1>Announcements</h1>
          <!-- Optional filter toggle could be added here -->
        </div>

        <!-- Announcements Grid -->
        <div class="announcements-grid" id="announcementsGrid">
          <!-- Cards injected by JavaScript -->
        </div>
      </div>
    </main>
  </div>

  <!-- Overlay for mobile sidebar -->
  <div class="overlay" id="overlay"></div>

  <!-- Shared sidebar toggle script -->
  <script src="shared.js"></script>
  <!-- Announcements specific script -->
  <script src="announcements.js"></script>
</body>
</html>