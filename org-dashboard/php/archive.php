<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$org_id = (int) $_SESSION['user_id'];

// ── Fetch archived submissions from DB ────────────────────────────────────────
$archived_submissions = [];
$total_archived = 0;
$total_approved = 0;
$total_rejected = 0;
$newest_archive_date = null;

if ($conn) {
    // Stats counts
    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id AND status = 'archived'");
    $total_archived = (int) mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id AND status = 'approved'");
    $total_approved = (int) mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id AND status = 'rejected'");
    $total_rejected = (int) mysqli_fetch_assoc($r)['cnt'];

    // Newest archive date
    $r = mysqli_query($conn, "SELECT MAX(updated_at) AS latest FROM submissions WHERE org_id = $org_id AND status = 'archived'");
    $row = mysqli_fetch_assoc($r);
    $newest_archive_date = $row['latest'] ? date('M d, Y', strtotime($row['latest'])) : '—';

    // All archived submissions with file info
    $r = mysqli_query($conn, "
        SELECT s.submission_id, s.title, s.description, s.status,
               s.file_name, s.file_path, s.submitted_at, s.updated_at,
               u.full_name AS submitted_by_name
        FROM submissions s
        LEFT JOIN users u ON s.submitted_by = u.user_id
        WHERE s.org_id = $org_id AND s.status = 'archived'
        ORDER BY s.updated_at DESC
    ");
    while ($row = mysqli_fetch_assoc($r)) {
        $archived_submissions[] = $row;
    }

    mysqli_close($conn);
}

// Determine file type icon class
function getFileIcon(string $filename): array {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return match($ext) {
        'pdf'           => ['icon' => 'fa-file-pdf',  'color' => '#ef4444', 'bg' => '#fee2e2', 'label' => 'PDF'],
        'doc', 'docx'   => ['icon' => 'fa-file-word', 'color' => '#1d4ed8', 'bg' => '#dbeafe', 'label' => 'DOC'],
        'xls', 'xlsx'   => ['icon' => 'fa-file-excel','color' => '#065f46', 'bg' => '#d1fae5', 'label' => 'XLS'],
        'png','jpg','jpeg','gif' => ['icon' => 'fa-file-image','color' => '#7c3aed','bg' => '#ede9fe', 'label' => 'IMG'],
        default         => ['icon' => 'fa-file-alt',  'color' => '#374151', 'bg' => '#f3f4f6', 'label' => strtoupper($ext) ?: 'FILE'],
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - OrgHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <link rel="stylesheet" href="../css/archive.css">
</head>
<body>

<?php include '../php/navbar.php'; ?>
    <?php include '../php/topbar.php'; ?>

    <div class="archive-container">

        <!-- ── Page Header ───────────────────────────────────────────────── -->
        <div class="archive-header">
            <div class="archive-header-left">
                <div class="archive-header-icon">
                    <i class="fas fa-archive"></i>
                </div>
                <div>
                    <h1>Archive</h1>
                    <p>Archived submissions and documents</p>
                </div>
            </div>
        </div>

        <!-- ── Stats Bar ─────────────────────────────────────────────────── -->
        <div class="archive-stats-bar">
            <div class="arch-stat">
                <div class="arch-stat-icon" style="background:#e3f2eb;color:#2d6a4f;">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="arch-stat-body">
                    <span class="arch-stat-num"><?= $total_archived ?></span>
                    <span class="arch-stat-label">Total Archived</span>
                </div>
            </div>
            <div class="arch-stat-divider"></div>
            <div class="arch-stat">
                <div class="arch-stat-icon" style="background:#d1fae5;color:#065f46;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="arch-stat-body">
                    <span class="arch-stat-num"><?= $total_approved ?></span>
                    <span class="arch-stat-label">Approved Submissions</span>
                </div>
            </div>
            <div class="arch-stat-divider"></div>
            <div class="arch-stat">
                <div class="arch-stat-icon" style="background:#fee2e2;color:#b91c1c;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="arch-stat-body">
                    <span class="arch-stat-num"><?= $total_rejected ?></span>
                    <span class="arch-stat-label">Rejected Submissions</span>
                </div>
            </div>
            <div class="arch-stat-divider"></div>
            <div class="arch-stat">
                <div class="arch-stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="arch-stat-body">
                    <span class="arch-stat-num" style="font-size:1rem;"><?= $newest_archive_date ?></span>
                    <span class="arch-stat-label">Last Archived</span>
                </div>
            </div>
        </div>

        <!-- ── Tabs + Search/Filter ───────────────────────────────────────── -->
        <div class="archive-toolbar">
            <div class="archive-tabs">
                <button class="tab-btn active" data-tab="all">
                    <i class="fas fa-layer-group"></i> All Archives
                </button>
                <button class="tab-btn" data-tab="recent">
                    <i class="fas fa-clock"></i> Last 30 Days
                </button>
            </div>
            <div class="archive-search-row">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by title or file...">
                </div>
                <input type="date" id="dateFilter" title="Filter by archive date">
            </div>
        </div>

        <!-- ── Archive Table ──────────────────────────────────────────────── -->
        <div class="table-card">
            <?php if (empty($archived_submissions)): ?>
            <div class="archive-empty">
                <div class="archive-empty-icon"><i class="fas fa-box-open"></i></div>
                <h3>No Archived Items</h3>
                <p>Submissions that are archived will appear here.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="archive-table" id="archiveTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>File</th>
                            <th>Submitted</th>
                            <th>Archived</th>
                            <th>Submitted By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($archived_submissions as $i => $item):
                        $fileInfo   = getFileIcon($item['file_name'] ?? '');
                        $archivedAt = date('M d, Y', strtotime($item['updated_at']));
                        $archivedTs = strtotime($item['updated_at']);
                        $isRecent   = (time() - $archivedTs) < (30 * 86400);
                        $submittedAt = date('M d, Y', strtotime($item['submitted_at']));
                        $desc = $item['description'] ? htmlspecialchars($item['description']) : '—';
                    ?>
                    <tr data-recent="<?= $isRecent ? 'true' : 'false' ?>"
                        data-archived-date="<?= date('Y-m-d', $archivedTs) ?>"
                        data-title="<?= strtolower(htmlspecialchars($item['title'])) ?>"
                        data-id="<?= $item['submission_id'] ?>">
                        <td class="row-num"><?= $i + 1 ?></td>
                        <td class="title-cell">
                            <span class="item-title"><?= htmlspecialchars($item['title']) ?></span>
                            <?php if ($item['description']): ?>
                            <span class="item-desc"><?= mb_strimwidth($item['description'], 0, 60, '…') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['file_name']): ?>
                            <span class="file-badge" style="background:<?= $fileInfo['bg'] ?>;color:<?= $fileInfo['color'] ?>">
                                <i class="fas <?= $fileInfo['icon'] ?>"></i> <?= $fileInfo['label'] ?>
                            </span>
                            <?php else: ?>
                            <span class="file-badge" style="background:#f3f4f6;color:#9ca3af">
                                <i class="fas fa-minus"></i> None
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="date-cell"><?= $submittedAt ?></td>
                        <td class="date-cell">
                            <?= $archivedAt ?>
                            <?php if ($isRecent): ?>
                            <span class="recent-pill">New</span>
                            <?php endif; ?>
                        </td>
                        <td class="submitter-cell">
                            <?= htmlspecialchars($item['submitted_by_name'] ?? '—') ?>
                        </td>
                        <td class="actions-cell">
                            <button class="btn-action btn-view"
                                    onclick="viewItem(this)"
                                    data-title="<?= htmlspecialchars($item['title']) ?>"
                                    data-desc="<?= $desc ?>"
                                    data-file="<?= htmlspecialchars($item['file_name'] ?? '') ?>"
                                    data-filepath="<?= htmlspecialchars($item['file_path'] ?? '') ?>"
                                    data-submitted="<?= $submittedAt ?>"
                                    data-archived="<?= $archivedAt ?>"
                                    data-by="<?= htmlspecialchars($item['submitted_by_name'] ?? '—') ?>"
                                    title="View details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action btn-restore"
                                    onclick="restoreItem(<?= $item['submission_id'] ?>, this)"
                                    title="Restore to pending">
                                <i class="fas fa-undo-alt"></i>
                            </button>
                            <button class="btn-action btn-delete"
                                    onclick="deleteItem(<?= $item['submission_id'] ?>, this)"
                                    title="Permanently delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Row count -->
            <div class="table-footer">
                <span id="rowCount"><?= count($archived_submissions) ?> item(s)</span>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.archive-container -->
</main>

<!-- ── View Details Modal ────────────────────────────────────────────────── -->
<div id="viewModal" class="arch-modal">
    <div class="arch-modal-content">
        <span class="arch-close" id="closeViewModal">&times;</span>
        <div class="arch-modal-header">
            <div class="arch-modal-header-icon"><i class="fas fa-file-alt"></i></div>
            <h2>Archived Item Details</h2>
        </div>
        <div id="viewModalBody" class="arch-modal-body"></div>
        <div class="arch-modal-actions" id="viewModalActions"></div>
    </div>
</div>

<!-- ── Confirm Delete Modal ──────────────────────────────────────────────── -->
<div id="deleteModal" class="arch-modal">
    <div class="arch-modal-content arch-modal-sm">
        <div class="arch-modal-header danger">
            <div class="arch-modal-header-icon danger-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h2>Permanently Delete?</h2>
        </div>
        <p class="arch-modal-msg">This action <strong>cannot be undone</strong>. The submission and its file will be permanently removed.</p>
        <div class="arch-modal-actions">
            <button class="btn-modal-cancel" id="cancelDelete"><i class="fas fa-times"></i> Cancel</button>
            <button class="btn-modal-danger" id="confirmDelete"><i class="fas fa-trash-alt"></i> Delete</button>
        </div>
    </div>
</div>

<script src="../js/script.js"></script>
<script src="../js/notifications.js"></script>
<script src="../js/archive.js"></script>
</body>
</html>