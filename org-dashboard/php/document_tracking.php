<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$userId = $_SESSION['user_id'];

// Fetch org profile for auto-fill in template modal
$orgUser = [];
if ($conn) {
    $orgStmt = mysqli_prepare($conn, "SELECT org_name, description FROM users WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($orgStmt, 'i', $userId);
    mysqli_stmt_execute($orgStmt);
    $orgResult = mysqli_stmt_get_result($orgStmt);
    $orgUser = mysqli_fetch_assoc($orgResult) ?: [];
    mysqli_stmt_close($orgStmt);
}
$autoOrgName    = htmlspecialchars($orgUser['org_name']    ?? '', ENT_QUOTES);
$autoOrgTagline = htmlspecialchars($orgUser['description'] ?? '', ENT_QUOTES);

$submissions = [];
if ($conn) {
    $submissionsQuery = "
        SELECT s.submission_id, s.title, s.submitted_at,
               u.full_name,
               COALESCE(u.org_name, u.full_name) AS display_org,
               s.status,
               COALESCE(r.feedback, 'Awaiting review') AS admin_remarks,
               s.file_name,
               s.file_path,
               s.description,
               s.submission_data,
               IF(
                   (s.file_name IS NOT NULL AND s.file_name <> '')
                   OR (s.file_path IS NOT NULL AND s.file_path <> ''),
                   1, 0
               ) AS has_file,
               NULL AS file_size
        FROM   submissions s
        JOIN   users u ON s.submitted_by = u.user_id
        LEFT JOIN reviews r ON s.submission_id = r.submission_id
        WHERE  s.submitted_by = ?
        ORDER  BY s.submitted_at DESC
    ";
    $stmt = $conn->prepare($submissionsQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $submissions[] = $row;
    $stmt->close();

    // Stats
    $total    = count($submissions);
    $pending  = count(array_filter($submissions, fn($s) => $s['status'] === 'pending'));
    $approved = count(array_filter($submissions, fn($s) => $s['status'] === 'approved'));
    $rejected = count(array_filter($submissions, fn($s) => $s['status'] === 'rejected'));
    $in_review= count(array_filter($submissions, fn($s) => $s['status'] === 'in_review'));
}

/* ── Helpers ── */
function fileTypeBadge(string $fileName): string {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $map = [
        'pdf'  => ['PDF',  '#ef4444'],
        'docx' => ['DOCX', '#1d4ed8'],
        'doc'  => ['DOC',  '#1d4ed8'],
        'xlsx' => ['XLSX', '#16a34a'],
        'xls'  => ['XLS',  '#16a34a'],
    ];
    [$label, $color] = $map[$ext] ?? [strtoupper($ext) ?: 'FILE', '#6b7280'];
    return '<span class="file-type-badge" style="background:' . $color . '">' . $label . '</span>';
}

function docIconBox(string $fileName, bool $isTemplate = false): string {
    if ($isTemplate) {
        return '<div class="doc-icon-wrap tpl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg></div>';
    }
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        return '<div class="doc-icon-wrap pdf"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 13h1a1 1 0 0 1 0 2H9v-2z"/><path d="M9 17h6"/></svg></div>';
    }
    if (in_array($ext, ['doc','docx'])) {
        return '<div class="doc-icon-wrap word"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="8 13 10 17 12 13 14 17 16 13"/></svg></div>';
    }
    if (in_array($ext, ['xls','xlsx'])) {
        return '<div class="doc-icon-wrap excel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="17"/><line x1="16" y1="13" x2="8" y2="17"/></svg></div>';
    }
    return '<div class="doc-icon-wrap gen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>';
}

function b64img($path) {
    $abs = realpath(__DIR__ . '/' . $path);
    if (!$abs || !file_exists($abs)) return '';
    $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $mime = in_array($ext, ['jpg','jpeg']) ? 'image/jpeg' : ($ext === 'png' ? 'image/png' : 'image/webp');
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
}

$plspLogoB64 = b64img('../../plsplogo.png');

// ── Full org name map (filename without ext => full name) ─────────────────
$orgNameMap = [
    // Mandated Organizations
    'USP'           => 'University Student Parliament',
    'PDC'           => 'PLSP Dance Company',
    'PC'            => 'PLSP Chorale',
    'CRCY'          => 'CRCY-PLSP',
    // Mandated Student Publication
    'BS'            => 'Bagong Sinag',
    // Independent Student Organization
    'CIG'           => 'Council of Interior Governance',
    // Student Council / Admin
    'Admission'     => 'Pamantasan ng Lungsod ng San Pablo',
    'OSAS'          => 'Office of Student Affairs and Services',
    'COA'           => 'College of Accountancy',
    'CAS'           => 'College of Arts and Sciences',
    'CBAM'          => 'College of Business Administration and Management',
    'CCSE'          => 'College of Computing Science and Engineering',
    'CTHM'          => 'College of Tourism Hospitality and Management',
    'CTHED'         => 'College of Teacher Education',
    'CNAHS'         => 'College of Nursing and Allied Health Sciences',
    // Academic Organizations
    'AIS'           => 'Accounting Information Society',
    'ACES'          => 'Alliance of Competent English Students',
    'APA'           => 'Alliance of Public Administration',
    'ATMS'          => 'Alliance of Tourism and Management Students',
    'ASO'           => 'ATHLEADS Students Organization',
    'FMS'           => 'Financial Management Society',
    'HMS'           => 'Hotelier Management Society',
    'HRDMS'         => 'Human Resource Development Management Society',
    'ISC'           => 'Information System Club',
    'ITS'           => 'Information Technology Society',
    'MAS'           => 'Management Accounting Society',
    'MS'            => 'Manthanien Society',
    'MAPAP'         => 'MAPAP Society',
    'MMS'           => 'Marketing Management Society',
    'NSS'           => 'Natural Science Society',
    'OAS'           => 'Office Administration Society',
    'PCL'           => 'PLSP Communicators\' League',
    'ECOS'          => 'PLSP Economics Society – EcoS',
    'JPIA'          => 'PLSP Junior Institute Philippine Accountant (JPIA)',
    'SASI'          => 'PLSP Samahang Sikolohiya',
    'POLSO'         => 'Political Science Society',
    'SFCE'          => 'Society of Future Computer Engineers',
    'SFIE'          => 'Society of Future Industrial Engineers',
    'SJE'           => 'Society of Junior Entrepreneurs',
    'TVEA'          => 'Technical-Vocational Education Association',
    'USAF'          => 'Umuusbong na Samahang may Atikha sa Filipino',
    'UAEE'          => 'United Association of Elementary Educators',
    'YES'           => 'Young Executive Society',
    // Socio-Cultural Organizations
    'CMH'           => 'PLSP Center for Mental Health',
    'ALIW'          => 'PLSP Association of LGBTQIA+ towards Inclusivity and Diversity (ALIW)',
    'RC'            => 'Rotaract Club',
    'YMCA'          => 'Youth Movement for Community Advocacies (YMCA)',
    // Religious Organizations (no logos)
    'HODCYO'        => 'House of David Christian Youth Organization',
    'IGNITE'        => 'Igniting Generations, Nurturing Individuals for the Truth Eternal (IGNITE)',
    'FSP'           => 'PLSP×FSP',
    'YLC'           => 'Youth LIFE Club',
];

// Friendly display name — use map if available, else fallback to filename
function assetLabel(string $filename, array $map = []): string {
    $key = pathinfo($filename, PATHINFO_FILENAME);
    return $map[$key] ?? ucwords(str_replace(['_','-'], ' ', $key));
}

// Dynamically scan Assets folder for all images
$assetsDir  = realpath(__DIR__ . '/../../Assets/');
$assetLogos = []; // [ 'filename' => ['b64'=>'...', 'label'=>'...'] ]
if ($assetsDir && is_dir($assetsDir)) {
    $allowed = ['jpg','jpeg','png','webp','gif'];
    foreach (scandir($assetsDir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $b64 = b64img('../../Assets/' . $file);
        if ($b64) $assetLogos[$file] = [
            'b64'   => $b64,
            'label' => assetLabel($file, $orgNameMap),
        ];
    }
}

// Admission logo from Assets (left logo in template output header)
$admissionLogoB64 = '';
if ($assetsDir && is_dir($assetsDir)) {
    foreach (scandir($assetsDir) as $file) {
        if (stripos(pathinfo($file, PATHINFO_FILENAME), 'admission') !== false) {
            $admissionLogoB64 = b64img('../../Assets/' . $file);
            break;
        }
    }
}
if (!$admissionLogoB64) $admissionLogoB64 = $plspLogoB64; // fallback

// Religious orgs — no logo files, add as text-only entries
$religiousOrgs = [
    'HODCYO' => 'House of David Christian Youth Organization',
    'IGNITE' => 'Igniting Generations, Nurturing Individuals for the Truth Eternal (IGNITE)',
    'FSP'    => 'PLSP×FSP',
    'YLC'    => 'Youth LIFE Club',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents – OrgHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <link rel="stylesheet" href="../css/document_tracking.css">
    <style>
        /* docx preview overrides */
        #previewDocxWrap { background: #e8e8e8; }
        #previewDocxWrap .docx-wrapper { background:#e8e8e8!important;padding:16px!important; }
        #previewDocxWrap .docx-wrapper>section.docx { width:100%!important;max-width:900px!important;min-height:auto!important;margin:0 auto 16px auto!important;padding:72px 90px!important;box-shadow:0 2px 12px rgba(0,0,0,.2)!important;box-sizing:border-box!important;overflow:visible!important;background:#fff!important; }
        #previewDocxWrap img,#previewDocxWrap svg image { max-width:100%!important;height:auto!important;visibility:visible!important;display:inline-block!important; }
        #previewDocxWrap table { max-width:100%!important;table-layout:fixed!important;word-break:break-word!important; }
        #previewDocxWrap [style*="position:absolute"],#previewDocxWrap [style*="position: absolute"] { position:relative!important;left:auto!important;top:auto!important;transform:none!important;margin:0!important; }
        #previewDocxWrap header table,#previewDocxWrap .docx-wrapper header table { width:100%!important;table-layout:fixed!important;border-collapse:collapse!important; }
        #previewDocxWrap header td,#previewDocxWrap .docx-wrapper header td { vertical-align:middle!important;text-align:center!important;overflow:visible!important;padding:4px!important; }
        #previewDocxWrap header img,#previewDocxWrap .docx-wrapper header img { position:relative!important;display:block!important;margin:0 auto!important;max-width:100%!important;max-height:120px!important;width:auto!important;height:auto!important;left:auto!important;top:auto!important;transform:none!important;visibility:visible!important; }
    </style>
</head>
<body>

<?php include '../php/navbar.php'; ?>
    <?php include '../php/topbar.php'; ?>

    <div class="document-container">

        <!-- Page Header -->
        <div class="document-header">
            <div class="document-header-left">
                <div class="document-header-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div>
                    <h1>Documents</h1>
                    <p>Track and manage your submitted documents</p>
                </div>
            </div>
            <button class="btn-upload" id="openUploadModal">
                <i class="fas fa-cloud-upload-alt"></i> Upload Document
            </button>
        </div>

        <!-- Stats Bar -->
        <div class="doc-stats-bar">
            <div class="doc-stat">
                <div class="doc-stat-icon" style="background:#e3f2eb;color:#2d6a4f;">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="doc-stat-body">
                    <span class="doc-stat-num"><?= $total ?? 0 ?></span>
                    <span class="doc-stat-label">Total Documents</span>
                </div>
            </div>
            <div class="doc-stat-divider"></div>
            <div class="doc-stat">
                <div class="doc-stat-icon" style="background:#dcfce7;color:#16a34a;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="doc-stat-body">
                    <span class="doc-stat-num"><?= $approved ?? 0 ?></span>
                    <span class="doc-stat-label">Approved</span>
                </div>
            </div>
            <div class="doc-stat-divider"></div>
            <div class="doc-stat">
                <div class="doc-stat-icon" style="background:#fef9c3;color:#ca8a04;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="doc-stat-body">
                    <span class="doc-stat-num"><?= $pending ?? 0 ?></span>
                    <span class="doc-stat-label">Pending</span>
                </div>
            </div>
            <div class="doc-stat-divider"></div>
            <div class="doc-stat">
                <div class="doc-stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                    <i class="fas fa-search"></i>
                </div>
                <div class="doc-stat-body">
                    <span class="doc-stat-num"><?= $in_review ?? 0 ?></span>
                    <span class="doc-stat-label">In Review</span>
                </div>
            </div>
            <div class="doc-stat-divider"></div>
            <div class="doc-stat">
                <div class="doc-stat-icon" style="background:#fee2e2;color:#dc2626;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="doc-stat-body">
                    <span class="doc-stat-num"><?= $rejected ?? 0 ?></span>
                    <span class="doc-stat-label">Rejected</span>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="doc-toolbar">
            <div class="doc-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by title or file name…">
            </div>
            <div class="doc-filters">
                <select id="statusFilter" class="doc-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="in_review">In Review</option>
                </select>
                <div style="position:relative;display:flex;align-items:center;">
                    <input type="date" id="dateFilter">
                    <button id="clearDate" title="Clear date" style="position:absolute;right:10px;background:none;border:none;color:#9ca3af;cursor:pointer;font-size:0.85rem;padding:0;line-height:1;display:none;">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <?php if (empty($submissions)): ?>
            <div class="doc-empty">
                <div class="doc-empty-icon"><i class="fas fa-folder-open"></i></div>
                <h3>No documents yet</h3>
                <p>Upload your first document to start tracking submissions.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="documents-table" id="documentsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Document</th>
                            <th>Date Submitted</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th>Admin Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($submissions as $i => $doc):
                        $isTemplate  = strpos($doc['description'] ?? '', 'Template:') === 0 && !empty($doc['submission_data']);
                        $safeSubData = $isTemplate ? htmlspecialchars($doc['submission_data'], ENT_QUOTES, 'UTF-8') : '';
                        $ext         = strtolower(pathinfo($doc['file_name'] ?? '', PATHINFO_EXTENSION));
                        $iconBox      = docIconBox($doc['file_name'] ?? '', $isTemplate);
                        $submittedFmt = date('M d, Y', strtotime($doc['submitted_at']));
                    ?>
                    <tr data-title="<?= strtolower(htmlspecialchars($doc['title'])) ?>"
                        data-status="<?= strtolower($doc['status']) ?>"
                        data-date="<?= date('Y-m-d', strtotime($doc['submitted_at'])) ?>"
                        data-is-template="<?= $isTemplate ? '1' : '0' ?>"
                        data-submission-data="<?= $safeSubData ?>">

                        <td class="row-num"><?= $i + 1 ?></td>

                        <td>
                            <div class="doc-name-cell">
                                <?= $iconBox ?>
                                <div class="doc-meta-text">
                                    <span class="doc-title"><?= htmlspecialchars($doc['title']) ?></span>
                                    <span class="doc-sub">
                                        <?php if ($isTemplate): ?>
                                            <span class="file-type-badge" style="background:#7c3aed;">TEMPLATE</span>
                                        <?php elseif ($doc['file_name']): ?>
                                            <?= fileTypeBadge($doc['file_name']) ?>
                                        <?php else: ?>
                                            <em>No file</em>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td class="date-cell"><?= $submittedFmt ?></td>

                        <td>
                            <span class="submitter-name"><?= htmlspecialchars($doc['full_name']) ?></span>
                            <?php if (!empty($doc['display_org']) && $doc['display_org'] !== $doc['full_name']): ?>
                            <span class="submitter-org"><?= htmlspecialchars($doc['display_org']) ?></span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="status-badge <?= strtolower($doc['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $doc['status'])) ?>
                            </span>
                        </td>

                        <td class="remarks-cell">
                            <?php
                            $remark = $doc['admin_remarks'];
                            $st = strtolower($doc['status']);
                            if ($st === 'approved') {
                                $remarkIcon = '<i class="fas fa-check-circle" style="color:#16a34a;margin-right:5px;flex-shrink:0;"></i>';
                            } elseif ($st === 'rejected') {
                                $remarkIcon = '<i class="fas fa-times-circle" style="color:#dc2626;margin-right:5px;flex-shrink:0;"></i>';
                            } elseif ($st === 'in_review') {
                                $remarkIcon = '<i class="fas fa-search" style="color:#1d4ed8;margin-right:5px;flex-shrink:0;"></i>';
                            } elseif ($st === 'pending' && $remark === 'Awaiting review') {
                                $remarkIcon = '<i class="fas fa-hourglass-half" style="color:#ca8a04;margin-right:5px;flex-shrink:0;"></i>';
                            } else {
                                $remarkIcon = '<i class="fas fa-comment-alt" style="color:#6b9080;margin-right:5px;flex-shrink:0;"></i>';
                            }
                            $maxLen  = 60;
                            $isLong  = mb_strlen($remark) > $maxLen;
                            $preview = $isLong ? mb_substr($remark, 0, $maxLen) . '…' : $remark;
                            ?>
                            <div style="display:flex;align-items:flex-start;gap:4px;">
                                <?= $remarkIcon ?>
                                <span>
                                    <?= htmlspecialchars($preview) ?>
                                    <?php if ($isLong): ?>
                                        <button class="see-more-btn"
                                            data-idx="<?= $i ?>"
                                            style="background:none;border:none;color:#2d6a4f;font-size:0.78rem;font-weight:600;cursor:pointer;padding:0;margin-left:3px;font-family:inherit;white-space:nowrap;text-decoration:underline;position:relative;z-index:5;">
                                            See more
                                        </button>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </td>

                        <td class="actions-cell">
                            <?php if ($isTemplate): ?>
                            <button class="btn-action btn-view"
                                    title="View document"
                                    onclick="openTemplatePreview(this.closest('tr'))">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php elseif ($doc['has_file']): ?>
                            <button class="btn-action btn-view"
                                    title="View document"
                                    onclick="openPreviewModal(<?= $doc['submission_id'] ?>,'<?= $ext ?>','<?= addslashes(htmlspecialchars($doc['title'])) ?>')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php else: ?>
                            <span class="no-file"><i class="fas fa-ban"></i> No file</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <span id="rowCount"><?= count($submissions) ?> document(s)</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- No results -->
        <div id="docNoResults" style="display:none;flex-direction:column;align-items:center;justify-content:center;padding:3rem;gap:0.6rem;color:#8aaa92;font-size:0.9rem;">
            <i class="fas fa-search" style="font-size:1.8rem;"></i>
            <p>No documents match your search or filters.</p>
            <button onclick="document.getElementById('searchInput').value='';document.getElementById('statusFilter').value='';document.getElementById('dateFilter').value='';document.querySelectorAll('#documentsTable tbody tr').forEach(r=>r.style.display='');document.getElementById('rowCount').textContent='<?= count($submissions) ?> document(s)';this.closest('#docNoResults').style.display='none';" style="margin-top:0.4rem;background:#2d6a4f;color:white;border:none;padding:0.5rem 1.2rem;border-radius:40px;font-size:0.82rem;font-weight:600;cursor:pointer;font-family:inherit;">
                Clear Filters
            </button>
        </div>

    </div><!-- /.document-container -->
</main>

<!-- ── Upload Modal ─────────────────────────────────────────────────────── -->
<div id="uploadModal" class="modal">
    <div class="modal-content upload-modal-content">

        <!-- Left sidebar -->
        <div class="modal-sidebar">
            <button class="close-modal" id="closeUploadModal">&times;</button>
            <div class="modal-sidebar-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <h2 class="modal-sidebar-title">Upload Document</h2>
            <p class="modal-sidebar-sub">Submit documents to the Council of Internal Governance for review and approval.</p>
            <div class="modal-steps">
                <div class="modal-step active" id="step1">
                    <div class="step-dot"><i class="fas fa-pen"></i></div>
                    <span>Fill in details</span>
                </div>
                <div class="modal-step" id="step2">
                    <div class="step-dot"><i class="fas fa-paperclip"></i></div>
                    <span>Attach file or choose template</span>
                </div>
                <div class="modal-step" id="step3">
                    <div class="step-dot"><i class="fas fa-paper-plane"></i></div>
                    <span>Submit for review</span>
                </div>
            </div>
            <div class="modal-sidebar-footer">
                <i class="fas fa-check-circle"></i> Accepted: PDF, DOCX, XLSX &middot; Max 10 MB
            </div>
        </div>

        <!-- Right content -->
        <div class="modal-main">
            <div class="modal-main-tabs">
                <button class="tab-button active" data-tab="regular-upload">
                    <i class="fas fa-file-upload"></i> Regular Upload
                </button>
                <button class="tab-button" data-tab="template-upload">
                    <i class="fas fa-file-contract"></i> Use Template
                </button>
                <button class="close-modal" id="closeUploadModalAlt">&times;</button>
            </div>

            <div class="modal-main-body">
                <!-- Regular Upload -->
                <div id="regular-upload" class="tab-content active">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="docTitle">Document Title <span>*</span></label>
                            <input type="text" id="docTitle" name="title" required placeholder="Enter document title">
                        </div>
                        <div class="form-group">
                            <label for="docDescription">Description <small>(Optional)</small></label>
                            <textarea id="docDescription" name="description" rows="3" placeholder="Brief description of this document…"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="relatedEvent">Related Event <small>(Optional)</small></label>
                            <select id="relatedEvent" name="related_event">
                                <option value="">None</option>
                                <option value="Outreach Program">Outreach Program</option>
                                <option value="Quarterly Meeting">Quarterly Meeting</option>
                                <option value="Fundraising Gala">Fundraising Gala</option>
                                <option value="Team Building">Team Building</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>File Upload <span>*</span></label>
                            <label for="fileUpload" class="file-drop-zone" id="fileDropZone">
                                <div class="file-drop-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                <div class="file-drop-text">Drag &amp; drop your file here</div>
                                <div class="file-drop-or">or <span>browse to upload</span></div>
                                <div class="file-drop-hint">PDF, DOCX, XLSX &middot; Max 10 MB</div>
                                <div class="file-drop-name" id="fileDropName"></div>
                            </label>
                            <input type="file" id="fileUpload" name="file" accept=".pdf,.docx,.xlsx" required style="display:none;">
                        </div>
                    </form>
                </div>

                <!-- Template Upload -->
                <div id="template-upload" class="tab-content">
                    <form id="templateForm">
                        <div class="form-group">
                            <label for="templateTitle">Document Title <span>*</span></label>
                            <input type="text" id="templateTitle" name="title" placeholder="Enter document title" required>
                        </div>
                        <div class="form-group">
                            <label>Select Template <span>*</span></label>
                            <div class="tpl-input-wrap">
                                <input type="text" id="templateSelectDisplay" placeholder="Choose a template…" readonly
                                       onclick="toggleTplDropdown()" autocomplete="off" class="tpl-text-input">
                                <i class="fas fa-chevron-down tpl-chevron" id="tplChevron"></i>
                                <input type="hidden" id="templateSelect" name="template_id">
                                <div class="tpl-dropdown" id="tplDropdown">
                                    <div class="tpl-dropdown-item" data-value="" onclick="pickTemplate(this,'')">-- Choose a Template --</div>
                                    <div class="tpl-dropdown-item" data-value="meeting_minutes"  onclick="pickTemplate(this,'Meeting Minutes')">Meeting Minutes</div>
                                    <div class="tpl-dropdown-item" data-value="event_proposal"   onclick="pickTemplate(this,'Event Proposal')">Event Proposal</div>
                                    <div class="tpl-dropdown-item" data-value="financial_report" onclick="pickTemplate(this,'Financial Report')">Financial Report</div>
                                    <div class="tpl-dropdown-item" data-value="incident_report"  onclick="pickTemplate(this,'Incident Report')">Incident Report</div>
                                    <div class="tpl-dropdown-item" data-value="membership_form"  onclick="pickTemplate(this,'Membership Form')">Membership Form</div>
                                    <div class="tpl-dropdown-item" data-value="project_proposal" onclick="pickTemplate(this,'Project Proposal')">Project Proposal</div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Collaborated Logo <small>(Optional)</small></label>
                            <input type="hidden" id="collaboratedLogo" name="collaborated_logo" value="">
                            <div class="logo-picker" id="logoPicker">
                                <div class="logo-picker-input-wrap">
                                    <div class="logo-picker-thumb" id="logoPickerThumb"></div>
                                    <input type="text" id="logoPickerSearch" class="logo-picker-text-input"
                                           placeholder="No Collaborated Logo"
                                           oninput="filterLogos(this.value)"
                                           onfocus="openLogoPicker()"
                                           autocomplete="off">
                                    <i class="fas fa-chevron-down logo-picker-arrow" id="logoPickerArrow" onclick="openLogoPicker()"></i>
                                </div>
                                <div class="logo-picker-panel" id="logoPickerPanel" style="display:none;">
                                    <div class="logo-picker-list" id="logoPickerList">
                                        <div class="logo-picker-item selected" data-value="" data-label="" onclick="selectLogo('','','')">
                                            <div class="logo-picker-none"><i class="fas fa-ban"></i></div>
                                            <span>No Collaborated Logo</span>
                                        </div>
                                        <?php foreach ($assetLogos as $filename => $info): ?>
                                        <div class="logo-picker-item"
                                             data-value="<?= htmlspecialchars($filename) ?>"
                                             data-label="<?= htmlspecialchars($info['label']) ?>"
                                             onclick="selectLogo('<?= htmlspecialchars($filename, ENT_QUOTES) ?>','<?= $info['b64'] ?>','<?= htmlspecialchars($info['label'], ENT_QUOTES) ?>')">
                                            <img src="<?= $info['b64'] ?>" class="logo-picker-img" alt="<?= htmlspecialchars($info['label']) ?>">
                                            <span><?= htmlspecialchars($info['label']) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if (!empty($religiousOrgs)): ?>
                                        <div class="logo-picker-group-label"><i class="fas fa-church"></i> Religious Organizations</div>
                                        <?php foreach ($religiousOrgs as $code => $name): ?>
                                        <div class="logo-picker-item"
                                             data-value="__religious_<?= htmlspecialchars($code) ?>"
                                             data-label="<?= htmlspecialchars($name) ?>"
                                             onclick="selectLogo('__religious_<?= htmlspecialchars($code, ENT_QUOTES) ?>','','<?= htmlspecialchars($name, ENT_QUOTES) ?>')">
                                            <div class="logo-picker-none"><i class="fas fa-church"></i></div>
                                            <span><?= htmlspecialchars($name) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="organizationName">Organization Name <span>*</span></label>
                            <input type="text" id="organizationName" name="organization_name"
                                   value="<?= $autoOrgName ?>" placeholder="Enter organization name" required>
                        </div>
                        <div class="form-group">
                            <label for="organizationTagline">Organization Tagline <small>(Optional)</small></label>
                            <input type="text" id="organizationTagline" name="organization_tagline"
                                   value="<?= $autoOrgTagline ?>" placeholder="Enter tagline or leave blank">
                        </div>
                        <div id="templateFieldsContainer" class="template-fields-container"></div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-main-footer">
                <div class="form-actions" id="formActions">
                    <button type="button" class="btn-cancel" id="cancelBtn"><i class="fas fa-times"></i> Cancel</button>
                    <button type="submit" class="btn-submit" id="submitBtn"><i class="fas fa-paper-plane"></i> Generate &amp; Submit</button>
                </div>
            </div>
        </div>

    </div>
</div>


<!-- ── Document Preview Modal ───────────────────────────────────────────── -->
<div id="previewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;width:92vw;max-width:1060px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.4);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 18px;background:#2d6a4f;color:#fff;flex-shrink:0;border-radius:18px 18px 0 0;">
            <div style="display:flex;align-items:center;gap:9px;min-width:0;">
                <i id="previewFileIcon" class="fas fa-file-alt" style="font-size:1.1rem;flex-shrink:0;"></i>
                <span id="previewTitle" style="font-size:.95rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
            </div>
            <button onclick="closePreviewModal()" style="background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer;line-height:1;flex-shrink:0;margin-left:12px;">&times;</button>
        </div>
        <div id="previewLoading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:12px;color:#666;">
            <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#2d6a4f;"></i>
            <span style="font-size:.9rem;">Loading document…</span>
        </div>
        <div id="previewError" style="display:none;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:10px;color:#c0392b;">
            <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
            <span id="previewErrorMsg" style="font-size:.9rem;text-align:center;max-width:400px;"></span>
        </div>
        <iframe id="previewPdfFrame" style="display:none;flex:1;border:none;width:100%;"></iframe>
        <div id="previewDocxWrap" style="display:none;flex:1;overflow:auto;"></div>
    </div>
</div>

<!-- ── Template Preview Modal ───────────────────────────────────────────── -->
<div id="tplPreviewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;width:92vw;max-width:820px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.4);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:#1e3a3a;color:#fff;flex-shrink:0;border-radius:18px 18px 0 0;">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                <i class="fas fa-file-contract" style="font-size:1.1rem;color:#a8d5b5;flex-shrink:0;"></i>
                <div style="min-width:0;">
                    <div id="tplPreviewTitle" style="font-size:1rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                    <div id="tplPreviewSubtitle" style="font-size:.75rem;color:#a8d5b5;margin-top:2px;"></div>
                </div>
            </div>
            <button onclick="closeTplPreview()" style="background:rgba(255,255,255,.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">&times;</button>
        </div>
        <div id="tplPreviewBody" style="flex:1;overflow-y:auto;padding:0;background:#e8ecec;"></div>
    </div>
</div>

<script>
/* ── Template Preview ─────────────────────────────────────────────────────── */
const TPL_LABELS = { meeting_minutes:'Meeting Minutes', event_proposal:'Event Proposal', financial_report:'Financial Report', incident_report:'Incident Report', membership_form:'Membership Form', project_proposal:'Project Proposal' };
const TPL_TEXTAREA_KEYS = new Set(['agenda','discussion','action_items','description','requirements','expense_breakdown','remarks','incident_description','individuals_involved','witnesses','action_taken','recommendations','opening_statement','project_summary','project_goal','project_objectives','expected_outputs','monitoring_details','evaluation_details','security_plan','closing_statement','attendees','skills','availability']);
const PP_SECTIONS = [
    { heading: null, keys: ['proposal_date','recipient_1','recipient_2','dear_opening','opening_statement'] },
    { heading: 'I. Identifying Information', keys: ['organization','project_title','project_type','project_involvement','project_location','proposed_start_date','proposed_end_date','number_participants'] },
    { heading: 'II. Project Description', keys: ['project_summary','project_goal','project_objectives','expected_outputs'] },
    { heading: 'III. Budget', keys: ['budget_source','budget_partner','budget_total'] },
    { heading: 'IV. Monitoring & Evaluation', keys: ['monitoring_details','evaluation_details'] },
    { heading: 'V. Security Plan', keys: ['security_plan'] },
    { heading: 'Closing', keys: ['closing_statement','sender_name','noted_by','endorsed_by'] },
];
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function renderValue(key, val) {
    if (!val || !val.trim()) return '<em style="color:#aaa">—</em>';
    const lines = val.split('\n').filter(l => l.trim());
    if (TPL_TEXTAREA_KEYS.has(key) && lines.length > 1) return lines.map(l => '<div style="margin-bottom:3px">'+esc(l)+'</div>').join('');
    return esc(val);
}
function buildGenericBody(data) {
    const labels = data.field_labels || {}, fields = data.fields || {};
    let html = '';
    Object.entries(labels).forEach(([key, label]) => {
        const isBlock = TPL_TEXTAREA_KEYS.has(key);
        html += `<div style="margin-bottom:${isBlock?'18px':'10px'}"><div style="font-size:.7rem;font-weight:700;color:#2d6a4f;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">${esc(label)}</div><div style="font-size:.92rem;color:#1e3a3a;line-height:1.55;${isBlock?'background:#f9fbf9;padding:8px 12px;border-radius:8px;border-left:3px solid #2d6a4f;':''}">${renderValue(key, fields[key]||'')}</div></div>`;
    });
    return html;
}
function buildProjectProposalBody(data) {
    const labels = data.field_labels || {}, fields = data.fields || {};
    const tableKeys = new Set(['organization','project_title','project_type','project_involvement','project_location','proposed_start_date','proposed_end_date','number_participants','budget_source','budget_partner','budget_total']);
    let html = '';
    PP_SECTIONS.forEach(sec => {
        if (sec.heading) html += `<div style="font-size:.95rem;font-weight:700;color:#fff;background:#2d6a4f;padding:7px 14px;border-radius:6px;margin-bottom:12px;">${esc(sec.heading)}</div>`;
        const isTable = sec.keys.some(k => tableKeys.has(k));
        if (isTable) {
            html += '<table style="width:100%;border-collapse:collapse;margin-bottom:14px;">';
            sec.keys.forEach(key => {
                if (!labels[key]) return;
                html += `<tr><td style="font-weight:600;font-size:.82rem;color:#444;background:#f0f5f2;padding:7px 10px;border:1px solid #d4e6d8;width:38%;vertical-align:top;">${esc(labels[key])}</td><td style="font-size:.88rem;color:#1e3a3a;padding:7px 10px;border:1px solid #d4e6d8;vertical-align:top;">${renderValue(key, fields[key]||'')}</td></tr>`;
            });
            html += '</table>';
        } else {
            sec.keys.forEach(key => {
                if (!labels[key]) return;
                const isBlock = TPL_TEXTAREA_KEYS.has(key);
                html += `<div style="margin-bottom:${isBlock?'16px':'8px'}"><div style="font-size:.7rem;font-weight:700;color:#2d6a4f;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">${esc(labels[key])}</div><div style="font-size:.9rem;color:#1e3a3a;line-height:1.55;${isBlock?'background:#f9fbf9;padding:8px 12px;border-radius:8px;border-left:3px solid #2d6a4f;':''}">${renderValue(key, fields[key]||'')}</div></div>`;
            });
        }
    });
    return html;
}
const LOGO_PLSP = '<?= $admissionLogoB64 ?>';
const LOGO_MAP  = <?= json_encode(array_map(fn($info) => $info['b64'], $assetLogos)) ?>;

function renderTplPreviewBody(data, title) {
    const orgName    = data.organization_name    || '<?= $autoOrgName ?>';
    const orgTagline = data.organization_tagline || '<?= $autoOrgTagline ?>';
    const bodyContent = (data.template_id === 'project_proposal') ? buildProjectProposalBody(data) : buildGenericBody(data);
    return `<div style="background:#fff;max-width:700px;margin:20px auto;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,.12);overflow:hidden;"><div style="background:#1e3a3a;padding:16px 20px;"><table style="width:100%;border-collapse:collapse;"><tr><td style="width:80px;text-align:center;vertical-align:middle;padding:0 8px 0 0;">${LOGO_PLSP?`<img src="${LOGO_PLSP}" alt="PLSP" style="width:64px;height:64px;border-radius:50%;object-fit:cover;display:block;margin:0 auto;border:2px solid rgba(255,255,255,0.2);">`:''}</td><td style="text-align:center;vertical-align:middle;padding:0 8px;"><div style="font-size:.65rem;font-weight:700;color:#a8d5b5;letter-spacing:.08em;text-transform:uppercase;margin-bottom:3px;">PAMANTASAN NG LUNGSOD NG SAN PABLO</div><div style="font-size:1rem;font-weight:800;color:#fff;margin-bottom:3px;">${esc(orgName)}</div><div style="font-size:.73rem;color:#a8d5b5;font-style:italic;">"${esc(orgTagline)}"</div></td><td style="width:80px;text-align:center;vertical-align:middle;padding:0 0 0 8px;">${data.collaborated_logo&&LOGO_MAP[data.collaborated_logo]?`<img src="${LOGO_MAP[data.collaborated_logo]}" alt="Logo" style="width:64px;height:64px;border-radius:50%;object-fit:cover;display:block;margin:0 auto;border:2px solid rgba(255,255,255,0.2);">`:''}</td></tr></table></div><div style="padding:22px 26px;">${bodyContent}</div><div style="background:#f4faf7;border-top:2px solid #2d6a4f;padding:10px 24px;text-align:center;"><div style="font-size:.75rem;color:#2d6a4f;font-style:italic;">"Primed to Lead and Serve for Progress"</div></div></div>`;
}
window.openTemplatePreview = function(row) {
    const raw = row ? row.getAttribute('data-submission-data') : null;
    const title = row ? (row.querySelector('.doc-title')||{}).textContent||'Document' : 'Document';
    let data = null;
    if (raw) { try { data = JSON.parse(raw); } catch(e) {} }
    if (!data) { alert('Preview data not available for this submission.'); return; }
    document.getElementById('tplPreviewTitle').textContent = title;
    document.getElementById('tplPreviewSubtitle').textContent = (data.template_name||'') + ' — Template Document';
    document.getElementById('tplPreviewBody').innerHTML = renderTplPreviewBody(data, title);
    document.getElementById('tplPreviewModal').style.display = 'flex';
};
window._pendingTplData = {};
window.openTemplatePreviewById = function(id, dataJson, title) {
    let data = null;
    try { data = typeof dataJson==='string' ? JSON.parse(dataJson) : dataJson; } catch(e) {}
    if (!data) return;
    document.getElementById('tplPreviewTitle').textContent = title;
    document.getElementById('tplPreviewSubtitle').textContent = (data.template_name||'')+' — Template Document';
    document.getElementById('tplPreviewBody').innerHTML = renderTplPreviewBody(data, title);
    document.getElementById('tplPreviewModal').style.display = 'flex';
};
function closeTplPreview() { document.getElementById('tplPreviewModal').style.display='none'; document.getElementById('tplPreviewBody').innerHTML=''; }
document.getElementById('tplPreviewModal').addEventListener('click', function(e){ if(e.target===this) closeTplPreview(); });
document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeTplPreview(); closePreviewModal(); } });
</script>

