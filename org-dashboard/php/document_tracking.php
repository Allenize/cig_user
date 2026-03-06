<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "cig_system");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$userId = $_SESSION['user_id'];

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
$submissionsResult = $stmt->get_result();
$submissions = [];
while ($row = $submissionsResult->fetch_assoc()) {
    $submissions[] = $row;
}
$stmt->close();
$conn->close();

/* ── helpers ── */
function fileTypeBadge(string $fileName): string {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $map = [
        'pdf'  => ['PDF',  '#e74c3c'],
        'docx' => ['DOCX', '#2980b9'],
        'doc'  => ['DOC',  '#2980b9'],
        'xlsx' => ['XLSX', '#27ae60'],
        'xls'  => ['XLS',  '#27ae60'],
    ];
    [$label, $color] = $map[$ext] ?? [strtoupper($ext), '#7f8c8d'];
    return '<span class="file-type-badge" style="background:' . $color . '">' . $label . '</span>';
}

function fileIcon(string $fileName): string {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $icons = [
        'pdf'  => 'fa-file-pdf',
        'docx' => 'fa-file-word',
        'doc'  => 'fa-file-word',
        'xlsx' => 'fa-file-excel',
        'xls'  => 'fa-file-excel',
    ];
    return $icons[$ext] ?? 'fa-file-alt';
}

