<?php
session_start();
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit(); }

header('Content-Type: application/json');
require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$org_id = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// ── Helpers ───────────────────────────────────────────────────────────────────
function esc(string $v, $conn): string {
    return mysqli_real_escape_string($conn, trim($v));
}
function respond(bool $ok, string $msg = '', array $extra = []): void {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit();
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTION: save_profile
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'save_profile') {
    $description   = esc($_POST['description']   ?? '', $conn);
    $contact_person= esc($_POST['contact_person']?? '', $conn);
    $phone         = esc($_POST['phone']         ?? '', $conn);

    $q = mysqli_query($conn, "
        UPDATE users
        SET description    = '$description',
            contact_person = '$contact_person',
            phone          = '$phone',
            updated_at     = NOW()
        WHERE user_id = $org_id
    ");

    if ($q) respond(true, 'Organization profile updated successfully.');
    respond(false, 'Update failed: ' . mysqli_error($conn));
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTION: save_account
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'save_account') {
    $username  = esc($_POST['username']  ?? '', $conn);
    $email     = esc($_POST['email']     ?? '', $conn);
    $full_name = esc($_POST['full_name'] ?? '', $conn);

    if (!$username) respond(false, 'Username is required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, 'Invalid email address.');

    // Check uniqueness (exclude self)
    $r = mysqli_query($conn, "SELECT user_id FROM users WHERE username = '$username' AND user_id != $org_id LIMIT 1");
    if (mysqli_num_rows($r) > 0) respond(false, 'That username is already taken.');

    $r = mysqli_query($conn, "SELECT user_id FROM users WHERE email = '$email' AND user_id != $org_id LIMIT 1");
    if (mysqli_num_rows($r) > 0) respond(false, 'That email is already in use.');

    $q = mysqli_query($conn, "
        UPDATE users SET username='$username', email='$email', full_name='$full_name', updated_at=NOW()
        WHERE user_id = $org_id
    ");

    if ($q) respond(true, 'Account information updated successfully.');
    respond(false, 'Update failed: ' . mysqli_error($conn));
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTION: change_password
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) respond(false, 'All password fields are required.');
    if ($new !== $confirm) respond(false, 'New passwords do not match.');
    if (strlen($new) < 8)  respond(false, 'Password must be at least 8 characters.');

    // Verify current password
    $r = mysqli_query($conn, "SELECT password_hash FROM users WHERE user_id = $org_id LIMIT 1");
    $row = mysqli_fetch_assoc($r);
    if (!$row || !password_verify($current, $row['password_hash'])) {
        respond(false, 'Current password is incorrect.');
    }

    $new_hash = mysqli_real_escape_string($conn, password_hash($new, PASSWORD_BCRYPT));
    $q = mysqli_query($conn, "UPDATE users SET password_hash='$new_hash', updated_at=NOW() WHERE user_id=$org_id");

    if ($q) respond(true, 'Password changed successfully.');
    respond(false, 'Failed to update password.');
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTION: upload_logo  (multipart/form-data POST)
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'upload_logo') {
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'No file uploaded or upload error.');
    }

    $file     = $_FILES['logo'];
    $allowed  = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
    $maxBytes = 2 * 1024 * 1024; // 2 MB

    if (!in_array($file['type'], $allowed)) respond(false, 'Only JPG, PNG, GIF, or WebP images are allowed.');
    if ($file['size'] > $maxBytes)          respond(false, 'Image must be under 2MB.');

    // Build upload directory
    $uploadDir = dirname(dirname(__DIR__)) . '/uploads/logos/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Delete old logo if exists
    $r = mysqli_query($conn, "SELECT logo_path FROM users WHERE user_id = $org_id LIMIT 1");
    $old = mysqli_fetch_assoc($r);
    if ($old && $old['logo_path']) {
        $oldFull = dirname(dirname(__DIR__)) . '/' . ltrim($old['logo_path'], './');
        if (file_exists($oldFull)) @unlink($oldFull);
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . $org_id . '_' . time() . '.' . strtolower($ext);
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        respond(false, 'Failed to save the image.');
    }

    $logo_path = mysqli_real_escape_string($conn, '../uploads/logos/' . $filename);
    $q = mysqli_query($conn, "UPDATE users SET logo_path='$logo_path', updated_at=NOW() WHERE user_id=$org_id");

    if ($q) {
        // Return web-accessible URL (from the php/ folder perspective)
        respond(true, 'Logo uploaded successfully.', ['logo_url' => '../../uploads/logos/' . $filename]);
    }
    respond(false, 'DB update failed.');
}

// ═════════════════════════════════════════════════════════════════════════════
// ACTION: remove_logo
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'remove_logo') {
    $r = mysqli_query($conn, "SELECT logo_path FROM users WHERE user_id = $org_id LIMIT 1");
    $row = mysqli_fetch_assoc($r);
    if ($row && $row['logo_path']) {
        $full = dirname(dirname(__DIR__)) . '/' . ltrim($row['logo_path'], './');
        if (file_exists($full)) @unlink($full);
    }
    $q = mysqli_query($conn, "UPDATE users SET logo_path=NULL, updated_at=NOW() WHERE user_id=$org_id");
    if ($q) respond(true, 'Logo removed.');
    respond(false, 'Failed to remove logo.');
}

respond(false, 'Unknown action.');