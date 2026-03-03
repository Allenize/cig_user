<?php
/**
 * view_document.php
 * Renders a document stored as a BLOB in the DB.
 *  - PDF  → native <iframe>
 *  - DOCX → docx-preview.js (client-side render)
 *  - XLSX → SheetJS in-browser preview
 *  - Others → download prompt
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "cig_system");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$submissionId = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
if (!$submissionId) {
    die("Invalid submission ID");
}

$stmt = $conn->prepare(
    "SELECT s.submission_id, s.file_name, s.submitted_by, s.title, s.file_content,
            s.status, s.submitted_at, u.full_name
     FROM   submissions s
     JOIN   users u ON s.submitted_by = u.user_id
     WHERE  s.submission_id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $submissionId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$submission) {
    die("Document not found");
}

if ((int)$submission['submitted_by'] !== (int)$_SESSION['user_id']) {
    die("Access denied");
}

$ext      = strtolower(pathinfo($submission['file_name'], PATHINFO_EXTENSION));
$title    = htmlspecialchars($submission['title']);
$fileName = htmlspecialchars($submission['file_name']);
$hasFile  = !empty($submission['file_content']);

// Map extension → icon + colour
$fileIcons = [
    'pdf'  => ['icon' => 'fa-file-pdf',   'color' => '#e74c3c'],
    'docx' => ['icon' => 'fa-file-word',  'color' => '#2980b9'],
    'doc'  => ['icon' => 'fa-file-word',  'color' => '#2980b9'],
    'xlsx' => ['icon' => 'fa-file-excel', 'color' => '#27ae60'],
    'xls'  => ['icon' => 'fa-file-excel', 'color' => '#27ae60'],
];
$iconInfo = $fileIcons[$ext] ?? ['icon' => 'fa-file-alt', 'color' => '#7f8c8d'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View: <?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Top bar ── */
        .doc-topbar {
            background: #1a3c2f;
            color: #fff;
            padding: .75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.25);
            flex-shrink: 0;
            z-index: 10;
        }
        .doc-topbar .btn-back {
            background: rgba(255,255,255,.15);
            border: none;
            color: #fff;
            padding: .45rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: .9rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            transition: background .2s;
            text-decoration: none;
        }
        .doc-topbar .btn-back:hover { background: rgba(255,255,255,.28); }
        .doc-topbar .doc-title {
            flex: 1;
            font-size: 1.05rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .6rem;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .doc-topbar .doc-title i { color: <?php echo $iconInfo['color']; ?>; font-size: 1.2rem; }
        .doc-topbar .btn-download {
            background: #27ae60;
            border: none;
            color: #fff;
            padding: .45rem 1.1rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: .9rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            text-decoration: none;
            transition: background .2s;
        }
        .doc-topbar .btn-download:hover { background: #219a52; }

        /* ── Meta strip ── */
        .doc-meta {
            background: #fff;
            border-bottom: 1px solid #dde;
            padding: .45rem 1.5rem;
            font-size: .82rem;
            color: #555;
            display: flex;
            gap: 1.8rem;
            flex-shrink: 0;
        }
        .doc-meta span { display: flex; align-items: center; gap: .35rem; }
        .status-badge {
            display: inline-block;
            padding: .15rem .65rem;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-badge.pending  { background:#fff3cd; color:#856404; }
        .status-badge.approved { background:#d1f0e0; color:#155724; }
        .status-badge.rejected { background:#f8d7da; color:#721c24; }

        /* ── Viewer ── */
        .viewer-wrap {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        #viewer-frame, #docx-container, #xlsx-container, #unsupported-msg {
            position: absolute;
            inset: 0;
        }
        #viewer-frame {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* DOCX */
        #docx-container {
            overflow: auto;
            background: #e8e8e8;
            padding: 24px;
        }
        #docx-container .docx-wrapper {
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.15);
            margin: 0 auto;
            max-width: 900px;
            padding: 2rem;
            line-height: 1.6;
            font-size: 14px;
            color: #333;
            font-family: 'Segoe UI', 'Calibri', Arial, sans-serif;
        }
        #docx-container .docx-wrapper * { font-family: inherit; }
        #docx-container .docx-wrapper p { margin-bottom: 0.5em; }
        #docx-container .docx-wrapper h1, #docx-container .docx-wrapper h2,
        #docx-container .docx-wrapper h3, #docx-container .docx-wrapper h4,
        #docx-container .docx-wrapper h5, #docx-container .docx-wrapper h6 {
            margin: 0.8em 0 0.4em 0;
            font-weight: 600;
        }

        /* XLSX */
        #xlsx-container {
            overflow: auto;
            background: #fff;
            padding: 1rem;
        }
        #xlsx-container table {
            border-collapse: collapse;
            font-size: .82rem;
            min-width: 100%;
        }
        #xlsx-container th, #xlsx-container td {
            border: 1px solid #ccc;
            padding: .3rem .6rem;
            white-space: nowrap;
        }
        #xlsx-container th {
            background: #2d6a4f;
            color: #fff;
            position: sticky;
            top: 0;
        }
        #xlsx-container tr:nth-child(even) td { background: #f9fbf9; }

        /* Unsupported */
        #unsupported-msg {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            color: #777;
            text-align: center;
        }
        #unsupported-msg i { font-size: 4rem; color: #ccc; }
        #unsupported-msg a {
            background: #2d6a4f;
            color: #fff;
            padding: .6rem 1.4rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: .95rem;
        }

        /* Loading overlay */
        #loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,.85);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .8rem;
            z-index: 5;
            font-size: 1rem;
            color: #555;
        }
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid #dde;
            border-top-color: #2d6a4f;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<!-- ── Top bar ── -->
