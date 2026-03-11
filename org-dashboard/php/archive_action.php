<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit(); }

header('Content-Type: application/json');
require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$id     = (int) ($data['submission_id'] ?? 0);
$org_id = (int) $_SESSION['user_id'];

if (!$id || !in_array($action, ['restore', 'delete'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

// Always verify the submission belongs to this org
$check = mysqli_query($conn, "SELECT submission_id, file_path FROM submissions WHERE submission_id = $id AND org_id = $org_id AND status = 'archived' LIMIT 1");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'error' => 'Not found or not authorised']);
    exit();
}
$row = mysqli_fetch_assoc($check);

if ($action === 'restore') {
    // Set status back to pending so the org can resubmit / admin can review
    $q = mysqli_query($conn, "UPDATE submissions SET status = 'pending', updated_at = NOW() WHERE submission_id = $id AND org_id = $org_id");
    echo json_encode(['success' => (bool)$q]);

} elseif ($action === 'delete') {
    // Delete related documents records first (FK)
    mysqli_query($conn, "DELETE FROM documents WHERE submission_id = $id");

    // Delete the physical file if it exists
    $filePath = $row['file_path'] ?? '';
    if ($filePath) {
        $fullPath = dirname(dirname(__DIR__)) . '/' . ltrim($filePath, './');
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    // Delete the submission
    $q = mysqli_query($conn, "DELETE FROM submissions WHERE submission_id = $id AND org_id = $org_id");
    echo json_encode(['success' => (bool)$q]);
}

mysqli_close($conn);