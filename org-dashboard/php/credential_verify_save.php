<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Session expired. Please log in again.']); exit();
}

require_once __DIR__ . '/db_connection.php';
$userId = (int)$_SESSION['user_id'];

// Check-only request — just return whether admin has granted access
if (!empty($_POST['check_only'])) {
    $chk = mysqli_prepare($conn, "SELECT credentials_verified FROM users WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($chk, 'i', $userId);
    mysqli_stmt_execute($chk);
    $chkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
    mysqli_stmt_close($chk);
    $verified = !empty($chkRow['credentials_verified']);
    if ($verified) $_SESSION['credentials_verified'] = true;
    echo json_encode(['verified' => $verified]);
    exit();
}

// Load locked fields from DB — org_name and org_code are never changed here
$orgStmt = mysqli_prepare($conn, "SELECT org_name, org_code FROM users WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($orgStmt, 'i', $userId);
mysqli_stmt_execute($orgStmt);
$orgRow = mysqli_fetch_assoc(mysqli_stmt_get_result($orgStmt));
mysqli_stmt_close($orgStmt);

$editableLabels = [
    'contact_person' => 'Contact Person',
    'phone'          => 'Contact Number',
    'description'    => 'Organization Tagline / Mission',
];

$data        = [];
$missing     = [];
$missingKeys = [];

foreach ($editableLabels as $field => $label) {
    $val = trim($_POST[$field] ?? '');
    if (empty($val)) { $missing[] = $label.' is required.'; $missingKeys[] = $field; }
    $data[$field] = $val;
}

if (!empty($missing)) {
    echo json_encode(['success'=>false,'message'=>'Please complete all required fields:','missing'=>$missing,'missing_keys'=>$missingKeys]); exit();
}

// Save editable fields only — credentials_verified is set ONLY by admin
$stmt = mysqli_prepare($conn, "UPDATE users SET contact_person=?, phone=?, description=? WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'sssi', $data['contact_person'], $data['phone'], $data['description'], $userId);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($ok) {
    echo json_encode(['success'=>true,'message'=>'Profile saved. Your documents are now under review by the admin.']);
} else {
    echo json_encode(['success'=>false,'message'=>'Database error. Please try again.']);
}
?>