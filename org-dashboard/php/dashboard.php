<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

// dashboard.php is at cig_user/org-dashboard/php/dashboard.php
// db_connection.php is at cig_user/db_connection.php
require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$announcements_db   = [];
$total_members      = 0;
$total_documents    = 0;
$submitted_reports  = 0;
$pending_documents  = 0;

if ($conn) {
    $org_id  = (int) ($_SESSION['user_id'] ?? 0);

    // Total Members: users sharing the same org_code with role='user'
    $org_code_res = mysqli_query($conn, "SELECT org_code FROM users WHERE user_id = $org_id LIMIT 1");
    $org_code_row = mysqli_fetch_assoc($org_code_res);
    $org_code     = mysqli_real_escape_string($conn, $org_code_row['org_code'] ?? '');

    if ($org_code) {
        $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE org_code = '$org_code' AND role = 'user'");
        $total_members = (int) mysqli_fetch_assoc($r)['cnt'];
    }

    // Total Documents: all submissions by this org
    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM submissions WHERE org_id = $org_id");
    $total_documents = (int) mysqli_fetch_assoc($r)['cnt'];

    // Submitted Reports: approved submissions
    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM submissions WHERE org_id = $org_id AND status = 'approved'");
    $submitted_reports = (int) mysqli_fetch_assoc($r)['cnt'];

    // Pending Documents: pending or in_review submissions
    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM submissions WHERE org_id = $org_id AND status IN ('pending', 'in_review')");
    $pending_documents = (int) mysqli_fetch_assoc($r)['cnt'];

    $org_code_escaped = mysqli_real_escape_string($conn, $org_code);
    // Fetch active, non-expired, audience-matched announcements
    $res = mysqli_query($conn, "
        SELECT a.announcement_id, a.title, a.content,
               a.priority, a.category, a.audience,
               a.is_pinned, a.expires_at, a.created_at,
               u.full_name as created_by
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        WHERE a.is_active = 1
          AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())
          AND (a.audience IS NULL OR a.audience = ''
               OR FIND_IN_SET('$org_code_escaped', a.audience) > 0)
        ORDER BY a.is_pinned DESC,
                 FIELD(a.priority,'urgent','high','low'),
                 a.created_at DESC
        LIMIT 5
    ");
    if (!$res) {
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
                        <h3><?php echo $total_members; ?></h3>
                        <p>Total Members</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                    <div class="card-content">
                        <h3><?php echo $total_documents; ?></h3>
                        <p>Total Documents</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="card-content">
                        <h3><?php echo $submitted_reports; ?></h3>
                        <p>Submitted Reports</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                    <div class="card-content">
                        <h3><?php echo $pending_documents; ?></h3>
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
                    <?php else:
                        $priority_map = [
                            'urgent' => ['label'=>'Urgent','color'=>'#c0392b','bg'=>'#fde8e8','icon'=>'fa-fire'],
                            'high'   => ['label'=>'High',  'color'=>'#b7770d','bg'=>'#fff3cd','icon'=>'fa-arrow-up'],
                            'low'    => ['label'=>'Low',   'color'=>'#555',   'bg'=>'#f0f0f0','icon'=>'fa-arrow-down'],
                        ];
                        $category_map = [
                            'event'    => ['label'=>'Event',    'color'=>'#1d4ed8','bg'=>'#dbeafe','icon'=>'fa-calendar-alt'],
                            'deadline' => ['label'=>'Deadline', 'color'=>'#b91c1c','bg'=>'#fee2e2','icon'=>'fa-clock'],
                            'policy'   => ['label'=>'Policy',   'color'=>'#6d28d9','bg'=>'#ede9fe','icon'=>'fa-gavel'],
                            'general'  => ['label'=>'General',  'color'=>'#065f46','bg'=>'#d1fae5','icon'=>'fa-info-circle'],
                        ];
                        foreach ($announcements_db as $index => $ann):
                            $p  = $ann['priority'] ?? 'low';
                            $pb = $priority_map[$p] ?? $priority_map['low'];
                            $c  = $ann['category'] ?? 'general';
                            $cb = $category_map[$c] ?? $category_map['general'];
                            $pinned  = !empty($ann['is_pinned']);
                            $expires = !empty($ann['expires_at']) ? date('M d, Y', strtotime($ann['expires_at'])) : null;
                    ?>
                        <div class="announcement-item<?php echo $pinned ? ' ann-pinned' : ''; ?>"
                             id="dash-ann-<?php echo $ann['announcement_id']; ?>"
                             data-ann-id="<?php echo $ann['announcement_id']; ?>">

                            <?php if ($pinned): ?>
                                <div class="ann-pin-ribbon"><i class="fas fa-thumbtack"></i> Pinned</div>
                            <?php endif; ?>

                            <div class="announcement-header">
                                <h3 class="announcement-title">
                                    <span class="ann-badge" style="background:<?php echo $cb['bg']; ?>;color:<?php echo $cb['color']; ?>;">
                                        <i class="fas <?php echo $cb['icon']; ?>"></i> <?php echo $cb['label']; ?>
                                    </span>
                                    <span class="ann-badge" style="background:<?php echo $pb['bg']; ?>;color:<?php echo $pb['color']; ?>;">
                                        <i class="fas <?php echo $pb['icon']; ?>"></i> <?php echo $pb['label']; ?>
                                    </span>
                                    <?php echo htmlspecialchars($ann['title']); ?>
                                </h3>
                                <?php if ($index === 0 && !$pinned): ?>
                                    <span class="badge-new">NEW</span>
                                <?php endif; ?>
                            </div>

                            <div class="announcement-meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($ann['created_at'])); ?></span>
                                <span><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($ann['created_at'])); ?></span>
                                <?php if (!empty($ann['audience'])): ?>
                                    <span class="ann-audience-tag"><i class="fas fa-users"></i> Targeted</span>
                                <?php endif; ?>
                                <?php if ($expires): ?>
                                    <span class="ann-expires-tag"><i class="fas fa-hourglass-end"></i> Expires <?php echo $expires; ?></span>
                                <?php endif; ?>
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

    <style>
    .ann-badge {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 0.68rem; font-weight: 700;
        padding: 2px 8px; border-radius: 20px;
        text-transform: uppercase; letter-spacing: 0.4px;
        white-space: nowrap; flex-shrink: 0; margin-right: 4px;
        vertical-align: middle;
    }
    .announcement-title {
        margin: 0; font-size: 0.97rem; line-height: 1.5;
        word-break: break-word; overflow-wrap: anywhere;
        min-width: 0; flex: 1;
        display: flex; flex-wrap: wrap; align-items: center; gap: 4px;
    }
    .announcement-header {
        display: flex; align-items: flex-start;
        gap: 8px; flex-wrap: nowrap; margin-bottom: 0.5rem;
    }
    .badge-new { flex-shrink: 0; }
    .ann-pinned {
        border-left: 3px solid #f59e0b !important;
        background: #fffdf0;
        border-radius: 8px;
        padding-left: 12px;
    }
    .ann-pin-ribbon {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 0.72rem; font-weight: 700; color: #b45309;
        background: #fef3c7; padding: 2px 10px; border-radius: 20px;
        margin-bottom: 6px;
    }
    .ann-audience-tag { color: #1d4ed8; font-weight: 600; }
    .ann-expires-tag  { color: #b91c1c; font-weight: 600; }
    </style>

    <script>
    function toggleAnnouncement(btn) {
        const desc = btn.previousElementSibling;
        const isTruncated = desc.classList.toggle('announcement-truncated');
        btn.innerHTML = isTruncated
            ? '<i class="fas fa-chevron-down"></i> Read More'
            : '<i class="fas fa-chevron-up"></i> Show Less';
    }

    // Hide "Read More" buttons when text isn't actually overflowing
    function initExpandButtons() {
        document.querySelectorAll('.expand-btn').forEach(btn => {
            const desc = btn.previousElementSibling;
            if (!desc) return;
            // Temporarily remove truncation to measure full height
            desc.classList.remove('announcement-truncated');
            const fullHeight = desc.scrollHeight;
            desc.classList.add('announcement-truncated');
            const clampedHeight = desc.clientHeight;
            // Only show the button if content is actually being cut off
            if (fullHeight <= clampedHeight) {
                btn.style.display = 'none';
                desc.classList.remove('announcement-truncated');
            }
        });
    }
    document.addEventListener('DOMContentLoaded', initExpandButtons);

    // Read receipt — fire once per announcement when user expands it
    const _read = new Set();
    document.querySelectorAll('.expand-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const item  = this.closest('.announcement-item');
            const annId = item?.dataset.annId;
            if (annId && !_read.has(annId)) {
                _read.add(annId);
                fetch('mark_read.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ announcement_id: parseInt(annId) })
                }).catch(() => {});
            }
        });
    });
    </script>
</body>
</html>