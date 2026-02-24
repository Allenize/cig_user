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
  <title>Reports · Organization</title>
  <!-- Google Fonts - Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Shared styles -->
  <link rel="stylesheet" href="shared.css">
  <!-- Reports specific styles -->
  <link rel="stylesheet" href="reports.css">
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
          <li class="active"><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
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
          <h1>Reports Submission</h1>
          <button class="btn btn-primary" id="uploadReportBtn"><i class="fas fa-upload"></i> Upload Report</button>
        </div>

        <!-- Previous Submissions Table -->
        <div class="table-container">
          <table id="reportsTable">
            <thead>
              <tr>
                <th>Report Title</th>
                <th>Event</th>
                <th>Submitted Date</th>
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

  <!-- Upload/Edit Report Modal -->
  <div class="modal" id="reportModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Upload Report</h2>
        <span class="close-modal" id="closeModal">&times;</span>
      </div>
      <form id="reportForm">
        <input type="hidden" id="reportId">
        <div class="form-group">
          <label for="title">Report Title *</label>
          <input type="text" id="title" required>
        </div>
        <div class="form-group">
          <label for="event">Event Reference *</label>
          <select id="event" required>
            <option value="">Select Event</option>
            <!-- Options will be populated by JavaScript -->
          </select>
        </div>
        <div class="form-group">
          <label for="file">File (PDF/DOC) *</label>
          <input type="file" id="file" accept=".pdf,.doc,.docx" required>
          <small class="file-hint">Max 10MB</small>
        </div>
        <div class="form-group">
          <label for="status">Status</label>
          <select id="status">
            <option value="Submitted">Submitted</option>
            <option value="Under Review">Under Review</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="submit" class="btn btn-primary">Save Report</button>
          <button type="button" class="btn btn-outline" id="cancelModal">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Shared sidebar toggle script -->
  <script src="shared.js"></script>
  <!-- Reports specific script -->
  <script src="reports.js"></script>
</body>
</html>