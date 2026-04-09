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
               s.control_number,
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
    $_lq  = mysqli_prepare($conn, "SELECT logo_path, description, org_name, full_name FROM users WHERE user_id = ? LIMIT 1");
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
$_lr = $_lr ?? [];
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


    /* ── Edit / resubmit button ── */
    .btn-edit {
        background: #fff7ed !important;
        color: #c2410c !important;
        border: 1px solid #fed7aa !important;
    }
    .btn-edit:hover {
        background: #ffedd5 !important;
        border-color: #fb923c !important;
    }

    /* ── Edit & Resubmit Modal ── */
    #editResubmitModal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(0,0,0,.55);
        align-items: center;
        justify-content: center;
    }
    #editResubmitModal.open { display: flex; }
    .edit-modal-box {
        background: #fff;
        border-radius: 18px;
        width: 94vw;
        max-width: 760px;
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 12px 48px rgba(0,0,0,.35);
        animation: editModalIn .22s ease;
    }
    @keyframes editModalIn {
        from { transform: translateY(24px) scale(.97); opacity: 0; }
        to   { transform: none; opacity: 1; }
    }
    .edit-modal-header {
        background: linear-gradient(135deg, #b45309 0%, #c2410c 100%);
        color: #fff;
        padding: 16px 22px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }
    .edit-modal-header-icon {
        width: 38px; height: 38px;
        background: rgba(255,255,255,.18);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .edit-modal-header-text { flex: 1; min-width: 0; }
    .edit-modal-header-text h3 { margin: 0; font-size: 1rem; font-weight: 700; }
    .edit-modal-header-text p  { margin: 2px 0 0; font-size: .75rem; opacity: .85; }
    .edit-modal-close {
        background: rgba(255,255,255,.15);
        border: none; color: #fff;
        width: 32px; height: 32px;
        border-radius: 50%; cursor: pointer;
        font-size: 1.15rem; display: flex;
        align-items: center; justify-content: center;
        flex-shrink: 0; transition: background .15s;
    }
    .edit-modal-close:hover { background: rgba(255,255,255,.3); }

    /* Rejection notice strip */
    .edit-rejection-strip {
        background: #fff7ed;
        border-bottom: 1.5px solid #fed7aa;
        padding: 10px 22px;
        display: flex;
        gap: 10px;
        align-items: flex-start;
        flex-shrink: 0;
    }
    .edit-rejection-strip .rej-icon { color: #ea580c; font-size: 1rem; margin-top: 1px; flex-shrink: 0; }
    .edit-rejection-strip .rej-body { font-size: .8rem; color: #7c2d12; line-height: 1.5; }
    .edit-rejection-strip .rej-body strong { display: block; font-size: .82rem; margin-bottom: 2px; color: #c2410c; }

    /* Body */
    .edit-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px 22px;
        background: #f9fafb;
    }

    /* Field card */
    .edit-field-card {
        background: #fff;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        margin-bottom: 12px;
        overflow: hidden;
        transition: border-color .15s;
    }
    .edit-field-card:focus-within { border-color: #2d6a4f; }
    .edit-field-card.changed { border-color: #fb923c; }
    .edit-field-label {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #6b7280;
        padding: 8px 12px 4px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .edit-field-label .changed-pill {
        display: none;
        font-size: .65rem;
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
        border-radius: 20px;
        padding: 1px 7px;
        font-weight: 600;
        text-transform: none;
        letter-spacing: 0;
    }
    .edit-field-card.changed .changed-pill { display: inline-block; }
    .edit-field-input {
        width: 100%;
        border: none;
        outline: none;
        font-size: .88rem;
        font-family: inherit;
        color: #1f2937;
        padding: 4px 12px 10px;
        background: transparent;
        resize: vertical;
        box-sizing: border-box;
        line-height: 1.5;
    }
    .edit-field-input::placeholder { color: #d1d5db; }

    /* File re-upload zone */
    .edit-file-zone {
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        padding: 18px;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s, background .15s;
        margin-bottom: 12px;
        background: #fff;
    }
    .edit-file-zone:hover, .edit-file-zone.drag-over { border-color: #2d6a4f; background: #f0faf4; }
    .edit-file-zone.has-file { border-color: #2d6a4f; background: #f0faf4; }
    .edit-file-zone .zone-icon { font-size: 1.4rem; color: #9ca3af; margin-bottom: 6px; }
    .edit-file-zone.has-file .zone-icon { color: #2d6a4f; }
    .edit-file-zone .zone-text { font-size: .82rem; color: #6b7280; }
    .edit-file-zone .zone-file { font-size: .8rem; color: #2d6a4f; font-weight: 600; margin-top: 4px; }

    /* Section heading inside edit modal */
    .edit-section-heading {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #2d6a4f;
        margin: 16px 0 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .edit-section-heading::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #d1fae5;
    }

    /* Footer */
    .edit-modal-footer {
        padding: 14px 22px;
        background: #fff;
        border-top: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        gap: 10px;
    }
    .edit-change-count {
        font-size: .78rem;
        color: #6b7280;
        min-width: 0;
    }
    .edit-change-count span { font-weight: 700; color: #c2410c; }
    .edit-footer-btns { display: flex; gap: 8px; flex-shrink: 0; }
    .btn-edit-cancel {
        padding: 8px 18px;
        border-radius: 8px;
        border: 1.5px solid #e5e7eb;
        background: #fff;
        color: #374151;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: background .15s;
    }
    .btn-edit-cancel:hover { background: #f3f4f6; }
    .btn-edit-submit {
        padding: 8px 22px;
        border-radius: 8px;
        border: none;
        background: #2d6a4f;
        color: #fff;
        font-size: .85rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        display: flex;
        align-items: center;
        gap: 7px;
        transition: background .15s;
    }
    .btn-edit-submit:hover { background: #1e3a2f; }
    .btn-edit-submit:disabled { background: #9ca3af; cursor: not-allowed; }
    /* ── Inline Project Proposal Wizard ── */
    #inlinePWizard {
        display: none;
        flex-direction: column;
        gap: 0;
        margin-top: 4px;
    }
    #inlinePWizard.pw-active { display: flex; }

    /* Step strip */
    #ipw-steps {
        display: flex;
        align-items: center;
        background: #f4faf7;
        border: 1px solid #d1e7dc;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 14px;
        gap: 0;
        overflow-x: auto;
        flex-shrink: 0;
    }
    .ipw-step { display:flex; align-items:center; gap:.35rem; flex-shrink:0; }
    .ipw-step-dot {
        width:24px; height:24px; border-radius:50%;
        background:#e2ece7; color:#6b9080;
        font-size:.68rem; font-weight:700;
        display:flex; align-items:center; justify-content:center;
        border:2px solid #c8ddd6; transition:all .2s; flex-shrink:0;
    }
    .ipw-step.active .ipw-step-dot { background:#2d6a4f; color:#fff; border-color:#2d6a4f; box-shadow:0 0 0 3px rgba(45,106,79,.15); }
    .ipw-step.done   .ipw-step-dot { background:#16a34a; color:#fff; border-color:#16a34a; }
    .ipw-step-label { font-size:.68rem; font-weight:600; color:#6b9080; white-space:nowrap; }
    .ipw-step.active .ipw-step-label { color:#1a3c2f; }
    .ipw-step.done   .ipw-step-label { color:#16a34a; }
    .ipw-step-sep { flex:1; min-width:10px; max-width:22px; height:2px; background:#d1e7dc; margin:0 .25rem; }
    .ipw-step-sep.done { background:#16a34a; }

    /* Step body */
    #ipw-body { flex:1; }
    #ipw-body .pw-section-title {
        font-size:.75rem; font-weight:800; text-transform:uppercase;
        letter-spacing:.06em; color:#2d6a4f;
        margin-bottom:.9rem; padding-bottom:.35rem;
        border-bottom:2px solid #e2ece7;
    }
    #ipw-body .form-group { margin-bottom:.9rem; }
    #ipw-body .form-group label {
        display:block; font-size:.82rem; font-weight:600;
        color:#1e3a2e; margin-bottom:.3rem;
        text-transform:none; letter-spacing:0;
    }
    #ipw-body .form-group label span { color:#dc2626; }
    #ipw-body input[type=text],
    #ipw-body input[type=date],
    #ipw-body input[type=time],
    #ipw-body textarea,
    #ipw-body select {
        width:100%; padding:.6rem .85rem;
        border:1px solid #cbd5e0; border-radius:10px;
        font-size:.88rem; font-family:inherit;
        transition:border .2s; box-sizing:border-box; background:#fff;
    }
    #ipw-body input:focus,
    #ipw-body textarea:focus { border-color:#2d6a4f; outline:none; box-shadow:0 0 0 3px rgba(45,106,79,.1); }
    #ipw-body textarea { resize:vertical; min-height:68px; }
    #ipw-body .checkbox-group { display:flex; flex-wrap:wrap; gap:.4rem; }
    #ipw-body .checkbox-option {
        display:flex; align-items:center; gap:.35rem;
        padding:.38rem .75rem; border:1px solid #d1e7dc;
        border-radius:8px; cursor:pointer; font-size:.83rem;
        font-weight:500; color:#1e3a2e; transition:all .15s;
    }
    #ipw-body .checkbox-option:hover { background:#f0faf5; border-color:#2d6a4f; }
    #ipw-body .checkbox-option input { width:13px; height:13px; accent-color:#2d6a4f; margin:0; }
    .ipw-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:.7rem; }

    /* Nav bar inside wizard */
    #ipw-nav {
        display:flex; align-items:center; justify-content:space-between;
        margin-top:12px; padding-top:12px;
        border-top:1px solid #e2ece7;
    }
    #ipw-progress-wrap { display:flex; flex-direction:column; gap:4px; flex:1; margin-right:1rem; }
    #ipw-progress-bar-bg { background:#e2ece7; border-radius:99px; height:6px; width:100%; overflow:hidden; }
    #ipw-progress-bar-fill { height:100%; background:#2d6a4f; border-radius:99px; transition:width 0.35s cubic-bezier(0.16,1,0.3,1); width:16.67%; }
    #ipw-progress { font-size:.75rem; color:#6b9080; font-weight:600; }
    .ipw-nav-btns { display:flex; gap:.5rem; }
    #ipw-back-btn {
        background:#fff; border:1px solid #cbd5e0; color:#374151;
        padding:.48rem 1.1rem; border-radius:40px; font-weight:600;
        font-size:.83rem; cursor:pointer; font-family:inherit; transition:background .15s;
    }
    #ipw-back-btn:hover { background:#f0f4f2; }
    #ipw-next-btn {
        background:#2d6a4f; border:none; color:#fff;
        padding:.48rem 1.3rem; border-radius:40px; font-weight:700;
        font-size:.83rem; cursor:pointer; font-family:inherit;
        display:flex; align-items:center; gap:.4rem; transition:background .15s;
    }
    #ipw-next-btn:hover { background:#1e4f3a; }
    #ipw-next-btn:disabled { background:#9ca3af; cursor:not-allowed; }
    /* ── Project Proposal expanded modal (2× size) ── */
    /* Smooth resize when wizard opens/closes */
    .upload-modal-content {
        transition: max-width 0.3s cubic-bezier(0.16,1,0.3,1),
                    width 0.3s cubic-bezier(0.16,1,0.3,1),
                    max-height 0.3s cubic-bezier(0.16,1,0.3,1),
                    height 0.3s cubic-bezier(0.16,1,0.3,1);
    }
    .upload-modal-content.pw-expanded {
        max-width: 1600px !important;
        width: 84vw !important;
        max-height: 84vh !important;
        height: 84vh !important;
    }
    /* Make sidebar narrower so fields get the extra space */
    .upload-modal-content.pw-expanded .modal-sidebar {
        width: 180px;
        padding: 1.4rem 1.1rem 1.2rem;
        flex-shrink: 0;
    }
    .upload-modal-content.pw-expanded .modal-sidebar-title { font-size: 1rem; }
    .upload-modal-content.pw-expanded .modal-sidebar-sub   { font-size: 0.7rem; }
    .upload-modal-content.pw-expanded .modal-sidebar-footer { font-size: 0.63rem; }
    /* Body takes full remaining height and shows more fields */
    .upload-modal-content.pw-expanded .modal-main-body {
        max-height: none;
        flex: 1;
        padding: 1.2rem 1.8rem;
    }
    /* Inside the expanded modal make the inline wizard 2-column on wide screens */
    .upload-modal-content.pw-expanded #ipw-body .form-group,
    .upload-modal-content.pw-expanded #templateFieldsContainer .form-group {
        margin-bottom: 0.85rem;
    }
    .upload-modal-content.pw-expanded #ipw-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 1.4rem;
        align-items: start;
    }
    /* Section title spans both columns */
    .upload-modal-content.pw-expanded #ipw-body .pw-section-title {
        grid-column: 1 / -1;
    }
    /* Textarea and checkbox groups also span both columns */
    .upload-modal-content.pw-expanded #ipw-body .form-group:has(textarea),
    .upload-modal-content.pw-expanded #ipw-body .form-group:has(.checkbox-group),
    .upload-modal-content.pw-expanded #ipw-body .form-group:has(.ipw-row-2) {
        grid-column: 1 / -1;
    }
    /* Step indicator stays single row */
    .upload-modal-content.pw-expanded #ipw-steps {
        padding: 8px 12px;
    }
    .upload-modal-content.pw-expanded .ipw-step-label { font-size: .7rem; }
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
                        data-submission-data="<?= $safeSubData ?>"
                        data-submission-id="<?= $doc['submission_id'] ?>"
                        data-submitted-at="<?= strtotime($doc['submitted_at']) ?>"
                        data-title-raw="<?= htmlspecialchars($doc['title'], ENT_QUOTES) ?>"
                        data-description-raw="<?= htmlspecialchars($doc['description'] ?? '', ENT_QUOTES) ?>"
                        data-remarks="<?= htmlspecialchars($doc['admin_remarks'], ENT_QUOTES) ?>"
                        data-control-number="<?= htmlspecialchars($doc['control_number'] ?? '', ENT_QUOTES) ?>">

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
                            <div class="actions-cell-inner">
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
                            <?php if ($doc['status'] === 'rejected'): ?>
                            <button class="btn-action btn-edit"
                                    title="Edit &amp; Resubmit"
                                    onclick="openEditResubmit(this.closest('tr'))">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            </div>
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

                        <!-- Step 1: Document Title — must fill first -->
                        <div class="form-group tpl-step-group" id="titleStepGroup">
                            <label for="templateTitle">
                                <span class="tpl-step-badge">1</span>
                                Document Title <span>*</span>
                            </label>
                            <input type="text" id="templateTitle" name="title"
                                placeholder="Give your document a title before choosing a templateâ¦" required
                                autocomplete="off">
                        </div>

                        <!-- Step 2: Template select — locked until title entered -->
                        <div class="form-group tpl-step-group select-locked" id="templateSelectGroup">
                            <label>
                                <span class="tpl-step-badge tpl-step-badge--locked">2</span>
                                Select Template <span>*</span>
                            </label>
                            <select id="templateSelect" name="template_id" required disabled onchange="loadTemplateFields()">
                                <option value="">Choose a Template</option>
                                <option value="meeting_minutes">Meeting Minutes</option>
                                <option value="event_proposal">Event Proposal</option>
                                <option value="financial_report">Financial Report</option>
                                <option value="incident_report">Incident Report</option>
                                <option value="membership_form">Membership Form</option>
                                <option value="project_proposal">Project Proposal</option>
                            </select>
                            <div id="templateSelectHint" class="tpl-lock-hint">
                                <i class="fas fa-lock"></i>
                                Enter a document title above to unlock template selection
                            </div>
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

                        <!-- ── Inline Project Proposal Wizard ── -->
                        <div id="inlinePWizard">
                            <div id="ipw-steps"></div>
                            <div id="ipw-body"></div>
                            <div id="ipw-nav">
                                <div id="ipw-progress-wrap">
                                    <span id="ipw-progress">Step 1 of 6</span>
                                    <div id="ipw-progress-bar-bg">
                                        <div id="ipw-progress-bar-fill"></div>
                                    </div>
                                </div>
                                <div class="ipw-nav-btns">
                                    <button type="button" id="ipw-back-btn" onclick="ipwBack()"><i class="fas fa-arrow-left"></i> Back</button>
                                    <button type="button" id="ipw-next-btn" onclick="ipwNext()">Next <i class="fas fa-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            </div><!-- /.modal-main-body -->

            <div class="modal-main-footer" style="position:relative;">
                <div id="autosave-indicator" style="font-size:0.75rem;color:#52b788;margin-bottom:0.4rem;opacity:0;transition:opacity 0.4s;min-height:1rem;"></div>
                <div class="form-actions" id="formActions">
                    <button type="submit" class="btn-submit" id="submitBtn"><i class="fas fa-paper-plane"></i> Upload Document</button>
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
/* ── Logged-in user name for autofill ─────────────────────────────────────── */
const CURRENT_USER_NAME = <?= json_encode(trim($_lr['full_name'] ?? $_SESSION['full_name'] ?? '')) ?>;
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
function buildGenericBody(data, ctrlNum) {
    const labels = data.field_labels || {}, fields = data.fields || {};
    let html = '';

    // Control number — shown above all fields, right-aligned
    if (ctrlNum) {
        html += `<div style="text-align:right;margin-bottom:12px;">
            <span style="display:inline-block;background:#f0faf4;border:1.5px solid #2d6a4f;border-radius:6px;padding:3px 12px;font-size:.78rem;font-weight:700;color:#1a4731;letter-spacing:.04em;">
                <span style="color:#5a9070;font-weight:600;font-size:.72rem;margin-right:4px;">Control No.:</span>${esc(ctrlNum)}
            </span>
        </div>`;
    }

    Object.entries(labels).forEach(([key, label]) => {
        const isBlock = TPL_TEXTAREA_KEYS.has(key);
        html += `<div style="margin-bottom:${isBlock?'18px':'10px'}"><div style="font-size:.7rem;font-weight:700;color:#2d6a4f;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">${esc(label)}</div><div style="font-size:.92rem;color:#1e3a3a;line-height:1.55;${isBlock?'background:#f9fbf9;padding:8px 12px;border-radius:8px;':''}">${renderValue(key, fields[key]||'')}</div></div>`;
    });
    return html;
}
function buildProjectProposalBody(data, ctrlNum) {
    const labels = data.field_labels || {}, fields = data.fields || {};
    const tableKeys = new Set(['organization','project_title','project_type','project_involvement','project_location','proposed_start_date','proposed_end_date','number_participants','budget_source','budget_partner','budget_total']);
    let html = '';

    // Control number — shown above date, right-aligned
    if (ctrlNum) {
        html += `<div style="text-align:right;margin-bottom:6px;">
            <span style="display:inline-block;background:#f0faf4;border:1.5px solid #2d6a4f;border-radius:6px;padding:3px 12px;font-size:.78rem;font-weight:700;color:#1a4731;letter-spacing:.04em;">
                <span style="color:#5a9070;font-weight:600;font-size:.72rem;margin-right:4px;">Control No.:</span>${esc(ctrlNum)}
            </span>
        </div>`;
    }

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

function renderTplPreviewBody(data, title, controlNumber) {
    const orgName    = data.organization_name    || '';
    const orgTagline = data.organization_tagline || '';
    const collabLogo = data.collaborated_logo || data.collaborated_logo_value || '';
    const ctrlNum    = controlNumber || data.control_number || '';
    const bodyContent = (data.template_id === 'project_proposal') ? buildProjectProposalBody(data, ctrlNum) : buildGenericBody(data, ctrlNum);

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
    const controlNumber = row ? (row.getAttribute('data-control-number') || '') : '';
    let data = null;
    if (raw) { try { data = JSON.parse(raw); } catch(e) {} }
    if (!data) { alert('Preview data not available for this submission.'); return; }
    document.getElementById('tplPreviewTitle').textContent = title;
    document.getElementById('tplPreviewSubtitle').textContent = (data.template_name||'') + ' — Template Document';
    document.getElementById('tplPreviewBody').innerHTML = renderTplPreviewBody(data, title, controlNumber);
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

<!-- ═══════════════════════════════════════════════════════════════════════
     EDIT & RESUBMIT MODAL
     ═══════════════════════════════════════════════════════════════════════ -->
<div id="editResubmitModal">
    <div class="edit-modal-box">

        <!-- Header -->
        <div class="edit-modal-header">
            <div class="edit-modal-header-icon"><i class="fas fa-edit"></i></div>
            <div class="edit-modal-header-text">
                <h3 id="editModalDocTitle">Edit &amp; Resubmit</h3>
                <p id="editModalDocType">Review the admin's feedback and update the fields below</p>
            </div>
            <button class="edit-modal-close" onclick="closeEditModal()" title="Close"><i class="fas fa-times"></i></button>
        </div>

        <!-- Rejection remarks strip -->
        <div class="edit-rejection-strip" id="editRejectionStrip">
            <i class="fas fa-comment-alt rej-icon"></i>
            <div class="rej-body">
                <strong><i class="fas fa-exclamation-circle"></i>&nbsp; Admin Feedback</strong>
                <span id="editRemarksText"></span>
            </div>
        </div>

        <!-- Scrollable body — fields injected here by JS -->
        <div class="edit-modal-body" id="editModalBody"></div>

        <!-- Footer -->
        <div class="edit-modal-footer">
            <div class="edit-change-count" id="editChangeCount"></div>
            <div class="edit-footer-btns">
                <button class="btn-edit-cancel" onclick="closeEditModal()">Cancel</button>
                <button class="btn-edit-submit" id="editSubmitBtn" onclick="submitEditResubmit()">
                    <i class="fas fa-paper-plane"></i> Resubmit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════════════════════════
   EDIT & RESUBMIT — full cached-field edit modal
   ═══════════════════════════════════════════════════════════════════════════ */
(function() {

    // ── State ──────────────────────────────────────────────────────────────
    let _editState = {
        submissionId : null,
        isTemplate   : false,
        ext          : '',
        origValues   : {},   // field_id → original value
        templateId   : null,
        templateName : '',
    };

    // ── Open ───────────────────────────────────────────────────────────────
    window.openEditResubmit = function(row) {
        if (!row) return;

        const isTemplate = row.getAttribute('data-is-template') === '1';
        const subDataRaw = row.getAttribute('data-submission-data') || '';
        const titleRaw   = row.getAttribute('data-title-raw') || '';
        const descRaw    = row.getAttribute('data-description-raw') || '';
        const remarksRaw = row.getAttribute('data-remarks') || 'No specific remarks provided.';
        const subId      = row.getAttribute('data-submission-id') || '';
        // derive ext from the view button onclick attr if possible
        const viewBtn    = row.querySelector('.btn-view');
        const extMatch   = viewBtn ? (viewBtn.getAttribute('onclick') || '').match(/'([a-z]+)'/) : null;
        const ext        = extMatch ? extMatch[1] : 'docx';

        _editState.submissionId = subId;
        _editState.isTemplate   = isTemplate;
        _editState.ext          = ext;
        _editState.origValues   = {};

        // Show remarks
        document.getElementById('editRemarksText').textContent = remarksRaw;

        if (isTemplate && subDataRaw) {
            let data = null;
            try { data = JSON.parse(subDataRaw); } catch(e) {}
            if (!data) { alert('Could not load cached data for this submission.'); return; }

            _editState.templateId   = data.template_id || '';
            _editState.templateName = data.template_name || 'Template';

            document.getElementById('editModalDocTitle').textContent = titleRaw || 'Edit & Resubmit';
            document.getElementById('editModalDocType').textContent  = data.template_name + ' — Template Document';

            buildTemplateEditBody(data, titleRaw);
        } else {
            _editState.templateId = null;
            document.getElementById('editModalDocTitle').textContent = titleRaw || 'Edit & Resubmit';
            document.getElementById('editModalDocType').textContent  = 'Regular Document Upload';
            buildRegularEditBody(titleRaw, descRaw);
        }

        updateChangeCount();
        document.getElementById('editResubmitModal').classList.add('open');
    };

    // ── Close ──────────────────────────────────────────────────────────────
    window.closeEditModal = function() {
        document.getElementById('editResubmitModal').classList.remove('open');
        document.getElementById('editModalBody').innerHTML = '';
        _editState = { submissionId:null, isTemplate:false, ext:'', origValues:{}, templateId:null, templateName:'' };
    };

    // Close on backdrop click
    document.getElementById('editResubmitModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    // ── Build body for TEMPLATE submission ─────────────────────────────────
    function buildTemplateEditBody(data, titleRaw) {
        const body = document.getElementById('editModalBody');
        const fields = data.fields || {};
        const labels = data.field_labels || {};
        let html = '';

        // Title card
        html += sectionHeading('Document Info');
        html += fieldCard('__title__', 'Document Title', titleRaw, 'text', true);

        // All template field cards
        html += sectionHeading('Template Fields');
        Object.keys(labels).forEach(function(key) {
            const label = labels[key] || key;
            const val   = fields[key] || '';
            const isTA  = isTextareaField(key);
            html += fieldCard(key, label, val, isTA ? 'textarea' : 'text', false);
        });

        body.innerHTML = html;

        // Store originals
        _editState.origValues['__title__'] = titleRaw;
        Object.keys(labels).forEach(function(key) {
            _editState.origValues[key] = fields[key] || '';
        });

        attachChangeListeners();
    }

    // ── Build body for REGULAR file submission ─────────────────────────────
    function buildRegularEditBody(titleRaw, descRaw) {
        const body = document.getElementById('editModalBody');
        const descClean = descRaw.replace(/\s*\|\s*Related Event:.*$/i, '').trim();
        const eventMatch = descRaw.match(/Related Event:\s*(.+)$/i);
        const eventVal   = eventMatch ? eventMatch[1].trim() : '';

        const eventOptions = ['', 'Outreach Program', 'Quarterly Meeting', 'Fundraising Gala', 'Team Building'];
        let eventHtml = '<select id="ef___event__" class="edit-field-input" style="padding-bottom:8px;">';
        eventOptions.forEach(function(o) {
            eventHtml += '<option value="' + esc(o) + '"' + (o === eventVal ? ' selected' : '') + '>' + (o || '— None —') + '</option>';
        });
        eventHtml += '</select>';

        let html = '';
        html += sectionHeading('Document Details');
        html += fieldCard('__title__', 'Document Title', titleRaw, 'text', true);
        html += fieldCard('__desc__',  'Description (Optional)', descClean, 'textarea', false);

        html += '<div class="edit-field-card" id="efc___event__">';
        html += '<div class="edit-field-label">Related Event <span class="changed-pill">Changed</span></div>';
        html += eventHtml + '</div>';

        html += sectionHeading('Replace File');
        html += `<label for="editFileInput" class="edit-file-zone" id="editFileZone">
            <div class="zone-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <div class="zone-text">Drop a new file here, or <strong>click to browse</strong></div>
            <div class="zone-text" style="font-size:.75rem;color:#9ca3af;margin-top:2px;">Leave empty to keep the current file &nbsp;·&nbsp; PDF, DOCX, XLSX · Max 50 MB</div>
            <div class="zone-file" id="editFileLabel"></div>
        </label>
        <input type="file" id="editFileInput" accept=".pdf,.docx,.xlsx" style="display:none;">`;

        body.innerHTML = html;

        _editState.origValues['__title__'] = titleRaw;
        _editState.origValues['__desc__']  = descClean;
        _editState.origValues['__event__'] = eventVal;

        // File drop zone wiring
        const zone  = document.getElementById('editFileZone');
        const input = document.getElementById('editFileInput');
        const label = document.getElementById('editFileLabel');
        if (zone && input) {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    label.textContent = '📄 ' + this.files[0].name;
                    zone.classList.add('has-file');
                }
                updateChangeCount();
            });
            zone.addEventListener('dragover',  function(e){ e.preventDefault(); zone.classList.add('drag-over'); });
            zone.addEventListener('dragleave', function()  { zone.classList.remove('drag-over'); });
            zone.addEventListener('drop', function(e) {
                e.preventDefault(); zone.classList.remove('drag-over');
                if (e.dataTransfer && e.dataTransfer.files[0]) {
                    input.files = e.dataTransfer.files;
                    label.textContent = '📄 ' + e.dataTransfer.files[0].name;
                    zone.classList.add('has-file');
                    updateChangeCount();
                }
            });
        }

        attachChangeListeners();

        // Wire event select change detection separately
        const evSel = document.getElementById('ef___event__');
        if (evSel) {
            evSel.addEventListener('change', function() {
                const card = document.getElementById('efc___event__');
                if (card) card.classList.toggle('changed', this.value !== _editState.origValues['__event__']);
                updateChangeCount();
            });
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    function sectionHeading(text) {
        return '<div class="edit-section-heading"><i class="fas fa-chevron-right" style="font-size:.6rem;"></i>' + esc(text) + '</div>';
    }

    function fieldCard(id, label, value, type, required) {
        const req  = required ? '<span style="color:#ef4444;margin-left:2px;">*</span>' : '';
        const tag  = type === 'textarea' ? 'textarea' : 'input';
        const rows = type === 'textarea' ? ' rows="4"' : '';
        const typeAttr = type === 'textarea' ? '' : ' type="text"';
        const val  = type === 'textarea' ? esc(value) : '';
        const valAttr = type === 'textarea' ? '' : ' value="' + esc(value) + '"';
        return `<div class="edit-field-card" id="efc_${id}">
            <div class="edit-field-label">${esc(label)}${req}<span class="changed-pill">Changed</span></div>
            <${tag} id="ef_${id}" class="edit-field-input"${typeAttr}${valAttr}${rows} placeholder="${esc(label)}">${val}${type==='textarea'?'</textarea>':''}
        </div>`;
    }

    function attachChangeListeners() {
        document.querySelectorAll('#editModalBody .edit-field-input').forEach(function(el) {
            el.addEventListener('input',  onFieldChange);
            el.addEventListener('change', onFieldChange);
        });
    }

    function onFieldChange(e) {
        const el   = e.target;
        const id   = el.id.replace(/^ef_/, '');
        const card = document.getElementById('efc_' + id);
        if (card) {
            const orig = _editState.origValues[id] !== undefined ? _editState.origValues[id] : '';
            card.classList.toggle('changed', el.value !== orig);
        }
        updateChangeCount();
    }

    function updateChangeCount() {
        let n = 0;
        document.querySelectorAll('#editModalBody .edit-field-card.changed').forEach(function() { n++; });

        // Also count file change
        const fileInput = document.getElementById('editFileInput');
        if (fileInput && fileInput.files && fileInput.files.length) n++;

        const el = document.getElementById('editChangeCount');
        if (el) {
            el.innerHTML = n === 0
                ? 'No changes yet'
                : '<span>' + n + '</span> field' + (n > 1 ? 's' : '') + ' changed';
        }
    }

    function isTextareaField(key) {
        const taKeys = new Set([
            'agenda','discussion','action_items','description','requirements',
            'expense_breakdown','remarks','incident_description','individuals_involved',
            'witnesses','action_taken','recommendations','opening_statement',
            'project_summary','project_goal','project_objectives','expected_outputs',
            'monitoring_details','evaluation_details','security_plan','closing_statement',
            'attendees','skills','availability',
            'sender_name','adviser_name','co_adviser_name',
            'additional_signer_1','additional_signer_2','endorsed_by'
        ]);
        return taKeys.has(key);
    }

    function esc(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Submit ─────────────────────────────────────────────────────────────
    window.submitEditResubmit = function() {
        const btn = document.getElementById('editSubmitBtn');
        const formData = new FormData();

        if (_editState.isTemplate) {
            // Collect all template field values from the edit modal
            const titleEl = document.getElementById('ef___title__');
            const title   = titleEl ? titleEl.value.trim() : '';
            if (!title) { alert('Document title is required.'); return; }

            formData.append('template_id', _editState.templateId);
            formData.append('title', title);

            // Collect every rendered template field
            document.querySelectorAll('#editModalBody .edit-field-input').forEach(function(el) {
                const id = el.id.replace(/^ef_/, '');
                if (id === '__title__') return;
                formData.append(id, el.value);
            });

            // Pull org name and tagline from hidden fields in upload modal (they don't change)
            const orgName    = document.getElementById('organizationName');
            const orgTagline = document.getElementById('organizationTagline');
            if (orgName)    formData.append('organization_name',    orgName.value);
            if (orgTagline) formData.append('organization_tagline', orgTagline.value);

        } else {
            // Regular file resubmit
            const titleEl = document.getElementById('ef___title__');
            const descEl  = document.getElementById('ef___desc__');
            const evEl    = document.getElementById('ef___event__');
            const fileEl  = document.getElementById('editFileInput');

            const title = titleEl ? titleEl.value.trim() : '';
            if (!title) { alert('Document title is required.'); return; }
            if (!fileEl || !fileEl.files || !fileEl.files.length) {
                alert('Please attach a file. The original file cannot be re-used — please re-upload it (even if unchanged).');
                return;
            }

            formData.append('title', title);
            if (descEl) formData.append('description', descEl.value);
            if (evEl)   formData.append('related_event', evEl.value);
            formData.append('file', fileEl.files[0]);
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';

        fetch('../php/upload_document.php', { method: 'POST', body: formData })
            .then(function(r) { return r.text(); })
            .then(function(text) {
                try { return JSON.parse(text); }
                catch(e) {
                    var preview = text.trim().substring(0, 300) || '(empty response)';
                    throw new Error('Server error: ' + preview);
                }
            })
            .then(function(data) {
                if (data.success) {
                    showEditToast('Resubmitted successfully! Refreshing…', true);
                    closeEditModal();
                    setTimeout(function() { location.reload(); }, 1400);
                } else {
                    showEditToast('Error: ' + (data.message || 'Submission failed.'), false);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Resubmit';
                }
            })
            .catch(function(err) {
                showEditToast('Network error: ' + err.message, false);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Resubmit';
            });
    };

    function showEditToast(msg, success) {
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:10px;font-size:.88rem;font-weight:600;color:#fff;background:' + (success ? '#2d6a4f' : '#e74c3c') + ';box-shadow:0 4px 20px rgba(0,0,0,.2);transition:opacity .3s;';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function() { t.style.opacity='0'; setTimeout(function(){ t.remove(); },300); }, 3500);
    }

}());
</script>
</body>
</html>