<?php
/**
 * get_dashboard_stats.php
 * Returns live stat counts based on actual schema:
 *  - members  = active records in org_members for this org
 *  - documents/reports/pending = from submissions table
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

header('Content-Type: application/json');
require_once __DIR__ . '/db_connection.php';

$stats = [
    'total_members'     => 0,
    'total_documents'   => 0,
    'submitted_reports' => 0,
    'pending_documents' => 0,
];

if ($conn) {
    $org_id = (int) $_SESSION['user_id'];

    // Members: active records in org_members for this org
    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM org_members WHERE org_id = $org_id AND status = 'active'");
    $stats['total_members'] = (int) mysqli_fetch_assoc($r)['cnt'];

    // Documents (all submissions by this org)
    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id");
    $stats['total_documents'] = (int) mysqli_fetch_assoc($r)['cnt'];

    // Approved submissions
    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id AND status = 'approved'");
    $stats['submitted_reports'] = (int) mysqli_fetch_assoc($r)['cnt'];

    // Pending / in_review submissions
    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM submissions WHERE org_id = $org_id AND status IN ('pending','in_review')");
    $stats['pending_documents'] = (int) mysqli_fetch_assoc($r)['cnt'];

    mysqli_close($conn);
}

echo json_encode($stats);