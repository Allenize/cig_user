<?php
session_start();
// Allow both admin and org users to view their own docs
if (!isset($_SESSION['user_id'])) { http_response_code(401); die("Unauthorized"); }

$docId    = isset($_GET['doc_id']) ? (int)$_GET['doc_id'] : 0;
$download = isset($_GET['download']) && $_GET['download'] === '1';
if (!$docId) { http_response_code(400); die("Invalid document ID"); }

require_once __DIR__ . '/db_connection.php';
if (!$conn) { http_response_code(500); die("DB error"); }

$stmt = mysqli_prepare($conn, "SELECT file_name, file_path, mime_type, user_id FROM documents WHERE document_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $docId);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$row) { http_response_code(404); die("Document not found"); }

// Non-admin can only view their own docs
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
if (!$isAdmin && (int)$row['user_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403); die("Access denied");
}

$fileName   = $row['file_name'];
$storedPath = $row['file_path'];
$userId     = (int)$row['user_id'];
$ext        = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$base_name  = basename($fileName);
$sep        = DIRECTORY_SEPARATOR;

// __DIR__ = C:\xampp\htdocs\cig_user\org-dashboard\php
// Files are saved by doc_upload.php to: dirname(dirname(__DIR__))/uploads/...
// which resolves to: C:\xampp\htdocs\cig_user\uploads\...
$base = dirname(dirname(__DIR__)); // C:\xampp\htdocs\cig_user
$sep  = DIRECTORY_SEPARATOR;

$candidates = [];
// 1. base + stored relative path
if ($storedPath) {
    $candidates[] = $base . $sep . str_replace(['/', '\\'], $sep, ltrim($storedPath, '/\\'));
}
// 2. base + uploads/accreditation/{uid}/{file}
$candidates[] = $base . $sep . 'uploads' . $sep . 'accreditation' . $sep . $userId . $sep . $base_name;
// 3. base + uploads/accreditation/{file}
$candidates[] = $base . $sep . 'uploads' . $sep . 'accreditation' . $sep . $base_name;
// 4. base + uploads/{file}
$candidates[] = $base . $sep . 'uploads' . $sep . $base_name;

// Find file
$diskPath = null;
foreach ($candidates as $c) {
    if (file_exists($c) && is_file($c)) { $diskPath = $c; break; }
}

if (!$diskPath) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    $globTest = glob($base . $sep . 'uploads' . $sep . 'accreditation' . $sep . $userId . $sep . '*');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>body{font-family:Arial,sans-serif;padding:1.5rem;background:#f9f9f9;}
    h3{color:#c0392b;}code{background:#f0f0f0;padding:2px 5px;border-radius:3px;font-size:.82em;display:block;margin:2px 0;word-break:break-all;}</style></head><body>
    <h3>File not found</h3>
    <p><b>file_name:</b> <code>' . htmlspecialchars($fileName) . '</code></p>
    <p><b>file_path:</b> <code>' . htmlspecialchars($storedPath ?? '') . '</code></p>
    <p><b>base:</b> <code>' . htmlspecialchars($base) . '</code></p>
    <p><b>base exists:</b> <code>' . (is_dir($base) ? 'YES' : 'NO — wrong path!') . '</code></p>
    <p><b>Tried:</b></p>';
    foreach ($candidates as $c) { echo '<code>' . htmlspecialchars($c) . ' [' . (file_exists($c)?'EXISTS':'missing') . ']</code>'; }
    if ($globTest !== false) {
        echo '<p><b>Files in uploads/accreditation/' . $userId . '/:</b></p>';
        if (empty($globTest)) { echo '<code>(folder empty or not found)</code>'; }
        foreach ($globTest as $g) { echo '<code>' . htmlspecialchars($g) . '</code>'; }
    }
    echo '</body></html>';
    exit;
}

$mimeMap = ['pdf'=>'application/pdf','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'doc'=>'application/msword','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'];
$mime = $row['mime_type'] ?: ($mimeMap[$ext] ?? 'application/octet-stream');

while (ob_get_level()) ob_end_clean();
header('Content-Type: '   . $mime);
header('Content-Length: ' . filesize($diskPath));
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: private, max-age=300');
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . rawurlencode($fileName) . '"');
readfile($diskPath);
exit;