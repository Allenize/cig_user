    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    session_start();
    require 'db_connection.php';

    header('Content-Type: application/json');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if (!isset($_POST['email']) || !isset($_POST['password'])) {
            echo json_encode(['success' => false, 'message' => 'Form data not received.']);
            exit();
        }

        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        // Query using correct column names from schema
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            // Check account status before anything else
            if ($user['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Your account is inactive or suspended. Please contact the administrator.']);
                exit();
            }

            // Verify password against password_hash column
            if (password_verify($password, $user['password_hash'])) {

                // Store RBAC-relevant data in session
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['status']    = $user['status'];
                $_SESSION['org_name']  = $user['org_name'];
                $_SESSION['org_code']  = $user['org_code'];

                // Update last_login timestamp
                $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update->bind_param("i", $user['user_id']);
                $update->execute();

                // Role-based redirect
                switch ($user['role']) {
                    case 'admin':
                        $redirect = './org-dashboard/php/admin-dashboard.php';
                        break;
                    default: // 'user'
                        $redirect = './org-dashboard/php/dashboard.php';
                        break;
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