<?php
/**
 * update_submission_status.php
 * Super Admin endpoint to update document status following the workflow:
 *
 *   pending / in_review  →  (Super Admin approves)  →  approved_for_recommendation
 *   approved_for_recommendation  →  (President signs / accepts, returns to Super Admin)  →  approved
 *   any  →  rejected
 *   any  →  in_review
 *
 * Control Number is generated ONLY when status transitions to 'approved' (final).
 *
 * POST params:
 *   submission_id  int     required
 *   status         string  required  (in_review | approved_for_recommendation | approved | rejected)
 *   remarks        string  optional
 *
 * Returns JSON: { success, message, status, control_number? }
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db_connection.php';

header('Content-Type: application/json');

// Only Super Admin (role check — adjust role value to match your schema)
$userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
$isSuperAdmin = in_array(strtolower($userRole), ['super_admin', 'superadmin', 'admin', 'cig_admin'], true);

if (!$isSuperAdmin) {
    ob_end_clean();
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized. Super Admin access required.']));
}

$submissionId = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
$newStatus    = isset($_POST['status'])        ? trim($_POST['status'])        : '';
$remarks      = isset($_POST['remarks'])       ? trim($_POST['remarks'])       : '';

// Allowed statuses
$allowed = ['in_review', 'approved_for_recommendation', 'approved', 'rejected'];
if (!$submissionId || !in_array($newStatus, $allowed, true)) {
    ob_end_clean();
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid submission ID or status.']));
}

// Fetch current submission
$fetchStmt = mysqli_prepare($conn, "SELECT submission_id, status, control_number, org_id, title, submitted_by FROM submissions WHERE submission_id = ? LIMIT 1");
if (!$fetchStmt) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]));
}
mysqli_stmt_bind_param($fetchStmt, 'i', $submissionId);
mysqli_stmt_execute($fetchStmt);
$current = mysqli_fetch_assoc(mysqli_stmt_get_result($fetchStmt));
mysqli_stmt_close($fetchStmt);

if (!$current) {
    ob_end_clean();
    http_response_code(404);
    exit(json_encode(['success' => false, 'message' => 'Submission not found.']));
}

$controlNumber = $current['control_number'];

// Generate control number ONLY when transitioning to 'approved' (final status)
// and one has not already been assigned.
if ($newStatus === 'approved' && empty($controlNumber)) {
    $yearNow  = date('Y');
    $cntStmt  = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE status = 'approved' AND YEAR(submitted_at) = ?");
    $cntStmt->bind_param('i', $yearNow);
    $cntStmt->execute();
    $cnt      = $cntStmt->get_result()->fetch_assoc()['cnt'];
    $cntStmt->close();
    $controlNumber = 'CIG-' . $yearNow . '-' . str_pad($cnt + 1, 6, '0', STR_PAD_LEFT);
}

// If status is NOT approved, ensure control number is not exposed/set
if ($newStatus !== 'approved') {
    $controlNumber = $current['control_number']; // keep whatever was already there (should be NULL)
}

// Update the submission
if ($newStatus === 'approved' && !empty($controlNumber)) {
    $updStmt = mysqli_prepare($conn,
        "UPDATE submissions SET status = ?, control_number = ?, updated_at = NOW() WHERE submission_id = ?");
    if (!$updStmt) {
        ob_end_clean();
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]));
    }
    $updStmt->bind_param('ssi', $newStatus, $controlNumber, $submissionId);
} else {
    $updStmt = mysqli_prepare($conn,
        "UPDATE submissions SET status = ?, updated_at = NOW() WHERE submission_id = ?");
    if (!$updStmt) {
        ob_end_clean();
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]));
    }
    $updStmt->bind_param('si', $newStatus, $submissionId);
}

if (!$updStmt->execute()) {
    $err = $updStmt->error;
    $updStmt->close();
    ob_end_clean();
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Update failed: ' . $err]));
}
$updStmt->close();

// Upsert into reviews table (admin remarks)
if ($remarks !== '') {
    $adminId  = (int)$_SESSION['user_id'];
    $revCheck = mysqli_prepare($conn, "SELECT review_id FROM reviews WHERE submission_id = ? LIMIT 1");
    $revCheck->bind_param('i', $submissionId);
    $revCheck->execute();
    $existingRev = $revCheck->get_result()->fetch_assoc();
    $revCheck->close();

    if ($existingRev) {
        $revStmt = mysqli_prepare($conn,
            "UPDATE reviews SET feedback = ?, reviewed_by = ?, reviewed_at = NOW() WHERE submission_id = ?");
        $revStmt->bind_param('sii', $remarks, $adminId, $submissionId);
    } else {
        $revStmt = mysqli_prepare($conn,
            "INSERT INTO reviews (submission_id, feedback, reviewed_by, reviewed_at) VALUES (?, ?, ?, NOW())");
        $revStmt->bind_param('isi', $submissionId, $remarks, $adminId);
    }
    $revStmt->execute();
    $revStmt->close();
}

// Friendly label for the response
$statusLabels = [
    'pending'                    => 'Pending',
    'in_review'                  => 'Under Review',
    'approved_for_recommendation'=> 'Approved for Recommendation',
    'approved'                   => 'Approved',
    'rejected'                   => 'Rejected',
];
$statusLabel = $statusLabels[$newStatus] ?? ucfirst(str_replace('_', ' ', $newStatus));

ob_end_clean();
echo json_encode([
    'success'        => true,
    'message'        => 'Status updated to "' . $statusLabel . '" successfully.',
    'status'         => $newStatus,
    'status_label'   => $statusLabel,
    'control_number' => ($newStatus === 'approved') ? $controlNumber : null,
]);