<script src="../js/script.js"></script>
<script src="../js/navbar.js"></script>
<script src="../js/notifications.js"></script>
<script src="../js/document_tracking.js"></script>
<script>
/* ── Template fields grid patch ───────────────────────────────
   Widen modal only for project_proposal.
   Resets on: close, cancel, backdrop click, tab switch.
─────────────────────────────────────────────────────────────── */
(function() {
    const WIDE_TEMPLATES = new Set(['project_proposal']);

    function resetModalSize() {
        const mc = document.querySelector('.upload-modal-content');
        if (mc) mc.classList.remove('modal-wide', 'modal-compact');
    }

    /* Patch loadTemplateFields */
    const _orig = window.loadTemplateFields;
    if (typeof _orig === 'function') {
        window.loadTemplateFields = function() {
            _orig.apply(this, arguments);
            const templateId = document.getElementById('templateSelect').value;
            const container  = document.getElementById('templateFieldsContainer');
            const mc         = document.querySelector('.upload-modal-content');
            if (!container || !mc) return;
            container.querySelectorAll('.form-group').forEach(function(fg) {
                if (fg.querySelector('textarea')) fg.classList.add('full-width');
            });
            if (WIDE_TEMPLATES.has(templateId)) {
                mc.classList.add('modal-wide');
                mc.classList.remove('modal-compact');
            } else {
                mc.classList.remove('modal-wide');
                mc.classList.add('modal-compact');
            }
        };
    }

    /* Reset when template dropdown cleared */
    var sel = document.getElementById('templateSelect');
    if (sel) sel.addEventListener('change', function() {
        if (!this.value) resetModalSize();
    });

    /* Reset when switching back to Regular Upload tab */
    document.querySelectorAll('.tab-button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (this.getAttribute('data-tab') === 'regular-upload') resetModalSize();
        });
    });

    /* Reset on close button, cancel button, backdrop */
    var closeBtn = document.getElementById('closeUploadModal');
    var cancelBtn = document.getElementById('cancelBtn');
    var modalEl   = document.getElementById('uploadModal');
    if (closeBtn)  closeBtn.addEventListener('click',  resetModalSize);
    if (cancelBtn) cancelBtn.addEventListener('click', resetModalSize);
    if (modalEl)   modalEl.addEventListener('click', function(e) {
        if (e.target === this) resetModalSize();
    });
    /* ── Override validateTemplateForm ────────────────────────────
       Org name auto-filled from DB. Tagline is optional.
       Re-define on window so all existing listeners call new version.
    ───────────────────────────────────────────────────────────── */
    window.validateTemplateForm = function() {
        var submitBtn = document.getElementById('submitBtn');
        var sel       = document.getElementById('templateSelect');
        var title     = document.getElementById('templateTitle');

        if (!submitBtn) return;
        if (!sel || !sel.value || !title || !title.value.trim()) {
            submitBtn.disabled = true;
            return;
        }
        var allFields = document.querySelectorAll(
            '#templateFieldsContainer input:not([type="hidden"]), ' +
            '#templateFieldsContainer textarea'
        );
        submitBtn.disabled = Array.from(allFields).some(function(f) {
            return !f.value.trim();
        });
    };

    /* Force tagline = space before EVERY submit attempt so original
       onsubmit and formData.append never see an empty value          */
    var templateForm = document.getElementById('templateForm');
    if (templateForm) {
        templateForm.addEventListener('submit', function() {
            var tag = document.getElementById('organizationTagline');
            if (tag && !tag.value.trim()) tag.value = ' ';
        }, true); // capture phase — runs before the original handler
    }

    /* Same safety net on the submitBtn click */
    var submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            var tag = document.getElementById('organizationTagline');
            if (tag && !tag.value.trim()) tag.value = ' ';
        }, true);
    }

    /* Also seed the hidden field right now if empty */
    var tagEl = document.getElementById('organizationTagline');
    if (tagEl && !tagEl.value.trim()) tagEl.value = ' ';

    /* Re-attach listeners to new validateTemplateForm */
    ['templateSelect','templateTitle','organizationName','organizationTagline']
        .forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            var evt = (el.tagName === 'SELECT') ? 'change' : 'input';
            el.addEventListener(evt, window.validateTemplateForm);
        });
})();

