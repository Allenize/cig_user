<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db_connection.php';

$userId = $_SESSION['user_id'];

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
    $stmt = mysqli_prepare($conn, $submissionsQuery);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) $submissions[] = $row;
    mysqli_stmt_close($stmt);

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

function b64img($absPath) {
    if (!$absPath || !file_exists($absPath)) return '';
    $mime = in_array(strtolower(pathinfo($absPath, PATHINFO_EXTENSION)), ['jpg','jpeg']) ? 'image/jpeg' : 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absPath));
}

// Base: cig_user/ folder (two levels up from php/)
$_baseDir = dirname(dirname(__DIR__));   // C:\xampp\htdocs\cig_user

// Left logo: admission.png from Assets, fallback plsplogo.png
$admissionB64 = '';
$_assetsDir = $_baseDir . '/Assets/';
foreach (['admission.png','admission.jpg','Admission.png','Admission.jpg'] as $_f) {
    if (file_exists($_assetsDir . $_f)) {
        $admissionB64 = b64img($_assetsDir . $_f);
        break;
    }
}
if (!$admissionB64) {
    // Fallback: plsplogo.png in cig_user root
    foreach ([$_baseDir . '/plsplogo.png', $_baseDir . '/Assets/plsplogo.png'] as $_f) {
        if (file_exists($_f)) { $admissionB64 = b64img($_f); break; }
    }
}

