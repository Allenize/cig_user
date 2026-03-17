<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit();
}

require_once __DIR__ . '/db_connection.php';
$userId   = (int)$_SESSION['user_id'];
$docKey   = trim($_POST['doc_key']   ?? '');
$docLabel = trim($_POST['doc_label'] ?? '');
$docSeq   = (int)($_POST['doc_seq']  ?? 0);
$docPhase = (int)($_POST['doc_phase'] ?? 1);
$file     = $_FILES['doc_file'] ?? null;

if (!$docKey || !$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'Invalid upload.']); exit();
}
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
    echo json_encode(['success'=>false,'message'=>'Only PDF files are accepted.']); exit();
}
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success'=>false,'message'=>'File too large. Max 10 MB.']); exit();
}

// Save to uploads/accreditation/{user_id}/
$uploadDir = dirname(dirname(__DIR__)) . '/uploads/accreditation/' . $userId . '/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$safeName = preg_replace('/[^a-z0-9_\-]/', '_', strtolower($docKey)) . '_' . time() . '.pdf';
$destPath = $uploadDir . $safeName;
$relPath  = 'uploads/accreditation/' . $userId . '/' . $safeName;
$mimeType = 'application/pdf';
$fileSize = $file['size'];

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success'=>false,'message'=>'Could not save file.']); exit();
}

// Upsert into documents table
$stmt = mysqli_prepare($conn,
    "INSERT INTO `documents`
        (user_id, doc_key, doc_label, file_name, file_path, file_size, mime_type,
         doc_status, doc_phase, doc_seq, uploaded_at, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted', ?, ?, NOW(), NOW())
     ON DUPLICATE KEY UPDATE
        file_name   = VALUES(file_name),
        file_path   = VALUES(file_path),
        file_size   = VALUES(file_size),
        doc_status  = 'submitted',
        uploaded_at = NOW()"
);
mysqli_stmt_bind_param($stmt, 'issssiiii',
    $userId, $docKey, $docLabel, $safeName, $relPath, $fileSize, $mimeType, $docPhase, $docSeq
);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    echo json_encode(['success'=>true, 'file_name'=>$safeName, 'message'=>'Uploaded successfully.']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Database error: ' . mysqli_error($conn)]);
}
?>