function humanFileSize(int $bytes): string {
    if ($bytes < 1024)       return $bytes . ' B';
    if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Tracking – OrgHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/document_tracking.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <style>
        /* ── File-type badge ── */
        .file-type-badge {
            display: inline-block;
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            padding: .15rem .5rem;
            border-radius: 4px;
            letter-spacing: .04em;
            vertical-align: middle;
        }

        /* ── Document name cell ── */
        .doc-name-cell {
            display: flex;
            align-items: center;
            gap: .55rem;
        }
        .doc-name-cell .doc-icon {
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .doc-name-cell .doc-icon.pdf-icon  { color: #e74c3c; }
        .doc-name-cell .doc-icon.word-icon { color: #2980b9; }
        .doc-name-cell .doc-icon.xlsx-icon { color: #27ae60; }
        .doc-name-cell .doc-icon.gen-icon  { color: #7f8c8d; }
        .doc-name-cell .doc-meta-text strong { display: block; font-size: .9rem; }
        .doc-name-cell .doc-meta-text small  { color: #888; font-size: .75rem; }

        /* ── Status badges ── */
        .status-badge {
            display: inline-block;
            padding: .25rem .75rem;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 600;
        }
        .status-badge.pending  { background: #fff3cd; color: #856404; }
        .status-badge.approved { background: #d1f0e0; color: #155724; }
        .status-badge.rejected { background: #f8d7da; color: #721c24; }
        .status-badge.in_review { background: #cce5ff; color: #004085; }

        /* ── Action buttons ── */
        .action-btns { display: flex; gap: .4rem; flex-wrap: wrap; }
        .btn-view, .btn-dl {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .85rem;
            border-radius: 6px;
            font-size: .8rem;
            font-weight: 600;
            text-decoration: none;
            transition: filter .15s;
            border: none;
            cursor: pointer;
        }
        .btn-view { background: #2d6a4f; color: #fff; }
        .btn-dl   { background: #2980b9; color: #fff; }
        .btn-view:hover, .btn-dl:hover { filter: brightness(1.12); }
        .no-file { color: #aaa; font-size: .82rem; font-style: italic; }

        /* ── Remarks cell ── */
        .remarks-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .85rem;
            color: #555;
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #aaa;
        }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }

        /* ── docx-preview rendering fixes ── */
        #previewDocxWrap { background: #e8e8e8; }
        #previewDocxWrap .docx-wrapper {
            background: #e8e8e8 !important;
            padding: 16px !important;
        }
        #previewDocxWrap .docx-wrapper > section.docx {
            width: 100% !important;
            max-width: 900px !important;
            min-height: auto !important;
            margin: 0 auto 16px auto !important;
            padding: 72px 90px !important;
            box-shadow: 0 2px 12px rgba(0,0,0,.2) !important;
            box-sizing: border-box !important;
            overflow: visible !important;
            background: #fff !important;
        }
        /* Force all images visible */
        #previewDocxWrap img,
        #previewDocxWrap svg image,
        #previewDocxWrap .docx-wrapper image {
            max-width: 100% !important;
            height: auto !important;
            visibility: visible !important;
            display: inline-block !important;
        }
        #previewDocxWrap table {
            max-width: 100% !important;
            table-layout: fixed !important;
            word-break: break-word !important;
        }
        /* Anchored/floating drawing containers — reset ALL positioning
           so images land inside their parent table cell, not the page */
        #previewDocxWrap [style*="position:absolute"],
        #previewDocxWrap [style*="position: absolute"] {
            position: relative !important;
            left: auto !important;
            top: auto !important;
            transform: none !important;
            margin: 0 !important;
        }
        /* Header table: equal-width columns, images centered in each cell */
        #previewDocxWrap header table,
        #previewDocxWrap .docx-wrapper header table {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
        }
        #previewDocxWrap header td,
        #previewDocxWrap .docx-wrapper header td {
            vertical-align: middle !important;
            text-align: center !important;
            overflow: visible !important;
            padding: 4px !important;
        }
        #previewDocxWrap header img,
        #previewDocxWrap .docx-wrapper header img {
            position: relative !important;
            display: block !important;
            margin: 0 auto !important;
            max-width: 100% !important;
            max-height: 120px !important;
            width: auto !important;
            height: auto !important;
            left: auto !important;
            top: auto !important;
            transform: none !important;
            visibility: visible !important;
        }
    </style>
</head>
<body>
    <?php include '../php/navbar.php'; ?>
    <?php include '../php/topbar.php'; ?>

    <main class="main-content">
        <div class="document-container">

            <!-- Header -->
            <div class="document-header">
                <h1><i class="fas fa-folder-open"></i> Documents</h1>
                <button class="btn-upload" id="openUploadModal">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Document
                </button>
            </div>

            <!-- Search / Filter -->
            <div class="search-filter-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by title or file name…">
                </div>
                <div class="filter-wrapper">
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="In_review">In Review</option>
                    </select>
                    <input type="date" id="dateFilter">
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <!-- Fixed header — never scrolls -->
                <table class="documents-table" style="display:table;width:100%;table-layout:fixed;">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Date Submitted</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th>Admin Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
                <!-- Scrollable body — scrollbar stays inside the card -->
                <div class="table-scroll-body">
                <table class="documents-table" id="documentsTable" style="display:table;width:100%;table-layout:fixed;">
                    <tbody>
                        <?php if (empty($submissions)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <p>No documents submitted yet.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($submissions as $doc):
                            $ext   = strtolower(pathinfo($doc['file_name'] ?? '', PATHINFO_EXTENSION));
                            $imap  = [
                                'pdf'  => ['fa-file-pdf',   'pdf-icon'],
                                'docx' => ['fa-file-word',  'word-icon'],
                                'doc'  => ['fa-file-word',  'word-icon'],
                                'xlsx' => ['fa-file-excel', 'xlsx-icon'],
                                'xls'  => ['fa-file-excel', 'xlsx-icon'],
                            ];
                            [$iClass, $iColor] = $imap[$ext] ?? ['fa-file-alt', 'gen-icon'];
                        ?>
                        <?php
                            $isTemplateDoc = strpos($doc['description'] ?? '', 'Template:') === 0 && !empty($doc['submission_data']);
                            $safeSubData = $isTemplateDoc ? htmlspecialchars($doc['submission_data'], ENT_QUOTES, 'UTF-8') : '';
                        ?>
                        <tr data-title="<?php echo strtolower(htmlspecialchars($doc['title'])); ?>"
                            data-status="<?php echo strtolower($doc['status']); ?>"
                            data-date="<?php echo date('Y-m-d', strtotime($doc['submitted_at'])); ?>"
                            data-is-template="<?php echo $isTemplateDoc ? '1' : '0'; ?>"
                            data-submission-data="<?php echo $safeSubData; ?>">

                            <!-- Document name + type badge + size -->
                            <td>
                                <div class="doc-name-cell">
                                    <?php if ($isTemplateDoc): ?>
                                        <i class="fas fa-file-contract doc-icon" style="color:#6c3483;"></i>
                                    <?php else: ?>
                                        <i class="fas <?php echo $iClass; ?> doc-icon <?php echo $iColor; ?>"></i>
                                    <?php endif; ?>
                                    <div class="doc-meta-text">
                                        <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                        <small>
                                            <?php if ($isTemplateDoc): ?>
                                                <span class="file-type-badge" style="background:#6c3483;">TEMPLATE</span>
                                            <?php elseif ($doc['file_name']): ?>
                                                <?php echo fileTypeBadge($doc['file_name']); ?>
                                                <?php if ($doc['file_size']): ?>
                                                    &nbsp;<?php echo humanFileSize((int)$doc['file_size']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <em>No file</em>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <!-- Date -->
                            <td><?php echo date('M d, Y', strtotime($doc['submitted_at'])); ?></td>

                            <!-- Submitted by -->
                            <td>
                                <div><?php echo htmlspecialchars($doc['full_name']); ?></div>
                                <?php if (!empty($doc['display_org']) && $doc['display_org'] !== $doc['full_name']): ?>
                                    <small style="color:#6b9080;font-size:.78rem;"><?php echo htmlspecialchars($doc['display_org']); ?></small>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td>
                                <span class="status-badge <?php echo strtolower($doc['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $doc['status'])); ?>
                                </span>
                            </td>

                            <!-- Admin remarks -->
                            <td>
                                <?php if (!empty($doc['admin_remarks']) && $doc['admin_remarks'] !== 'Awaiting review'): ?>
                                    <div style="display:flex;align-items:flex-start;gap:6px;">
                                        <?php if ($doc['status'] === 'approved'): ?>
                                            <i class="fas fa-check-circle" style="color:#27ae60;margin-top:2px;flex-shrink:0;"></i>
                                        <?php elseif ($doc['status'] === 'rejected'): ?>
                                            <i class="fas fa-times-circle" style="color:#e74c3c;margin-top:2px;flex-shrink:0;"></i>
                                        <?php else: ?>
                                            <i class="fas fa-comment-alt" style="color:#2980b9;margin-top:2px;flex-shrink:0;"></i>
                                        <?php endif; ?>
                                        <span style="font-size:.85rem;color:#333;line-height:1.4;">
                                            <?php echo htmlspecialchars($doc['admin_remarks']); ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#aaa;font-size:.82rem;font-style:italic;">
                                        <i class="fas fa-clock"></i> Awaiting review
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td>
                                <?php if ($isTemplateDoc): ?>
                                <div class="action-btns">
                                    <button class="btn-view"
                                        onclick="openTemplatePreview(this.closest('tr'))">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                                <?php elseif ($doc['has_file']): ?>
                                <div class="action-btns">
                                    <button class="btn-view"
                                        onclick="openPreviewModal(<?php echo $doc['submission_id']; ?>,'<?php echo $ext; ?>','<?php echo addslashes(htmlspecialchars($doc['title'])); ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                                <?php else: ?>
                                <span class="no-file"><i class="fas fa-ban"></i> No file</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div><!-- /.table-scroll-body -->
            </div><!-- /.table-responsive -->
        </div><!-- /.document-container -->
    </main>

    <!-- Upload Modal (unchanged from original) -->
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
                            <input type="text" id="docTitle" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="docDescription">Description (Optional)</label>
                            <textarea id="docDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="relatedEvent">Related Event (Optional)</label>
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
                            <small>Allowed: PDF, DOCX, XLSX (Max 10 MB)</small>
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
                                <option value="">-- Choose a Template --</option>
                                <option value="meeting_minutes">Meeting Minutes</option>
                                <option value="event_proposal">Event Proposal</option>
                                <option value="financial_report">Financial Report</option>
                                <option value="incident_report">Incident Report</option>
                                <option value="membership_form">Membership Form</option>
                                <option value="project_proposal">Project Proposal</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="collaboratedLogo">Collaborated Logo (Optional)</label>
                            <select id="collaboratedLogo" name="collaborated_logo">
                                <option value="">-- No Collaborated Logo --</option>
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
                    <button type="submit" class="btn-submit" id="submitBtn">Submit Document</button>
                    <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Document Preview Modal ── -->
    <div id="previewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:10px;width:92vw;max-width:1060px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.4);">
            <!-- Header bar -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 18px;background:#2d6a4f;color:#fff;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:9px;min-width:0;">
                    <i id="previewFileIcon" class="fas fa-file-alt" style="font-size:1.1rem;flex-shrink:0;"></i>
                    <span id="previewTitle" style="font-size:.95rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                </div>
                <button onclick="closePreviewModal()" style="background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer;line-height:1;flex-shrink:0;margin-left:12px;">&times;</button>
            </div>
            <!-- Loading -->
            <div id="previewLoading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:12px;color:#666;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#2d6a4f;"></i>
                <span style="font-size:.9rem;">Loading document…</span>
            </div>
            <!-- Error -->
            <div id="previewError" style="display:none;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:10px;color:#c0392b;">
                <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
                <span id="previewErrorMsg" style="font-size:.9rem;text-align:center;max-width:400px;"></span>
            </div>
            <!-- PDF iframe -->
            <iframe id="previewPdfFrame" style="display:none;flex:1;border:none;width:100%;"></iframe>
            <!-- DOCX / XLSX container -->
            <div id="previewDocxWrap" style="display:none;flex:1;overflow:auto;"></div>
        </div>
    </div>


    <!-- ── Template Preview Modal ── -->
    <div id="tplPreviewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:16px;width:92vw;max-width:820px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.4);">
            <!-- Header bar -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:#1e3a3a;color:#fff;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                    <i class="fas fa-file-contract" style="font-size:1.1rem;color:#a8d5b5;flex-shrink:0;"></i>
                    <div style="min-width:0;">
                        <div id="tplPreviewTitle" style="font-size:1rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
                        <div id="tplPreviewSubtitle" style="font-size:.75rem;color:#a8d5b5;margin-top:2px;"></div>
                    </div>
                </div>
                <button onclick="closeTplPreview()" style="background:rgba(255,255,255,.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">&times;</button>
            </div>
            <!-- Document body -->
            <div id="tplPreviewBody" style="flex:1;overflow-y:auto;padding:0;background:#e8ecec;">
            </div>
        </div>
    </div>

    <script>
    /* ── Template Preview Modal ───────────────────────────────────────────── */
    const TPL_LABELS = {
        meeting_minutes:  'Meeting Minutes',
        event_proposal:   'Event Proposal',
        financial_report: 'Financial Report',
        incident_report:  'Incident Report',
        membership_form:  'Membership Form',
        project_proposal: 'Project Proposal',
    };

    /* field groups that should render as block sections (not inline) */
    const TPL_TEXTAREA_KEYS = new Set([
        'agenda','discussion','action_items','description','requirements',
        'expense_breakdown','remarks','incident_description','individuals_involved',
        'witnesses','action_taken','recommendations','opening_statement',
        'project_summary','project_goal','project_objectives','expected_outputs',
        'monitoring_details','evaluation_details','security_plan','closing_statement',
        'attendees','skills','availability'
    ]);

    /* Project Proposal section grouping */
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
        if (TPL_TEXTAREA_KEYS.has(key) && lines.length > 1) {
            return lines.map(l => '<div style="margin-bottom:3px">'+esc(l)+'</div>').join('');
        }
        return esc(val);
    }

    function buildGenericBody(data) {
        const labels = data.field_labels || {};
        const fields = data.fields || {};
        let html = '';
        Object.entries(labels).forEach(([key, label]) => {
            const val = fields[key] || '';
            const isBlock = TPL_TEXTAREA_KEYS.has(key);
            html += `<div style="margin-bottom:${isBlock?'18px':'10px'}">
                <div style="font-size:.7rem;font-weight:700;color:#2d6a4f;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">${esc(label)}</div>
                <div style="font-size:.92rem;color:#1e3a3a;line-height:1.55;${isBlock?'background:#f9fbf9;padding:8px 12px;border-radius:8px;border-left:3px solid #2d6a4f;':''}">${renderValue(key, val)}</div>
            </div>`;
        });
        return html;
    }

    function buildProjectProposalBody(data) {
        const labels = data.field_labels || {};
        const fields = data.fields || {};
        const tableKeys = new Set(['organization','project_title','project_type','project_involvement','project_location','proposed_start_date','proposed_end_date','number_participants','budget_source','budget_partner','budget_total']);
        let html = '';
        PP_SECTIONS.forEach(sec => {
            if (sec.heading) {
                html += `<div style="font-size:.95rem;font-weight:700;color:#fff;background:#2d6a4f;padding:7px 14px;border-radius:6px;margin-bottom:12px;">${esc(sec.heading)}</div>`;
            }
            const isTable = sec.keys.some(k => tableKeys.has(k));
            if (isTable) {
                html += '<table style="width:100%;border-collapse:collapse;margin-bottom:14px;">';
                sec.keys.forEach(key => {
                    if (!labels[key]) return;
                    html += `<tr>
                        <td style="font-weight:600;font-size:.82rem;color:#444;background:#f0f5f2;padding:7px 10px;border:1px solid #d4e6d8;width:38%;vertical-align:top;">${esc(labels[key])}</td>
                        <td style="font-size:.88rem;color:#1e3a3a;padding:7px 10px;border:1px solid #d4e6d8;vertical-align:top;">${renderValue(key, fields[key]||'')}</td>
                    </tr>`;
                });
                html += '</table>';
            } else {
                sec.keys.forEach(key => {
                    if (!labels[key]) return;
                    const val = fields[key] || '';
                    const isBlock = TPL_TEXTAREA_KEYS.has(key);
                    html += `<div style="margin-bottom:${isBlock?'16px':'8px'}">
                        <div style="font-size:.7rem;font-weight:700;color:#2d6a4f;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">${esc(labels[key])}</div>
                        <div style="font-size:.9rem;color:#1e3a3a;line-height:1.55;${isBlock?'background:#f9fbf9;padding:8px 12px;border-radius:8px;border-left:3px solid #2d6a4f;':''}">${renderValue(key, val)}</div>
                    </div>`;
                });
            }
        });
        return html;
    }

    // Logo data URIs — embedded by PHP so paths are always correct
    <?php
    function b64img($path) {
        $abs = realpath(__DIR__ . '/' . $path);
        if (!$abs || !file_exists($abs)) return '';
        $mime = in_array(strtolower(pathinfo($abs, PATHINFO_EXTENSION)), ['jpg','jpeg']) ? 'image/jpeg' : 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
    }
    $plspLogoB64   = b64img('../../plsplogo.png');
    $cigLogoB64    = b64img('../../Assets/CIG.jpg');
    $osasLogoB64   = b64img('../../Assets/OSAS.jpg');
    ?>
    const LOGO_PLSP = '<?php echo $plspLogoB64; ?>';
    const LOGO_MAP  = {
        'CIG.jpg':  '<?php echo $cigLogoB64; ?>',
        'OSAS.jpg': '<?php echo $osasLogoB64; ?>',
    };

    function renderTplPreviewBody(data, title) {
        const orgName    = data.organization_name    || 'PLSP Economics Society – EcoS';
        const orgTagline = data.organization_tagline || 'Empowered and committed organization of service.';
        const tplName    = data.template_name || TPL_LABELS[data.template_id] || 'Document';
        const isProposal = (data.template_id === 'project_proposal');

        const bodyContent = isProposal ? buildProjectProposalBody(data) : buildGenericBody(data);

        return `<div style="background:#fff;max-width:700px;margin:20px auto;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,.12);overflow:hidden;">
            <!-- Document header -->
            <div style="background:#1e3a3a;padding:16px 20px;">
                <!-- Logo row: PLSP | Org info | Collab logo -->
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <!-- Left: PLSP logo -->
                        <td style="width:80px;text-align:center;vertical-align:middle;padding:0 8px 0 0;">
                            ${LOGO_PLSP ? `<img src="${LOGO_PLSP}" alt="PLSP" style="height:64px;width:auto;display:block;margin:0 auto;">` : ''}
                        </td>
                        <!-- Center: org text -->
                        <td style="text-align:center;vertical-align:middle;padding:0 8px;">
                            <div style="font-size:.65rem;font-weight:700;color:#a8d5b5;letter-spacing:.08em;text-transform:uppercase;margin-bottom:3px;">PAMANTASAN NG LUNGSOD NG SAN PABLO</div>
                            <div style="font-size:1rem;font-weight:800;color:#fff;margin-bottom:3px;">${esc(orgName)}</div>
                            <div style="font-size:.73rem;color:#a8d5b5;font-style:italic;">"${esc(orgTagline)}"</div>
                        </td>
                        <!-- Right: collaborated logo or blank -->
                        <td style="width:80px;text-align:center;vertical-align:middle;padding:0 0 0 8px;">
                            ${data.collaborated_logo && LOGO_MAP[data.collaborated_logo]
                                ? `<img src="${LOGO_MAP[data.collaborated_logo]}" alt="Logo" style="height:64px;width:auto;display:block;margin:0 auto;">`
                                : ''}
                        </td>
                    </tr>
                </table>

            </div>
            <!-- Fields -->
            <div style="padding:22px 26px;">
                ${bodyContent}
            </div>
            <!-- Footer -->
            <div style="background:#f4faf7;border-top:2px solid #2d6a4f;padding:10px 24px;text-align:center;">
                <div style="font-size:.75rem;color:#2d6a4f;font-style:italic;">"Primed to Lead and Serve for Progress"</div>
            </div>
        </div>`;
    }

    window.openTemplatePreview = function(row) {
        const raw = row ? row.getAttribute('data-submission-data') : null;
        const title = row ? (row.querySelector('.doc-meta-text strong') || {}).textContent || 'Document' : 'Document';
        let data = null;
        if (raw) {
            try { data = JSON.parse(raw); } catch(e) {}
        }
        if (!data) {
            // Fall back: no JSON stored (old submission) — open file preview
            const btn = row ? row.querySelector('.btn-view') : null;
            if (btn) { alert('Preview data not available for this submission. Please re-submit using the template form.'); }
            return;
        }
        const modal = document.getElementById('tplPreviewModal');
        document.getElementById('tplPreviewTitle').textContent = title;
        document.getElementById('tplPreviewSubtitle').textContent = (data.template_name || '') + ' — Template Document';
        document.getElementById('tplPreviewBody').innerHTML = renderTplPreviewBody(data, title);
        modal.style.display = 'flex';
    };

    /* Also handle in-memory preview for just-submitted rows (before page reload) */
    window._pendingTplData = {};
    window.openTemplatePreviewById = function(submissionId, dataJson, title) {
        let data = null;
        try { data = typeof dataJson === 'string' ? JSON.parse(dataJson) : dataJson; } catch(e) {}
        if (!data) return;
        const modal = document.getElementById('tplPreviewModal');
        document.getElementById('tplPreviewTitle').textContent = title;
        document.getElementById('tplPreviewSubtitle').textContent = (data.template_name || '') + ' — Template Document';
        document.getElementById('tplPreviewBody').innerHTML = renderTplPreviewBody(data, title);
        modal.style.display = 'flex';
    };

    function closeTplPreview() {
        document.getElementById('tplPreviewModal').style.display = 'none';
        document.getElementById('tplPreviewBody').innerHTML = '';
    }
    document.getElementById('tplPreviewModal').addEventListener('click', function(e) {
        if (e.target === this) closeTplPreview();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeTplPreview();
    });
    </script>

    <script src="../js/script.js"></script>
    <script src="../js/document_tracking.js"></script>
    <script>
    /* ── Preview modal ── */
    function openPreviewModal(id, ext, title) {
        var modal    = document.getElementById('previewModal');
        var loading  = document.getElementById('previewLoading');
        var errorDiv = document.getElementById('previewError');
        var pdfFrame = document.getElementById('previewPdfFrame');
        var docxWrap = document.getElementById('previewDocxWrap');
        var titleEl  = document.getElementById('previewTitle');
        var iconEl   = document.getElementById('previewFileIcon');

        // Reset state
        loading.style.display  = 'flex';
        errorDiv.style.display = 'none';
        pdfFrame.style.display = 'none';
        docxWrap.style.display = 'none';
        pdfFrame.src           = '';
        docxWrap.innerHTML     = '';
        modal.style.display    = 'flex';
        titleEl.textContent    = title;

        var iconMap = { pdf:'fa-file-pdf', docx:'fa-file-word', doc:'fa-file-word', xlsx:'fa-file-excel', xls:'fa-file-excel' };
        iconEl.className = 'fas ' + (iconMap[ext] || 'fa-file-alt');

        var previewUrl = 'file_preview.php?submission_id=' + id;

        if (ext === 'pdf') {
            pdfFrame.src           = previewUrl;
            pdfFrame.style.display = 'block';
            loading.style.display  = 'none';
            pdfFrame.onerror = function() { showPreviewError('Failed to load PDF.'); };

        } else if (ext === 'docx' || ext === 'doc') {
            // Convert DOCX → PDF server-side via LibreOffice, display in iframe
            var convertUrl = 'docx_to_pdf.php?submission_id=' + id;
            pdfFrame.src           = convertUrl;
            pdfFrame.style.display = 'block';
            // Show a "converting…" message while LibreOffice runs (may take 2–5s)
            loading.querySelector('span').textContent = 'Converting document…';
            pdfFrame.onload = function() {
                loading.style.display = 'none';
            };
            pdfFrame.onerror = function() {
                showPreviewError('Failed to convert document.');
            };

        } else if (ext === 'xlsx' || ext === 'xls') {
            if (typeof XLSX === 'undefined') {
                var s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
                s.onload = function() { loadXlsx(previewUrl, docxWrap, loading); };
                document.head.appendChild(s);
            } else {
                loadXlsx(previewUrl, docxWrap, loading);
            }
        } else {
            showPreviewError('Preview not available for this file type.');
        }
    }

    function loadXlsx(url, wrap, loading) {
        fetch(url)
            .then(function(r) {
                if (!r.ok) throw new Error('Server error ' + r.status);
                return r.arrayBuffer();
            })
            .then(function(buf) {
                var wb   = XLSX.read(new Uint8Array(buf), { type: 'array' });
                var html = '<style>table{border-collapse:collapse;font-size:.8rem;width:100%;}td,th{border:1px solid #ccc;padding:4px 8px;white-space:nowrap;}</style>';
                wb.SheetNames.forEach(function(name) {
                    html += '<div style="padding:16px;"><h3 style="margin:0 0 8px;color:#2d6a4f;font-size:.9rem;">' + name + '</h3>';
                    html += XLSX.utils.sheet_to_html(wb.Sheets[name], { editable: false });
                    html += '</div>';
                });
                wrap.innerHTML        = html;
                wrap.style.display    = 'block';
                loading.style.display = 'none';
            })
            .catch(function(e) { showPreviewError('Could not render spreadsheet: ' + e.message); });
    }

    function showPreviewError(msg) {
        document.getElementById('previewLoading').style.display = 'none';
        document.getElementById('previewError').style.display   = 'flex';
        document.getElementById('previewErrorMsg').textContent  = msg;
    }

    function closePreviewModal() {
        document.getElementById('previewModal').style.display = 'none';
        document.getElementById('previewPdfFrame').src        = '';
        document.getElementById('previewDocxWrap').innerHTML  = '';
    }

    document.getElementById('previewModal').addEventListener('click', function(e) {
        if (e.target === this) closePreviewModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePreviewModal();
    });

    /* ── Client-side search / filter ── */
    (function () {
        const searchInput  = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter   = document.getElementById('dateFilter');
        const rows         = document.querySelectorAll('#documentsTable tbody tr[data-title]');

        function applyFilters() {
            const q      = searchInput.value.toLowerCase().trim();
            const status = statusFilter.value.toLowerCase();
            const date   = dateFilter.value;

            rows.forEach(function(row) {
                const titleMatch  = !q      || row.dataset.title.includes(q);
                const statusMatch = !status || row.dataset.status === status;
                const dateMatch   = !date   || row.dataset.date === date;
                row.style.display = (titleMatch && statusMatch && dateMatch) ? '' : 'none';
            });
        }

        searchInput .addEventListener('input',  applyFilters);
        statusFilter.addEventListener('change', applyFilters);
        dateFilter  .addEventListener('input',  applyFilters);
    }());
    </script>
     <script src="../js/notifications.js"></script>
</body>
</html>