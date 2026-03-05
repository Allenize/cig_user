<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$announcement_id = isset($input['announcement_id']) ? (int)$input['announcement_id'] : 0;

if (!$announcement_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid announcement ID']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$org_code = mysqli_real_escape_string($conn, $_SESSION['org_code'] ?? '');

// Check if already read
$check = mysqli_query($conn, 
    "SELECT read_id FROM announcement_reads 
     WHERE announcement_id = $announcement_id AND org_code = '$org_code'"
);

if (mysqli_num_rows($check) == 0) {
    // Insert read receipt
    $insert = mysqli_query($conn,
        "INSERT INTO announcement_reads (announcement_id, user_id, org_code, read_at)
         VALUES ($announcement_id, $user_id, '$org_code', NOW())"
    );
    
    if ($insert) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
} else {
    // Already read
    echo json_encode(['success' => true, 'already_read' => true]);
}

mysqli_close($conn);
?>