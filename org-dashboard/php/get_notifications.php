<?php
/**
 * get_notifications.php
 * Returns unread + recent notifications for the logged-in user.
 * Located at: cig_user/org-dashboard/php/get_notifications.php
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'notifications' => []]);
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$user_id = (int) $_SESSION['user_id'];

// Fetch latest 20 notifications for this user, newest first
$stmt = $conn->prepare("
    SELECT notification_id, title, message, type, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id'         => $row['notification_id'],
        'title'      => $row['title'],
        'message'    => $row['message'],
        'type'       => $row['type'],       // 'success', 'error', 'info', 'warning'
        'is_read'    => (bool) $row['is_read'],
        'created_at' => $row['created_at'],
    ];
}

$stmt->close();
mysqli_close($conn);

echo json_encode(['success' => true, 'notifications' => $notifications]);