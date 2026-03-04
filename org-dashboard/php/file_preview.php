<?php
/**
 * file_preview.php
 * Streams a stored BLOB file from the submissions table to the browser.
 * Supports: PDF (inline), DOCX (binary for docx-preview.js), XLSX (download)
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized");
}

$conn = mysqli_connect("localhost", "root", "", "cig_system");
if (!$conn) {
    http_response_code(500);
    die("Connection failed");
}

$submissionId = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
$download     = isset($_GET['download']) && $_GET['download'] === '1';

if (!$submissionId) {
    http_response_code(400);
    die("Invalid submission ID");
}

// Fetch file metadata
$stmt = $conn->prepare("SELECT file_name, file_path, submitted_by FROM submissions WHERE submission_id = ? LIMIT 1");
$stmt->bind_param("i", $submissionId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { http_response_code(404); die("Document not found"); }
if ((int)$row['submitted_by'] !== (int)$_SESSION['user_id']) { http_response_code(403); die("Access denied"); }

$fileName = $row['file_name'];
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Read file from disk (files stored on disk via file_path)
$conn->close();
$fileBytes = null;
$diskPath  = $row['file_path'];
// Resolve relative path (e.g. ../uploads/submissions/file.pdf)
if (!empty($diskPath) && !file_exists($diskPath)) {
    $resolved = realpath(__DIR__ . '/' . $diskPath);
    if ($resolved !== false && file_exists($resolved)) $diskPath = $resolved;
}
if (!empty($diskPath) && file_exists($diskPath)) {
    $fileBytes = file_get_contents($diskPath);
}

if (empty($fileBytes)) {
    http_response_code(404);
    die("File content not found");
}

$mimeMap = [
    'pdf'  => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'doc'  => 'application/msword',
    'xls'  => 'application/vnd.ms-excel',
    'txt'  => 'text/plain',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];

// --- Output ---
while (ob_get_level()) { ob_end_clean(); }

if ($download) {
    // Force download with correct MIME
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
} elseif (in_array($ext, ['pdf','jpg','jpeg','png','gif','txt'])) {
    // Browser can render these natively inline
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . addslashes($fileName) . '"');
} else {
    // DOCX/XLSX — served as raw binary for JS fetch(), no Content-Disposition
    // so the browser never triggers a download dialog
    header('Content-Type: application/octet-stream');
}

header('Content-Length: ' . mb_strlen($fileBytes, '8bit'));
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');

echo $fileBytes;
exit;