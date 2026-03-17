<?php
/**
 * auth_guard.php
 * - Session start & idle timeout (30 min)
 * - Redirects to login if not logged in or timed out
 * - Redirects unverified non-admin users to credential_verification.php
 * - Re-syncs credentials_verified from DB on every request
 */
if (session_status() === PHP_SESSION_NONE) session_start();

// No-cache headers — prevent browser back button serving stale protected pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

define('IDLE_TIMEOUT', 30 * 60); // 30 minutes

// ── Not logged in ─────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// ── Idle timeout check ────────────────────────────────────────────────────
if (isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > IDLE_TIMEOUT) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header("Location: index.php?reason=timeout");
        exit();
    }
}
$_SESSION['last_activity'] = time();

// ── CSRF token ────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Admin bypasses credential check ──────────────────────────────────────
if (($_SESSION['role'] ?? '') === 'admin') return;

// ── Re-sync credentials_verified from DB ─────────────────────────────────
$_cv_conn = @mysqli_connect("localhost", "root", "", "cig_system");
if ($_cv_conn) {
    $_cv_stmt = mysqli_prepare($_cv_conn,
        "SELECT credentials_verified FROM users WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($_cv_stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($_cv_stmt);
    $_cv_row = mysqli_fetch_assoc(mysqli_stmt_get_result($_cv_stmt));
    mysqli_stmt_close($_cv_stmt);
    mysqli_close($_cv_conn);

    $_db_verified = !empty($_cv_row['credentials_verified']);

    // Detect revocation: was verified in session, now false in DB
    if (!empty($_SESSION['credentials_verified']) && !$_db_verified) {
        $_SESSION['just_revoked'] = true;
    }

    $_SESSION['credentials_verified'] = $_db_verified;
}

// ── Redirect unverified users ─────────────────────────────────────────────
$_current = basename($_SERVER['PHP_SELF']);
$_allowed = ['credential_verification.php', 'credential_verify_save.php', 'doc_upload.php'];

if (empty($_SESSION['credentials_verified']) && !in_array($_current, $_allowed)) {
    header("Location: credential_verification.php");
    exit();
}
?>