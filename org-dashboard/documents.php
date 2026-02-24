<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documents · Organization</title>
  <!-- Google Fonts - Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Shared styles -->
  <link rel="stylesheet" href="shared.css">
  <!-- Documents specific styles -->
  <link rel="stylesheet" href="documents.css">
</head>
<body>
  <div class="dashboard">

    <!-- Main Content -->
    <main class="main-content">


      <div class="content">
        <div class="page-header">
          <h1>Documents Management</h1>
          <button class="btn btn-primary" id="uploadDocBtn"><i class="fas fa-upload"></i> Upload Document</button>
        </div>

        <!-- Pinned Documents Section -->
        <section class="pinned-section">
          <h2><i class="fas fa-thumbtack"></i> Pinned / Important</h2>
          <div class="documents-grid" id="pinnedGrid">
            <!-- Pinned cards injected by JS -->
          </div>
        </section>

        <!-- Search & Filters -->
        <div class="filters-row">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by title or keyword...">
          </div>
          <div class="filter-group">
            <select id="filterCategory">
              <option value="">All Categories</option>
              <option value="Memorandum">Memorandum</option>
              <option value="Forms">Forms</option>
              <option value="Templates">Templates</option>
              <option value="Reports">Reports</option>
              <option value="Policies">Policies</option>
            </select>
            <select id="filterDate">
              <option value="">All Dates</option>
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
            </select>
            <select id="filterType">
              <option value="">All Types</option>
              <option value="pdf">PDF</option>
              <option value="docx">DOCX</option>
              <option value="xlsx">XLSX</option>
            </select>
          </div>
        </div>

        <!-- All Documents Grid -->
        <section class="all-documents">
          <h2>All Documents</h2>
          <div class="documents-grid" id="documentsGrid">
            <!-- Cards injected by JS -->
          </div>
        </section>
      </div>
    </main>
  </div>

  <!-- Overlay for mobile sidebar -->
  <div class="overlay" id="overlay"></div>

  <!-- Document Preview Modal -->
  <div class="modal" id="previewModal">
    <div class="modal-content modal-lg">
      <div class="modal-header">
        <h2 id="previewTitle">Document Preview</h2>
        <span class="close-modal" id="closePreview">&times;</span>
      </div>
      <div class="modal-body">
        <div class="preview-container">
          <div class="preview-details">
            <p><strong>Description:</strong> <span id="previewDesc"></span></p>
            <p><strong>Uploaded By:</strong> <span id="previewUploader"></span></p>
            <p><strong>Upload Date:</strong> <span id="previewDate"></span></p>
            <p><strong>File Size:</strong> <span id="previewSize"></span></p>
          </div>
          <div class="preview-embed">
            <iframe id="previewIframe" src="" width="100%" height="400px" style="border: 1px solid #e2e8f0; border-radius: 8px;"></iframe>
            <p class="preview-note">(PDF preview simulation)</p>
          </div>
        </div>
        <div class="modal-actions">
          <a href="#" id="downloadFromPreview" class="btn btn-primary"><i class="fas fa-download"></i> Download</a>
          <button class="btn btn-outline" id="closePreviewBtn">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Upload Document Modal -->
  <div class="modal" id="uploadModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="uploadModalTitle">Upload Document</h2>
        <span class="close-modal" id="closeUpload">&times;</span>
      </div>
      <form id="uploadForm" enctype="multipart/form-data">
        <div class="form-group">
          <label for="docTitle">Document Title *</label>
          <input type="text" id="docTitle" required>
        </div>
        <div class="form-group">
          <label for="docCategory">Category *</label>
          <select id="docCategory" required>
            <option value="">Select Category</option>
            <option value="Memorandum">Memorandum</option>
            <option value="Forms">Forms</option>
            <option value="Templates">Templates</option>
            <option value="Reports">Reports</option>
            <option value="Policies">Policies</option>
          </select>
        </div>
        <div class="form-group">
          <label for="docDescription">Description (optional)</label>
          <textarea id="docDescription" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label for="relatedEvent">Related Event (optional)</label>
          <select id="relatedEvent">
            <option value="">None</option>
            <!-- populated by JS -->
          </select>
        </div>
        <div class="form-group">
          <label for="docFile">File (PDF, DOCX, XLSX) *</label>
          <input type="file" id="docFile" accept=".pdf,.docx,.xlsx" required>
          <small class="file-hint">Max 10MB</small>
        </div>
        <div class="modal-actions">
          <button type="submit" class="btn btn-primary">Submit for Review</button>
          <button type="button" class="btn btn-outline" id="cancelUpload">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Shared sidebar toggle script -->
  <script src="shared.js"></script>
  <!-- Documents specific script -->
  <script src="documents.js"></script>
</body>
</html>