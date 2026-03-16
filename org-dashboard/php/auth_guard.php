<?php
/**
 * auth_guard.php — Only checks login, not credential verification.
 * Credential restriction is shown as UI overlay, not a hard redirect.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}
?>