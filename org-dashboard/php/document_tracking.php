<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

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
    $mime = in_array(strtolower(pathinfo($abs, PATHINFO_EXTENSION)), ['jpg','jpeg']) ? 'image/jpeg' : 'image/png';
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
}
$plspLogoB64 = b64img('../../plsplogo.png');
$cigLogoB64  = b64img('../../Assets/CIG.jpg');
$osasLogoB64 = b64img('../../Assets/OSAS.jpg');
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

                        <td class="remarks-cell" title="<?= htmlspecialchars($doc['admin_remarks']) ?>">
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
                            ?>
                            <div style="display:flex;align-items:center;">
                                <?= $remarkIcon ?>
                                <span><?= htmlspecialchars($remark) ?></span>
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
        <div class="modal-content-header">
            <span class="close-modal" id="closeUploadModal">&times;</span>
            <h2><i class="fas fa-cloud-upload-alt"></i> Upload Document</h2>
            <div class="upload-tabs">
                <button class="tab-button active" data-tab="regular-upload">
                    <i class="fas fa-file-upload"></i> Regular Upload
                </button>
                <button class="tab-button" data-tab="template-upload">
                    <i class="fas fa-file-contract"></i> Use Template
                </button>
            </div>
        </div>
        <div class="modal-content-body">
            <!-- Regular Upload -->
            <div id="regular-upload" class="tab-content active">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="docTitle">Document Title <span>*</span></label>
                        <input type="text" id="docTitle" name="title" required placeholder="Enter document title">
                    </div>
                    <div class="form-group">
                        <label for="docDescription">Description <small style="font-weight:400;color:#9ca3af;">(Optional)</small></label>
                        <textarea id="docDescription" name="description" rows="3" placeholder="Brief description…"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="relatedEvent">Related Event <small style="font-weight:400;color:#9ca3af;">(Optional)</small></label>
                        <select id="relatedEvent" name="related_event">
                            <option value="">None</option>
                            <option value="Outreach Program">Outreach Program</option>
                            <option value="Quarterly Meeting">Quarterly Meeting</option>
                            <option value="Fundraising Gala">Fundraising Gala</option>
                            <option value="Team Building">Team Building</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fileUpload">File Upload <span>*</span></label>
                        <input type="file" id="fileUpload" name="file" accept=".pdf,.docx,.xlsx" required>
                        <small>Allowed: PDF, DOCX, XLSX &nbsp;·&nbsp; Max 10 MB</small>
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
                        <label for="templateSelect">Select Template <span>*</span></label>
                        <select id="templateSelect" name="template_id" required onchange="loadTemplateFields()">
                            <option value="">— Choose a Template —</option>
                            <option value="meeting_minutes">Meeting Minutes</option>
                            <option value="event_proposal">Event Proposal</option>
                            <option value="financial_report">Financial Report</option>
                            <option value="incident_report">Incident Report</option>
                            <option value="membership_form">Membership Form</option>
                            <option value="project_proposal">Project Proposal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="collaboratedLogo">Collaborated Logo <small style="font-weight:400;color:#9ca3af;">(Optional)</small></label>
                        <select id="collaboratedLogo" name="collaborated_logo">
                            <option value="">— No Collaborated Logo —</option>
                            <option value="CIG.jpg">CIG Logo</option>
                            <option value="OSAS.jpg">OSAS Logo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="organizationName">Organization Name <span>*</span></label>
                        <input type="text" id="organizationName" name="organization_name"
                               value="PLSP Economics Society – EcoS" required>
                    </div>
                    <div class="form-group">
                        <label for="organizationTagline">Organization Tagline <span>*</span></label>
                        <input type="text" id="organizationTagline" name="organization_tagline"
                               value="Empowered and committed organization of service." required>
                    </div>
                    <div id="templateFieldsContainer" class="template-fields-container"></div>
                </form>
            </div>
        </div>
        <div class="modal-content-footer">
            <div class="form-actions" id="formActions">
                <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                <button type="submit" class="btn-submit" id="submitBtn">Submit Document</button>
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
const LOGO_PLSP = '<?= $plspLogoB64 ?>';
const LOGO_MAP  = { 'CIG.jpg':'<?= $cigLogoB64 ?>', 'OSAS.jpg':'<?= $osasLogoB64 ?>' };

function renderTplPreviewBody(data, title) {
    const orgName    = data.organization_name    || 'PLSP Economics Society – EcoS';
    const orgTagline = data.organization_tagline || 'Empowered and committed organization of service.';
    const bodyContent = (data.template_id === 'project_proposal') ? buildProjectProposalBody(data) : buildGenericBody(data);
    return `<div style="background:#fff;max-width:700px;margin:20px auto;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,.12);overflow:hidden;"><div style="background:#1e3a3a;padding:16px 20px;"><table style="width:100%;border-collapse:collapse;"><tr><td style="width:80px;text-align:center;vertical-align:middle;padding:0 8px 0 0;">${LOGO_PLSP?`<img src="${LOGO_PLSP}" alt="PLSP" style="height:64px;width:auto;display:block;margin:0 auto;">`:''}</td><td style="text-align:center;vertical-align:middle;padding:0 8px;"><div style="font-size:.65rem;font-weight:700;color:#a8d5b5;letter-spacing:.08em;text-transform:uppercase;margin-bottom:3px;">PAMANTASAN NG LUNGSOD NG SAN PABLO</div><div style="font-size:1rem;font-weight:800;color:#fff;margin-bottom:3px;">${esc(orgName)}</div><div style="font-size:.73rem;color:#a8d5b5;font-style:italic;">"${esc(orgTagline)}"</div></td><td style="width:80px;text-align:center;vertical-align:middle;padding:0 0 0 8px;">${data.collaborated_logo&&LOGO_MAP[data.collaborated_logo]?`<img src="${LOGO_MAP[data.collaborated_logo]}" alt="Logo" style="height:64px;width:auto;display:block;margin:0 auto;">`:''}</td></tr></table></div><div style="padding:22px 26px;">${bodyContent}</div><div style="background:#f4faf7;border-top:2px solid #2d6a4f;padding:10px 24px;text-align:center;"><div style="font-size:.75rem;color:#2d6a4f;font-style:italic;">"Primed to Lead and Serve for Progress"</div></div></div>`;
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