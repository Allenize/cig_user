<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$org_code = mysqli_real_escape_string($conn, $_SESSION['org_code'] ?? '');

// Fetch active, non-expired announcements targeted at this org (or all orgs)
$result = mysqli_query($conn, "
    SELECT a.announcement_id, a.title, a.content,
           a.priority, a.category, a.audience,
           a.is_pinned, a.expires_at, a.created_at,
           u.full_name AS created_by
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.user_id
    WHERE a.is_active = 1
      AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())
      AND (a.audience IS NULL OR a.audience = ''
           OR FIND_IN_SET('$org_code', a.audience) > 0)
    ORDER BY a.is_pinned DESC,
             FIELD(a.priority,'urgent','high','low'),
             a.created_at DESC
    LIMIT 10
");

$announcements = [];
while ($row = mysqli_fetch_assoc($result)) {
    $announcements[] = [
        'id'         => $row['announcement_id'],
        'title'      => $row['title'],
        'content'    => $row['content'],
        'priority'   => $row['priority']  ?? 'low',
        'category'   => $row['category']  ?? 'general',
        'audience'   => $row['audience']  ?? '',
        'is_pinned'  => (bool) $row['is_pinned'],
        'expires_at' => $row['expires_at'] ?? null,
        'created_by' => $row['created_by'] ?? 'Admin',
        'created_at' => $row['created_at'],
    ];
}

echo json_encode(['success' => true, 'announcements' => $announcements]);
mysqli_close($conn);