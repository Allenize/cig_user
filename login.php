<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        die("Form data not received.");
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? OR username=?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];

            header("Location: ./org-dashboard/php/dashboard.php");
            exit();

        } else {
            echo "Incorrect password.";
        }

    } else {
        echo "User not found.";
    }
}
?>  