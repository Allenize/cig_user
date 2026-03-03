<?php
ini_set('memory_limit', '256M');
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); die("Not logged in"); }

$id   = isset($_GET['id'])   ? (int)$_GET['id']  : 0;
$mode = isset($_GET['mode']) ? $_GET['mode']      : 'binary';
if (!$id) { http_response_code(400); die("No ID"); }

$conn   = mysqli_connect("localhost", "root", "", "cig_system");
$result = mysqli_query($conn, "SELECT file_content, file_name, submitted_by FROM submissions WHERE submission_id=$id LIMIT 1");
$row    = mysqli_fetch_assoc($result);
mysqli_close($conn);

if (!$row)                                                   { http_response_code(404); die("Not found"); }
if ((int)$row['submitted_by'] !== (int)$_SESSION['user_id']) { http_response_code(403); die("Access denied"); }
if (empty($row['file_content']))                             { http_response_code(404); die("No content"); }

// Serve raw binary
if ($mode === 'binary') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . strlen($row['file_content']));
    header('Cache-Control: no-cache');
    echo $row['file_content'];
    exit;
}

// Serve a single image by filename
if ($mode === 'img') {
    $filename = isset($_GET['file']) ? basename($_GET['file']) : '';
    if (!$filename) { http_response_code(400); die("No file"); }

    $tmp = tempnam(sys_get_temp_dir(), 'docx_');
    file_put_contents($tmp, $row['file_content']);
    unset($row['file_content']);

    $zip = new ZipArchive();
    if ($zip->open($tmp) !== true) { unlink($tmp); http_response_code(500); die("ZIP error"); }

    $data = $zip->getFromName('word/media/' . $filename);
    $zip->close();
    unlink($tmp);

    if ($data === false) { http_response_code(404); die("Image not found: $filename"); }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','bmp'=>'image/bmp','svg'=>'image/svg+xml'];
    $mime = $mimeMap[$ext] ?? 'image/jpeg';

    while (ob_get_level()) ob_end_clean();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: private, max-age=3600');
    header('Access-Control-Allow-Credentials: true');
    echo $data;
    exit;
}

// List all media files as JSON
if ($mode === 'list') {
    $tmp = tempnam(sys_get_temp_dir(), 'docx_');
    file_put_contents($tmp, $row['file_content']);
    unset($row['file_content']);

    $zip = new ZipArchive();
    $zip->open($tmp);

    $files = [];
    // Also parse relationships to know which images go in header
    $headerImageNames = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        // Get header rels
        if (preg_match('/word\/_rels\/(header\d*)\.xml\.rels/i', $name)) {
            $xml = $zip->getFromIndex($i);
            preg_match_all('/Id="([^"]+)"[^>]+Target="media\/([^"]+)"/', $xml, $m, PREG_SET_ORDER);
            foreach ($m as $match) {
                $headerImageNames[] = $match[2];
            }
        }
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'word/media/') === 0) {
            $basename = basename($name);
            $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $files[]  = [
                'name'     => $basename,
                'ext'      => $ext,
                'inHeader' => in_array($basename, $headerImageNames),
            ];
        }
    }
    $zip->close();
    unlink($tmp);

    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['files' => $files, 'headerImages' => $headerImageNames]);
    exit;
}

http_response_code(400); die("Unknown mode");