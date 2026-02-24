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
  <title>Members Management · Organization</title>
  <!-- Google Fonts - Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Shared styles -->
  <link rel="stylesheet" href="shared.css">
  <!-- Members specific styles -->
  <link rel="stylesheet" href="members.css">
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
          <li class="active"><a href="members.php"><i class="fas fa-users"></i> Members</a></li>
          <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
          <li ><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
          <li><a href="documents.php"><i class="fas fa-folder"></i> Documents</a></li>
          <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
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
              <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
          <img src="https://placehold.co/40x40/2d3748/white?text=JD" alt="User avatar" class="avatar">
        </div>
      </header>

      <div class="content">
        <div class="page-header">
          <h1>Members Management</h1>
          <button class="btn btn-primary" id="addMemberBtn"><i class="fas fa-plus"></i> Add Member</button>
        </div>

        <!-- Filters & Export -->
        <div class="filters-row">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search members...">
          </div>
          <div class="filter-group">
            <select id="filterPosition">
              <option value="">All Positions</option>
              <option value="Manager">Manager</option>
              <option value="Developer">Developer</option>
              <option value="Designer">Designer</option>
              <option value="Sales">Sales</option>
            </select>
            <select id="filterStatus">
              <option value="">All Status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Pending">Pending</option>
            </select>
          </div>
          <div class="export-buttons">
            <button class="btn btn-outline" id="exportExcel"><i class="fas fa-file-excel"></i> Excel</button>
            <button class="btn btn-outline" id="exportPDF"><i class="fas fa-file-pdf"></i> PDF</button>
          </div>
        </div>

        <!-- Members Table -->
        <div class="table-container">
          <table id="membersTable">
            <thead>
              <tr>
                <th>Name</th>
                <th>Position</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="tableBody">
              <!-- Data injected by JavaScript -->
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Overlay for mobile sidebar -->
  <div class="overlay" id="overlay"></div>

  <!-- Add/Edit Member Modal -->
  <div class="modal" id="memberModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Add Member</h2>
        <span class="close-modal" id="closeModal">&times;</span>
      </div>
      <form id="memberForm">
        <input type="hidden" id="memberId">
        <div class="form-group">
          <label for="name">Name *</label>
          <input type="text" id="name" required>
        </div>
        <div class="form-group">
          <label for="position">Position *</label>
          <select id="position" required>
            <option value="">Select Position</option>
            <option value="Manager">Manager</option>
            <option value="Developer">Developer</option>
            <option value="Designer">Designer</option>
            <option value="Sales">Sales</option>
          </select>
        </div>
        <div class="form-group">
          <label for="contact">Contact Number</label>
          <input type="tel" id="contact" placeholder="+1 234 567 890">
        </div>
        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" required>
        </div>
        <div class="form-group">
          <label for="status">Status</label>
          <select id="status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Pending">Pending</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="submit" class="btn btn-primary" id="saveMember">Save</button>
          <button type="button" class="btn btn-outline" id="cancelModal">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Shared sidebar toggle script -->
  <script src="shared.js"></script>
  <!-- Members specific script -->
  <script src="members.js"></script>
</body>
</html>