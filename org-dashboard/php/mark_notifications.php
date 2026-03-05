<?php
/**
 * mark_notifications_read.php
 * Marks one or all notifications as read for the logged-in user.
 * Located at: cig_user/org-dashboard/php/mark_notifications_read.php
 *
 * POST body (JSON):
 *   { "id": 5 }         — mark single notification read
 *   { "all": true }     — mark all read
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$user_id = (int) $_SESSION['user_id'];
$body    = json_decode(file_get_contents('php://input'), true);

if (!empty($body['all'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
} elseif (!empty($body['id'])) {
    $notif_id = (int) $body['id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $user_id);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$stmt->execute();
$stmt->close();
mysqli_close($conn);

echo json_encode(['success' => true]);