// Org logo: from users.logo_path (stored as ../uploads/logos/filename.png relative to php/)
$orgLogoB64 = '';
if (!empty($_SESSION['user_id'])) {
    $_uid = (int)$_SESSION['user_id'];
    $_lq  = mysqli_prepare($conn, "SELECT logo_path, description, org_name FROM users WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($_lq, 'i', $_uid);
    mysqli_stmt_execute($_lq);
    $_lr  = mysqli_fetch_assoc(mysqli_stmt_get_result($_lq));
    mysqli_stmt_close($_lq);
    if (!empty($_lr['logo_path'])) {
        // logo_path stored as e.g. "../uploads/logos/file.png" relative to php/
        // Resolve: php/ + logo_path
        $_logoAbs = realpath(__DIR__ . '/' . $_lr['logo_path']);
        if (!$_logoAbs || !file_exists($_logoAbs)) {
            // Also try from cig_user base
            $_logoAbs = realpath($_baseDir . '/' . ltrim($_lr['logo_path'], './'));
        }
        if ($_logoAbs && file_exists($_logoAbs)) {
            $orgLogoB64 = b64img($_logoAbs);
        }
    }
}
// Org tagline and name from DB (fixed — based on logged-in user)
$orgTaglineFixed = trim($_lr['description'] ?? '');
$orgNameFixed    = trim($_lr['org_name']    ?? $_SESSION['org_name'] ?? '');

// Load all org logos from Assets for dropdown
$_allOrgLogos = [];
$_skip = ['Admission.png','admission.png','CRCY.png'];
if (is_dir($_assetsDir)) {
    $_files = scandir($_assetsDir);
    sort($_files);
    foreach ($_files as $_f) {
        if (in_array($_f, $_skip)) continue;
        $_ext = strtolower(pathinfo($_f, PATHINFO_EXTENSION));
        if (!in_array($_ext, ['jpg','jpeg','png'])) continue;
        $_fp = $_assetsDir . $_f;
        if (!file_exists($_fp)) continue;
        $_mime = ($_ext === 'png') ? 'image/png' : 'image/jpeg';
        $_allOrgLogos[pathinfo($_f, PATHINFO_FILENAME)] = [
            'file' => $_f,
            'b64'  => 'data:' . $_mime . ';base64,' . base64_encode(file_get_contents($_fp)),
        ];
    }
}
// CIG and OSAS still available for backwards compat
$cigLogoB64  = $_allOrgLogos['CIG']['b64']  ?? '';
$osasLogoB64 = $_allOrgLogos['OSAS']['b64'] ?? '';
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
    .fas,.far,.fab,.fa{font-family:"Font Awesome 6 Free","Font Awesome 6 Brands"!important;}

    /* ── docx preview overrides ── */
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

<?php include 'navbar.php'; ?>
<main class="main-content">
<?php include 'topbar.php'; ?>

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

                        <td class="remarks-cell" title="<?= htmlspecialchars($doc['admin_remarks']) ?>">
                            <?= htmlspecialchars($doc['admin_remarks']) ?>
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
            <div class="modal-sidebar-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <h2 class="modal-sidebar-title">Upload Document</h2>
            <p class="modal-sidebar-sub">Submit documents to CIG for review and approval.</p>
            <div class="modal-steps">
                <div class="modal-step active" id="step1">
                    <div class="step-dot"><i class="fas fa-pen"></i></div>
                    <span>Fill in details</span>
                </div>
                <div class="modal-step" id="step2">
                    <div class="step-dot"><i class="fas fa-paperclip"></i></div>
                    <span>Attach file or template</span>
                </div>
                <div class="modal-step" id="step3">
                    <div class="step-dot"><i class="fas fa-paper-plane"></i></div>
                    <span>Submit for review</span>
                </div>
            </div>
            <div class="modal-sidebar-footer">
                <i class="fas fa-check-circle"></i> Accepted: PDF, DOCX, XLSX · Max 50 MB
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
                                <div class="file-drop-hint">PDF, DOCX, XLSX · Max 50 MB</div>
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
                            <select id="templateSelect" name="template_id" required onchange="loadTemplateFields()">
                                <option value="">Choose a Template </option>
                                <option value="meeting_minutes">Meeting Minutes</option>
                                <option value="event_proposal">Event Proposal</option>
                                <option value="financial_report">Financial Report</option>
                                <option value="incident_report">Incident Report</option>
                                <option value="membership_form">Membership Form</option>
                                <option value="project_proposal">Project Proposal</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label style="cursor:default;">
                                <input type="checkbox" id="useColloborated" onchange="toggleColloboratedPicker(this)"
                                       style="width:14px;height:14px;accent-color:#2d6a4f;cursor:pointer;vertical-align:middle;margin-right:0.4rem;">
                                <span style="text-transform:none;letter-spacing:0;font-weight:600;font-size:0.82rem;color:#1e3a2e;">Add Collaborated Logo</span>
                                <small>(Optional)</small>
                            </label>
                            <div id="colloboratedPickerWrap" style="display:none;margin-top:0.5rem;">
                                <select id="collaboratedLogo" name="collaborated_logo" onchange="updateCollabPreview(this.value)">
                                    <option value="">Choose Organization Logo</option>
                                    <?php foreach ($_allOrgLogos as $_key => $_logo): ?>
                                    <option value="<?= htmlspecialchars($_logo['file']) ?>"><?= htmlspecialchars($_key) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="collabLogoPreview" style="display:none;margin-top:0.5rem;text-align:center;">
                                    <img id="collabLogoImg" src="" style="height:50px;width:50px;object-fit:contain;border-radius:6px;border:1px solid #e2ece7;">
                                </div>
                                <small style="color:#9ab5ac;font-size:0.73rem;margin-top:0.25rem;display:block;">
                                    <i class="fas fa-info-circle"></i> Will appear beside your org logo on the header.
                                </small>
                            </div>
                            <input type="hidden" id="collaboratedLogoHidden" name="collaborated_logo_value" value="">
                        </div>
                        <div class="form-group">
                            <label>Organization</label>
                            <div style="background:#f4faf7;border:1px solid #dde8e3;border-radius:8px;padding:0.6rem 0.95rem;display:flex;align-items:center;gap:0.7rem;">
                                <?php if ($orgLogoB64): ?>
                                <img src="<?= $orgLogoB64 ?>" style="width:36px;height:36px;object-fit:contain;border-radius:6px;flex-shrink:0;">
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight:700;font-size:0.9rem;color:#1a3028;"><?= htmlspecialchars($orgNameFixed) ?></div>
                                    <?php if ($orgTaglineFixed): ?>
                                    <div style="font-size:0.75rem;color:#6b9080;font-style:italic;margin-top:1px;"><?= htmlspecialchars($orgTaglineFixed) ?></div>
                                    <?php endif; ?>
                                </div>
                                <span style="margin-left:auto;font-size:0.7rem;color:#52b788;display:flex;align-items:center;gap:3px;"><i class="fas fa-check-circle"></i> From profile</span>
                            </div>
                            <!-- Hidden inputs carry the values to PHP -->
                            <input type="hidden" id="organizationName" name="organization_name" value="<?= htmlspecialchars($orgNameFixed) ?>">
                            <input type="hidden" id="organizationTagline" name="organization_tagline" value="<?= htmlspecialchars($orgTaglineFixed) ?>">
                        </div>
                        <div id="templateFieldsContainer" class="template-fields-container"></div>
                    </form>
                </div>

            </div><!-- /.modal-main-body -->

            <div class="modal-main-footer" style="position:relative;">
                <div id="autosave-indicator" style="font-size:0.75rem;color:#52b788;margin-bottom:0.4rem;opacity:0;transition:opacity 0.4s;min-height:1rem;"></div>
                <div class="form-actions" id="formActions">
                    <button type="submit" class="btn-submit" id="submitBtn"><i class="fas fa-paper-plane"></i> Generate &amp; Submit</button>
                </div>
            </div>
        </div><!-- /.modal-main -->

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
    { heading: null, keys: ['proposal_date','recipient_1','recipient_2','opening_statement'] },
    { heading: 'I. Identifying Information', keys: ['organization','project_title','project_type','project_involvement','project_location','proposed_start_date','proposed_end_date','number_participants'] },
    { heading: 'II. Project Description ', keys: ['project_summary','project_goal','project_objectives','expected_outputs'] },
    { heading: 'III. Budget', keys: ['budget_source','budget_partner','budget_total'] },
    { heading: 'IV. Monitoring & Evaluation', keys: ['monitoring_details','evaluation_details'] },
    { heading: 'V. Security Plan', keys: ['security_plan', 'closing_statement','sender_name','adviser_name','co_adviser_name','endorsed_by' ]},
];
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
const BULLET_KEYS = new Set(['attendees','agenda','discussion','action_items','requirements','expense_breakdown','incident_description','individuals_involved','witnesses','action_taken','recommendations','opening_statement','project_summary','project_goal','project_objectives','expected_outputs','monitoring_details','evaluation_details','security_plan','closing_statement','skills','availability']);
function renderValue(key, val) {
    if (!val || !val.trim()) return '<em style="color:#aaa">—</em>';
    const lines = val.split('\n').filter(l => l.trim());
    if (BULLET_KEYS.has(key) && lines.length > 1) {
        return '<ul style="margin:0;padding-left:1.2rem;">' + lines.map(l => '<li style="margin-bottom:2px;">'+esc(l)+'</li>').join('') + '</ul>';
    }
    if (TPL_TEXTAREA_KEYS.has(key) && lines.length > 1) return lines.map(l => '<div style="margin-bottom:3px">'+esc(l)+'</div>').join('');
    return esc(val);
}
function buildGenericBody(data) {
    const labels = data.field_labels || {}, fields = data.fields || {};
    let html = '';
    Object.entries(labels).forEach(([key, label]) => {
        const isBlock = TPL_TEXTAREA_KEYS.has(key);
        html += `<div style="margin-bottom:${isBlock?'18px':'10px'}"><div style="font-size:.7rem;font-weight:700;color:#2d6a4f;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">${esc(label)}</div><div style="font-size:.92rem;color:#1e3a3a;line-height:1.55;${isBlock?'background:#f9fbf9;padding:8px 12px;border-radius:8px;':''}">${renderValue(key, fields[key]||'')}</div></div>`;
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
                html += `<div style="margin-bottom:${isBlock?'16px':'8px'}"><div style="font-size:.7rem;font-weight:700;color:#2d6a4f;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">${esc(labels[key])}</div><div style="font-size:.9rem;color:#1e3a3a;line-height:1.55;${isBlock?'background:#f9fbf9;padding:8px 12px;border-radius:8px;':''}">${renderValue(key, fields[key]||'')}</div></div>`;
            });
        }
    });
    return html;
}
const LOGO_ADMISSION = '<?= $admissionB64 ?>';   // Left: PLSP admission/seal
const LOGO_ORG       = '<?= $orgLogoB64 ?>';       // Right: organization's own logo
const LOGO_MAP = {
<?php foreach ($_allOrgLogos as $_key => $_logo): ?>
    '<?= $_logo['file'] ?>': '<?= $_logo['b64'] ?>',
<?php endforeach; ?>
};

