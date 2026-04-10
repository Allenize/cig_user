<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db_connection.php';

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
$points_issued      = 0;
$leaderboard        = [];

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

    // Points = 10 per approved submission
    $points_issued = $approved * 10;

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
        SELECT submission_id, title, status, submitted_at, submission_data
        FROM submissions WHERE org_id = $org_id
        ORDER BY submitted_at DESC LIMIT 10
    ");
    while ($row = mysqli_fetch_assoc($r)) { $recent_submissions[] = $row; }

    // ── Event status counts (from submission_data JSON dates) ─────────────
    $ev_ongoing   = 0;
    $ev_completed = 0;
    $ev_upcoming  = 0;
    $now_dt = new DateTime();
    foreach ($recent_submissions as $sub) {
        $sd = json_decode($sub['submission_data'] ?? '', true);
        $evS = $sd['fields']['proposed_start_date'] ?? null;
        $evE = $sd['fields']['proposed_end_date']   ?? null;
        if (!$evS && !$evE) continue;
        try {
            $dtS = $evS ? (DateTime::createFromFormat('F j, Y \a\t h:i A', $evS) ?: new DateTime($evS)) : null;
            $dtE = $evE ? new DateTime($evE) : null;
            if ($dtE) $dtE->setTime(23, 59, 59);
            if ($dtS && $now_dt < $dtS)                                  $ev_upcoming++;
            elseif ($dtS && $dtE && $now_dt >= $dtS && $now_dt <= $dtE)  $ev_ongoing++;
            elseif ($dtE && $now_dt > $dtE)                              $ev_completed++;
        } catch (Exception $e) {}
    }

    $r = mysqli_query($conn, "
        SELECT report_id, title, report_type, created_at
        FROM reports WHERE generated_by = $org_id
        ORDER BY created_at DESC LIMIT 20
    ");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $saved_reports[] = $row; } }

    // ── Org Rankings leaderboard ──────────────────────────────────────────
    $r = mysqli_query($conn, "
        SELECT u.user_id, u.org_name,
               COUNT(s.submission_id)                              AS total_reports,
               SUM(s.status = 'approved')                         AS reports_approved,
               SUM(s.status IN ('pending','in_review'))           AS reports_pending,
               SUM(s.status = 'approved') * 10                    AS points
        FROM users u
        LEFT JOIN submissions s ON s.org_id = u.user_id
        WHERE u.role = 'user' AND u.status = 'active'
        GROUP BY u.user_id, u.org_name
        ORDER BY points DESC, reports_approved DESC
    ");
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $leaderboard[] = $row; } }

    mysqli_close($conn);
}

