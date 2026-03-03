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

// Generate preview token for DOCX files (matches get_docx.php logic)
function docxToken(int $submissionId): string {
    $secret = 'cig_preview_2026';
    return sha1($submissionId . date('YmdH') . $secret);
}

$submissionsQuery = "
    SELECT s.submission_id, s.title, s.submitted_at, u.full_name, s.status,
           COALESCE(r.feedback, 'Awaiting review') AS admin_remarks,
           s.file_name,
           s.file_path,
           IF(
               (s.file_content IS NOT NULL AND LENGTH(s.file_content) > 0)
               OR (s.file_name IS NOT NULL AND s.file_name <> ''),
               1, 0
           ) AS has_file,
           LENGTH(s.file_content) AS file_size
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
    <!-- JSZip: required by docx-preview to parse DOCX ZIP format -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
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
                <table class="documents-table" id="documentsTable">
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
                        <tr data-title="<?php echo strtolower(htmlspecialchars($doc['title'])); ?>"
                            data-status="<?php echo strtolower($doc['status']); ?>"
                            data-date="<?php echo date('Y-m-d', strtotime($doc['submitted_at'])); ?>">

                            <!-- Document name + type badge + size -->
                            <td>
                                <div class="doc-name-cell">
                                    <i class="fas <?php echo $iClass; ?> doc-icon <?php echo $iColor; ?>"></i>
                                    <div class="doc-meta-text">
                                        <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                        <small>
                                            <?php if ($doc['file_name']): ?>
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
                            <td><?php echo htmlspecialchars($doc['full_name']); ?></td>

                            <!-- Status -->
                            <td>
                                <span class="status-badge <?php echo strtolower($doc['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $doc['status'])); ?>
                                </span>
                            </td>

                            <!-- Admin remarks -->
                            <td class="remarks-cell" title="<?php echo htmlspecialchars($doc['admin_remarks']); ?>">
                                <?php echo htmlspecialchars($doc['admin_remarks']); ?>
                            </td>

                            <!-- Actions -->
                            <td>
                                <?php if ($doc['has_file']): ?>
                                <div class="action-btns">
                                    <?php if ($ext === 'docx'): ?>
                                    <input type="hidden" id="docx-token-<?php echo $doc['submission_id']; ?>"
                                           value="<?php echo docxToken((int)$doc['submission_id']); ?>">
                                    <?php endif; ?>
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

    <script src="../js/script.js"></script>
    <script src="../js/document_tracking.js"></script>
    <script>
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
</body>
</html>