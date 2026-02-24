<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Organization Login · Single Logo</title>
  <!-- Google Fonts - Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="split-screen">
    <!-- LEFT PANEL – Single large rotating logo (no container) -->
    <div class="left-panel">
      <div class="carousel-container">
        <div class="logo-single" id="logoContainer">
          <img id="logoImage" src="https://placehold.co/600x600/2d3748/white?text=LOGO" alt="Organization Logo">
        </div>
      </div>
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
    </div>

    <!-- RIGHT PANEL – Glass effect login form -->
    <div class="right-panel">
      <div class="form-container glass">
        <h2>Organization Login</h2>
        <p class="subtitle">Access your corporate account</p>
        
<form id="loginForm" method="POST" action="login.php">
  <div class="input-group">
    <label for="email">Email / Username</label>
    <input type="text" name="email" id="email" required>
  </div>

  <div class="input-group">
    <label for="password">Password</label>
    <input type="password" name="password" id="password" required>
  </div>

  <button type="submit" class="btn">Log in</button>
</form>

        <p class="footer-text">© 2026 · Authorized organizations only</p>
      </div>
    </div>
  </div>
</body>
</html>