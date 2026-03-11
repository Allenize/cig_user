<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit(); }

header('Content-Type: application/json');
require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$data      = json_decode(file_get_contents('php://input'), true);
$report_id = (int) ($data['report_id'] ?? 0);
$user_id   = (int) $_SESSION['user_id'];

if (!$report_id) {
    echo json_encode(['success' => false]);
    exit();
}

// Only allow deleting own reports
$q = mysqli_query($conn, "
    DELETE FROM reports
    WHERE report_id = $report_id
      AND generated_by = $user_id
");

echo json_encode(['success' => (bool) $q, 'affected' => mysqli_affected_rows($conn)]);
mysqli_close($conn);