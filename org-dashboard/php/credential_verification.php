<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db_connection.php';

$userId = (int)$_SESSION['user_id'];

// Detect and clear the revocation flash flag set by auth_guard
$wasJustRevoked = !empty($_SESSION['just_revoked']);
unset($_SESSION['just_revoked']);

// Load org profile
$stmt = mysqli_prepare($conn, "SELECT org_name, org_code, contact_person, phone, description, credentials_verified FROM users WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Sync session with DB truth
$_SESSION['credentials_verified'] = !empty($user['credentials_verified']);

// ── Credential fields (org profile) ──────────────────────────────────────
$credFields = [
    'org_name'       => ['label' => 'Organization Name',         'icon' => 'fa-building',    'hint' => 'Official registered name at PLSP'],
    'org_code'       => ['label' => 'Organization Code',         'icon' => 'fa-id-badge',    'hint' => 'Unique code assigned by CIG (e.g. ECOS, PDC)'],
    'contact_person' => ['label' => 'Contact Person',            'icon' => 'fa-user-tie',    'hint' => 'Current President or authorized officer'],
    'phone'          => ['label' => 'Contact Number',            'icon' => 'fa-phone',       'hint' => 'Active mobile number of the contact person'],
    'description'    => ['label' => 'Organization Tagline / Mission', 'icon' => 'fa-quote-left', 'hint' => 'Brief mission statement or tagline'],
];
$credFilled = 0;
foreach ($credFields as $k => $_) { if (!empty(trim($user[$k] ?? ''))) $credFilled++; }
$credTotal   = count($credFields);
$credPct     = round(($credFilled / $credTotal) * 100);
$credAllMet  = ($credFilled === $credTotal);
$isVerified  = !empty($user['credentials_verified']);

// ── Accreditation documents (11 required) — all online ───────────────────
$accredDocs = [
    ['key'=>'letter_of_intent',         'seq'=>1,  'label'=>'Letter of Intent',                     'hint'=>'OSLS Form 1 s. 24–25 — use your organization letterhead'],
    ['key'=>'constitution_bylaws',      'seq'=>2,  'label'=>'Constitution and By-Laws',              'hint'=>'Signed by all officers; reviewed and signed by Advisers/College Dean'],
    ['key'=>'resolution_ratification',  'seq'=>3,  'label'=>'Resolution / Ratification',             'hint'=>'If applicable — for minor or major amendments to org documents'],
    ['key'=>'list_of_officers',         'seq'=>4,  'label'=>'List of Officers',                      'hint'=>'Academic Year format with photo, position, college/program'],
    ['key'=>'list_of_members',          'seq'=>5,  'label'=>'List of Members',                       'hint'=>'Name, program, year/section, address, email, contact'],
    ['key'=>'list_of_representatives',  'seq'=>6,  'label'=>'List of Representatives',              'hint'=>'Gender Dev, Mental Health, Anti-Hazing, Anti-Drug, Anti-Smoking, Anti-HIV, Env, Multi-Faith'],
    ['key'=>'pledge_against_hazing',    'seq'=>7,  'label'=>'Pledge Against Hazing',                'hint'=>'Pangako Laban sa Hazing — signed by President and Chief Adviser'],
    ['key'=>'adviser_acceptance',       'seq'=>8,  'label'=>'Adviser Letter of Acceptance',         'hint'=>'At least 2 teacher-advisers; one must be regular full-time faculty'],
    ['key'=>'calendar_activities',      'seq'=>9,  'label'=>'Proposed Calendar of Activities',      'hint'=>'Must not conflict with USP/Institutional activities; include Env. Extension'],
    ['key'=>'calendar_plan',            'seq'=>10, 'label'=>'Proposed Calendar Plan of Activities', 'hint'=>'Include partners, objectives, budget source, expected outcomes'],
    ['key'=>'jpia_audited_report',      'seq'=>11, 'label'=>'JPIA Audited Report',                  'hint'=>'Not required for new applicants — previous A.Y. financial statement'],
];

// ── Online-only process steps ─────────────────────────────────────────────
$processSteps = [
    ['num'=>1, 'title'=>'Fill in Organization Profile',      'desc'=>'Complete all 5 organization credentials — name, code, contact person, phone, and tagline — in the form on this page.'],
    ['num'=>2, 'title'=>'Upload Required Documents',          'desc'=>'Upload all 11 required documents in PDF format. Each document must be a separate file.'],
    ['num'=>3, 'title'=>'Submit for Online Assessment',       'desc'=>'Once all documents are uploaded, submit them for online review by the CIG Compliance Division.'],
    ['num'=>4, 'title'=>'Initial Assessment Result',          'desc'=>'Wait for the initial assessment result online. You will be notified through the system if revisions are needed.'],
    ['num'=>5, 'title'=>'Revise & Resubmit (if needed)',      'desc'=>'If revisions are required, update and re-upload the flagged documents. Resubmit for reassessment.'],
    ['num'=>6, 'title'=>'Admin Reviews & Grants Access',    'desc'=>'The CIG Admin reviews all submitted documents. Once everything is approved, the admin will grant your organization full system access.'],
    ['num'=>7, 'title'=>'Accreditation Certificate Awarded',  'desc'=>'Upon admin approval, your organization is officially recognized and may schedule EED activities per your approved calendar.'],
];

// ── Load existing submissions ─────────────────────────────────────────────
$existing = [];
$res = mysqli_query($conn, "SELECT doc_key, doc_status AS status, file_name, admin_notes, document_id, uploaded_at AS submitted_at FROM `documents` WHERE user_id = $userId AND doc_key IS NOT NULL");
if ($res) { while ($row = mysqli_fetch_assoc($res)) $existing[$row['doc_key']] = $row; }

$docsSubmitted = 0; $docsApproved = 0;
foreach ($accredDocs as $d) {
    $s = $existing[$d['key']]['status'] ?? 'pending';
    if (in_array($s, ['submitted','revision','approved'])) $docsSubmitted++;
    if ($s === 'approved') $docsApproved++;
}
$docPct = round(($docsSubmitted / count($accredDocs)) * 100);

// Overall progress counts approved docs toward completion
$overallTotal     = $credTotal + count($accredDocs);
$overallCompleted = $credFilled + $docsApproved;
$overallPct       = round(($overallCompleted / $overallTotal) * 100);

// Waiting state: profile complete + all docs uploaded → waiting for admin
$isWaitingForAdmin = $credAllMet && ($docsSubmitted === count($accredDocs)) && !$isVerified;

// Determine active process step (online 7-step flow)
$activeStep = 1;
if ($credAllMet)                                    $activeStep = 2;
if ($credAllMet && $docsSubmitted > 0)              $activeStep = 3;
if ($credAllMet && $docsSubmitted === count($accredDocs)) $activeStep = 4;
if ($docsApproved > 0)                              $activeStep = 5;
if ($docsApproved === count($accredDocs))            $activeStep = 6;
if ($isVerified && $docsApproved === count($accredDocs)) $activeStep = 7;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accreditation Submission — OrgHub</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/navbar.css">
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="../css/notifications.css">
<style>
.fas,.far,.fab,.fa{font-family:"Font Awesome 6 Free","Font Awesome 6 Brands"!important;}

.cv-page { padding: 1.5rem 1.8rem 3rem; max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.4rem; }

/* ── Page header ── */
.cv-page-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.cv-page-header-left { display:flex; align-items:center; gap:0.9rem; }
.cv-page-header-icon {
    width:50px; height:50px; border-radius:14px;
    background:linear-gradient(135deg,#2d6a4f,#1a3d2b);
    display:flex; align-items:center; justify-content:center;
    font-size:1.2rem; color:#fff; box-shadow:0 4px 14px rgba(45,106,79,0.3); flex-shrink:0;
}
.cv-page-header h1 { font-size:1.3rem; font-weight:800; color:#1a3d2b; margin:0 0 0.2rem; }
.cv-page-header p  { font-size:0.83rem; color:#6b8f7a; margin:0; }
.cv-status-pill {
    display:inline-flex; align-items:center; gap:0.45rem;
    font-size:0.82rem; font-weight:700; border-radius:20px; padding:0.35rem 1rem;
}
.cv-status-pill.verified { background:#e8f5ee; border:1.5px solid #86efac; color:#166534;
    display:inline-flex; align-items:center; gap:0.45rem; font-size:0.82rem; font-weight:700; border-radius:20px; padding:0.35rem 1rem; }

/* ── Pending progress bar (replaces pill) ── */
.cv-progress-wrap {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 220px;
}
.cv-progress-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #6b8f7a;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.cv-progress-bar-outer {
    flex: 1;
    height: 8px;
    background: #dde8e3;
    border-radius: 20px;
    overflow: hidden;
    min-width: 100px;
}
.cv-progress-bar-inner {
    height: 100%;
    border-radius: 20px;
    background: linear-gradient(90deg, #2d6a4f, #52b788);
}
.cv-progress-pct {
    font-size: 0.82rem;
    font-weight: 800;
    color: #1a3d2b;
    white-space: nowrap;
}

/* ── Main layout ── */
.cv-layout { display:grid; grid-template-columns:1fr 340px; gap:1.4rem; align-items:start; }

/* ── Card base ── */
.cv-card { background:#fff; border-radius:18px; box-shadow:0 2px 14px rgba(0,20,10,0.07); overflow:hidden; }
.cv-card-header {
    display:flex; align-items:center; gap:0.65rem;
    padding:0.9rem 1.4rem; background:#f6faf7; border-bottom:1.5px solid #e8f0eb;
}
.cv-card-header i {
    width:30px; height:30px; border-radius:8px;
    background:#e3f2eb; color:#2d6a4f; font-size:0.88rem;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.cv-card-header h3 { font-size:0.93rem; font-weight:700; color:#1a3d2b; margin:0; flex:1; }
.cv-card-body { padding:1.3rem 1.4rem; }

/* ── Org profile form ── */
.cv-fields-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; }
.cv-fields-grid .span2 { grid-column:1/-1; }
.cv-field { display:flex; flex-direction:column; gap:0.3rem; }
.cv-field-label {
    display:flex; align-items:center; justify-content:space-between;
    font-size:0.76rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#1e3a2e;
}
.cv-field-label i { color:#2d6a4f; margin-right:0.3rem; font-size:0.75rem; }
.cv-field-badge { font-size:0.67rem; font-weight:600; border-radius:20px; padding:0.1rem 0.5rem; text-transform:none; letter-spacing:0; }
.cv-field-badge.ok     { background:#e8f5ee; color:#166534; }
.cv-field-badge.needed { background:#fee2e2; color:#dc3545; }
.cv-field-badge.locked { background:#f1f5f9; color:#64748b; display:inline-flex; align-items:center; gap:0.25rem; }
.cv-field input, .cv-field textarea {
    width:100%; padding:0.6rem 0.9rem; border:1.5px solid #dde8e3; border-radius:8px;
    font-size:0.88rem; font-family:inherit; background:#f6faf7; color:#1e3a2e; outline:none; box-sizing:border-box;
}
.cv-field.is-filled input, .cv-field.is-filled textarea { background:#f0faf5; border-color:#b8d9c4; }
.cv-field.is-missing input, .cv-field.is-missing textarea { border-color:#fca5a5; background:#fff9f9; }
.cv-field.is-locked input {
    background:#f1f5f9; border-color:#e2e8f0; color:#64748b;
    cursor:not-allowed; user-select:none;
}
.cv-field input:focus, .cv-field textarea:focus { border-color:#2d6a4f; background:#fff; box-shadow:0 0 0 3px rgba(45,106,79,0.09); }
.cv-field input::placeholder, .cv-field textarea::placeholder { color:#adc0b8; }
.cv-field textarea { resize:vertical; min-height:65px; }

/* Alert */
.cv-alert { padding:0.7rem 1rem; border-radius:8px; font-size:0.83rem; font-weight:600; gap:0.5rem; display:none; }
.cv-alert.error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; display:flex; }
.cv-alert.success { background:#e8f5ee; color:#166534; border:1px solid #86efac; display:flex; }
.cv-alert ul { padding-left:1rem; margin-top:0.25rem; font-weight:400; line-height:1.6; }

.cv-submit-row { display:flex; justify-content:flex-end; padding-top:0.5rem; }
.btn-verify {
    background:#1a3d2b; color:#fff; border:none; padding:0.65rem 1.4rem; border-radius:8px;
    font-size:0.88rem; font-weight:700; cursor:pointer; font-family:inherit;
    display:flex; align-items:center; gap:0.45rem; box-shadow:0 2px 8px rgba(26,61,43,0.2);
}
.btn-verify:hover { background:#235c3f; }
.btn-verify:disabled { opacity:0.5; cursor:not-allowed; }

/* ── Document submission list ── */
.doc-list { display:flex; flex-direction:column; gap:0; }
.doc-item {
    display:flex; align-items:center; gap:0.85rem;
    padding:0.85rem 0; border-bottom:1px solid #f0f5f2;
}
.doc-item:last-child { border-bottom:none; }
.doc-seq {
    width:28px; height:28px; border-radius:8px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:0.72rem; font-weight:800;
}
.doc-seq.pending   { background:#f0f5f2; color:#6b8f7a; }
.doc-seq.submitted { background:#dbeafe; color:#1d4ed8; }
.doc-seq.revision  { background:#fff7ed; color:#b45309; }
.doc-seq.approved  { background:#e8f5ee; color:#166534; }
.doc-seq.done      { background:#e8f5ee; color:#166534; }

.doc-info { flex:1; min-width:0; }
.doc-title { font-size:0.85rem; font-weight:600; color:#1a3d2b; margin-bottom:0.1rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.doc-hint  { font-size:0.72rem; color:#9ab5ac; line-height:1.3; }
.doc-file-name { font-size:0.72rem; color:#2d6a4f; margin-top:0.1rem; }

.doc-status-badge {
    font-size:0.68rem; font-weight:700; border-radius:20px; padding:0.2rem 0.55rem;
    flex-shrink:0; white-space:nowrap;
}
.doc-status-badge.pending   { background:#f0f5f2; color:#6b8f7a; }
.doc-status-badge.submitted { background:#dbeafe; color:#1d4ed8; }
.doc-status-badge.revision  { background:#fff7ed; color:#b45309; }
.doc-status-badge.approved  { background:#e8f5ee; color:#166534; }

.doc-upload-btn {
    display:flex; align-items:center; gap:0.3rem;
    background:#f6faf7; border:1.5px solid #dde8e3; border-radius:7px;
    padding:0.3rem 0.7rem; font-size:0.75rem; font-weight:600;
    color:#2d6a4f; cursor:pointer; font-family:inherit; flex-shrink:0;
}
.doc-upload-btn:hover { background:#e8f5ee; border-color:#2d6a4f; }
.doc-upload-input { display:none; }

/* ── Process steps ── */
.process-steps { display:flex; flex-direction:column; }
.process-step {
    display:flex; gap:0.8rem; padding-bottom:1rem;
    position:relative;
}
.process-step:not(:last-child)::after {
    content:''; position:absolute; left:14px; top:30px; bottom:0;
    width:2px; background:linear-gradient(to bottom,#c3e0cc,#edf2ef);
}
.process-step-num {
    width:30px; height:30px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:0.72rem; font-weight:800; position:relative; z-index:1;
}
.process-step.done    .process-step-num { background:#2d6a4f; color:#fff; box-shadow:0 2px 6px rgba(45,106,79,0.3); }
.process-step.active  .process-step-num { background:#1a3d2b; color:#fff; box-shadow:0 2px 8px rgba(26,61,43,0.35); }
.process-step.waiting .process-step-num { background:#edf2ef; color:#9ab5ac; }
.process-step-body { padding-top:0.3rem; flex:1; }
.process-step-title { font-size:0.83rem; font-weight:700; color:#1a3d2b; margin-bottom:0.2rem; }
.process-step.waiting .process-step-title { color:#9ab5ac; }
.process-step-desc { font-size:0.76rem; color:#6b8f7a; line-height:1.5; }
.process-step.waiting .process-step-desc { color:#c8ddd5; }

/* Verified banner */
.cv-verified-banner {
    background:linear-gradient(135deg,#1a6335,#2d6a4f); border-radius:16px;
    padding:1.3rem 1.6rem; display:flex; align-items:center; gap:1rem;
    box-shadow:0 4px 16px rgba(26,99,53,0.2);
}
.cv-verified-banner-icon {
    width:46px; height:46px; border-radius:50%;
    background:rgba(255,255,255,0.15); border:2px solid rgba(255,255,255,0.25);
    display:flex; align-items:center; justify-content:center;
    font-size:1.2rem; color:#fff; flex-shrink:0;
}
.cv-verified-banner h3 { font-size:0.95rem; font-weight:700; color:#fff; margin:0 0 0.15rem; }
.cv-verified-banner p  { font-size:0.78rem; color:rgba(178,220,195,0.8); margin:0; }
.cv-verified-banner a  {
    margin-left:auto; flex-shrink:0; background:#fff; color:#1a3d2b;
    border:none; padding:0.55rem 1.2rem; border-radius:8px;
    font-size:0.83rem; font-weight:700; text-decoration:none;
    display:flex; align-items:center; gap:0.4rem;
}
.cv-verified-banner a:hover { background:#e8f5ee; }

/* Waiting for admin banner */
.cv-waiting-banner {
    background:linear-gradient(135deg,#1e3a5f,#1d4ed8); border-radius:16px;
    padding:1.3rem 1.6rem; display:flex; align-items:center; gap:1rem;
    box-shadow:0 4px 16px rgba(29,78,216,0.2);
}
.cv-waiting-banner-icon {
    width:46px; height:46px; border-radius:50%;
    background:rgba(255,255,255,0.15); border:2px solid rgba(255,255,255,0.25);
    display:flex; align-items:center; justify-content:center;
    font-size:1.2rem; color:#fff; flex-shrink:0; animation: pulse-icon 2s infinite;
}
@keyframes pulse-icon { 0%,100%{opacity:1;} 50%{opacity:0.5;} }
.cv-waiting-banner h3 { font-size:0.95rem; font-weight:700; color:#fff; margin:0 0 0.15rem; }
.cv-waiting-banner p  { font-size:0.78rem; color:rgba(191,219,254,0.85); margin:0; }
.cv-waiting-banner-badge {
    margin-left:auto; flex-shrink:0;
    background:rgba(255,255,255,0.15); border:1.5px solid rgba(255,255,255,0.25);
    color:#fff; padding:0.45rem 1rem; border-radius:20px;
    font-size:0.8rem; font-weight:700;
    display:flex; align-items:center; gap:0.4rem; white-space:nowrap;
}

.doc-actions { display:flex; gap:0.4rem; flex-shrink:0; align-items:center; }

.doc-view-btn {
    display:inline-flex; align-items:center; gap:0.3rem;
    padding:0.25rem 0.6rem; border-radius:6px; font-size:0.7rem; font-weight:700;
    background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;
    cursor:pointer; text-decoration:none; white-space:nowrap;
    font-family:inherit;
}
.doc-view-btn:hover { background:#dbeafe; }

.doc-remarks {
    font-size:0.72rem; color:#b45309; margin-top:0.25rem;
    background:#fff7ed; border-left:3px solid #f97316;
    padding:0.25rem 0.5rem; border-radius:0 6px 6px 0;
    line-height:1.4;
}
.doc-remarks.approved {
    color:#166534; background:#f0fdf4; border-left-color:#22c55e;
}

/* Revoked banner */
.cv-revoked-banner {
    background:linear-gradient(135deg,#7f1d1d,#dc2626); border-radius:16px;
    padding:1.3rem 1.6rem; display:flex; align-items:center; gap:1rem;
    box-shadow:0 4px 16px rgba(220,38,38,0.25);
}
.cv-revoked-banner-icon {
    width:46px; height:46px; border-radius:50%;
    background:rgba(255,255,255,0.15); border:2px solid rgba(255,255,255,0.25);
    display:flex; align-items:center; justify-content:center;
    font-size:1.2rem; color:#fff; flex-shrink:0;
}
.cv-revoked-banner h3 { font-size:0.95rem; font-weight:700; color:#fff; margin:0 0 0.15rem; }
.cv-revoked-banner p  { font-size:0.78rem; color:rgba(254,202,202,0.9); margin:0; }
.doc-remarks.approved {
    color:#166534; background:#f0fdf4; border-left-color:#22c55e;
}

/* Submit all btn */
.btn-submit-all {
    width:100%; padding:0.75rem; border:none; border-radius:9px;
    background:#1a3d2b; color:#fff; font-size:0.9rem; font-weight:700;
    cursor:pointer; font-family:inherit; display:flex; align-items:center; justify-content:center; gap:0.5rem;
    box-shadow:0 2px 10px rgba(26,61,43,0.22); margin-top:0.8rem;
}
.btn-submit-all:hover { background:#235c3f; }
.btn-submit-all:disabled { opacity:0.45; cursor:not-allowed; }

@media(max-width:960px){ .cv-layout{ grid-template-columns:1fr; } }
@media(max-width:640px){ .cv-fields-grid{ grid-template-columns:1fr; } }
</style>
</head>
<body>
<?php include 'navbar.php'; ?>
<main class="main-content" style="overflow-y:auto;overflow-x:hidden;height:100vh;">

<div class="cv-page">

    <!-- Page header -->
    <div class="cv-page-header">
        <div class="cv-page-header-left">
            <div class="cv-page-header-icon"><i class="fas fa-certificate"></i></div>
            <div>
                <h1>Accreditation Submission</h1>
                <p>PLSP Accreditation Manual · CIG · A.Y. 2024–2025 · OVPSLS / OSDS</p>
            </div>
        </div>
        <?php if ($isVerified): ?>
        <span class="cv-status-pill verified"><i class="fas fa-check-circle"></i> Credentials Verified</span>
        <?php else: ?>
        <div class="cv-progress-wrap">
            <span class="cv-progress-label">Overall</span>
            <div class="cv-progress-bar-outer">
                <div class="cv-progress-bar-inner" style="width:<?= $overallPct ?>%"></div>
            </div>
            <span class="cv-progress-pct"><?= $overallCompleted ?>/<?= $overallTotal ?> &nbsp;·&nbsp; <?= $overallPct ?>%</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- State banners -->
    <?php if ($wasJustRevoked): ?>
    <!-- 🚫 Just revoked — show prominent warning first -->
    <div class="cv-revoked-banner">
        <div class="cv-revoked-banner-icon"><i class="fas fa-ban"></i></div>
        <div>
            <h3>Access Revoked by Admin</h3>
            <p>Your organization's accreditation access has been revoked by the CIG Admin. Please review your documents and resubmit, or contact the office for clarification.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($isVerified): ?>
    <!-- ✅ Fully verified by admin -->
    <div class="cv-verified-banner">
        <div class="cv-verified-banner-icon"><i class="fas fa-award"></i></div>
        <div>
            <h3>Organization credentials verified by Admin!</h3>
            <p>Your accreditation has been approved by the CIG Admin. You now have full access to the system.</p>
        </div>
        <a href="dashboard.php"><i class="fas fa-arrow-right"></i> Go to Dashboard</a>
    </div>

    <?php elseif ($isWaitingForAdmin): ?>
    <!-- All submitted — waiting for admin to grant -->
    <div class="cv-waiting-banner">
        <div class="cv-waiting-banner-icon"><i class="fas fa-hourglass-half"></i></div>
        <div>
            <h3>Submitted — Waiting for Admin Approval</h3>
            <p>All your credentials and documents have been submitted. The CIG Admin will review and grant you system access. You will be notified once approved.</p>
        </div>
        <div class="cv-waiting-banner-badge"><i class="fas fa-clock"></i> Under Review</div>
    </div>

    <?php else: ?>
    <!-- Still filling in / uploading -->
    <div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:14px;padding:1rem 1.4rem;display:flex;align-items:center;gap:0.9rem;">
        <div style="width:38px;height:38px;border-radius:50%;background:#fef3c7;border:2px solid #fde68a;display:flex;align-items:center;justify-content:center;font-size:1rem;color:#b45309;flex-shrink:0;">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div>
            <div style="font-size:0.88rem;font-weight:700;color:#92400e;margin-bottom:0.15rem;">Complete your profile and upload all documents to request access</div>
            <div style="font-size:0.78rem;color:#b45309;">Once all <?= $credTotal ?> profile fields and <?= count($accredDocs) ?> documents are submitted, the CIG Admin will review and grant system access.</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main layout -->
    <div class="cv-layout">

        <!-- LEFT: Org profile + Documents -->
        <div style="display:flex;flex-direction:column;gap:1.4rem;">

            <!-- Org profile credentials -->
            <div class="cv-card">
                <div class="cv-card-header">
                    <i class="fas fa-id-card"></i>
                    <h3>Organization Profile Credentials</h3>
                    <span style="font-size:0.75rem;color:#9ab5ac;margin-left:auto"><?= $credFilled ?>/<?= $credTotal ?> complete</span>
                </div>
                <div class="cv-card-body">
                    <div class="cv-alert" id="cvAlert"></div>
                    <form id="cvForm">
                        <div class="cv-fields-grid">
                            <?php foreach ($credFields as $key => $req):
                                $val    = htmlspecialchars($user[$key] ?? '');
                                $ok     = !empty(trim($user[$key] ?? ''));
                                $locked = in_array($key, ['org_name', 'org_code']);
                                $cls    = $locked ? 'is-locked' : ($ok ? 'is-filled' : 'is-missing');
                                $isArea = $key === 'description';
                            ?>
                            <div class="cv-field <?= $cls ?> <?= $isArea ? 'span2' : '' ?>">
                                <div class="cv-field-label">
                                    <span><i class="fas <?= $req['icon'] ?>"></i><?= $req['label'] ?></span>
                                    <?php if ($locked): ?>
                                    <span class="cv-field-badge locked"><i class="fas fa-lock" style="font-size:0.6rem"></i> Fixed</span>
                                    <?php else: ?>
                                    <span class="cv-field-badge <?= $ok ? 'ok' : 'needed' ?>"><?= $ok ? '<i class="fas fa-check"></i> Saved' : '<i class="fas fa-xmark"></i> Required' ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isArea): ?>
                                <textarea id="cv_<?= $key ?>" name="<?= $key ?>" rows="2" placeholder="<?= htmlspecialchars($req['hint']) ?>"><?= $val ?></textarea>
                                <?php elseif ($locked): ?>
                                <input type="text" value="<?= $val ?>" readonly tabindex="-1"
                                       placeholder="<?= htmlspecialchars($req['hint']) ?>">
                                <?php else: ?>
                                <input type="text" id="cv_<?= $key ?>" name="<?= $key ?>" value="<?= $val ?>"
                                       placeholder="<?= htmlspecialchars($req['hint']) ?>">
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="cv-submit-row">
                            <button type="submit" class="btn-verify" id="verifyBtn">
                                <i class="fas fa-save"></i>
                                <?= $credAllMet ? 'Update Profile' : 'Save Profile' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Document submissions -->
            <div class="cv-card">
                <div class="cv-card-header">
                    <i class="fas fa-folder-open"></i>
                    <h3>Required Accreditation Documents</h3>
                    <span style="font-size:0.75rem;color:#9ab5ac;margin-left:auto"><?= $docsSubmitted ?>/<?= count($accredDocs) ?> uploaded</span>
                </div>
                <div class="cv-card-body" style="padding:1rem 1.4rem;">

                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:9px;padding:0.7rem 1rem;margin-bottom:1rem;font-size:0.8rem;color:#1e40af;display:flex;align-items:flex-start;gap:0.5rem;">
                        <i class="fas fa-cloud-upload-alt" style="flex-shrink:0;margin-top:1px"></i>
                        <span>Upload all documents in <strong>PDF format</strong> directly through this system. Each document must be a separate file. The system will notify you of assessment results online.</span>
                    </div>

                    <div class="doc-list" id="docList">
                        <?php foreach ($accredDocs as $doc):
                            $sub        = $existing[$doc['key']] ?? null;
                            $status     = $sub['status'] ?? 'pending';
                            $fname      = $sub['file_name'] ?? null;
                            $docId      = (int)($sub['document_id'] ?? 0);
                            $remarks    = trim($sub['admin_notes'] ?? '');
                            $isApproved = $status === 'approved';
                        ?>
                        <div class="doc-item" id="docitem_<?= $doc['key'] ?>">
                            <div class="doc-seq <?= $status ?>">
                                <?php if ($isApproved): ?>
                                <i class="fas fa-check" style="font-size:0.6rem"></i>
                                <?php else: ?>
                                <?= $doc['seq'] ?>
                                <?php endif; ?>
                            </div>
                            <div class="doc-info">
                                <div class="doc-title"><?= htmlspecialchars($doc['label']) ?></div>
                                <div class="doc-hint"><?= htmlspecialchars($doc['hint']) ?></div>
                                <?php if ($fname): ?>
                                <div class="doc-file-name"><i class="fas fa-paperclip" style="font-size:0.65rem"></i> <?= htmlspecialchars($fname) ?></div>
                                <?php endif; ?>
                                <?php if ($remarks !== ''): ?>
                                <div class="doc-remarks <?= $isApproved ? 'approved' : '' ?>">
                                    <i class="fas <?= $isApproved ? 'fa-circle-check' : 'fa-comment-dots' ?>" style="font-size:0.65rem;margin-right:0.25rem"></i>
                                    <strong>Remark:</strong> <?= htmlspecialchars($remarks) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <span class="doc-status-badge <?= $status ?>">
                                <?= match($status) {
                                    'approved'  => '<i class="fas fa-circle-check"></i> Approved',
                                    'submitted' => '<i class="fas fa-clock"></i> Submitted',
                                    'revision'  => '<i class="fas fa-rotate-right"></i> Revision',
                                    default     => 'Pending'
                                } ?>
                            </span>
                            <div class="doc-actions">
                                <?php if ($fname && $docId): ?>
                                <button class="doc-view-btn"
                                   onclick="openDocPreview(<?= $docId ?>,'<?= addslashes(htmlspecialchars($doc['label'])) ?>')"
                                   title="View uploaded file">
                                    <i class="fas fa-eye" style="font-size:0.65rem"></i> View
                                </button>
                                <?php endif; ?>
                                <?php if (!$isApproved): ?>
                                <label class="doc-upload-btn" title="<?= $fname ? 'Replace file' : 'Upload PDF' ?>">
                                    <i class="fas fa-upload" style="font-size:0.7rem"></i>
                                    <?= $fname ? 'Replace' : 'Upload' ?>
                                    <input type="file" class="doc-upload-input" accept=".pdf"
                                           data-key="<?= $doc['key'] ?>"
                                           data-label="<?= htmlspecialchars($doc['label'], ENT_QUOTES) ?>"
                                           data-seq="<?= $doc['seq'] ?>"
                                           data-phase="1"
                                           onchange="uploadDoc(this)">
                                </label>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>

        </div><!-- /left -->

        <!-- RIGHT: Process steps + Softbind -->
        <div style="display:flex;flex-direction:column;gap:1.4rem;">

            <!-- Process steps -->
            <div class="cv-card">
                <div class="cv-card-header">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Online Accreditation Process</h3>
                </div>
                <div class="cv-card-body" style="padding:1rem 1.3rem;">
                    <div class="process-steps">
                        <?php foreach ($processSteps as $step):
                            $sNum    = $step['num'];
                            $sDone   = $sNum < $activeStep;
                            $sActive = $sNum === $activeStep;
                            $sCls    = $sDone ? 'done' : ($sActive ? 'active' : 'waiting');
                            $isLast  = $sNum === count($processSteps);
                        ?>
                        <div class="process-step <?= $sCls ?> <?= $isLast ? 'last' : '' ?>">
                            <div class="process-step-num">
                                <?= $sDone ? '<i class="fas fa-check" style="font-size:0.55rem"></i>' : $sNum ?>
                            </div>
                            <div class="process-step-body">
                                <div class="process-step-title"><?= htmlspecialchars($step['title']) ?></div>
                                <div class="process-step-desc"><?= htmlspecialchars($step['desc']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Online submission guidelines -->
            <div class="cv-card">
                <div class="cv-card-header">
                    <i class="fas fa-circle-info"></i>
                    <h3>Online Submission Guidelines</h3>
                </div>
                <div class="cv-card-body">
                    <div style="display:flex;flex-direction:column;gap:0.6rem;">
                        <?php foreach ([
                            ['fa-file-pdf',     '#1d4ed8', 'PDF Format Only',          'All documents must be uploaded as individual PDF files.'],
                            ['fa-list-ol',      '#166534', 'Correct Sequence',          'Upload documents in the numbered order shown (1–11).'],
                            ['fa-cloud-upload-alt','#2d6a4f','Upload Directly Here',   'Use the Upload button beside each document row on this page.'],
                            ['fa-bell',         '#b45309', 'Await Online Notification', 'Assessment results and revision requests will be sent through this system.'],
                            ['fa-rotate-right', '#7c3aed', 'Re-upload if Needed',       'If a document is flagged for revision, replace it using the Replace button.'],
                            ['fa-award',        '#166534', 'Certificate Awarded Online','Accreditation certificate will be issued digitally upon final approval.'],
                        ] as [$icon, $color, $title, $desc]): ?>
                        <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.55rem 0.75rem;background:#f6faf7;border-radius:9px;border-left:3px solid <?= $color ?>;">
                            <i class="fas <?= $icon ?>" style="color:<?= $color ?>;font-size:0.85rem;flex-shrink:0;margin-top:2px;"></i>
                            <div>
                                <div style="font-size:0.82rem;font-weight:700;color:#1a3d2b;margin-bottom:0.1rem;"><?= $title ?></div>
                                <div style="font-size:0.76rem;color:#6b8f7a;line-height:1.4;"><?= $desc ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:0.9rem;padding-top:0.8rem;border-top:1px solid #edf2ef;font-size:0.72rem;color:#9ab5ac;line-height:1.5;">
                        <i class="fas fa-book-open" style="color:#2d6a4f"></i>
                        References: OVPSLS Form 1 / Letter of Intent · PLSP Student Handbook 2018
                    </div>
                </div>
            </div>

        </div><!-- /right -->
    </div><!-- /cv-layout -->
</div><!-- /cv-page -->

<script>
// ── Org profile form ──────────────────────────────────────────────────────
document.getElementById('cvForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn   = document.getElementById('verifyBtn');
    var alert = document.getElementById('cvAlert');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';
    alert.style.display = 'none';
    alert.className = 'cv-alert';

    fetch('credential_verify_save.php', { method:'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert.className = 'cv-alert success';
            alert.innerHTML = '<i class="fas fa-check-circle"></i><span>' + data.message + ' Refreshing…</span>';
            setTimeout(() => location.reload(), 1200);
        } else {
            alert.className = 'cv-alert error';
            var html = '<div><div style="margin-bottom:0.25rem"><i class="fas fa-times-circle"></i> ' + data.message + '</div>';
            if (data.missing && data.missing.length) {
                html += '<ul>' + data.missing.map(m => '<li>' + m + '</li>').join('') + '</ul>';
            }
            alert.innerHTML = html + '</div>';
            if (data.missing_keys) {
                data.missing_keys.forEach(key => {
                    var f = document.getElementById('field_' + key);
                    if (f) { f.classList.remove('is-filled'); f.classList.add('is-missing'); }
                });
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save & Verify';
        }
    })
    .catch(() => {
        alert.className = 'cv-alert error';
        alert.innerHTML = '<i class="fas fa-times-circle"></i> Server error. Please try again.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save & Verify';
    });
});

// ── Document upload ───────────────────────────────────────────────────────
function uploadDoc(input) {
    if (!input.files || !input.files[0]) return;
    var key   = input.dataset.key;
    var label = input.dataset.label;
    var file  = input.files[0];
    if (file.type !== 'application/pdf') {
        alert('Only PDF files are accepted.');
        input.value = '';
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        alert('File too large. Maximum size is 10 MB.');
        input.value = '';
        return;
    }
    var fd = new FormData();
    fd.append('doc_key',   key);
    fd.append('doc_label', label);
    fd.append('doc_seq',   input.dataset.seq   || 0);
    fd.append('doc_phase', input.dataset.phase || 1);
    fd.append('doc_file',  file);

    var item = document.getElementById('docitem_' + key);
    var btn  = item.querySelector('.doc-upload-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:0.7rem"></i> Uploading…';

    fetch('doc_upload.php', { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update seq badge
            var seq = item.querySelector('.doc-seq');
            seq.className = 'doc-seq submitted';
            seq.textContent = seq.textContent;
            // Update status badge
            var badge = item.querySelector('.doc-status-badge');
            badge.className = 'doc-status-badge submitted';
            badge.innerHTML = '<i class="fas fa-clock"></i> Submitted';
            // Show filename
            var hint = item.querySelector('.doc-hint');
            var existing = item.querySelector('.doc-file-name');
            if (existing) existing.remove();
            var fn = document.createElement('div');
            fn.className = 'doc-file-name';
            fn.innerHTML = '<i class="fas fa-paperclip" style="font-size:0.65rem"></i> ' + data.file_name;
            hint.after(fn);
            btn.innerHTML = '<i class="fas fa-upload" style="font-size:0.7rem"></i> Replace';
        } else {
            alert('Upload failed: ' + (data.message || 'Unknown error'));
            btn.innerHTML = '<i class="fas fa-upload" style="font-size:0.7rem"></i> Upload';
        }
    })
    .catch(() => {
        alert('Upload error. Please try again.');
        btn.innerHTML = '<i class="fas fa-upload" style="font-size:0.7rem"></i> Upload';
    });
}
</script>

</main>
<?php if ($isWaitingForAdmin && !$isVerified): ?>
<script>
// Auto-check every 30 seconds if admin has granted access
setInterval(function() {
    fetch('credential_verify_save.php', {
        method: 'POST',
        body: new URLSearchParams({ check_only: '1' })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.verified) {
            window.location.reload();
        }
    })
    .catch(function() {});
}, 30000);
</script>
<?php endif; ?>
<script src="../js/script.js"></script>
<script src="../js/navbar.js"></script>
<script src="../js/notifications.js"></script>
<script>
window.__PAGE_TYPE = 'protected';
window.__LOGIN_URL = 'index.php';
</script>
<script src="../js/no_back.js"></script>
<script>
window.__SM = {
    timeout:  86400,   // 24h — effectively disabled on this page (user is reviewing files)
    warn:     120,
    verified: false,
    pingUrl:  'ping_session.php',
    logoutUrl:'logout.php',
    loginUrl: 'index.php',
    checkUrl: 'credential_verify_save.php'
};
</script>
<script src="../js/session_manager.js"></script>
<!-- ── Document Preview Modal ─────────────────────────────────────────── -->
<div id="docPreviewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;width:92vw;max-width:1000px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.4);">
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 18px;background:#1a3d2b;color:#fff;flex-shrink:0;border-radius:18px 18px 0 0;">
            <div style="display:flex;align-items:center;gap:9px;min-width:0;">
                <i class="fas fa-file-pdf" style="font-size:1.1rem;color:#52b788;flex-shrink:0;"></i>
                <span id="docPreviewTitle" style="font-size:.95rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <a id="docPreviewDownload" href="#" target="_blank"
                   style="background:rgba(255,255,255,.15);border:none;color:#fff;padding:0.3rem 0.75rem;border-radius:6px;font-size:0.78rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:0.35rem;">
                    <i class="fas fa-download" style="font-size:0.7rem;"></i> Download
                </a>
                <button onclick="closeDocPreview()" style="background:rgba(255,255,255,.15);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">&times;</button>
            </div>
        </div>
        <!-- Loading -->
        <div id="docPreviewLoading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:12px;color:#666;">
            <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#2d6a4f;"></i>
            <span style="font-size:.9rem;">Loading document…</span>
        </div>
        <!-- Error -->
        <div id="docPreviewError" style="display:none;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:10px;color:#c0392b;">
            <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
            <span id="docPreviewErrorMsg" style="font-size:.9rem;text-align:center;max-width:400px;"></span>
        </div>
        <!-- PDF iframe -->
        <iframe id="docPreviewFrame" style="display:none;flex:1;border:none;width:100%;"></iframe>
    </div>
</div>

<script>
function openDocPreview(docId, title) {
    var modal   = document.getElementById('docPreviewModal');
    var loading = document.getElementById('docPreviewLoading');
    var errDiv  = document.getElementById('docPreviewError');
    var frame   = document.getElementById('docPreviewFrame');
    var titleEl = document.getElementById('docPreviewTitle');
    var dlBtn   = document.getElementById('docPreviewDownload');

    // Reset state
    loading.style.display = 'flex';
    errDiv.style.display  = 'none';
    frame.style.display   = 'none';
    frame.src             = '';
    titleEl.textContent   = title;

    var url = 'accreditation_file.php?doc_id=' + docId;
    dlBtn.href = url + '&download=1';

    // Load PDF in iframe
    frame.src = url;
    frame.onload = function() {
        loading.style.display = 'none';
        frame.style.display   = 'block';
    };
    frame.onerror = function() {
        loading.style.display = 'none';
        errDiv.style.display  = 'flex';
        document.getElementById('docPreviewErrorMsg').textContent = 'Failed to load document.';
    };

    modal.style.display = 'flex';

    // Keep session alive while modal is open — ping every 4 minutes
    window._previewPingInterval = setInterval(function() {
        fetch('ping_session.php', { method: 'POST', credentials: 'same-origin' }).catch(function(){});
    }, 4 * 60 * 1000);
}

function closeDocPreview() {
    document.getElementById('docPreviewModal').style.display = 'none';
    document.getElementById('docPreviewFrame').src = '';
    // Stop keep-alive ping
    if (window._previewPingInterval) {
        clearInterval(window._previewPingInterval);
        window._previewPingInterval = null;
    }
}

document.getElementById('docPreviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeDocPreview();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDocPreview();
});
</script>

</body>
</html>