<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db_connection.php';
header('Content-Type: application/json');

$orgId    = (int)$_SESSION['user_id'];
$memberId = (int)($_POST['member_id'] ?? 0);

if (!$memberId) {
    echo json_encode(['success'=>false,'message'=>'Invalid member ID.']); exit();
}

$stmt = mysqli_prepare($conn,
    "DELETE FROM org_members WHERE member_id=? AND org_id=?");
mysqli_stmt_bind_param($stmt,'ii',$memberId,$orgId);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($affected > 0) {
    echo json_encode(['success'=>true,'message'=>'Member removed.']);
} else {
    echo json_encode(['success'=>false,'message'=>'Member not found or already removed.']);
}
?>