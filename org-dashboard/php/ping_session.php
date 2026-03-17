<?php
/**
 * ping_session.php
 * Called every time the user clicks "Stay Logged In" in the idle warning modal.
 * Just touches $_SESSION['last_activity'] so the idle timeout resets server-side.
 */
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'reason' => 'not_logged_in']);
    exit();
}

$_SESSION['last_activity'] = time();
echo json_encode(['ok' => true, 'last_activity' => $_SESSION['last_activity']]);