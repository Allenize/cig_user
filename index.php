<?php
// ── Dynamically load all logos from the assets folder ────────────────────────
$assets_dir = __DIR__ . '/assets/';
$assets_url = './assets/';
$logo_files = [];

if (is_dir($assets_dir)) {
    foreach (['png','jpg','jpeg','webp','svg','PNG','JPG','JPEG','WEBP','SVG'] as $ext)
        foreach (glob($assets_dir . '*.' . $ext) as $file)
            $logo_files[] = $assets_url . basename($file);
}
if (empty($logo_files))
    $logo_files = ['https://placehold.co/480x480/2d6a4f/white?text=OrgHub'];

$logos_json = json_encode($logo_files);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OrgHub · Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Animated botanical background -->
<div class="bg-canvas">
  <svg class="blob-1" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
    <path fill="#2d6a4f" d="M300,50 C420,40 540,120 560,240 C580,360 500,480 380,530 C260,580 120,530 70,410 C20,290 60,140 180,80 C230,55 270,52 300,50Z"/>
  </svg>
  <svg class="blob-2" viewBox="0 0 700 700" xmlns="http://www.w3.org/2000/svg">
    <path fill="#40916c" d="M350,80 C480,60 600,160 640,300 C680,440 600,570 460,610 C320,650 160,590 90,450 C20,310 80,150 200,90 C260,62 310,85 350,80Z"/>
  </svg>
  <svg class="blob-3" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
    <path fill="#1a3d2b" d="M200,40 C280,30 360,100 370,190 C380,280 310,360 220,370 C130,380 50,310 40,210 C30,110 110,30 200,40Z"/>
  </svg>
</div>

<!-- Loading screen -->
<div id="loadingScreen">
  <div class="loader-ring"></div>
  <p>Signing you in&hellip;</p>
</div>

<!-- Error popup -->
<div id="errorPopup">
  <div class="error-card">
    <h3>
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Login Error
    </h3>
    <p id="errorMessage"></p>
    <button class="error-close-btn" onclick="closeErrorPopup()">Try Again</button>
  </div>
</div>

<div class="split-screen">

  <!-- LEFT PANEL -->
  <div class="left-panel">
    <svg class="corner-leaf tl" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
      <path fill="#74c69d" d="M100,10 C150,5 190,40 195,100 C200,160 160,195 100,195 C40,195 5,155 10,95 C15,35 50,15 100,10Z"/>
    </svg>
    <svg class="corner-leaf br" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
      <path fill="#b7e4c7" d="M100,10 C155,8 190,50 192,105 C194,160 155,195 100,192 C45,189 8,150 10,95 C12,40 45,12 100,10Z"/>
    </svg>

    <div class="logo-ring">
      <div class="logo-single" id="logoContainer">
        <img id="logoImage" src="<?= htmlspecialchars($logo_files[0]) ?>" alt="Organization Logo">
      </div>
    </div>



    <p class="panel-tagline">PLSP Student Organizations &middot; <?= date('Y') ?></p>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right-panel">
    <div class="form-card">

      <div class="wordmark">
        <div class="wordmark-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
            <path d="M12 2a10 10 0 0 1 0 20A10 10 0 0 1 12 2z"/>
            <path d="M12 6c0 0-4 3-4 7s4 5 4 5 4-1 4-5-4-7-4-7z"/>
          </svg>
        </div>
        <span class="wordmark-text">Org<span>Hub</span></span>
      </div>

      <h1 class="form-heading">Welcome back.</h1>
      <p class="form-sub">Sign in to your organization account</p>
      <div class="form-divider"></div>

      <form id="loginForm" method="POST">
        <div class="input-group">
          <label for="email">Email or Username</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input type="text" name="email" id="email" required autocomplete="username" placeholder="Enter your email or username">
          </div>
        </div>

        <div class="input-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Enter your password">
            <button type="button" class="toggle-pw" id="togglePw" tabindex="-1" aria-label="Toggle password">
              <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="17" height="17">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login">
          <span class="btn-content">
            Sign In
            <svg class="btn-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
          </span>
        </button>
      </form>

      <p class="form-footer">&copy; 2025&ndash;2026 &middot; Authorized organizations only</p>
    </div>
  </div>

</div>

<script>
  // Leaf particles
  const canvas = document.querySelector('.bg-canvas');
  const leafSVG = '<svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"><path fill="#2d6a4f" d="M20,3 C28,2 36,10 37,20 C38,30 30,37 20,37 C10,37 2,29 3,19 C4,9 12,4 20,3Z"/></svg>';
  for (let i = 0; i < 12; i++) {
    const el = document.createElement('div');
    el.className = 'leaf-particle';
    el.innerHTML = leafSVG;
    const s = 14 + Math.random() * 18;
    el.style.cssText = `width:${s}px;height:${s}px;left:${Math.random()*100}vw;top:${Math.random()*-20}px;animation-duration:${12+Math.random()*18}s;animation-delay:${Math.random()*16}s;opacity:0;`;
    canvas.appendChild(el);
  }

  // Carousel
  const logos    = <?= $logos_json ?>;
  let   curIdx   = 0;
  const logoImg  = document.getElementById('logoImage');
  const logoWrap = document.getElementById('logoContainer');
  function goTo(idx) {
    logoImg.style.opacity = '0';
    setTimeout(() => {
      curIdx = (idx + logos.length) % logos.length;
      logoImg.src = logos[curIdx];
      logoImg.style.opacity = '1';
    }, 350);
  }
  if (logos.length > 1) {
    setInterval(() => goTo(curIdx + 1), 4500);
  }

  // Password toggle
  const togglePw = document.getElementById('togglePw');
  const pwInput  = document.getElementById('password');
  const eyeIcon  = document.getElementById('eyeIcon');
  const EYE_OPEN   = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  const EYE_CLOSED = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  togglePw.addEventListener('click', () => {
    const hide = pwInput.type === 'password';
    pwInput.type = hide ? 'text' : 'password';
    eyeIcon.innerHTML = hide ? EYE_CLOSED : EYE_OPEN;
  });

  // Login form
  function showLoading() { document.getElementById('loadingScreen').style.display = 'flex'; }
  function hideLoading() { document.getElementById('loadingScreen').style.display = 'none'; }
  function showError(msg) { document.getElementById('errorMessage').textContent = msg; document.getElementById('errorPopup').style.display = 'flex'; }
  function closeErrorPopup() { document.getElementById('errorPopup').style.display = 'none'; }

  document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    showLoading();
    fetch('login.php', { method:'POST', body: new FormData(this) })
      .then(r => r.json())
      .then(data => { hideLoading(); if (data.success) window.location.href = data.redirect; else showError(data.message); })
      .catch(() => { hideLoading(); showError('An error occurred. Please try again.'); });
  });
</script>
</body>
</html>