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
  <title>Settings · Organization</title>
  <!-- Google Fonts - Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Shared styles -->
  <link rel="stylesheet" href="shared.css">
  <!-- Settings specific styles -->
  <link rel="stylesheet" href="settings.css">
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
          <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
          <li class="active"><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
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
          <h1>Organization Settings</h1>
        </div>

        <!-- Settings Form -->
        <form id="settingsForm">
          <!-- Organization Profile Section -->
          <section class="settings-section">
            <h2><i class="fas fa-building"></i> Organization Profile</h2>
            <div class="section-content">
              <!-- Logo Upload with Preview -->
              <div class="form-row">
                <div class="form-group logo-group">
                  <label for="orgLogo">Organization Logo</label>
                  <div class="logo-upload">
                    <div class="logo-preview" id="logoPreview">
                      <img id="logoImage" src="https://placehold.co/120x120/2d3748/white?text=Logo" alt="Logo preview">
                    </div>
                    <div class="upload-controls">
                      <input type="file" id="orgLogo" accept="image/*">
                      <p class="file-hint">Recommended: 200x200px, PNG or JPG</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Description -->
              <div class="form-row">
                <div class="form-group">
                  <label for="orgDescription">Organization Description</label>
                  <textarea id="orgDescription" rows="4" placeholder="Brief description of your organization...">Acme Corporation is a leading provider of innovative solutions.</textarea>
                </div>
              </div>

              <!-- Contact Information -->
              <div class="form-row">
                <div class="form-group">
                  <label for="orgEmail">Email Address</label>
                  <input type="email" id="orgEmail" value="contact@acme.org">
                </div>
                <div class="form-group">
                  <label for="orgPhone">Phone Number</label>
                  <input type="tel" id="orgPhone" value="+1 555 123 4567">
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="orgAddress">Address</label>
                  <input type="text" id="orgAddress" value="123 Business Ave, Suite 100">
                </div>
                <div class="form-group">
                  <label for="orgWebsite">Website</label>
                  <input type="url" id="orgWebsite" value="https://www.acme.org">
                </div>
              </div>

              <!-- Adviser Name -->
              <div class="form-row">
                <div class="form-group">
                  <label for="adviserName">Adviser Name</label>
                  <input type="text" id="adviserName" value="Dr. Sarah Johnson">
                </div>
              </div>
            </div>
          </section>

          <!-- Security Section -->
          <section class="settings-section">
            <h2><i class="fas fa-lock"></i> Security</h2>
            <div class="section-content">
              <div class="form-row">
                <div class="form-group">
                  <label for="currentPassword">Current Password</label>
                  <input type="password" id="currentPassword" placeholder="Enter current password">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="newPassword">New Password</label>
                  <input type="password" id="newPassword" placeholder="Enter new password">
                </div>
                <div class="form-group">
                  <label for="confirmPassword">Confirm New Password</label>
                  <input type="password" id="confirmPassword" placeholder="Confirm new password">
                </div>
              </div>
              <p class="password-hint">Password must be at least 8 characters and include a number and a letter.</p>
            </div>
          </section>

          <!-- Save Button -->
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
          </div>
        </form>

        <!-- Success Message (hidden by default) -->
        <div id="successMessage" class="success-message">
          <i class="fas fa-check-circle"></i> Settings updated successfully!
        </div>
      </div>
    </main>
  </div>

  <!-- Overlay for mobile sidebar -->
  <div class="overlay" id="overlay"></div>

  <!-- Shared sidebar toggle script -->
  <script src="shared.js"></script>
  <!-- Settings specific script -->
  <script src="settings.js"></script>
</body>
</html>