// Wire alt close button
var altClose = document.getElementById('closeUploadModalAlt');
if (altClose) altClose.addEventListener('click', function() {
    if (typeof closeUploadModal === 'function') closeUploadModal();
    if (typeof resetModalSize === 'function') resetModalSize();
});

// File drop zone
(function() {
    var zone  = document.getElementById('fileDropZone');
    var input = document.getElementById('fileUpload');
    var name  = document.getElementById('fileDropName');
    if (!zone || !input) return;

    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            name.textContent = this.files[0].name;
            zone.classList.add('has-file');
        }
    });
    zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', function() { zone.classList.remove('drag-over'); });
    zone.addEventListener('drop', function(e) {
        e.preventDefault(); zone.classList.remove('drag-over');
        if (e.dataTransfer.files[0]) {
            input.files = e.dataTransfer.files;
            name.textContent = e.dataTransfer.files[0].name;
            zone.classList.add('has-file');
        }
    });
})();
</script>
<script>
/* ── Preview modal ─────────────────────────────────────────────────────────── */
function openPreviewModal(id, ext, title) {
    var modal=document.getElementById('previewModal'), loading=document.getElementById('previewLoading'),
        errorDiv=document.getElementById('previewError'), pdfFrame=document.getElementById('previewPdfFrame'),
        docxWrap=document.getElementById('previewDocxWrap'), titleEl=document.getElementById('previewTitle'), iconEl=document.getElementById('previewFileIcon');
    loading.style.display='flex'; errorDiv.style.display='none'; pdfFrame.style.display='none';
    docxWrap.style.display='none'; pdfFrame.src=''; docxWrap.innerHTML=''; modal.style.display='flex'; titleEl.textContent=title;
    var iconMap={pdf:'fa-file-pdf',docx:'fa-file-word',doc:'fa-file-word',xlsx:'fa-file-excel',xls:'fa-file-excel'};
    iconEl.className='fas '+(iconMap[ext]||'fa-file-alt');
    var previewUrl='file_preview.php?submission_id='+id;
    if(ext==='pdf'){ pdfFrame.src=previewUrl; pdfFrame.style.display='block'; loading.style.display='none'; pdfFrame.onerror=function(){showPreviewError('Failed to load PDF.');}; }
    else if(ext==='docx'||ext==='doc'){ var convertUrl='docx_to_pdf.php?submission_id='+id; pdfFrame.src=convertUrl; pdfFrame.style.display='block'; loading.querySelector('span').textContent='Converting document…'; pdfFrame.onload=function(){loading.style.display='none';}; pdfFrame.onerror=function(){showPreviewError('Failed to convert document.');}; }
    else if(ext==='xlsx'||ext==='xls'){ if(typeof XLSX==='undefined'){var s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';s.onload=function(){loadXlsx(previewUrl,docxWrap,loading);};document.head.appendChild(s);}else{loadXlsx(previewUrl,docxWrap,loading);} }
    else{ showPreviewError('Preview not available for this file type.'); }
}
function loadXlsx(url,wrap,loading){ fetch(url).then(function(r){if(!r.ok)throw new Error('Server error '+r.status);return r.arrayBuffer();}).then(function(buf){var wb=XLSX.read(new Uint8Array(buf),{type:'array'});var html='<style>table{border-collapse:collapse;font-size:.8rem;width:100%;}td,th{border:1px solid #ccc;padding:4px 8px;white-space:nowrap;}</style>';wb.SheetNames.forEach(function(name){html+='<div style="padding:16px;"><h3 style="margin:0 0 8px;color:#2d6a4f;font-size:.9rem;">'+name+'</h3>';html+=XLSX.utils.sheet_to_html(wb.Sheets[name],{editable:false});html+='</div>';});wrap.innerHTML=html;wrap.style.display='block';loading.style.display='none';}).catch(function(e){showPreviewError('Could not render spreadsheet: '+e.message);}); }
function showPreviewError(msg){ document.getElementById('previewLoading').style.display='none'; document.getElementById('previewError').style.display='flex'; document.getElementById('previewErrorMsg').textContent=msg; }
function closePreviewModal(){ document.getElementById('previewModal').style.display='none'; document.getElementById('previewPdfFrame').src=''; document.getElementById('previewDocxWrap').innerHTML=''; }
document.getElementById('previewModal').addEventListener('click',function(e){if(e.target===this)closePreviewModal();});

/* ── Search / filter (all connected, date functional) ─────────────────────── */
(function(){
    const search    = document.getElementById('searchInput');
    const statusSel = document.getElementById('statusFilter');
    const dateSel   = document.getElementById('dateFilter');
    const rows      = document.querySelectorAll('#documentsTable tbody tr[data-title]');
    const rowCount  = document.getElementById('rowCount');
    const noResults = document.getElementById('docNoResults');

    function apply() {
        const q  = search.value.toLowerCase().trim();
        const st = statusSel.value.toLowerCase();
        const dt = dateSel.value; // yyyy-mm-dd from date input

        let visible = 0;
        rows.forEach(function(row) {
            const titleMatch  = !q  || row.dataset.title.includes(q);
            const statusMatch = !st || row.dataset.status === st;
            // date input gives yyyy-mm-dd, data-date is also yyyy-mm-dd
            const dateMatch   = !dt || row.dataset.date === dt;

            const show = titleMatch && statusMatch && dateMatch;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (rowCount)  rowCount.textContent = visible + ' document(s)';
        if (noResults) noResults.style.display = (visible === 0 && rows.length > 0) ? 'flex' : 'none';
    }

    // Clear date button
    const clearDate = document.getElementById('clearDate');
    if (clearDate) {
        clearDate.addEventListener('click', function() {
            dateSel.value = '';
            apply();
        });
    }

    search.addEventListener('input',  apply);
    statusSel.addEventListener('change', apply);
    dateSel.addEventListener('change', function() {
        if (clearDate) clearDate.style.display = dateSel.value ? 'block' : 'none';
        apply();
    });
    dateSel.addEventListener('input', function() {
        if (clearDate) clearDate.style.display = dateSel.value ? 'block' : 'none';
        apply();
    });
    if (clearDate) {
        clearDate.addEventListener('click', function() {
            dateSel.value = '';
            clearDate.style.display = 'none';
            apply();
        });
    }
}());
</script>

<!-- ── Admin Remark Modal ─────────────────────────────────────────────────── -->
<?php
// Build JS remarks map
$remarksMap = [];
foreach ($submissions as $i => $doc) {
    $remarksMap[$i] = [
        'text'  => $doc['admin_remarks'],
        'title' => $doc['title'] ?? 'Document',
    ];
}
?>
<script>
window._remarks = <?= json_encode($remarksMap) ?>;
</script>
<div id="remarkModal" style="
  display:none; position:fixed; inset:0; z-index:10000;
  background:rgba(0,0,0,0.5); backdrop-filter:blur(4px);
  align-items:center; justify-content:center;
">
  <div style="
    background:#fff; border-radius:20px;
    max-width:520px; width:92vw;
    box-shadow:0 20px 60px rgba(0,0,0,0.25);
    overflow:hidden;
    animation: remarkPop 0.28s cubic-bezier(0.34,1.56,0.64,1) both;
  ">
    <!-- Header -->
    <div style="background:#2d6a4f;padding:1.1rem 1.4rem;display:flex;align-items:center;justify-content:space-between;">
      <div style="display:flex;align-items:center;gap:0.55rem;color:#fff;">
        <i class="fas fa-comment-alt" style="font-size:1rem;"></i>
        <span style="font-weight:600;font-size:0.95rem;" id="remarkModalTitle">Admin Remarks</span>
      </div>
      <button onclick="closeRemarkModal()" style="background:none;border:none;color:rgba(255,255,255,0.8);font-size:1.4rem;cursor:pointer;line-height:1;padding:0;">&times;</button>
    </div>
    <!-- Body -->
    <div style="padding:1.6rem 1.5rem;">
      <p id="remarkModalBody" style="
        font-size:0.92rem; color:#374151; line-height:1.75;
        white-space:pre-wrap; word-break:break-word;
        max-height:55vh; overflow-y:auto;
        background:#f9fafb; border-radius:12px;
        padding:1rem 1.1rem; border:1px solid #e5e7eb;
      "></p>
    </div>
    <!-- Footer -->
    <div style="padding:0 1.5rem 1.3rem;display:flex;justify-content:flex-end;">
      <button onclick="closeRemarkModal()" style="
        background:#2d6a4f; color:#fff; border:none;
        padding:0.6rem 1.5rem; border-radius:10px;
        font-size:0.88rem; font-weight:600; cursor:pointer;
        font-family:inherit; transition:background 0.2s;
      " onmouseover="this.style.background='#1a3d2b'" onmouseout="this.style.background='#2d6a4f'">
        Close
      </button>
    </div>
  </div>
</div>

<style>
@keyframes remarkPop {
  from { opacity:0; transform:scale(0.92) translateY(12px); }
  to   { opacity:1; transform:scale(1) translateY(0); }
}

/* ── Template custom select ──────────────────────────────────── */
.tpl-input-wrap { position: relative; }
.tpl-text-input {
    width: 100%; padding: 0.68rem 2.2rem 0.68rem 1rem;
    border: 1.5px solid #dde8e3; border-radius: 9px;
    font-size: 0.92rem; font-family: inherit;
    background: #f6faf7; color: #1e3a2e;
    outline: none; cursor: pointer; caret-color: transparent;
    box-sizing: border-box;
}
.tpl-text-input:hover { border-color: #a8ccba; }
.tpl-text-input.open { border-color: #2d6a4f; background: #fff; box-shadow: 0 0 0 3px rgba(45,106,79,0.09); }
.tpl-text-input::placeholder { color: #adc0b8; }
.tpl-chevron {
    position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%);
    color: #9ca3af; font-size: 0.75rem; pointer-events: none;
}
.tpl-text-input.open ~ .tpl-chevron { transform: translateY(-50%) rotate(180deg); }
.tpl-dropdown {
    display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1.5px solid #dde8e3; border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,20,10,0.12); z-index: 600; overflow: hidden;
}
.tpl-dropdown.open { display: block; }
.tpl-dropdown-item {
    padding: 0.6rem 1rem; font-size: 0.9rem; color: #1e3a2e; cursor: pointer; font-family: inherit;
}
.tpl-dropdown-item:hover { background: #f0faf5; }
.tpl-dropdown-item.active { background: #e6f4ed; font-weight: 600; color: #2d6a4f; }
.tpl-dropdown-item:first-child { color: #adc0b8; font-size: 0.86rem; }

/* ── Logo Picker ─────────────────────────────────────────────── */
.logo-picker { position: relative; }
.logo-picker-input-wrap {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0 1rem; min-height: 44px; box-sizing: border-box;
    border: 1.5px solid #dde8e3; border-radius: 9px; background: #f6faf7;
}
.logo-picker-input-wrap:hover { border-color: #a8ccba; }
.logo-picker-input-wrap:focus-within { border-color: #2d6a4f; background: #fff; box-shadow: 0 0 0 3px rgba(45,106,79,0.09); }
.logo-picker-thumb { display: none; width: 24px; height: 24px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
.logo-picker-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.logo-picker-thumb.visible { display: block; }
.logo-picker-text-input {
    flex: 1; border: none; outline: none; background: transparent;
    font-size: 0.92rem; font-family: inherit; color: #1e3a2e;
    padding: 0.68rem 0; min-width: 0; cursor: text;
}
.logo-picker-text-input::placeholder { color: #adc0b8; }
.logo-picker-arrow { color: #9ca3af; font-size: 0.75rem; flex-shrink: 0; cursor: pointer; }
.logo-picker-input-wrap:focus-within .logo-picker-arrow { transform: rotate(180deg); }
.logo-picker-panel {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1.5px solid #dde8e3; border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,20,10,0.12); z-index: 600; overflow: hidden;
}
.logo-picker-list { max-height: 220px; overflow-y: auto; padding: 0.3rem 0; scrollbar-width: thin; scrollbar-color: #c8ddd5 transparent; }
.logo-picker-item { display: flex; align-items: center; gap: 0.65rem; padding: 0.48rem 0.85rem; cursor: pointer; font-size: 0.88rem; color: #1e3a2e; }
.logo-picker-item:hover { background: #f0faf5; }
.logo-picker-item.selected { background: #e6f4ed; font-weight: 600; color: #2d6a4f; }
.logo-picker-img { width: 30px; height: 30px; object-fit: contain; border-radius: 5px; flex-shrink: 0; background: #f9f9f9; border: 1px solid #e5e7eb; }
.logo-picker-none { width: 30px; height: 30px; border-radius: 5px; border: 1px dashed #d1d5db; display: flex; align-items: center; justify-content: center; color: #adc0b8; font-size: 0.78rem; flex-shrink: 0; }
.logo-picker-group-label { padding: 0.45rem 0.85rem 0.2rem; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; border-top: 1px solid #f0f0f0; margin-top: 0.3rem; display: flex; align-items: center; gap: 0.35rem; }
.logo-picker-no-results { padding: 0.8rem 1rem; font-size: 0.83rem; color: #9ca3af; text-align: center; }
</style>

<script>
function openRemarkModal(text, docTitle) {
  document.getElementById('remarkModalTitle').textContent = docTitle ? 'Remarks — ' + docTitle : 'Admin Remarks';
  document.getElementById('remarkModalBody').textContent  = text;
  const m = document.getElementById('remarkModal');
  m.style.display = 'flex';
}
function closeRemarkModal() {
  document.getElementById('remarkModal').style.display = 'none';
}
document.getElementById('remarkModal').addEventListener('click', function(e) {
  if (e.target === this) closeRemarkModal();
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeRemarkModal();
});
// Event delegation for all "See more" buttons
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.see-more-btn');
  if (btn) {
    e.stopPropagation();
    const idx = btn.dataset.idx;
    const d = window._remarks && window._remarks[idx];
    if (d) openRemarkModal(d.text, d.title);
  }
});

// ── Template custom dropdown ──────────────────────────────────
function toggleTplDropdown() {
  var dd   = document.getElementById('tplDropdown');
  var inp  = document.getElementById('templateSelectDisplay');
  var open = dd.classList.contains('open');
  dd.classList.toggle('open', !open);
  inp.classList.toggle('open', !open);
  if (!open) {
    document.addEventListener('click', closeTplOnOutside);
  }
}
function closeTplOnOutside(e) {
  var wrap = document.querySelector('.tpl-input-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('tplDropdown').classList.remove('open');
    document.getElementById('templateSelectDisplay').classList.remove('open');
    document.removeEventListener('click', closeTplOnOutside);
  }
}
function pickTemplate(el, label) {
  var val = el.getAttribute('data-value');
  document.getElementById('templateSelect').value = val;
  document.getElementById('templateSelectDisplay').value = val ? label : '';
  document.getElementById('tplDropdown').classList.remove('open');
  document.getElementById('templateSelectDisplay').classList.remove('open');
  document.removeEventListener('click', closeTplOnOutside);
  // Mark active
  document.querySelectorAll('.tpl-dropdown-item').forEach(function(i) {
    i.classList.toggle('active', i.getAttribute('data-value') === val);
  });
  // Fire loadTemplateFields
  if (typeof loadTemplateFields === 'function') loadTemplateFields();
  if (typeof validateTemplateForm === 'function') validateTemplateForm();
}

// ── Logo Picker ───────────────────────────────────────────────
// ── Logo Picker ───────────────────────────────────────────────
function openLogoPicker() {
  var panel = document.getElementById('logoPickerPanel');
  if (panel.style.display === 'none') {
    panel.style.display = 'block';
    // Clear input so user can type fresh to search
    var inp = document.getElementById('logoPickerSearch');
    if (inp) { inp.value = ''; filterLogos(''); }
    document.addEventListener('click', closeLogoOnOutside);
  }
}
function closeLogoOnOutside(e) {
  var picker = document.getElementById('logoPicker');
  if (picker && !picker.contains(e.target)) {
    document.getElementById('logoPickerPanel').style.display = 'none';
    // Restore selected label in input
    var hidden  = document.getElementById('collaboratedLogo');
    var inp     = document.getElementById('logoPickerSearch');
    if (inp) {
      var selected = document.querySelector('.logo-picker-item.selected');
      inp.value = (hidden && hidden.value && selected) ? (selected.getAttribute('data-label') || '') : '';
    }
    document.removeEventListener('click', closeLogoOnOutside);
  }
}
function selectLogo(value, b64, label) {
  document.getElementById('collaboratedLogo').value = value;
  var inp   = document.getElementById('logoPickerSearch');
  var thumb = document.getElementById('logoPickerThumb');
  inp.value = value ? label : '';
  inp.placeholder = value ? '' : 'No Collaborated Logo';
  if (value && b64) {
    thumb.innerHTML = '<img src="' + b64 + '" alt="' + label + '">';
    thumb.classList.add('visible');
  } else {
    thumb.innerHTML = '';
    thumb.classList.remove('visible');
  }
  document.querySelectorAll('.logo-picker-item').forEach(function(el) {
    el.classList.toggle('selected', el.getAttribute('data-value') === value);
  });
  document.getElementById('logoPickerPanel').style.display = 'none';
  document.removeEventListener('click', closeLogoOnOutside);
}
function filterLogos(q) {
  var items = document.querySelectorAll('#logoPickerList .logo-picker-item');
  var lower = q.toLowerCase().trim();
  var visible = 0;
  items.forEach(function(item) {
    var label = (item.getAttribute('data-label') || '').toLowerCase();
    var match = !lower || label.includes(lower);
    item.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  document.querySelectorAll('.logo-picker-group-label').forEach(function(lbl) {
    var next = lbl.nextElementSibling;
    var anyVisible = false;
    while (next && !next.classList.contains('logo-picker-group-label')) {
      if (next.style.display !== 'none') anyVisible = true;
      next = next.nextElementSibling;
    }
    lbl.style.display = anyVisible ? '' : 'none';
  });
  var noRes = document.getElementById('logoPickerNoResults');
  if (visible === 0) {
    if (!noRes) {
      noRes = document.createElement('div');
      noRes.id = 'logoPickerNoResults';
      noRes.className = 'logo-picker-no-results';
      noRes.textContent = 'No logos found';
      document.getElementById('logoPickerList').appendChild(noRes);
    }
    noRes.style.display = '';
  } else if (noRes) {
    noRes.style.display = 'none';
  }
}
</script>
</body>
</html>