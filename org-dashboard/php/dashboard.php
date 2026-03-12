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
        $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE org_code = '$org_code' AND role != 'org' AND user_id != $org_id");
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
          AND a.category != 'event'
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
    // Event announcements (category='event') — shown in standalone events widget
    $event_announcements = [];
    $res_ev = mysqli_query($conn, "
        SELECT a.announcement_id, a.title, a.content,
               a.priority, a.expires_at, a.is_pinned, a.created_at
        FROM announcements a
        WHERE a.is_active = 1
          AND a.category = 'event'
          AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())
          AND (a.audience IS NULL OR a.audience = ''
               OR FIND_IN_SET('$org_code_escaped', a.audience) > 0)
        ORDER BY a.is_pinned DESC,
                 FIELD(a.priority,'urgent','high','low'),
                 a.created_at DESC
        LIMIT 5
    ");
    if ($res_ev) {
        while ($row = mysqli_fetch_assoc($res_ev)) {
            $event_announcements[] = $row;
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
                <div class="welcome-banner-top">
                    <div class="welcome-banner-text">
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['org_name'] ?? 'Organization'); ?>! </h1>
                        <p>Stay updated and manage your activities efficiently.</p>
                    </div>
                    <div class="welcome-banner-meta">
                        <span class="welcome-date-badge">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo date('F d, Y'); ?>
                        </span>
                        <span class="welcome-updated-tag">As of <span id="live-clock"></span></span>
                    </div>
                </div>
                <!-- Glass Quick Action Buttons -->
                <div class="welcome-glass-actions">
                    <a href="document_tracking.php" class="glass-btn">
                        <i class="fas fa-cloud-upload-alt"></i> Upload Document
                    </a>
                    <a href="members.php" class="glass-btn">
                        <i class="fas fa-user-plus"></i> Add Member
                    </a>
                    <a href="reports.php" class="glass-btn">
                        <i class="fas fa-file-alt"></i> Submit Report
                    </a>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="cards-grid">
                <a href="members.php" class="stat-card card-members skeleton-card" style="text-decoration:none;">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="card-content">
                        <h3 class="count-up" data-target="<?php echo $total_members; ?>">
                            <span class="skel skel-num"></span>
                        </h3>
                        <p>Total Members</p>
                    </div>
                    <span class="card-link-hint"><i class="fas fa-arrow-right"></i></span>
                </a>
                <a href="document_tracking.php" class="stat-card card-documents skeleton-card" style="text-decoration:none;">
                    <div class="card-icon"><i class="fas fa-folder-open"></i></div>
                    <div class="card-content">
                        <h3 class="count-up" data-target="<?php echo $total_documents; ?>">
                            <span class="skel skel-num"></span>
                        </h3>
                        <p>Total Documents</p>
                    </div>
                    <span class="card-link-hint"><i class="fas fa-arrow-right"></i></span>
                </a>
                <a href="reports.php" class="stat-card card-reports skeleton-card" style="text-decoration:none;">
                    <div class="card-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="card-content">
                        <h3 class="count-up" data-target="<?php echo $submitted_reports; ?>">
                            <span class="skel skel-num"></span>
                        </h3>
                        <p>Submitted Reports</p>
                    </div>
                    <span class="card-link-hint"><i class="fas fa-arrow-right"></i></span>
                </a>
                <a href="document_tracking.php?filter=pending" class="stat-card card-pending skeleton-card" style="text-decoration:none;">
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                    <div class="card-content">
                        <h3 class="count-up" data-target="<?php echo $pending_documents; ?>">
                            <span class="skel skel-num"></span>
                        </h3>
                        <p>Pending Documents</p>
                    </div>
                    <span class="card-link-hint"><i class="fas fa-arrow-right"></i></span>
                </a>
            </div>

            <!-- ── Event Announcements Widget ─────────────────────────── -->
            <div class="dash-events-widget">
                <div class="dash-events-header">
                    <div class="dash-events-title">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Event Announcements</h3>
                    </div>
                </div>
                <?php if (empty($event_announcements)): ?>
                    <div class="dash-events-empty">
                        <i class="fas fa-calendar-times"></i>
                        <span>No event announcements at this time.</span>
                    </div>
                <?php else: ?>
                    <div class="dash-events-list">
                    <?php
                        $priority_map_ev = [
                            'urgent' => ['label'=>'Urgent','color'=>'#c0392b','bg'=>'#fde8e8','icon'=>'fa-fire'],
                            'high'   => ['label'=>'High',  'color'=>'#b7770d','bg'=>'#fff3cd','icon'=>'fa-arrow-up'],
                            'low'    => ['label'=>'Low',   'color'=>'#555',   'bg'=>'#f0f0f0','icon'=>'fa-arrow-down'],
                        ];
                        foreach ($event_announcements as $idx => $ev):
                            $p  = $ev['priority'] ?? 'low';
                            $pb = $priority_map_ev[$p] ?? $priority_map_ev['low'];
                            $pinned  = !empty($ev['is_pinned']);
                            $expires = !empty($ev['expires_at']) ? date('M d, Y', strtotime($ev['expires_at'])) : null;
                            $created = new DateTime($ev['created_at']);
                    ?>
                        <div class="dash-event-item<?php echo $pinned ? ' ev-pinned' : ''; ?>">
                            <?php if ($pinned): ?>
                                <div class="ev-pin-badge"><i class="fas fa-thumbtack"></i></div>
                            <?php endif; ?>
                            <div class="dash-event-date-block" style="background:#d1fae5;color:#065f46">
                                <span class="dash-ev-day"><?= $created->format('d') ?></span>
                                <span class="dash-ev-mon"><?= $created->format('M') ?></span>
                            </div>
                            <div class="dash-event-body">
                                <span class="dash-event-name"><?= htmlspecialchars($ev['title']) ?></span>
                                <?php if (!empty($ev['content'])): ?>
                                <p class="dash-event-desc"><?= htmlspecialchars($ev['content']) ?></p>
                                <?php endif; ?>
                                <div class="dash-event-meta">
                                    <span><i class="fas fa-clock"></i> <?= $created->format('h:i A') ?></span>
                                    <?php if ($expires): ?>
                                        <span><i class="fas fa-hourglass-end"></i> Expires <?= $expires ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="dash-event-type-badge" style="background:<?= $pb['bg'] ?>;color:<?= $pb['color'] ?>">
                                <i class="fas <?= $pb['icon'] ?>"></i> <?= $pb['label'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bottom sections: Guidelines & Announcements -->
            <div class="dashboard-bottom">
                <!-- Guidelines card -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-book-open"></i>
                        <h2>Organizational Guidelines</h2>
                    </div>
                    <div class="section-card-body">

                    <!-- Key requirements from the manual -->
                    <p class="guidelines-section-label"><i class="fas fa-clipboard-list"></i> Key Accreditation Requirements</p>
                    <ul class="guidelines-list">
                        <li><i class="fas fa-check-circle"></i> Submit a Letter of Intent (OSLS Form 1 s. 24-25) using your organization letterhead.</li>
                        <li><i class="fas fa-check-circle"></i> Provide your Constitution &amp; By-Laws, signed by all officers and reviewed by the Adviser/Dean.</li>
                        <li><i class="fas fa-check-circle"></i> Submit a List of Officers, Members, and Representatives for all designated committees.</li>
                        <li><i class="fas fa-check-circle"></i> Include an Adviser&#39;s Letter of Acceptance &mdash; at least two teacher-advisers required.</li>
                        <li><i class="fas fa-check-circle"></i> Attach a Proposed Calendar of Activities free from conflicts with institutional schedules.</li>
                        <li><i class="fas fa-check-circle"></i> All documents must be in A4 size, Times New Roman 12pt, single-spaced, with a 1&Prime; left margin.</li>
                    </ul>

                    <!-- Process summary -->
                    <p class="guidelines-section-label"><i class="fas fa-tasks"></i> Submission Process</p>
                    <ul class="guidelines-list">
                        <li><i class="fas fa-paper-plane"></i> Initial Assessment (Online): Send soft copies in PDF via your org email to <a href="mailto:plspaccreditation@gmail.com" class="guidelines-link">plspaccreditation@gmail.com</a>.</li>
                        <li><i class="fas fa-sync-alt"></i> Revise documents if needed and resubmit until final approval for printing is granted.</li>
                        <li><i class="fas fa-print"></i> Final Assessment (Physical): Submit one (1) hard copy to the OSDS/CIG Office for final review.</li>
                        <li><i class="fas fa-certificate"></i> Upon approval, the organization receives its Accreditation Certificate and may schedule EED activities.</li>
                    </ul>

                    <!-- Accreditation Manual download — bottom -->
                    <div class="accred-manual-banner">
                        <div class="accred-manual-info">
                            <div class="accred-manual-icon">
                                <i class="fas fa-file-word"></i>
                            </div>
                            <div>
                                <div class="accred-manual-title">PLSP Accreditation Manual 2024&#8211;25</div>
                                <div class="accred-manual-sub">Requirements &amp; Guidelines for Accreditation, Re-accreditation, and Recognition</div>
                            </div>
                        </div>
                        <a href="../uploads/G4-PLSP-ACCREDITATION-MANUAL-24-25.docx"
                           download class="btn-download-manual">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div><!-- /.accred-manual-banner -->
                    </div><!-- /.section-card-body -->
                </div><!-- /.section-card guidelines -->

                <!-- Announcements card — from DB -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-bullhorn"></i>
                        <h2> Latest Announcements</h2>
                    </div>
                    <div class="section-card-body">

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
                            'event'    => ['label'=>'Event',    'color'=>'#065f46','bg'=>'#d1fae5','icon'=>'fa-calendar-alt'],
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

                    </div><!-- /.section-card-body -->
                </div><!-- /.section-card announcements -->
            </div><!-- /.dashboard-bottom -->
        </div><!-- /.dashboard-container -->
    </main>

    <!-- External JavaScript -->
    <script src="../js/script.js"></script>
    <script src="../js/notifications.js"></script>
    <script>
    // ── Count-up animation for stat cards ────────────────────────────────────
    (function () {
        const duration = 1200; // ms
        const ease = t => t < 0.5 ? 2*t*t : -1+(4-2*t)*t; // ease-in-out quad

        function animateCount(el) {
            const target = parseInt(el.getAttribute('data-target'), 10) || 0;
            if (target === 0) { el.textContent = '0'; return; }
            const start = performance.now();
            function step(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                el.textContent = Math.floor(ease(progress) * target);
                if (progress < 1) requestAnimationFrame(step);
                else el.textContent = target;
            }
            requestAnimationFrame(step);
        }

        // Fire when cards enter the viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCount(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('.count-up').forEach(el => observer.observe(el));
    })();
    </script>

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
    .ann-audience-tag { color: #065f46; font-weight: 600; }
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
    <script>
    // ── Live clock ────────────────────────────────────────────────────────────
    (function () {
        function updateClock() {
            const now = new Date();
            let h = now.getHours();
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            const el = document.getElementById('live-clock');
            if (el) el.textContent = h + ':' + m + ':' + s + ' ' + ampm;
        }
        updateClock();
        setInterval(updateClock, 1000);
    })();
    </script>

    <script>
    // ── Skeleton loader: remove shimmer once DOM is ready ────────────────────
    (function () {
        // Cards are server-rendered, so values are already in data-target.
        // We just fade out the skeleton class once the count-up fires.
        function removeSkeleton() {
            document.querySelectorAll('.skeleton-card').forEach(card => {
                card.classList.remove('skeleton-card');
            });
            document.querySelectorAll('.skel').forEach(el => el.remove());
        }
        // Remove after a brief artificial delay so shimmer is visible on fast loads too
        setTimeout(removeSkeleton, 600);
    })();

    // ── Real-time notification polling every 30 seconds ─────────────────────
    (function () {
        // Grab the existing notification badge element injected by notifications.js
        // We re-fetch the unread count from the server and update the badge.
        function pollNotifications() {
            fetch('get_notification_count.php', { credentials: 'same-origin' })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) return;
                    const count = parseInt(data.unread_count, 10) || 0;

                    // Update every badge that notifications.js renders
                    document.querySelectorAll('.notif-badge, .notification-badge, #notif-count').forEach(el => {
                        el.textContent = count > 99 ? '99+' : count;
                        el.style.display = count > 0 ? '' : 'none';
                    });

                    // If new notifications arrived since last poll, show a toast
                    if (typeof window._lastNotifCount !== 'undefined' && count > window._lastNotifCount) {
                        const diff = count - window._lastNotifCount;
                        showDashToast(
                            `You have ${diff} new notification${diff > 1 ? 's' : ''}!`,
                            'info'
                        );
                    }
                    window._lastNotifCount = count;
                })
                .catch(() => {}); // silently ignore network errors
        }

        // Also refresh the stat card numbers every 30 s so they stay accurate
        function pollStats() {
            fetch('get_dashboard_stats.php', { credentials: 'same-origin' })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) return;
                    const map = {
                        'card-members':   data.total_members,
                        'card-documents': data.total_documents,
                        'card-reports':   data.submitted_reports,
                        'card-pending':   data.pending_documents,
                    };
                    Object.entries(map).forEach(([cls, val]) => {
                        const el = document.querySelector('.' + cls + ' .count-up');
                        if (el && val !== undefined) el.textContent = val;
                    });
                })
                .catch(() => {});
        }

        // Run immediately then repeat every 30 s
        pollNotifications();
        pollStats();
        setInterval(pollNotifications, 30000);
        setInterval(pollStats,         30000);
    })()

    // ── Lightweight toast for in-page alerts ────────────────────────────────
    function showDashToast(msg, type) {
        const colors = { info: '#3b82f6', success: '#10b981', warning: '#f59e0b', error: '#ef4444' };
        const icons  = { info: 'fa-bell', success: 'fa-check-circle', warning: 'fa-exclamation-triangle', error: 'fa-times-circle' };
        const old = document.getElementById('dash-poll-toast');
        if (old) old.remove();
        const t = document.createElement('div');
        t.id = 'dash-poll-toast';
        t.style.cssText = `
            position:fixed;bottom:1.8rem;right:1.8rem;z-index:99999;
            padding:.8rem 1.3rem;border-radius:14px;font-size:.9rem;font-weight:600;
            box-shadow:0 6px 24px rgba(0,0,0,.18);color:#fff;
            display:flex;align-items:center;gap:.6rem;max-width:340px;
            background:${colors[type] || colors.info};
            animation:slideUpToast .3s ease;`;
        t.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${msg}</span>`;
        if (!document.getElementById('dash-toast-anim')) {
            const s = document.createElement('style');
            s.id = 'dash-toast-anim';
            s.textContent = '@keyframes slideUpToast{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}';
            document.head.appendChild(s);
        }
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 4500);
    }
    </script>
</body>
</html>