function renderTplPreviewBody(data, title) {
    const orgName    = data.organization_name    || '';
    const orgTagline = data.organization_tagline || '';
    const collabLogo = data.collaborated_logo || data.collaborated_logo_value || '';
    const bodyContent = (data.template_id === 'project_proposal') ? buildProjectProposalBody(data) : buildGenericBody(data);

    const LOGO_SIZE = '60px'; // uniform size for all logos

    // LEFT: admission/PLSP seal
    const leftCell = LOGO_ADMISSION
        ? `<td style="width:80px;text-align:center;vertical-align:middle;padding:0 8px 0 0;">
             <img src="${LOGO_ADMISSION}" alt="PLSP" style="height:${LOGO_SIZE};width:${LOGO_SIZE};object-fit:contain;border-radius:50%;border:2px solid rgba(255,255,255,0.25);display:block;margin:0 auto;">
           </td>`
        : `<td style="width:80px;"></td>`;

    // CENTER: text
    const centerCell = `<td style="text-align:center;vertical-align:middle;padding:0 6px;">
        <div style="font-size:.6rem;font-weight:700;color:#a8d5b5;letter-spacing:.1em;text-transform:uppercase;margin-bottom:3px;">Pamantasan ng Lungsod ng San Pablo</div>
        ${orgName ? `<div style="font-size:.95rem;font-weight:800;color:#fff;margin-bottom:2px;">${esc(orgName)}</div>` : ''}
        ${orgTagline && orgTagline.trim() ? `<div style="font-size:.68rem;color:rgba(183,228,195,.7);font-style:italic;">"${esc(orgTagline)}"</div>` : ''}
    </td>`;

    // RIGHT: org logo + collab logo side by side (same row, same size)
    let rightInner = '';
    if (LOGO_ORG) {
        rightInner += `<img src="${LOGO_ORG}" alt="Org" style="height:${LOGO_SIZE};width:${LOGO_SIZE};object-fit:contain;border-radius:50%;border:2px solid rgba(255,255,255,0.25);display:inline-block;vertical-align:middle;">`;
    }
    if (collabLogo) {
        // Try direct key first, then try adding extension
        const collabSrc = LOGO_MAP[collabLogo] || LOGO_MAP[collabLogo + '.jpg'] || LOGO_MAP[collabLogo + '.png'] || null;
        if (collabSrc) {
            rightInner += `<img src="${collabSrc}" alt="Collab" style="height:${LOGO_SIZE};width:${LOGO_SIZE};object-fit:contain;border-radius:50%;border:2px solid rgba(255,255,255,0.25);display:inline-block;vertical-align:middle;margin-left:6px;">`;
        }
    }

    const rightCell = rightInner
        ? `<td style="text-align:center;vertical-align:middle;padding:0 0 0 8px;white-space:nowrap;">${rightInner}</td>`
        : `<td style="width:80px;"></td>`;

    return `<div style="background:#fff;max-width:700px;margin:20px auto;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,.12);overflow:hidden;">
        <div style="background:#1e3a3a;padding:14px 18px;">
            <table style="width:100%;border-collapse:collapse;">
                <tr>${leftCell}${centerCell}${rightCell}</tr>
            </table>
        </div>
        <div style="padding:22px 26px;">${bodyContent}</div>
        <div style="background:#f4faf7;border-top:2px solid #2d6a4f;padding:10px 24px;text-align:center;">
            <div style="font-size:.75rem;color:#2d6a4f;font-style:italic;">"Primed to Lead and Serve for Progress"</div>
        </div>
    </div>`;
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

/* ── Collaborated logo checkbox toggle ─────────────────────────────────────── */
// All org logos for JS preview
const ALL_LOGO_MAP = LOGO_MAP; // reuse same map

function updateCollabPreview(val) {
    var src = ALL_LOGO_MAP[val] || null;
    var preview = document.getElementById('collabLogoPreview');
    var img = document.getElementById('collabLogoImg');
    if (src && preview && img) {
        img.src = src;
        preview.style.display = 'block';
    } else if (preview) {
        preview.style.display = 'none';
    }
    var hidden = document.getElementById('collaboratedLogoHidden');
    if (hidden) hidden.value = val;
}

function updateTaglineFromLogo(val) {
    if (!val) return;
    const stem = val.replace(/\.[^.]+$/, '').toUpperCase();
    const tag = document.getElementById('organizationTagline');
    if (tag && typeof ORG_TAGLINES !== 'undefined' && ORG_TAGLINES[stem]) {
        tag.value = ORG_TAGLINES[stem];
    }
}

function toggleColloboratedPicker(cb) {
    var wrap = document.getElementById('colloboratedPickerWrap');
    var hidden = document.getElementById('collaboratedLogoHidden');
    var sel    = document.getElementById('collaboratedLogo');
    if (cb.checked) {
        wrap.style.display = 'block';
        hidden.value = sel.value;
    } else {
        wrap.style.display = 'none';
        hidden.value = '';
        sel.value = '';
    }
}
// Keep hidden field in sync with select
document.addEventListener('DOMContentLoaded', function() {
    var sel    = document.getElementById('collaboratedLogo');
    var hidden = document.getElementById('collaboratedLogoHidden');
    if (sel && hidden) {
        sel.addEventListener('change', function() {
            hidden.value = this.value;
            updateCollabPreview(this.value);
        });
    }
});
</script>


<script src="../js/script.js"></script>
<script src="../js/navbar.js"></script>
<script src="../js/notifications.js"></script>
<script src="../js/document_tracking.js"></script>
<script>
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
        } else {
            name.textContent = '';
            zone.classList.remove('has-file');
        }
    });
    zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', function() { zone.classList.remove('drag-over'); });
    zone.addEventListener('drop', function(e) {
        e.preventDefault(); zone.classList.remove('drag-over');
        var dt = e.dataTransfer;
        if (dt && dt.files && dt.files[0]) {
            input.files = dt.files;
            name.textContent = dt.files[0].name;
            zone.classList.add('has-file');
        }
    });
})();

// ── Patches for document_tracking.js ────────────────────────────────────────

// validateTemplateForm — tagline and org name are now fixed hidden fields
window.validateTemplateForm = function() {
    var sel   = document.getElementById('templateSelect');
    var title = document.getElementById('templateTitle');
    var btn   = document.getElementById('submitBtn');
    if (!btn) return;
    if (!sel || !sel.value || !title || !title.value.trim()) {
        btn.disabled = true; return;
    }
    var allFields = document.querySelectorAll('#templateFieldsContainer input:not([type=hidden]):not([type=checkbox]), #templateFieldsContainer textarea');
    btn.disabled = Array.from(allFields).some(function(f) { return f.required && !f.value.trim(); });
};

// organizationTagline is now pre-filled as hidden field — no submit patch needed

// organizationTagline is now a fixed hidden field — no seeding needed

// 4. Re-attach listeners to new validateTemplateForm
['templateSelect','templateTitle'].forEach(function(id) {
    var el = document.getElementById(id);
    if (!el) return;
    var evt = el.tagName === 'SELECT' ? 'change' : 'input';
    el.addEventListener(evt, window.validateTemplateForm);
});
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
</body>
</html>