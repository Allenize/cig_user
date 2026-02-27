<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Tracking - OrgHub</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Core Styles -->
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/document_tracking.css">
      <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/notifications.css">
</head>
<body>
    <!-- Include navbar -->
    <?php include '../php/navbar.php'; ?>
    <?php include '../php/topbar.php'; ?>

    <!-- Main content -->
    <main class="main-content">
    

        <div class="document-container">
            <!-- Header with Upload Button -->
            <div class="document-header">
                <h1><i class="fas fa-folder-open"></i> Documents</h1>
                <button class="btn-upload" id="openUploadModal"><i class="fas fa-cloud-upload-alt"></i> Upload Document</button>
            </div>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by title or category...">
                </div>
                <div class="filter-wrapper">
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                    <input type="date" id="dateFilter" placeholder="Filter by date">
                </div>
            </div>

            <!-- Documents Table -->
            <div class="table-responsive">
                <table class="documents-table" id="documentsTable">
                    <thead>
                        <tr>
                            <th>Document Title</th>
                            <th>Date Submitted</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th>Admin Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sample Data Rows (same as before) -->
                        <tr>
                            <td>Q1 Financial Report</td>
                            <td>2026-02-15</td>
                            <td>John Santos</td>
                            <td><span class="status-badge pending">Pending</span></td>
                            <td>Awaiting review</td>
                        </tr>
                        <tr>
                            <td>Event Proposal - Outreach</td>
                            <td>2026-02-10</td>
                            <td>Maria Reyes</td>
                            <td><span class="status-badge approved">Approved</span></td>
                            <td>Approved with minor notes</td>
                        </tr>
                        <tr>
                            <td>Meeting Minutes - Feb 5</td>
                            <td>2026-02-07</td>
                            <td>Robert Lim</td>
                            <td><span class="status-badge approved">Approved</span></td>
                            <td>No issues</td>
                        </tr>
                        <tr>
                            <td>New Member Policy</td>
                            <td>2026-02-01</td>
                            <td>Anna Villanueva</td>
                            <td><span class="status-badge rejected">Rejected</span></td>
                            <td>Needs revision: update section 3</td>
                        </tr>
                        <tr>
                            <td>Budget Proposal 2026</td>
                            <td>2026-01-28</td>
                            <td>Carlos Mendoza</td>
                            <td><span class="status-badge pending">Pending</span></td>
                            <td>Under review</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Upload Document Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content upload-modal-content">
            <span class="close-modal" id="closeUploadModal">&times;</span>
            <h2><i class="fas fa-cloud-upload-alt"></i> Upload Document</h2>
            
            <!-- Tab Navigation -->
            <div class="upload-tabs">
                <button class="tab-button active" data-tab="regular-upload">
                    <i class="fas fa-file-upload"></i> Regular Upload
                </button>
                <button class="tab-button" data-tab="template-upload">
                    <i class="fas fa-file-contract"></i> Use Template
                </button>
            </div>

            <!-- Regular Upload Tab -->
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
                        <small>Allowed: PDF, DOCX, XLSX (Max 10MB)</small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Submit Document</button>
                        <button type="button" class="btn-cancel" id="cancelUpload">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Template Upload Tab -->
            <div id="template-upload" class="tab-content">
                <form id="templateForm">
                    <div class="form-group">
                        <label for="documentTitle">Document Title <span>*</span></label>
                        <input type="text" id="documentTitle" name="document_title" placeholder="Enter a title for this document" required>
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
                            <option value="CIG LOGO.png">CIG Logo</option>
                            <option value="OSAS LOGO.png">OSAS Logo</option>
                        </select>
                        <small>Select a collaborated organization logo to appear on the right side of the document header</small>
                    </div>
                    
                    <!-- Header Customization -->
                    <div class="form-group">
                        <label for="organizationName">Organization Name <span>*</span></label>
                        <input type="text" id="organizationName" name="organization_name" placeholder="e.g., PLSP Economics Society – EcoS" value="PLSP Economics Society – EcoS" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="organizationTagline">Organization Tagline <span>*</span></label>
                        <input type="text" id="organizationTagline" name="organization_tagline" placeholder='e.g., "Empowered and committed organization of service."' value="Empowered and committed organization of service." required>
                    </div>
                    
                    <!-- Template Fields Container -->
                    <div id="templateFieldsContainer" class="template-fields-container"></div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Generate & Download DOCX</button>
                        <button type="button" class="btn-cancel" id="cancelTemplate">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- External JavaScript -->
    <script src="../js/script.js"></script> <!-- sidebar toggle -->
    <script src="../js/document_tracking.js"></script>
</body>
</html>