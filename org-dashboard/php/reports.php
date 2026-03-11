<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$org_id   = (int) $_SESSION['user_id'];
$org_name = htmlspecialchars($_SESSION['org_name'] ?? 'Organization');

$approved           = 0;
$pending            = 0;
$rejected           = 0;
$total_submissions  = 0;
$total_members      = 0;
$saved_reports      = [];
$monthly_data       = [];
$recent_submissions = [];

if ($conn) {
    $r = mysqli_query($conn, "SELECT org_code FROM users WHERE user_id = $org_id LIMIT 1");
    $org_code = mysqli_real_escape_string($conn, mysqli_fetch_assoc($r)['org_code'] ?? '');

    if ($org_code) {
        $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users WHERE org_code = '$org_code' AND role = 'user'");
        $total_members = (int) mysqli_fetch_assoc($r)['cnt'];
    }

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id");
    $total_submissions = (int) mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id AND status = 'approved'");
    $approved = (int) mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id AND status IN ('pending','in_review')");
    $pending = (int) mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id AND status = 'rejected'");
    $rejected = (int) mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "
        SELECT DATE_FORMAT(submitted_at, '%b %Y') AS month,
               DATE_FORMAT(submitted_at, '%Y-%m')  AS month_key,
               COUNT(*) AS total,
               SUM(status = 'approved') AS approved,
               SUM(status IN ('pending','in_review')) AS pending,
               SUM(status = 'rejected') AS rejected
        FROM submissions
        WHERE org_id = $org_id
          AND submitted_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month_key, month
        ORDER BY month_key ASC
    ");
    while ($row = mysqli_fetch_assoc($r)) { $monthly_data[] = $row; }

    $r = mysqli_query($conn, "
        SELECT submission_id, title, status, submitted_at
        FROM submissions WHERE org_id = $org_id
        ORDER BY submitted_at DESC LIMIT 10
    ");
    while ($row = mysqli_fetch_assoc($r)) { $recent_submissions[] = $row; }

    $r = mysqli_query($conn, "
        SELECT report_id, title, report_type, created_at
        FROM reports WHERE generated_by = $org_id
        ORDER BY created_at DESC LIMIT 20
    ");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $saved_reports[] = $row; } }

    mysqli_close($conn);
}

$approval_rate = $total_submissions > 0 ? round(($approved / $total_submissions) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - OrgHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <link rel="stylesheet" href="../css/reports.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>

<?php include '../php/navbar.php'; ?>
    <?php include '../php/topbar.php'; ?>

    <style>
        /* Reports page: main-content does NOT scroll — container handles internal scroll */
        .main-content { overflow: hidden !important; display: flex; flex-direction: column; }
    </style>

    <div class="reports-container">

        <!-- ── Page Header ───────────────────────────────────────────────── -->
        <div class="reports-header">
            <div class="reports-header-left">
                <div class="reports-header-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="reports-header-text">
                    <h1>Reports</h1>
                    <p>Overview and analytics for <?= $org_name ?></p>
                </div>
            </div>
            <div class="reports-header-right">
                <button class="btn-export-pdf" id="btnExportPDF">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn-export-excel" id="btnExportExcel">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button class="btn-save-report" id="btnSaveReport">
                    <i class="fas fa-save"></i> Save Report
                </button>
            </div>
        </div>

        <!-- ── Stats Bar ──────────────────────────────────────────────────── -->
        <div class="reports-stats-bar">
            <div class="rp-stat">
                <div class="rp-stat-icon" style="background:#e3f2eb;color:#2d6a4f;"><i class="fas fa-file-alt"></i></div>
                <div>
                    <div class="rp-stat-num"><?= $total_submissions ?></div>
                    <div class="rp-stat-label">Total Submissions</div>
                </div>
            </div>
            <div class="rp-stat-divider"></div>
            <div class="rp-stat">
                <div class="rp-stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="rp-stat-num"><?= $approved ?></div>
                    <div class="rp-stat-label">Approved</div>
                </div>
            </div>
            <div class="rp-stat-divider"></div>
            <div class="rp-stat">
                <div class="rp-stat-icon" style="background:#fef9c3;color:#ca8a04;"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="rp-stat-num"><?= $pending ?></div>
                    <div class="rp-stat-label">Pending</div>
                </div>
            </div>
            <div class="rp-stat-divider"></div>
            <div class="rp-stat">
                <div class="rp-stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="rp-stat-num"><?= $rejected ?></div>
                    <div class="rp-stat-label">Rejected</div>
                </div>
            </div>
            <div class="rp-stat-divider"></div>
            <div class="rp-stat">
                <div class="rp-stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-tachometer-alt"></i></div>
                <div>
                    <div class="rp-stat-num"><?= $approval_rate ?>%</div>
                    <div class="rp-stat-label">Approval Rate</div>
                </div>
            </div>
        </div>

        <!-- ── Charts Row ─────────────────────────────────────────────────── -->
        <div class="report-charts-row">

            <!-- Submission Status Donut -->
            <div class="report-card">
                <div class="report-card-header">
                    <div class="report-card-header-left">
                        <i class="fas fa-chart-pie"></i>
                        <h3>Submission Status</h3>
                    </div>
                </div>
                <div class="report-card-body">
                    <div class="donut-wrap">
                        <svg class="donut-svg" viewBox="0 0 120 120">
                            <?php
                            $total_d = $approved + $pending + $rejected;
                            if ($total_d > 0):
                                $cx = 60; $cy = 60; $r_d = 45; $stroke = 18;
                                $circ = 2 * M_PI * $r_d;
                                $segments = [
                                    ['val' => $approved, 'color' => '#10b981'],
                                    ['val' => $pending,  'color' => '#f59e0b'],
                                    ['val' => $rejected, 'color' => '#ef4444'],
                                ];
                                $cumulative = 0;
                                foreach ($segments as $seg):
                                    if ($seg['val'] == 0) continue;
                                    $dash   = ($seg['val'] / $total_d) * $circ;
                                    $gap    = $circ - $dash;
                                    $offset = $circ / 4 - $cumulative;
                                    $cumulative += $dash;
                            ?>
                            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r_d ?>"
                                    fill="none"
                                    stroke="<?= $seg['color'] ?>"
                                    stroke-width="<?= $stroke ?>"
                                    stroke-dasharray="<?= round($dash,2) ?> <?= round($gap,2) ?>"
                                    stroke-dashoffset="<?= round($offset,2) ?>"
                                    transform="rotate(-90 <?= $cx ?> <?= $cy ?>)"/>
                            <?php endforeach; else: ?>
                            <circle cx="60" cy="60" r="45" fill="none" stroke="#e9f0ec" stroke-width="18"/>
                            <?php endif; ?>
                            <text x="60" y="56" text-anchor="middle" font-size="14" font-weight="700" fill="#1e3a3a"><?= $total_submissions ?></text>
                            <text x="60" y="68" text-anchor="middle" font-size="7" fill="#6b7280">Total</text>
                        </svg>
                        <div class="donut-legend">
                            <div class="legend-item">
                                <span class="legend-dot" style="background:#10b981"></span>
                                Approved
                                <strong><?= $approved ?></strong>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot" style="background:#f59e0b"></span>
                                Pending / In Review
                                <strong><?= $pending ?></strong>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot" style="background:#ef4444"></span>
                                Rejected
                                <strong><?= $rejected ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Bar Chart -->
            <div class="report-card">
                <div class="report-card-header">
                    <div class="report-card-header-left">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Monthly Submissions (Last 6 Months)</h3>
                    </div>
                </div>
                <div class="report-card-body">
                    <?php if (empty($monthly_data)): ?>
                        <div class="chart-empty">
                            <i class="fas fa-chart-bar"></i>
                            <p>No submission data yet</p>
                        </div>
                    <?php else:
                        $max_val = max(array_map(fn($m) => (int)$m['total'], $monthly_data)) ?: 1;
                    ?>
                    <div class="bar-chart-wrap">
                        <div class="bar-chart">
                            <?php foreach ($monthly_data as $m): ?>
                            <div class="bar-group">
                                <div class="bar-stack">
                                    <?php if ((int)$m['approved'] > 0): ?>
                                    <div class="bar-seg seg-green" style="height:<?= round(((int)$m['approved']/$max_val)*140) ?>px" title="Approved: <?= $m['approved'] ?>"></div>
                                    <?php endif; ?>
                                    <?php if ((int)$m['pending'] > 0): ?>
                                    <div class="bar-seg seg-amber" style="height:<?= round(((int)$m['pending']/$max_val)*140) ?>px" title="Pending: <?= $m['pending'] ?>"></div>
                                    <?php endif; ?>
                                    <?php if ((int)$m['rejected'] > 0): ?>
                                    <div class="bar-seg seg-red" style="height:<?= round(((int)$m['rejected']/$max_val)*140) ?>px" title="Rejected: <?= $m['rejected'] ?>"></div>
                                    <?php endif; ?>
                                </div>
                                <span class="bar-total"><?= $m['total'] ?></span>
                                <span class="bar-label"><?= $m['month'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="bar-legend">
                            <span><span class="legend-dot" style="background:#10b981"></span> Approved</span>
                            <span><span class="legend-dot" style="background:#f59e0b"></span> Pending</span>
                            <span><span class="legend-dot" style="background:#ef4444"></span> Rejected</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Approval Rate ──────────────────────────────────────────────── -->
        <div class="approval-row">
        <div class="report-card">
            <div class="report-card-header">
                <div class="report-card-header-left">
                    <i class="fas fa-tachometer-alt"></i>
                    <h3>Approval Rate</h3>
                </div>
                <span class="rate-label"><?= $approval_rate ?>%</span>
            </div>
            <div class="report-card-body">
                <div class="approval-bar-bg">
                    <div class="approval-bar-fill" style="width:<?= $approval_rate ?>%"></div>
                </div>
                <div class="approval-bar-labels">
                    <span>0%</span>
                    <span><?= $approval_rate ?>% approved out of <?= $total_submissions ?> submissions</span>
                    <span>100%</span>
                </div>
            </div>
        </div>
        </div><!-- /.approval-row -->

        <!-- ── Bottom Row ─────────────────────────────────────────────────── -->
        <div class="report-bottom-row">

            <!-- Recent Submissions Table -->
            <div class="report-card">
                <div class="report-card-header">
                    <div class="report-card-header-left">
                        <i class="fas fa-list-alt"></i>
                        <h3>Recent Submissions</h3>
                    </div>
                    <a href="document_tracking.php" class="view-all-link">
                        View all <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="report-card-body" style="padding:0;">
                    <?php if (empty($recent_submissions)): ?>
                        <div class="chart-empty" style="padding:2.5rem;">
                            <i class="fas fa-inbox"></i>
                            <p>No submissions yet</p>
                        </div>
                    <?php else: ?>
                    <div class="report-table-wrap">
                        <table class="report-table" id="recentTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $statusMap = [
                                'approved'  => ['label'=>'Approved',  'class'=>'st-approved'],
                                'rejected'  => ['label'=>'Rejected',  'class'=>'st-rejected'],
                                'pending'   => ['label'=>'Pending',   'class'=>'st-pending'],
                                'in_review' => ['label'=>'In Review', 'class'=>'st-review'],
                                'archived'  => ['label'=>'Archived',  'class'=>'st-archived'],
                            ];
                            foreach ($recent_submissions as $i => $sub):
                                $st = $statusMap[$sub['status']] ?? ['label'=>ucfirst($sub['status']),'class'=>'st-pending'];
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="sub-title"><?= htmlspecialchars($sub['title']) ?></td>
                                <td><span class="status-pill <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                                <td><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Saved Reports -->
            <div class="report-card">
                <div class="report-card-header">
                    <div class="report-card-header-left">
                        <i class="fas fa-bookmark"></i>
                        <h3>Saved Reports</h3>
                    </div>
                </div>
                <div class="report-card-body">
                    <div class="saved-reports-list" id="savedReportsList">
                    <?php if (empty($saved_reports)): ?>
                        <div class="chart-empty">
                            <i class="fas fa-bookmark"></i>
                            <p>No saved reports yet</p>
                            <small>Click "Save Report" to store a snapshot</small>
                        </div>
                    <?php else:
                        $typeIcons = ['submissions'=>'fa-file-alt','members'=>'fa-users','overview'=>'fa-chart-bar'];
                        foreach ($saved_reports as $rp):
                            $icon = $typeIcons[$rp['report_type']] ?? 'fa-file-alt';
                    ?>
                        <div class="saved-report-item" data-id="<?= $rp['report_id'] ?>">
                            <div class="saved-report-icon"><i class="fas <?= $icon ?>"></i></div>
                            <div class="saved-report-info">
                                <span class="saved-report-title"><?= htmlspecialchars($rp['title']) ?></span>
                                <span class="saved-report-date"><?= date('M d, Y · h:i A', strtotime($rp['created_at'])) ?></span>
                            </div>
                            <button class="btn-delete-report" onclick="deleteReport(<?= $rp['report_id'] ?>, this)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.reports-container -->
</main>

<!-- ── Save Report Modal ─────────────────────────────────────────────────── -->
<div id="saveModal" class="rp-modal">
    <div class="rp-modal-content">
        <span class="rp-close" id="closeSaveModal">&times;</span>
        <h2><i class="fas fa-save"></i> Save Report</h2>
        <div class="form-group">
            <label>Report Title <span>*</span></label>
            <input type="text" id="reportTitle" placeholder="e.g. Q1 2025 Overview">
        </div>
        <div class="form-group">
            <label>Report Type</label>
            <select id="reportType">
                <option value="overview">Overview</option>
                <option value="submissions">Submissions</option>
                <option value="members">Members</option>
            </select>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea id="reportNotes" rows="3" placeholder="Optional notes..."></textarea>
        </div>
        <div class="rp-modal-actions">
            <button class="btn-cancel-modal" id="cancelSave"><i class="fas fa-times"></i> Cancel</button>
            <button class="btn-save-confirm" id="confirmSave"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>
</div>

<script>
const REPORT_DATA = {
    orgName:   <?= json_encode($_SESSION['org_name'] ?? 'Organization') ?>,
    generated: <?= json_encode(date('F d, Y h:i A')) ?>,
    stats: {
        total:    <?= $total_submissions ?>,
        approved: <?= $approved ?>,
        pending:  <?= $pending ?>,
        rejected: <?= $rejected ?>,
        members:  <?= $total_members ?>,
        rate:     <?= $approval_rate ?>
    },
    monthly: <?= json_encode($monthly_data) ?>,
    recent:  <?= json_encode($recent_submissions) ?>
};
</script>
<script src="../js/script.js"></script>
<script src="../js/navbar.js"></script>
<script src="../js/notifications.js"></script>
<script src="../js/reports.js"></script>
</body>
</html>