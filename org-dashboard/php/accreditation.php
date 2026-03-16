<?php
require_once __DIR__ . '/auth_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accreditation Manual — OrgHub</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../css/navbar.css">
<link rel="stylesheet" href="../css/dashboard.css">
<link rel="stylesheet" href="../css/topbar.css">
<link rel="stylesheet" href="../css/notifications.css">
<link rel="stylesheet" href="../css/accreditation.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<main class="main-content">
<?php include 'topbar.php'; ?>

<div class="accred-container">

    <!-- Page Header -->
    <div class="accred-page-header">
        <div class="accred-page-header-left">
            <div class="accred-header-icon"><i class="fas fa-certificate"></i></div>
            <div>
                <h1>Accreditation Manual</h1>
                <p>Requirements &amp; Guidelines for Accreditation, Re-accreditation, and Recognition</p>
            </div>
        </div>
        <div class="accred-meta">
            <span><i class="fas fa-university"></i> PLSP — OVPSLS / OSDS / CIG</span>
            <span><i class="fas fa-calendar-alt"></i> A.Y. 2024–2025</span>
        </div>
    </div>

    <!-- Intro Banner -->
    <div class="accred-intro-banner">
        <div class="accred-intro-icon"><i class="fas fa-info-circle"></i></div>
        <p>This manual provides requirements and a step-by-step guide for the PLSP accreditation, re-accreditation, and recognition process. It includes processes, procedures, monitoring and assessment guidelines, and additional requirements.</p>
        <div class="accred-intro-ref">
            <i class="fas fa-book-open"></i>
            <span>PLSP Student Handbook · Chapter III Article 48 Section 5</span>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="accred-layout">

        <!-- LEFT: Requirements + Format -->
        <div class="accred-left">

            <!-- Requirements Card -->
            <div class="accred-card">
                <div class="accred-card-header">
                    <i class="fas fa-clipboard-list"></i>
                    <h2>Requirements</h2>
                </div>
                <div class="accred-card-body">

                    <div class="req-item">
                        <div class="req-num">01</div>
                        <div class="req-content">
                            <div class="req-title">Letter of Intent <span class="req-badge">OSLS Form 1 s. 24–25</span></div>
                            <div class="req-note">Use your organization letterhead. This serves as your requirements checklist.</div>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">02</div>
                        <div class="req-content">
                            <div class="req-title">Constitution and By-Laws</div>
                            <ul class="req-list">
                                <li>All officers must have signed this document.</li>
                                <li>Advisers/College Dean must review and sign this document.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">03</div>
                        <div class="req-content">
                            <div class="req-title">Resolution / Ratification <span class="req-badge optional">If Applicable</span></div>
                            <ul class="req-list">
                                <li><strong>Minor amendments</strong> — provide RESOLUTION, signed by officers (e.g., adding positions, policies, mission, vision).</li>
                                <li><strong>Major amendments</strong> — provide RATIFICATION, agreed upon by majority members (e.g., change of org name or official seal).</li>
                            </ul>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">04</div>
                        <div class="req-content">
                            <div class="req-title">List of Officers <span class="req-badge">See Templates</span></div>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">05</div>
                        <div class="req-content">
                            <div class="req-title">List of Members <span class="req-badge">See Templates</span></div>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">06</div>
                        <div class="req-content">
                            <div class="req-title">List of Representatives <span class="req-badge">See Templates</span></div>
                            <div class="req-note">Could be the officers or members.</div>
                            <div class="rep-tags">
                                <span>Gender and Development</span>
                                <span>Center for Mental Health</span>
                                <span>Anti-Hazing</span>
                                <span>Anti-Drug &amp; Substance Abuse</span>
                                <span>Anti-Smoking</span>
                                <span>Anti-HIV/AIDS</span>
                                <span>Environment Extension Program</span>
                                <span>Multi-Faith Alumni Relations</span>
                            </div>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">07</div>
                        <div class="req-content">
                            <div class="req-title">Pledge Against Hazing <span class="req-badge">Pangako Laban sa Hazing</span></div>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">08</div>
                        <div class="req-content">
                            <div class="req-title">Adviser Letter of Acceptance <span class="req-badge">See Templates</span></div>
                            <ul class="req-list">
                                <li>An organization should have at least <strong>two teacher-advisers.</strong></li>
                                <li>At least one must be a <strong>regular full-time faculty</strong> member.</li>
                                <li>Non-regular adviser must carry at least <strong>24 units per semester.</strong></li>
                                <li>Adviser from non-teaching personnel must have an <strong>extra teaching load.</strong></li>
                            </ul>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">09</div>
                        <div class="req-content">
                            <div class="req-title">Proposed Calendar of Activities <span class="req-badge">See Templates</span></div>
                            <ul class="req-list">
                                <li>Must not conflict with University Student Parliament/Institutional activities.</li>
                                <li>Must include <strong>Environment Extension Program</strong> in calendar.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">10</div>
                        <div class="req-content">
                            <div class="req-title">Proposed Calendar Plan of Activities <span class="req-badge">See Templates</span></div>
                        </div>
                    </div>

                    <div class="req-item">
                        <div class="req-num">11</div>
                        <div class="req-content">
                            <div class="req-title">JPIA Audited Report <span class="req-badge optional">Not for new applicants</span></div>
                            <ul class="req-list">
                                <li>The financial statement needed is from the previous academic year.</li>
                                <li>For financial statement matters, consult JPIA officers.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Re-accreditation additional -->
                    <div class="req-section-divider">
                        <span>Additional Requirements</span>
                        <small>For Re-Accreditation &amp; Recognition only</small>
                    </div>

                    <div class="req-item additional">
                        <div class="req-num">+</div>
                        <div class="req-content">
                            <div class="req-title">Approved Project Proposal</div>
                        </div>
                    </div>
                    <div class="req-item additional">
                        <div class="req-num">+</div>
                        <div class="req-content">
                            <div class="req-title">Project Accomplishment Report</div>
                        </div>
                    </div>
                    <div class="req-item additional">
                        <div class="req-num">+</div>
                        <div class="req-content">
                            <div class="req-title">Approved Previous A.Y. Calendar of Activities</div>
                        </div>
                    </div>
                    <div class="req-item additional">
                        <div class="req-num">+</div>
                        <div class="req-content">
                            <div class="req-title">Approved Previous A.Y. Calendar Plan of Activities</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Standard Format Card -->
            <div class="accred-card">
                <div class="accred-card-header">
                    <i class="fas fa-file-alt"></i>
                    <h2>Standard Format</h2>
                </div>
                <div class="accred-card-body">
                    <div class="format-grid">
                        <div class="format-item">
                            <i class="fas fa-file"></i>
                            <div>
                                <strong>Paper Size</strong>
                                <span>A4</span>
                            </div>
                        </div>
                        <div class="format-item">
                            <i class="fas fa-arrows-alt-h"></i>
                            <div>
                                <strong>Margins</strong>
                                <span>Right: 0.5 in · Left: 1 in</span>
                            </div>
                        </div>
                        <div class="format-item">
                            <i class="fas fa-text-height"></i>
                            <div>
                                <strong>Font Size</strong>
                                <span>12pt, Single spacing</span>
                            </div>
                        </div>
                        <div class="format-item">
                            <i class="fas fa-font"></i>
                            <div>
                                <strong>Font Style</strong>
                                <span>Times New Roman</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Softbind Card -->
            <div class="accred-card">
                <div class="accred-card-header">
                    <i class="fas fa-book"></i>
                    <h2>Softbind Details</h2>
                </div>
                <div class="accred-card-body">
                    <p class="softbind-note">The cover must be made of <strong>hard, clear plastic.</strong> The cover page should include the official seal, organization name, and academic year.</p>
                    <div class="color-codes">
                        <div class="color-code-item"><span style="background:#1e4d8c"></span> Mandated</div>
                        <div class="color-code-item"><span style="background:#2d6a4f"></span> Academic</div>
                        <div class="color-code-item"><span style="background:#e6b800"></span> Non-Academic</div>
                        <div class="color-code-item"><span style="background:#e07b00"></span> Socio-Cultural</div>
                        <div class="color-code-item"><span style="background:#7d4e00"></span> Religious</div>
                        <div class="color-code-item"><span style="background:#c0392b"></span> Sports</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT: Process -->
        <div class="accred-right">

            <div class="accred-card process-card">
                <div class="accred-card-header">
                    <i class="fas fa-route"></i>
                    <h2>Accreditation Process</h2>
                </div>
                <div class="accred-card-body">

                    <!-- Phase 1 -->
                    <div class="phase-label">
                        <span><i class="fas fa-laptop"></i> Phase 1 — Initial Assessment (Online)</span>
                    </div>

                    <div class="process-steps">
                        <div class="process-step">
                            <div class="process-step-num">1</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Submit Requirements</div>
                                <p>Submit soft copies of required documents in <strong>PDF format</strong> using your organization's official email.</p>
                                <ul>
                                    <li>Each document in a separate file.</li>
                                    <li>All files organized into a single folder.</li>
                                    <li>Files arranged in specified sequence.</li>
                                </ul>
                                <div class="email-chip"><i class="fas fa-envelope"></i> plspaccreditation@gmail.com</div>
                            </div>
                        </div>

                        <div class="process-step">
                            <div class="process-step-num">2</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Assessment Result</div>
                                <p>Wait for the initial assessment result. You will be notified if any revisions are needed.</p>
                            </div>
                        </div>

                        <div class="process-step">
                            <div class="process-step-num">3</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Submit Revised Documents</div>
                                <p>If revisions are required, submit the updated documents. Wait for assessment result again to confirm if further revisions are necessary.</p>
                            </div>
                        </div>

                        <div class="process-step">
                            <div class="process-step-num">4</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Approval for Printing</div>
                                <p>Once all documents are finalized and accepted, you will receive confirmation allowing you to print the submitted documents.</p>
                            </div>
                        </div>

                        <div class="process-step last">
                            <div class="process-step-num">5</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Secure Signatories</div>
                                <p>Ensure that the necessary signatories — officers, chief advisers, or Dean — approve the documents.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 2 -->
                    <div class="phase-label" style="margin-top:1.5rem;">
                        <span><i class="fas fa-building"></i> Phase 2 — Final Assessment (Physical)</span>
                    </div>

                    <div class="process-steps">
                        <div class="process-step">
                            <div class="process-step-num">6</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Submit Hard Copy</div>
                                <p>Submit <strong>one (1) hard copy</strong> of the final documents to the OSDS/CIG Office for final evaluation.</p>
                            </div>
                        </div>

                        <div class="process-step">
                            <div class="process-step-num">7</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Final Document Review</div>
                                <p>Submitted hard copies undergo a final check. If documents pass, they will be forwarded for approval by the OSDS Director and VP of SLS.</p>
                            </div>
                        </div>

                        <div class="process-step">
                            <div class="process-step-num">8</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Notification of Approval</div>
                                <p>Once approved, you will be notified of the status and the release of your documents.</p>
                            </div>
                        </div>

                        <div class="process-step">
                            <div class="process-step-num">9</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Scan and Duplicate Documents</div>
                                <p>Before binding, scan all required documents (items 1–10), create duplicates, and send scanned PDFs to the provided email. Submit softbound copies as well.</p>
                            </div>
                        </div>

                        <div class="process-step last">
                            <div class="process-step-num">10</div>
                            <div class="process-step-body">
                                <div class="process-step-title">Awarding of Accreditation Certificate <i class="fas fa-award" style="color:#f59e0b;margin-left:6px;"></i></div>
                                <p>Once accreditation is granted, the organization will be officially recognized and will be able to schedule EED activities and events as per its approved calendar and plan.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Prepared By Card -->
            <div class="accred-card">
                <div class="accred-card-header">
                    <i class="fas fa-users"></i>
                    <h2>Document Authority</h2>
                </div>
                <div class="accred-card-body">
                    <div class="authority-grid">
                        <div class="authority-item">
                            <div class="authority-role">Prepared by</div>
                            <div class="authority-name">Ms. Nyka Elaine Marasigan</div>
                            <div class="authority-pos">Director, CIG Compliance Division</div>
                        </div>
                        <div class="authority-item">
                            <div class="authority-role">Reviewed by</div>
                            <div class="authority-name">Mr. Mark Anthony A. Patal</div>
                            <div class="authority-pos">Chief Officer, CIG</div>
                        </div>
                        <div class="authority-item">
                            <div class="authority-role">Noted by</div>
                            <div class="authority-name">Mr. Paul Adrian Avecilla, LPT, RPm</div>
                            <div class="authority-pos">Chief Adviser, CIG</div>
                        </div>
                        <div class="authority-item">
                            <div class="authority-role">Endorsed by</div>
                            <div class="authority-name">Mr. Jeroze Jose A. Reyes, JD</div>
                            <div class="authority-pos">Director, Student Development Services</div>
                        </div>
                        <div class="authority-item approved">
                            <div class="authority-role">Approved by</div>
                            <div class="authority-name">Mr. Randall B. Pasco, DBA</div>
                            <div class="authority-pos">VP for Student Life and Success</div>
                        </div>
                    </div>
                    <div class="authority-ref">
                        <i class="fas fa-link"></i> References: OVPSLS Form 1 / Letter of Intent · PLSP Student Handbook 2018
                    </div>
                </div>
            </div>

        </div>
    </div><!-- /.accred-layout -->

</div><!-- /.accred-container -->
</main>

<script src="../js/script.js"></script>
<script src="../js/navbar.js"></script>
<script src="../js/notifications.js"></script>
</body>
</html>