<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// get_announcements.php is at cig_user/org-dashboard/php/
// db_connection.php is at cig_user/db_connection.php
require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$result = mysqli_query($conn, "
    SELECT a.announcement_id, a.title, a.content, a.created_at, u.full_name as created_by
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.user_id
    WHERE a.is_active = 1
    ORDER BY a.created_at DESC
    LIMIT 5
");

$announcements = [];
while ($row = mysqli_fetch_assoc($result)) {
    $announcements[] = [
        'id'         => $row['announcement_id'],
        'title'      => $row['title'],
        'content'    => $row['content'],
        'created_by' => $row['created_by'] ?? 'Admin',
        'created_at' => date('M d, Y', strtotime($row['created_at']))
    ];
}

echo json_encode(['success' => true, 'announcements' => $announcements]);
mysqli_close($conn);
?>