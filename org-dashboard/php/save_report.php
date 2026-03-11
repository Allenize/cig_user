<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit(); }

header('Content-Type: application/json');
require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$data        = json_decode(file_get_contents('php://input'), true);
$user_id     = (int) $_SESSION['user_id'];
$title       = mysqli_real_escape_string($conn, $data['title']       ?? '');
$type        = mysqli_real_escape_string($conn, $data['report_type'] ?? 'overview');
$description = mysqli_real_escape_string($conn, $data['description'] ?? '');
$json_data   = mysqli_real_escape_string($conn, $data['data']        ?? '{}');

if (!$title) {
    echo json_encode(['success' => false, 'error' => 'Title required']);
    exit();
}

$q = mysqli_query($conn, "
    INSERT INTO reports (title, description, report_type, generated_by, data)
    VALUES ('$title', '$description', '$type', $user_id, '$json_data')
");

if ($q) {
    $id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'report'  => [
            'report_id'   => $id,
            'title'       => htmlspecialchars($data['title']),
            'report_type' => $type,
            'created_at'  => date('M d, Y · h:i A'),
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
mysqli_close($conn);