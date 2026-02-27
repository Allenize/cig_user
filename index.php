<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Organization Login</title>
  <!-- Google Fonts - Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Loading Screen -->
  <div id="loadingScreen" class="loading-screen">
    <div class="spinner"></div>
    <p>Logging in...</p>
  </div>

  <!-- Error Popup -->
  <div id="errorPopup" class="error-popup">
    <div class="error-content">
      <button class="error-close" onclick="closeErrorPopup()">&times;</button>
      <h3>Login Error</h3>
      <p id="errorMessage"></p>
      <button class="error-btn" onclick="closeErrorPopup()">OK</button>
    </div>
  </div>

  <div class="split-screen">
    <!-- LEFT PANEL – Single large rotating logo (no container) -->
    <div class="left-panel">
      <div class="carousel-container">
        <div class="logo-single" id="logoContainer">
          <img id="logoImage" src="https://placehold.co/600x600/2d3748/white?text=LOGO+1" alt="Organization Logo">
        </div>
      </div>
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
    </div>

    <!-- RIGHT PANEL – Glass effect login form -->
    <div class="right-panel">
      <div class="form-container glass">
        <h2>PLSP Students Organization</h2>
        <p class="subtitle">Access your organization account</p>
        
<form id="loginForm" method="POST">
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

        <p class="footer-text">© 2025-2026 · Authorized organizations only</p>
      </div>
    </div>
  </div>

  <script>
    // Logo carousel configuration
    const logos = [
      'https://placehold.co/600x600/2d3748/white?text=LOGO+1',
      'https://placehold.co/600x600/27ae60/white?text=LOGO+2',
      'https://placehold.co/600x600/3498db/white?text=LOGO+3',
      'https://placehold.co/600x600/e74c3c/white?text=LOGO+4'
    ];
    
    let currentLogoIndex = 0;
    const logoImage = document.getElementById('logoImage');
    
    // Function to change logo with fade effect
    function changeLogoWithFade() {
      logoImage.style.opacity = '0';
      
      setTimeout(() => {
        currentLogoIndex = (currentLogoIndex + 1) % logos.length;
        logoImage.src = logos[currentLogoIndex];
        logoImage.style.opacity = '1';
      }, 300);
    }
    
    // Change logo every 4 seconds
    setInterval(changeLogoWithFade, 4000);

    function showLoadingScreen() {
      document.getElementById('loadingScreen').style.display = 'flex';
    }

    function hideLoadingScreen() {
      document.getElementById('loadingScreen').style.display = 'none';
    }

    function showErrorPopup(message) {
      document.getElementById('errorMessage').textContent = message;
      document.getElementById('errorPopup').style.display = 'flex';
    }

    function closeErrorPopup() {
      document.getElementById('errorPopup').style.display = 'none';
    }

    document.getElementById('loginForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      showLoadingScreen();
      
      const formData = new FormData(this);
      
      fetch('login.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        hideLoadingScreen();
        
        if (data.success) {
          // Redirect to dashboard after successful login
          window.location.href = './org-dashboard/php/dashboard.php';
        } else {
          // Show error popup
          showErrorPopup(data.message);
        }
      })
      .catch(error => {
        hideLoadingScreen();
        showErrorPopup('An error occurred. Please try again.');
        console.error('Error:', error);
      });
    });
  </script>
</body>
</html>