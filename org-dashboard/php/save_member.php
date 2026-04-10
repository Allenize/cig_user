<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db_connection.php';
header('Content-Type: application/json');

$orgId = (int)$_SESSION['user_id'];

$memberId = (int)($_POST['member_id'] ?? 0);
$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email']     ?? '');
$phone    = trim($_POST['phone']     ?? '');
$position = trim($_POST['position']  ?? 'member');
$program  = trim($_POST['program']   ?? '');
$status   = trim($_POST['status']    ?? 'active');

// Validate
if ($fullName === '') {
    echo json_encode(['success'=>false,'message'=>'Full name is required.']); exit();
}

$allowedPositions = ['president','vice_president','secretary','treasurer',
                     'auditor','pio','representative','member','adviser'];
if (!in_array($position, $allowedPositions)) $position = 'member';
if (!in_array($status, ['active','inactive'])) $status = 'active';

if ($memberId > 0) {
    // ── Update existing member ────────────────────────────────────────────
    // Verify belongs to this org
    $chk = mysqli_prepare($conn, "SELECT member_id, position FROM org_members WHERE member_id=? AND org_id=? LIMIT 1");
    mysqli_stmt_bind_param($chk, 'ii', $memberId, $orgId);
    mysqli_stmt_execute($chk);
    $chkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
    if (!$chkRow) {
        echo json_encode(['success'=>false,'message'=>'Member not found.']); exit();
    }
    mysqli_stmt_close($chk);

    // President is managed by super admin only
    if ($chkRow['position'] === 'president') {
        echo json_encode(['success'=>false,'message'=>'The president record is managed by the super admin and cannot be edited here.']); exit();
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE org_members SET full_name=?,email=?,phone=?,position=?,program=?,status=? WHERE member_id=? AND org_id=?");
    mysqli_stmt_bind_param($stmt,'ssssssii',
        $fullName,$email,$phone,$position,$program,$status,$memberId,$orgId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo json_encode(['success'=>true,'message'=>'Member updated.','member_id'=>$memberId]);

} else {
    // ── Insert new member ─────────────────────────────────────────────────
    $stmt = mysqli_prepare($conn,
        "INSERT INTO org_members (org_id,full_name,email,phone,position,program,status) VALUES (?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt,'issssss',
        $orgId,$fullName,$email,$phone,$position,$program,$status);
    mysqli_stmt_execute($stmt);
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    echo json_encode(['success'=>true,'message'=>'Member added.','member_id'=>$newId]);
}
?>