<div class="doc-topbar">
    <a href="javascript:history.back()" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <div class="doc-title">
        <i class="fas <?php echo $iconInfo['icon']; ?>"></i>
        <?php echo $title; ?>
    </div>
    <?php if ($hasFile): ?>
    <a href="file_preview.php?submission_id=<?php echo $submissionId; ?>&download=1"
       class="btn-download">
        <i class="fas fa-download"></i> Download
    </a>
    <?php endif; ?>
</div>

<!-- ── Meta strip ── -->
<div class="doc-meta">
    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($submission['full_name']); ?></span>
    <span><i class="fas fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($submission['submitted_at'])); ?></span>
    <span><i class="fas fa-file"></i> <?php echo strtoupper($ext); ?></span>
    <span>
        Status:&nbsp;
        <span class="status-badge <?php echo strtolower($submission['status']); ?>">
            <?php echo ucfirst($submission['status']); ?>
        </span>
    </span>
</div>

<!-- ── Viewer area ── -->
<div class="viewer-wrap">

    <?php if (!$hasFile): ?>
    <div id="unsupported-msg" style="display:flex">
        <i class="fas fa-exclamation-circle"></i>
        <p>No file content found for this submission.</p>
    </div>

    <?php elseif ($ext === 'pdf'): ?>
    <!-- PDF: direct iframe -->
    <iframe id="viewer-frame"
            src="file_preview.php?submission_id=<?php echo $submissionId; ?>">
    </iframe>

    <?php elseif (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
    <!-- Image: direct iframe -->
    <iframe id="viewer-frame"
            src="file_preview.php?submission_id=<?php echo $submissionId; ?>">
    </iframe>

    <?php elseif ($ext === 'docx'): ?>
    <!-- DOCX: rendered by docx-preview.js -->
    <div id="loading-overlay">
        <div class="spinner"></div>
        <span>Rendering document…</span>
    </div>
    <div id="docx-container"></div>

    <?php elseif (in_array($ext, ['xlsx','xls'])): ?>
    <!-- XLSX: rendered by SheetJS -->
    <div id="loading-overlay">
        <div class="spinner"></div>
        <span>Loading spreadsheet…</span>
    </div>
    <div id="xlsx-container"></div>

    <?php else: ?>
    <!-- Unsupported: offer download -->
    <div id="unsupported-msg" style="display:flex">
        <i class="fas fa-file-alt"></i>
        <p>Preview not available for <strong>.<?php echo strtoupper($ext); ?></strong> files.</p>
        <a href="file_preview.php?submission_id=<?php echo $submissionId; ?>&download=1">
            <i class="fas fa-download"></i>&nbsp; Download File
        </a>
    </div>
    <?php endif; ?>

</div><!-- /.viewer-wrap -->


<!-- ══════════════════════════════════════════════════════════ -->
<!--  JS — only load the libraries we actually need             -->
<!-- ══════════════════════════════════════════════════════════ -->

<?php if ($ext === 'docx' && $hasFile): ?>
<script src="https://cdn.jsdelivr.net/npm/docx-preview@0.3.0/dist/docx-preview.min.js"></script>
<script>
(function () {
    const container = document.getElementById('docx-container');
    const overlay   = document.getElementById('loading-overlay');

    fetch('file_preview.php?submission_id=<?php echo $submissionId; ?>')
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.arrayBuffer();
        })
        .then(function(buf) {
            return docx.renderAsync(buf, container, null, {
                className:       'docx-wrapper',
                inWrapper:       true,
                ignoreWidth:     false,
                ignoreHeight:    false,
                renderHeaders:   true,
                renderFooters:   true,
                renderFootnotes: true,
                renderEndnotes:  true,
            });
        })
        .then(function() {
            overlay.style.display = 'none';
        })
        .catch(function(err) {
            overlay.style.display = 'none';
            container.innerHTML =
                '<div style="padding:2rem;color:#c0392b;">' +
                '<i class="fas fa-exclamation-triangle"></i> ' +
                'Failed to render document: ' + err.message +
                '<br><br><a href="file_preview.php?submission_id=<?php echo $submissionId; ?>&download=1">' +
                'Download instead</a></div>';
        });
}());
</script>
<?php endif; ?>

