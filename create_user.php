<?php
/**
 * create_user.php
 * ─────────────────────────────────────────────────────────
 * One-time user creation tool for cig_system.
 * Place in your project root, run in browser, then DELETE it.
 * ─────────────────────────────────────────────────────────
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── DB Connection (inline so it works standalone) ──────
$conn = @mysqli_connect("localhost", "root", "", "cig_system");
if (!$conn) {
    die("<div style='font-family:sans-serif;padding:40px;background:#0f172a;color:#f87171;'>
        <h2>❌ Database Connection Failed</h2>
        <p>" . mysqli_connect_error() . "</p>
        <p style='color:#94a3b8;margin-top:12px;font-size:13px;'>
          Check: hostname, username, password, and that the database <strong>cig_system</strong> exists.
        </p>
    </div>");
}

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role      = $_POST['role'] ?? 'user';
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $message = 'All fields are required.';
        $msgType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address.';
        $msgType = 'error';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
        $msgType = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $msgType = 'error';
    } else {
        // Check for duplicate username or email
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = 'Username or email already exists.';
            $msgType = 'error';
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare(
                "INSERT INTO users (username, email, full_name, role, password_hash, status, created_at)
                 VALUES (?, ?, ?, ?, ?, 'active', NOW())"
            );
            $stmt->bind_param("sssss", $username, $email, $full_name, $role, $password_hash);

            if ($stmt->execute()) {
                $new_id  = $conn->insert_id;
                $message = "✅ User <strong>$full_name</strong> created successfully! (ID: $new_id, Role: $role)";
                $msgType = 'success';
            } else {
                $message = 'Database error: ' . $conn->error;
                $msgType = 'error';
            }
        }
    }
}

// Fetch existing users for display
$users_result = $conn->query("SELECT user_id, username, email, full_name, role, status, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create User — CIG System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      background: #a8e6cf;
      color: #e2e8f0;
      min-height: 100vh;
      padding: 40px 20px;
    }

    .container { max-width: 860px; margin: 0 auto; }

    .banner {
      background: #ef4444;
      color: #fff;
      border-radius: 10px;
      padding: 14px 20px;
      margin-bottom: 28px;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .banner strong { font-size: 14px; }

    h1 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 6px;
      color: #f1f5f9;
    }
    .subtitle { color: #94a3b8; font-size: 14px; margin-bottom: 30px; }

    .card {
      background: #ffffff;
      border-radius: 14px;
      padding: 30px;
      margin-bottom: 30px;
    }

    .card h2 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 22px;
      color: #cbd5e1;
      border-bottom: 1px solid #334155;
      padding-bottom: 12px;
    }

    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .full { grid-column: 1 / -1; }

    label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: .05em;
      margin-bottom: 6px;
    }

    input, select {
      width: 100%;
      background: #ffffff;
      border: 1px solid #94a3b8;
      border-radius: 8px;
      padding: 10px 14px;
      color: #f1f5f9;
      font-size: 14px;
      font-family: inherit;
      transition: border-color .2s;
    }
    input:focus, select:focus {
      outline: none;
      border-color: #6366f1;
    }
    select option { background: #1e293b; }

    .btn {
      background: #6366f1;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 12px 28px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s;
      margin-top: 8px;
    }
    .btn:hover { background: #4f46e5; }

    .alert {
      border-radius: 8px;
      padding: 14px 18px;
      margin-bottom: 22px;
      font-size: 14px;
    }
    .alert.success { background: #052e16; border: 1px solid #16a34a; color: #4ade80; }
    .alert.error   { background: #ffffff; border: 1px solid #dc2626; color: #f87171; }

    /* Role badge */
    .badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .04em;
    }
    .badge.admin    { background: #4c1d95; color: #c4b5fd; }
    .badge.reviewer { background: #0c4a6e; color: #7dd3fc; }
    .badge.user     { background: #14532d; color: #86efac; }

    .status-dot {
      display: inline-block;
      width: 8px; height: 8px;
      border-radius: 50%;
      background: #22c55e;
      margin-right: 6px;
    }

    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th {
      text-align: left;
      padding: 10px 14px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: #64748b;
      border-bottom: 1px solid #334155;
    }
    td {
      padding: 12px 14px;
      border-bottom: 1px solid #1e293b;
      color: #cbd5e1;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #1e293b44; }
  </style>
</head>
<body>
<div class="container">

  <!-- Security warning -->
  <div class="banner">
    ⚠️ <span><strong>Security Notice:</strong> Delete or restrict access to this file after creating your accounts. Do not leave it publicly accessible.</span>
  </div>

  <h1>Create User Account</h1>
  <p class="subtitle">Insert new users directly into the cig_system database</p>

  <?php if ($message): ?>
    <div class="alert <?= $msgType ?>"><?= $message ?></div>
  <?php endif; ?>

  <div class="card">
    <h2>New User</h2>
    <form method="POST">
      <div class="grid">
        <div>
          <label for="full_name">Full Name</label>
          <input type="text" name="full_name" id="full_name" placeholder="e.g. Maria Santos"
                 value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
        </div>
        <div>
          <label for="username">Username</label>
          <input type="text" name="username" id="username" placeholder="e.g. mariasantos"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
        <div>
          <label for="email">Email Address</label>
          <input type="email" name="email" id="email" placeholder="e.g. maria@cig.edu.ph"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div>
          <label for="role">Role</label>
          <select name="role" id="role">
            <option value="user"     <?= (($_POST['role'] ?? 'user') === 'user')     ? 'selected' : '' ?>>User (Organization Member)</option>
            <option value="reviewer" <?= (($_POST['role'] ?? '') === 'reviewer')     ? 'selected' : '' ?>>Reviewer</option>
            <option value="admin"    <?= (($_POST['role'] ?? '') === 'admin')        ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>
        <div>
          <label for="password">Password</label>
          <input type="password" name="password" id="password" placeholder="Min. 8 characters" required>
        </div>
        <div>
          <label for="confirm_password">Confirm Password</label>
          <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat password" required>
        </div>
      </div>
      <br>
      <button type="submit" class="btn"> + Create User</button>
    </form>
  </div>

  <!-- Existing users table -->
  <div class="card">
    <h2>Existing Users</h2>
    <?php if ($users_result && $users_result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Full Name</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($u = $users_result->fetch_assoc()): ?>
        <tr>
          <td>#<?= $u['user_id'] ?></td>
          <td><?= htmlspecialchars($u['full_name']) ?></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge <?= $u['role'] ?>"><?= $u['role'] ?></span></td>
          <td><span class="status-dot"></span><?= $u['status'] ?></td>
          <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p style="color:#64748b; font-size:14px;">No users found.</p>
    <?php endif; ?>
  </div>

</div>
</body>
</html>