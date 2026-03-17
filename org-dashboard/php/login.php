<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require __DIR__ . '/db_connection.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        echo json_encode(['success' => false, 'message' => 'Form data not received.']);
        exit();
    }

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if ($user['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Your account is inactive or suspended. Please contact the administrator.']);
            exit();
        }

        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']              = $user['user_id'];
            $_SESSION['username']             = $user['username'];
            $_SESSION['full_name']            = $user['full_name'];
            $_SESSION['role']                 = $user['role'];
            $_SESSION['status']               = $user['status'];
            $_SESSION['org_name']             = $user['org_name'];
            $_SESSION['org_code']             = $user['org_code'];
            $_SESSION['credentials_verified'] = (bool)($user['credentials_verified'] ?? 0);

            $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update->bind_param("i", $user['user_id']);
            $update->execute();

            // Admin goes straight to dashboard
            // Unverified users go to credential verification page
            // Verified users go to dashboard
            if ($user['role'] === 'admin') {
                $redirect = 'dashboard.php';
            } elseif (empty($user['credentials_verified'])) {
                $redirect = 'credential_verification.php';
            } else {
                $redirect = 'dashboard.php';
            }

            echo json_encode([
                'success'  => true,
                'message'  => 'Login successful',
                'role'     => $user['role'],
                'redirect' => $redirect
            ]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit();
    }
}
?>