<?php
/**
 * get_docx.php
 * Mode: binary = serve raw DOCX
 * Mode: images = return JSON map of all embedded images as base64
 */
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

$bytes = $row['file_content'];

// ── Serve raw binary ─────────────────────────────────────────────────────────
if ($mode === 'binary') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-cache');
    echo $bytes;
    exit;
}

// ── Extract all images from DOCX as base64 JSON ──────────────────────────────
if ($mode === 'images') {
    $tmp = tempnam(sys_get_temp_dir(), 'docx_');
    file_put_contents($tmp, $bytes);
    unset($bytes);

    $zip = new ZipArchive();
    if ($zip->open($tmp) !== true) {
        unlink($tmp);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Cannot open ZIP']);
        exit;
    }

    $images = [];
    $mimeMap = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'wmf'  => 'image/x-wmf',
        'emf'  => 'image/x-emf',
        'svg'  => 'image/svg+xml',
        'bmp'  => 'image/bmp',
    ];

    // Also read relationships to map rId -> filename
    $relsMap = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'word/_rels/') === 0 && substr($name, -5) === '.rels') {
            $xml = $zip->getFromIndex($i);
            preg_match_all('/<Relationship[^>]+Id="([^"]+)"[^>]+Target="([^"]+)"/', $xml, $m, PREG_SET_ORDER);
            foreach ($m as $match) {
                $target = $match[2];
                if (strpos($target, 'media/') !== false) {
                    $relsMap[$match[1]] = basename($target);
                }
            }
        }
    }

    // Extract all media files
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'word/media/') === 0) {
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = $mimeMap[$ext] ?? 'image/png';
            $data = $zip->getFromIndex($i);
            $base = basename($name);
            $images[$base] = [
                'mime'   => $mime,
                'base64' => base64_encode($data),
            ];
        }
    }

    $zip->close();
    unlink($tmp);

    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    header('Cache-Control: private, max-age=3600');
    echo json_encode(['images' => $images, 'rels' => $relsMap]);
    exit;
}

http_response_code(400);
die("Unknown mode");