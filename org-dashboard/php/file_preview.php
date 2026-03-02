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
// Use latin1 so MySQLi doesn't re-encode binary BLOB bytes as UTF-8
mysqli_set_charset($conn, 'latin1');

$submissionId = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
$download     = isset($_GET['download']) && $_GET['download'] === '1';

if (!$submissionId) {
    http_response_code(400);
    die("Invalid submission ID");
}

// Fetch the BLOB + metadata
$stmt = $conn->prepare(
    "SELECT file_name, file_content, file_path, submitted_by
     FROM submissions
     WHERE submission_id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $submissionId);
$stmt->execute();
$result = $stmt->get_result();
$row    = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) {
    http_response_code(404);
    die("Document not found");
}

// Only owner can view/download
if ((int)$row['submitted_by'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    die("Access denied");
}

$fileName = $row['file_name'];
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// ── Resolve file bytes: BLOB first, then disk fallback ──────────────────────
$fileBytes = null;

if (!empty($row['file_content'])) {
    // New rows: content stored as BLOB
    $fileBytes = $row['file_content'];
} elseif (!empty($row['file_path']) && file_exists($row['file_path'])) {
    // Legacy rows: content stored on disk
    $fileBytes = file_get_contents($row['file_path']);
} else {
    // Try common upload directories as a last resort
    $guessDir  = dirname(__DIR__) . '/uploads/';
    $guessPath = $guessDir . basename($fileName);
    if (file_exists($guessPath)) {
        $fileBytes = file_get_contents($guessPath);
    }
}

if ($fileBytes === null || $fileBytes === false) {
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
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

// Decide inline vs attachment
$inlineTypes = ['pdf', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
$disposition = ($download || !in_array($ext, $inlineTypes))
    ? 'attachment'
    : 'inline';

// DOCX is fetched by JS (docx-preview) – always send as binary attachment-friendly
if ($ext === 'docx' && !$download) {
    $disposition = 'inline'; // still inline so fetch() can grab it
}

// --- Output ---
// Prevent output-buffer from corrupting binary — must happen BEFORE any header
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: '        . $mime);
header('Content-Length: '      . mb_strlen($fileBytes, '8bit'));
header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($fileName) . '"');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('Pragma: public');

echo $fileBytes;
exit;