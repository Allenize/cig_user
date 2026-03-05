<?php
/**
 * get_dashboard_stats.php
 * Returns live stat counts for the logged-in org's dashboard cards.
 * Called by the dashboard every 30 s via fetch().
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$stats = [
    'total_members'     => 0,
    'total_documents'   => 0,
    'submitted_reports' => 0,
    'pending_documents' => 0,
];

if ($conn) {
    $org_id = (int) $_SESSION['user_id'];

    // Total members sharing the same org_code
    $org_code_res = mysqli_query($conn, "SELECT org_code FROM users WHERE user_id = $org_id LIMIT 1");
    $org_code_row = mysqli_fetch_assoc($org_code_res);
    $org_code     = mysqli_real_escape_string($conn, $org_code_row['org_code'] ?? '');

    if ($org_code) {
        $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE org_code = '$org_code' AND role = 'user'");
        $stats['total_members'] = (int) mysqli_fetch_assoc($r)['cnt'];
    }

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM submissions WHERE org_id = $org_id");
    $stats['total_documents'] = (int) mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM submissions WHERE org_id = $org_id AND status = 'approved'");
    $stats['submitted_reports'] = (int) mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM submissions WHERE org_id = $org_id AND status IN ('pending','in_review')");
    $stats['pending_documents'] = (int) mysqli_fetch_assoc($r)['cnt'];

    mysqli_close($conn);
}

echo json_encode($stats);