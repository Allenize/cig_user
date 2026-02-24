<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard · Organization</title>
  <!-- Google Fonts, Font Awesome, and shared CSS -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="shared.css">
  <!-- Page‑specific CSS -->
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>
  <div class="dashboard">
    <?php include 'includes/navbar.php'; ?>

    <!-- Page‑specific content starts here -->
    <div class="content">
      <h1 class="welcome">Welcome back, <span class="org-name">Gusion Yuser!!!</span></h1>
      <p class="date">Friday, February 20, 2026</p>

      <!-- Overview Cards, Charts, etc. -->
      <!-- ... your existing content ... -->
    </div> <!-- closes .content -->
  </div> <!-- closes .dashboard -->

  <?php include 'includes/footer.php'; ?>
  <!-- Dashboard specific script (if any) -->
</body>
</html>