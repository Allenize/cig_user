<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OrgHub</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- External CSS -->
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="topbar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="notifications.css">
</head>
<body>
    <!-- Include the navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main content -->
    <main class="main-content">
      <?php include 'topbar.php'; ?>
        <div class="dashboard-container">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['org_name'] ?? 'Organization'); ?>! </h1>
                <p>Stay updated and manage your activities efficiently.</p>
            </div>

            <!-- Overview Cards -->
            <div class="cards-grid">
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="card-content">
                        <h3>156</h3>
                        <p>Total Members</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="card-content">
                        <h3>8</h3>
                        <p>Upcoming Events</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="card-content">
                        <h3>23</h3>
                        <p>Submitted Reports</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                    <div class="card-content">
                        <h3>12</h3>
                        <p>Pending Documents</p>
                    </div>
                </div>
            </div>

            <!-- Bottom sections: Guidelines & Announcements -->
            <div class="dashboard-bottom">
                <!-- Guidelines card -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-book-open"></i>
                        <h2> Organizational Guidelines</h2>
                    </div>
                    <ul class="guidelines-list">
                        <li><i class="fas fa-check-circle"></i> Submit reports within 7 days after events.</li>
                        <li><i class="fas fa-check-circle"></i> Use official templates from the Documents section.</li>
                        <li><i class="fas fa-check-circle"></i> Secure approval before conducting events.</li>
                        <li><i class="fas fa-check-circle"></i> Keep member records updated.</li>
                    </ul>
                    <a href="guidelines.php" class="btn-outline-green"><i class="fas fa-arrow-right"></i> View Full Guidelines</a>
                </div>

                <!-- Announcements card -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-bullhorn"></i>
                        <h2> Latest Announcements</h2>
                    </div>

                    <div class="announcement-item">
                        <div class="announcement-header">
                            <h3>System Maintenance</h3>
                            <span class="badge-new">NEW</span>
                        </div>
                        <div class="announcement-meta">
                            <span><i class="far fa-calendar-alt"></i> Mar 15, 2026</span>
                        </div>
                        <p class="announcement-desc">Scheduled downtime on Sunday, March 20 from 2-4 AM. Please save your work.</p>
                        <a href="announcements.php?id=1" class="read-more">Read More <i class="fas fa-chevron-right"></i></a>
                    </div>

                    <div class="announcement-item">
                        <div class="announcement-header">
                            <h3>Quarterly Meeting</h3>
                        </div>
                        <div class="announcement-meta">
                            <span><i class="far fa-calendar-alt"></i> Mar 12, 2026</span>
                        </div>
                        <p class="announcement-desc">Join us on March 25 for the quarterly org update. All members are encouraged to attend.</p>
                        <a href="announcements.php?id=2" class="read-more">Read More <i class="fas fa-chevron-right"></i></a>
                    </div>

                    <div class="announcement-item">
                        <div class="announcement-header">
                            <h3>New Document Templates</h3>
                        </div>
                        <div class="announcement-meta">
                            <span><i class="far fa-calendar-alt"></i> Mar 5, 2026</span>
                        </div>
                        <p class="announcement-desc">Updated templates for event reports are now available in the Documents section.</p>
                        <a href="announcements.php?id=3" class="read-more">Read More <i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- External JavaScript -->
    <script src="script.js"></script>
</body>
</html>