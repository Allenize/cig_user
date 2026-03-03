<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

// dashboard.php is at cig_user/org-dashboard/php/dashboard.php
// db_connection.php is at cig_user/db_connection.php
require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$announcements_db = [];
if ($conn) {
    $res = mysqli_query($conn, "
        SELECT a.announcement_id, a.title, a.content, a.created_at, u.full_name as created_by
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.is_active = 1
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    if (!$res) {
        // Query failed - log error but don't break the page
        error_log("Announcements query failed: " . mysqli_error($conn));
    } elseif (mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $announcements_db[] = $row;
        }
    }
    mysqli_close($conn);
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
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/notifications.css">
</head>
<body>
    <!-- Include the navbar -->
    <?php include '../php/navbar.php'; ?>

    <!-- Main content -->
    <main class="main-content">
      <?php include '../php/topbar.php'; ?>
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
                        <h3>5</h3>
                        <p>Total Members</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                    <div class="card-content">
                        <h3>5</h3>
                        <p>Total Documents</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="card-content">
                        <h3>0</h3>
                        <p>Submitted Reports</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                    <div class="card-content">
                        <h3>2</h3>
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

                <!-- Announcements card — from DB -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-bullhorn"></i>
                        <h2> Latest Announcements</h2>
                    </div>

                    <?php if (empty($announcements_db)): ?>
                        <p style="color:#888; text-align:center; padding:20px 0;">
                            No announcements at this time.
                        </p>
                    <?php else: ?>
                        <?php foreach ($announcements_db as $index => $ann): ?>
                        <div class="announcement-item">
                            <div class="announcement-header">
                                <h3><?php echo htmlspecialchars($ann['title']); ?></h3>
                                <?php if ($index === 0): ?>
                                    <span class="badge-new">NEW</span>
                                <?php endif; ?>
                            </div>
                            <div class="announcement-meta">
                                <span>
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($ann['created_at'])); ?>
                                </span>
                            </div>
                            <p class="announcement-desc announcement-truncated">
                                <?php echo htmlspecialchars($ann['content']); ?>
                            </p>
                            <button class="expand-btn" onclick="toggleAnnouncement(this)">
                                <i class="fas fa-chevron-down"></i> Read More
                            </button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>

    <!-- External JavaScript -->
    <script src="../js/script.js"></script>
    <script src="../js/notifications.js"></script>

    <script>
    function toggleAnnouncement(btn) {
        const desc = btn.previousElementSibling;
        const isTruncated = desc.classList.toggle('announcement-truncated');
        btn.innerHTML = isTruncated
            ? '<i class="fas fa-chevron-down"></i> Read More'
            : '<i class="fas fa-chevron-up"></i> Show Less';
    }
    </script>
</body>
</html>