<?php if (in_array($ext, ['xlsx','xls']) && $hasFile): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
(function () {
    const container = document.getElementById('xlsx-container');
    const overlay   = document.getElementById('loading-overlay');

    fetch('file_preview.php?submission_id=<?php echo $submissionId; ?>')
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.arrayBuffer();
        })
        .then(function(buf) {
            const wb = XLSX.read(new Uint8Array(buf), { type: 'array' });

            // Build a tab bar if multiple sheets
            const sheetNames = wb.SheetNames;
            let html = '';

            if (sheetNames.length > 1) {
                html += '<div style="display:flex;gap:.5rem;padding:.5rem;background:#f4f4f4;border-bottom:1px solid #ccc;">';
                sheetNames.forEach(function(name, i) {
                    html += '<button onclick="showSheet(' + i + ')" ' +
                            'id="tab_' + i + '" ' +
                            'style="padding:.3rem .8rem;border:1px solid #ccc;border-radius:4px;' +
                            'cursor:pointer;background:' + (i===0?'#2d6a4f':'#fff') + ';' +
                            'color:' + (i===0?'#fff':'#333') + '">' +
                            XLSX.utils.escapeXML ? name : name + '</button>';
                });
                html += '</div>';
            }

            html += '<div id="sheet-display"></div>';
            container.innerHTML = html;
            overlay.style.display = 'none';

            // Store workbook globally for tab switching
            window._wb = wb;
            showSheet(0);
        })
        .catch(function(err) {
            overlay.style.display = 'none';
            container.innerHTML =
                '<div style="padding:2rem;color:#c0392b;">Failed to load spreadsheet: ' + err.message + '</div>';
        });

    window.showSheet = function(idx) {
        const wb = window._wb;
        // Highlight active tab
        wb.SheetNames.forEach(function(_, i) {
            const t = document.getElementById('tab_' + i);
            if (t) {
                t.style.background = i === idx ? '#2d6a4f' : '#fff';
                t.style.color      = i === idx ? '#fff'    : '#333';
            }
        });
        const ws  = wb.Sheets[wb.SheetNames[idx]];
        const tbl = XLSX.utils.sheet_to_html(ws, { editable: false });
        document.getElementById('sheet-display').innerHTML = tbl;
    };
}());
</script>
<?php endif; ?>

</body>
</html>