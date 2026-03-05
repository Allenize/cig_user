<?php
/**
 * get_notification_count.php
 * Returns the unread notification count for the logged-in org.
 * Called by the dashboard every 30 s via fetch().
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['unread_count' => 0]);
    exit();
}

header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$unread = 0;

if ($conn) {
    $org_id = (int) $_SESSION['user_id'];

    // 1. Count unseen approved/rejected submissions
    $r = mysqli_query($conn, "
        SELECT COUNT(*) AS cnt
        FROM submissions
        WHERE org_id = $org_id
          AND status IN ('approved', 'rejected')
          AND notified = 0
    ");
    if ($r) {
        $unread = (int) mysqli_fetch_assoc($r)['cnt'];
    }

    // 2. Mark them all as seen immediately — resets count to 0 on next call/refresh
    if ($unread > 0) {
        mysqli_query($conn, "
            UPDATE submissions
            SET notified = 1
            WHERE org_id = $org_id
              AND status IN ('approved', 'rejected')
              AND notified = 0
        ");
    }

    mysqli_close($conn);
}

echo json_encode(['unread_count' => $unread]);