$approval_rate  = $total_submissions > 0 ? round(($approved / $total_submissions) * 100) : 0;
$max_lb_points  = !empty($leaderboard) ? max(array_map(fn($l) => (int)$l['points'], $leaderboard)) : 1;
$max_lb_points  = $max_lb_points ?: 1; // avoid division by zero
// Find current org's rank
$my_rank = 0;
foreach ($leaderboard as $idx => $lb) {
    if ((int)$lb['user_id'] === $org_id) { $my_rank = $idx + 1; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accomplishment Reports - OrgHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <link rel="stylesheet" href="../css/reports.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
</head>
<body>

<?php include '../php/navbar.php'; ?>
    <?php include '../php/topbar.php'; ?>

    <!-- main-content layout controlled by reports.css -->

    <div class="reports-container">

        <!-- ── Page Header ───────────────────────────────────────────────── -->
        <div class="reports-header">
            <div class="reports-header-left">
                <div class="reports-header-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="reports-header-text">
                    <h1>Accomplishment Reports</h1>
                    <p>Review event reports submitted by organizations, approve or reject, and track rankings.</p>
                </div>
            </div>
            <div class="reports-header-right">
                <button class="btn-export-excel" id="btnExportExcel">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button class="btn-export-pdf" id="btnExportPDF">
                    <i class="fas fa-file-pdf"></i> Export PDF
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
                    <div class="rp-stat-label">Total Reports</div>
                </div>
            </div>
            <div class="rp-stat-divider"></div>
            <div class="rp-stat">
                <div class="rp-stat-icon rp-stat-icon--pulse" style="background:#dcfce7;color:#16a34a;">
                    <span class="rp-pulse-dot"></span>
                    <i class="fas fa-circle-dot"></i>
                </div>
                <div>
                    <div class="rp-stat-num"><?= $ev_ongoing ?></div>
                    <div class="rp-stat-label">Ongoing</div>
                </div>
            </div>
            <div class="rp-stat-divider"></div>
            <div class="rp-stat">
                <div class="rp-stat-icon" style="background:#f1f5f9;color:#475569;"><i class="fas fa-flag-checkered"></i></div>
                <div>
                    <div class="rp-stat-num"><?= $ev_completed ?></div>
                    <div class="rp-stat-label">Completed</div>
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
                <div class="rp-stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-star"></i></div>
                <div>
                    <div class="rp-stat-num"><?= $points_issued ?></div>
                    <div class="rp-stat-label">Points Issued</div>
                </div>
            </div>
        </div>

        <!-- ── Tabs ───────────────────────────────────────────────────────── -->
        <div class="rp-tabs" style="display:flex;gap:0;border-bottom:2px solid #e8f0eb;margin-top:0.3rem;">
            <button class="rp-tab active" id="tabReports" onclick="switchTab('reports')"
                style="padding:.6rem 1.4rem;font-size:.88rem;font-weight:700;border:none;background:none;cursor:pointer;border-bottom:3px solid #2d6a4f;color:#2d6a4f;margin-bottom:-2px;">
                <i class="fas fa-file-alt" style="margin-right:.4rem;"></i> Reports
            </button>
            <button class="rp-tab" id="tabRankings" onclick="switchTab('rankings')"
                style="padding:.6rem 1.4rem;font-size:.88rem;font-weight:700;border:none;background:none;cursor:pointer;border-bottom:3px solid transparent;color:#6b8f7a;margin-bottom:-2px;">
                <i class="fas fa-medal" style="margin-right:.4rem;"></i> Org Rankings
            </button>
        </div>

        <!-- ── TAB: Reports ───────────────────────────────────────────────── -->
        <div id="panelReports">

        <!-- ── Two-column layout: Accomplishment Cards (main) + Sidebar ─── -->
        <div class="rp-main-layout">

            <!-- ════════════════════════════════════════
                 LEFT: Accomplishment Report Cards (hero)
                 ════════════════════════════════════════ -->
            <div class="rp-accmpl-col">

                <div class="rp-accmpl-header">
                    <div class="rp-accmpl-header-left">
                        <i class="fas fa-trophy"></i>
                        <div>
                            <h2>Accomplishment Reports</h2>
                            <p>All submitted project &amp; event accomplishments from your organization</p>
                        </div>
                    </div>
                    <div class="rp-accmpl-filters">
                        <button class="rp-filter-btn active" onclick="filterCards('all', this)">All</button>
                        <button class="rp-filter-btn" onclick="filterCards('ongoing', this)">Ongoing</button>
                        <button class="rp-filter-btn" onclick="filterCards('completed', this)">Completed</button>
                        <button class="rp-filter-btn" onclick="filterCards('upcoming', this)">Upcoming</button>
                    </div>
                </div>

                <div class="rp-cards-grid" id="rpCardsGrid">
                <?php if (empty($recent_submissions)): ?>
                    <div class="chart-empty" style="padding:3rem;grid-column:1/-1;">
                        <i class="fas fa-folder-open"></i>
                        <p>No accomplishment reports yet</p>
                        <small>Submit a project proposal to get started</small>
                    </div>
                <?php else:
                    $statusMap = [
                        'approved'                    => ['label'=>'Approved',          'class'=>'st-approved',        'icon'=>'fa-check-circle'],
                        'rejected'                    => ['label'=>'Rejected',           'class'=>'st-rejected',        'icon'=>'fa-times-circle'],
                        'pending'                     => ['label'=>'Pending',            'class'=>'st-pending',         'icon'=>'fa-clock'],
                        'in_review'                   => ['label'=>'Under Review',       'class'=>'st-review',          'icon'=>'fa-search'],
                        'approved_for_recommendation' => ['label'=>'For Recommendation', 'class'=>'st-recommendation',  'icon'=>'fa-user-check'],
                        'archived'                    => ['label'=>'Archived',           'class'=>'st-archived',        'icon'=>'fa-archive'],
                    ];
                    $now = new DateTime();
                    foreach ($recent_submissions as $i => $sub):
                        $st    = $statusMap[$sub['status']] ?? ['label'=>ucfirst($sub['status']),'class'=>'st-pending','icon'=>'fa-file'];
                        $sData = json_decode($sub['submission_data'] ?? '', true);
                        $fields= $sData['fields'] ?? [];

                        // Event metadata
                        $evStart  = $fields['proposed_start_date'] ?? null;
                        $evEnd    = $fields['proposed_end_date']   ?? null;
                        $projTitle= $fields['project_title']       ?? '';
                        $projType = $fields['project_type']        ?? '';
                        $projInv  = $fields['project_involvement'] ?? '';
                        $location = $fields['project_location']    ?? '';
                        $summary  = $fields['project_summary']     ?? ($fields['opening_statement'] ?? '');
                        $goal     = $fields['project_goal']        ?? '';
                        $budget   = $fields['budget_total']        ?? '';
                        $partici  = $fields['number_participants'] ?? '';
                        $sender   = $fields['sender_name']         ?? '';
                        $orgName  = $sData['organization_name']    ?? '';
                        // Meeting minutes / event proposal fields
                        $evtName  = $fields['event_name']   ?? '';
                        $location = $location ?: ($fields['location'] ?? '');
                        $agenda   = $fields['agenda']        ?? '';
                        $tplName  = $sData['template_name'] ?? '';

                        // Event status
                        $evLabel = ''; $evClass = ''; $evInner = ''; $evFilter = 'other';
                        if ($evStart || $evEnd) {
                            try {
                                $dtStart = $evStart ? (DateTime::createFromFormat('F j, Y 	 h:i A', $evStart) ?: new DateTime($evStart)) : null;
                                $dtEnd   = $evEnd   ? new DateTime($evEnd) : null;
                                if ($dtEnd) $dtEnd->setTime(23,59,59);
                                if ($dtStart && $now < $dtStart) {
                                    $evLabel='Upcoming'; $evClass='ev-upcoming'; $evInner='<i class="fas fa-calendar-alt ev-icon"></i>'; $evFilter='upcoming';
                                } elseif ($dtStart && $dtEnd && $now>=$dtStart && $now<=$dtEnd) {
                                    $evLabel='Ongoing';  $evClass='ev-ongoing';  $evInner='<span class="ev-dot"></span>';                $evFilter='ongoing';
                                } elseif ($dtEnd && $now>$dtEnd) {
                                    $evLabel='Completed';$evClass='ev-completed';$evInner='<i class="fas fa-check-circle ev-icon"></i>';  $evFilter='completed';
                                }
                            } catch(Exception $e) {}
                        }

                        // Format dates nicely
                        $startFmt = '';
                        $endFmt   = '';
                        if ($evStart) { try { $startFmt = (DateTime::createFromFormat('F j, Y 	 h:i A',$evStart) ?: new DateTime($evStart))->format('M d, Y'); } catch(Exception $e){} }
                        if ($evEnd)   { try { $endFmt   = (new DateTime($evEnd))->format('M d, Y'); } catch(Exception $e){} }

                        // Card accent color based on event status
                        $accentMap = ['ongoing'=>'#10b981','completed'=>'#64748b','upcoming'=>'#f59e0b','other'=>'#2d6a4f'];
                        $accent = $accentMap[$evFilter];
                        $displayTitle = $projTitle ?: $evtName ?: $sub['title'];
                ?>
                <div class="rp-accmpl-card" data-ev-filter="<?= $evFilter ?>">
                    <!-- Top accent stripe -->
                    <div class="rp-card-stripe" style="background:<?= $accent ?>;"></div>

                    <!-- Card header -->
                    <div class="rp-card-top">
                        <div class="rp-card-top-left">
                            <div class="rp-card-icon" style="background:<?= $accent ?>18;color:<?= $accent ?>;">
                                <i class="fas fa-<?= ($evFilter==='ongoing')?'bolt':($evFilter==='completed'?'flag-checkered':($evFilter==='upcoming'?'calendar-alt':'file-alt')) ?>"></i>
                            </div>
                            <div>
                                <div class="rp-card-title"><?= htmlspecialchars($displayTitle ?: $sub['title']) ?></div>
                                <?php if ($tplName): ?>
                                <div class="rp-card-template"><i class="fas fa-file-contract"></i> <?= htmlspecialchars($tplName) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="rp-card-badges">
                            <?php if ($evLabel): ?>
                            <span class="ev-badge <?= $evClass ?>"><?= $evInner ?><?= $evLabel ?></span>
                            <?php endif; ?>
                            <span class="status-pill <?= $st['class'] ?>"><i class="fas <?= $st['icon'] ?>"></i> <?= $st['label'] ?></span>
                        </div>
                    </div>

                    <!-- Meta row -->
                    <div class="rp-card-meta">
                        <?php if ($startFmt || $endFmt): ?>
                        <span class="rp-meta-item"><i class="fas fa-calendar"></i>
                            <?= $startFmt ?><?= ($startFmt && $endFmt) ? ' — ' . $endFmt : $endFmt ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($location): ?>
                        <span class="rp-meta-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($location) ?></span>
                        <?php endif; ?>
                        <?php if ($partici): ?>
                        <span class="rp-meta-item"><i class="fas fa-users"></i> <?= htmlspecialchars($partici) ?> participants</span>
                        <?php endif; ?>
                        <?php if ($budget): ?>
                        <span class="rp-meta-item"><i class="fas fa-coins"></i> <?= htmlspecialchars($budget) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Tags row -->
                    <?php if ($projType || $projInv): ?>
                    <div class="rp-card-tags">
                        <?php if ($projType): foreach(explode(',',$projType) as $t): $t=trim($t); if(!$t) continue; ?>
                        <span class="rp-tag rp-tag-type"><?= htmlspecialchars($t) ?></span>
                        <?php endforeach; endif; ?>
                        <?php if ($projInv): foreach(explode(',',$projInv) as $v): $v=trim($v); if(!$v) continue; ?>
                        <span class="rp-tag rp-tag-inv"><?= htmlspecialchars($v) ?></span>
                        <?php endforeach; endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Summary -->
                    <?php $summaryText = $summary ?: $goal ?: $agenda; if ($summaryText): ?>
                    <p class="rp-card-summary"><?= htmlspecialchars(mb_strimwidth($summaryText, 0, 160, '…')) ?></p>
                    <?php endif; ?>

                    <!-- Footer -->
                    <div class="rp-card-footer">
                        <span class="rp-card-date"><i class="fas fa-clock"></i> Submitted <?= date('M d, Y', strtotime($sub['submitted_at'])) ?></span>
                        <?php if ($orgName): ?>
                        <span class="rp-card-org"><i class="fas fa-building"></i> <?= htmlspecialchars($orgName) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; endif; ?>
                </div><!-- /.rp-cards-grid -->
            </div><!-- /.rp-accmpl-col -->

            <!-- ════════════════════════════════════════
                 RIGHT: Sidebar (Charts + Saved Reports)
                 ════════════════════════════════════════ -->
            <div class="rp-sidebar-col">

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
                                <div class="legend-item"><span class="legend-dot" style="background:#10b981"></span>Approved<strong><?= $approved ?></strong></div>
                                <div class="legend-item"><span class="legend-dot" style="background:#f59e0b"></span>Pending / In Review<strong><?= $pending ?></strong></div>
                                <div class="legend-item"><span class="legend-dot" style="background:#ef4444"></span>Rejected<strong><?= $rejected ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approval Rate -->
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
                            <span><?= $approval_rate ?>% of <?= $total_submissions ?> submissions</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Submissions -->
                <div class="report-card">
                    <div class="report-card-header">
                        <div class="report-card-header-left">
                            <i class="fas fa-chart-bar"></i>
                            <h3>Monthly (Last 6 Months)</h3>
                        </div>
                    </div>
                    <div class="report-card-body">
                        <?php if (empty($monthly_data)): ?>
                            <div class="chart-empty"><i class="fas fa-chart-bar"></i><p>No data yet</p></div>
                        <?php else: $max_val = max(array_map(fn($m)=>(int)$m['total'],$monthly_data))?:1; ?>
                        <div class="monthly-progress-list">
                            <div class="mp-legend">
                                <span><span class="legend-dot" style="background:#10b981"></span> Approved</span>
                                <span><span class="legend-dot" style="background:#f59e0b"></span> Pending</span>
                                <span><span class="legend-dot" style="background:#ef4444"></span> Rejected</span>
                            </div>
                            <?php foreach ($monthly_data as $m):
                                $total=$m['total']; $approved=$m['approved']; $pending=$m['pending']; $rejected=$m['rejected'];
                                $pctA=$total>0?round(($approved/$total)*100):0;
                                $pctP=$total>0?round(($pending/$total)*100):0;
                                $pctR=$total>0?100-$pctA-$pctP:0;
                                $bw=$max_val>0?round(($total/$max_val)*100):0;
                            ?>
                            <div class="mp-row">
                                <div class="mp-month"><?= htmlspecialchars($m['month']) ?></div>
                                <div class="mp-bar-wrap">
                                    <div class="mp-bar-outer" style="width:<?= $bw ?>%">
                                        <?php if($pctA>0):?><div class="mp-seg mp-approved" style="width:<?= $pctA ?>%" title="Approved: <?= $approved ?>"></div><?php endif;?>
                                        <?php if($pctP>0):?><div class="mp-seg mp-pending"  style="width:<?= $pctP ?>%" title="Pending: <?= $pending ?>"></div><?php endif;?>
                                        <?php if($pctR>0):?><div class="mp-seg mp-rejected" style="width:<?= $pctR ?>%" title="Rejected: <?= $rejected ?>"></div><?php endif;?>
                                    </div>
                                </div>
                                <div class="mp-total"><?= $total ?> <span>total</span></div>
                                <div class="mp-counts">
                                    <?php if($approved>0):?><span class="mp-count mp-c-green"><?= $approved ?> approved</span><?php endif;?>
                                    <?php if($pending>0): ?><span class="mp-count mp-c-amber"><?= $pending  ?> pending</span><?php endif;?>
                                    <?php if($rejected>0):?><span class="mp-count mp-c-red"><?= $rejected  ?> rejected</span><?php endif;?>
                                </div>
                            </div>
                            <?php endforeach; ?>
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

            </div><!-- /.rp-sidebar-col -->
        </div><!-- /.rp-main-layout -->

        </div><!-- /#panelReports -->

        <!-- ── TAB: Org Rankings ─────────────────────────────────────────── -->
        <div id="panelRankings" style="display:none;">

            <div class="report-card">
                <div class="report-card-header">
                    <div class="report-card-header-left">
                        <i class="fas fa-trophy" style="color:#f59e0b;"></i>
                        <h3>Organization Leaderboard</h3>
                    </div>
                    <span style="font-size:0.75rem;color:#9ab5ac;margin-left:auto;">Ranked by total approved points</span>
                </div>
        <div class="report-card-body" style="padding:0;">
            <?php if (empty($leaderboard)): ?>
            <div class="chart-empty" style="padding:2.5rem;">
                <i class="fas fa-trophy"></i>
                <p>No organizations found</p>
            </div>
            <?php else: ?>
            <div class="report-table-wrap">
                <table class="report-table" id="leaderboardTable">
                    <thead>
                        <tr>
                            <th style="width:48px;">#</th>
                            <th>Organization</th>
                            <th style="text-align:center;">Reports Approved</th>
                            <th style="text-align:center;">Avg Points/Pts</th>
                            <th style="min-width:160px;">Progress</th>
                            <th style="text-align:right;">%</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($leaderboard as $rank => $lb):
                        $isMe       = (int)$lb['user_id'] === $org_id;
                        $pts        = (int)$lb['points'];
                        $rapproved  = (int)$lb['reports_approved'];
                        $avgPts     = $rapproved > 0 ? round($pts / $rapproved) : 0;
                        $pct        = $max_lb_points > 0 ? round(($pts / $max_lb_points) * 100) : 0;
                        $rankNum    = $rank + 1;
                        $medalColor = match($rankNum) { 1 => '#f59e0b', 2 => '#94a3b8', 3 => '#cd7f32', default => '#9ab5ac' };
                        $rowStyle   = $isMe ? 'background:#f0faf5;font-weight:700;' : '';
                    ?>
                    <tr style="<?= $rowStyle ?>">
                        <td style="text-align:center;">
                            <?php if ($rankNum <= 3): ?>
                            <i class="fas fa-medal" style="color:<?= $medalColor ?>;font-size:1rem;"></i>
                            <?php else: ?>
                            <span style="color:#9ab5ac;font-weight:600;"><?= $rankNum ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="<?= $isMe ? 'color:#2d6a4f;' : '' ?>">
                                <?= htmlspecialchars($lb['org_name']) ?>
                                <?php if ($isMe): ?><span style="font-size:0.72rem;background:#e3f2eb;color:#2d6a4f;border-radius:20px;padding:0.1rem 0.5rem;margin-left:0.4rem;font-weight:700;">You</span><?php endif; ?>
                            </span>
                        </td>
                        <td style="text-align:center;"><?= $rapproved ?></td>
                        <td style="text-align:center;color:#7c3aed;font-weight:700;"><?= $avgPts ?> pts</td>
                        <td>
                            <div style="background:#e8f0eb;border-radius:20px;height:8px;overflow:hidden;">
                                <div style="height:100%;border-radius:20px;width:<?= $pct ?>%;background:linear-gradient(90deg,#2d6a4f,#52b788);transition:width .4s;"></div>
                            </div>
                        </td>
                        <td style="text-align:right;font-size:0.82rem;color:#6b8f7a;"><?= $pct ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($my_rank > 0): ?>
            <div style="padding:.75rem 1.4rem;font-size:.8rem;color:#6b8f7a;background:#f6faf7;border-top:1px solid #e8f0eb;">
                <i class="fas fa-info-circle" style="margin-right:.35rem;color:#2d6a4f;"></i>
                Your organization is ranked <strong style="color:#2d6a4f;">#<?= $my_rank ?></strong> out of <?= count($leaderboard) ?> organizations with <strong style="color:#7c3aed;"><?= $points_issued ?> pts</strong>.
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

        </div><!-- /#panelRankings -->

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
        rate:     <?= $approval_rate ?>,
        points:   <?= $points_issued ?>
    },
    monthly: <?= json_encode($monthly_data) ?>,
    recent:  <?= json_encode(array_map(function($s) {
        $d = json_decode($s['submission_data'] ?? '', true);
        return [
            'submission_id' => $s['submission_id'],
            'title'         => $s['title'],
            'status'        => $s['status'],
            'submitted_at'  => $s['submitted_at'],
            'event_start'   => $d['fields']['proposed_start_date'] ?? null,
            'event_end'     => $d['fields']['proposed_end_date']   ?? null,
        ];
    }, $recent_submissions)) ?>
};

// ── Tab switching ─────────────────────────────────────────────────────────
function switchTab(tab) {
    const isReports  = tab === 'reports';
    document.getElementById('panelReports').style.display  = isReports  ? '' : 'none';
    document.getElementById('panelRankings').style.display = !isReports ? '' : 'none';

    const tR = document.getElementById('tabReports');
    const tK = document.getElementById('tabRankings');
    tR.style.borderBottomColor = isReports  ? '#2d6a4f' : 'transparent';
    tR.style.color             = isReports  ? '#2d6a4f' : '#6b8f7a';
    tK.style.borderBottomColor = !isReports ? '#2d6a4f' : 'transparent';
    tK.style.color             = !isReports ? '#2d6a4f' : '#6b8f7a';
}

function filterCards(filter, btn) {
    // Update active button
    document.querySelectorAll('.rp-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Show/hide cards
    document.querySelectorAll('.rp-accmpl-card').forEach(card => {
        const evf = card.getAttribute('data-ev-filter');
        card.classList.toggle('hidden', filter !== 'all' && evf !== filter);
    });
}
</script>
<script src="../js/script.js"></script>
<script src="../js/navbar.js"></script>
<script src="../js/notifications.js"></script>
<script src="../js/reports.js"></script>
</body>
</html>