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
    <title>Archive - OrgHub</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Core Styles -->
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="archive.css">
    <link rel="stylesheet" href="topbar.css">  
     <link rel="stylesheet" href="notifications.css">  
</head>
<body>
    <!-- Include navbar -->
    <?php include 'navbar.php'; ?>
    <?php include 'topbar.php'; ?>

    <!-- Main content -->
    <main class="main-content">


        <div class="archive-container">
            <!-- Header -->
            <div class="archive-header">
                <h1><i class="fas fa-archive"></i> Archive</h1>
                <p class="archive-subtitle">Completed, approved, and inactive records</p>
            </div>

            <!-- Tabs -->
            <div class="archive-tabs">
                <button class="tab-btn active" data-tab="all">All Archives</button>
                <button class="tab-btn" data-tab="documents">Archived Documents</button>
                <button class="tab-btn" data-tab="reports">Archived Reports</button>
                <button class="tab-btn" data-tab="events">Archived Events</button>
            </div>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by title or category...">
                </div>
                <div class="filter-wrapper">
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="Document">Document</option>
                        <option value="Report">Report</option>
                        <option value="Event">Event</option>
                    </select>
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Completed">Completed</option>
                        <option value="Approved">Approved</option>
                        <option value="Closed">Closed</option>
                    </select>
                    <input type="date" id="dateFilter" placeholder="Filter by archive date">
                </div>
            </div>

            <!-- Archive Table -->
            <div class="table-responsive">
                <table class="archive-table" id="archiveTable">
                    <thead>
                        <tr>
                            <th>Title / Name</th>
                            <th>Category</th>
                            <th>Date Archived</th>
                            <th>Original Submission Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sample Data Rows (archived items) -->
                        <tr data-category="Document" data-status="Approved">
                            <td>Annual Report 2025</td>
                            <td>Document</td>
                            <td>2026-01-15</td>
                            <td>2025-12-20</td>
                            <td><span class="status-badge approved">Approved</span></td>
                            <td class="actions">
                                <button class="btn-view" onclick="viewArchiveItem(this)"><i class="fas fa-eye"></i> View</button>
                            </td>
                        </tr>
                        <tr data-category="Report" data-status="Completed">
                            <td>Q4 Financial Report</td>
                            <td>Report</td>
                            <td>2026-01-10</td>
                            <td>2026-01-05</td>
                            <td><span class="status-badge completed">Completed</span></td>
                            <td class="actions">
                                <button class="btn-view" onclick="viewArchiveItem(this)"><i class="fas fa-eye"></i> View</button>
                            </td>
                        </tr>
                        <tr data-category="Event" data-status="Closed">
                            <td>Outreach Program 2025</td>
                            <td>Event</td>
                            <td>2025-12-18</td>
                            <td>2025-11-30</td>
                            <td><span class="status-badge closed">Closed</span></td>
                            <td class="actions">
                                <button class="btn-view" onclick="viewArchiveItem(this)"><i class="fas fa-eye"></i> View</button>
                            </td>
                        </tr>
                        <tr data-category="Document" data-status="Approved">
                            <td>Membership Policy v2</td>
                            <td>Document</td>
                            <td>2025-12-01</td>
                            <td>2025-11-15</td>
                            <td><span class="status-badge approved">Approved</span></td>
                            <td class="actions">
                                <button class="btn-view" onclick="viewArchiveItem(this)"><i class="fas fa-eye"></i> View</button>
                            </td>
                        </tr>
                        <tr data-category="Report" data-status="Completed">
                            <td>Volunteer Hours Report</td>
                            <td>Report</td>
                            <td>2025-11-20</td>
                            <td>2025-11-18</td>
                            <td><span class="status-badge completed">Completed</span></td>
                            <td class="actions">
                                <button class="btn-view" onclick="viewArchiveItem(this)"><i class="fas fa-eye"></i> View</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeViewModal">&times;</span>
            <h2><i class="fas fa-file-alt"></i> Archived Item Details</h2>
            <div id="viewModalBody" class="modal-body">
                <!-- Dynamic content will be inserted here -->
            </div>
        </div>
    </div>

    <!-- External JavaScript -->
    <script src="script.js"></script> <!-- sidebar toggle -->
    <script src="archive.js"></script>
</body>
</html>