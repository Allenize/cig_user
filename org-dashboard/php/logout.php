<?php
session_start();
$org_name = $_SESSION['org_name'] ?? 'your organization';

// Destroy session
session_unset();
session_destroy();
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Signing out · OrgHub</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #0f2318;
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
    }

    /* Soft radial glow */
    body::before {
      content: '';
      position: fixed; inset: 0;
      background: radial-gradient(ellipse 70% 60% at 50% 50%, rgba(45,106,79,0.28) 0%, transparent 70%);
      pointer-events: none;
    }

    .card {
      position: relative; z-index: 1;
      text-align: center;
      background: rgba(20, 48, 34, 0.55);
      border: 1px solid rgba(183,228,199,0.12);
      border-radius: 28px;
      padding: 3rem 3.2rem 2.6rem;
      backdrop-filter: blur(20px) saturate(1.2);
      -webkit-backdrop-filter: blur(20px) saturate(1.2);
      box-shadow: 0 24px 60px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.03);
      animation: fadeUp 0.6s cubic-bezier(0.16,1,0.3,1) both;
    }
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(22px); }
      to   { opacity:1; transform:translateY(0); }
    }

    /* Spinner */
    .spinner-wrap {
      width: 72px; height: 72px;
      position: relative; margin: 0 auto 2rem;
    }
    .spinner-ring {
      position: absolute; inset: 0;
      border-radius: 50%;
      border: 2.5px solid rgba(183,228,199,0.12);
      border-top-color: #74c69d;
      animation: spin 0.85s linear infinite;
    }
    .spinner-ring.r2 {
      inset: 10px;
      border-top-color: rgba(116,198,157,0.45);
      animation-duration: 1.4s;
      animation-direction: reverse;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Inner dot */
    .spinner-dot {
      position: absolute; inset: 0;
      display: flex; align-items: center; justify-content: center;
    }
    .spinner-dot::after {
      content: '';
      width: 10px; height: 10px; border-radius: 50%;
      background: #74c69d;
      animation: dotPulse 1.4s ease-in-out infinite;
    }
    @keyframes dotPulse {
      0%,100% { opacity: 0.4; transform: scale(0.8); }
      50%      { opacity: 1;   transform: scale(1.2); }
    }

    /* Text */
    .signing-out-label {
      font-size: 0.7rem; letter-spacing: 0.22em; text-transform: uppercase;
      color: rgba(183,228,199,0.38); margin-bottom: 0.55rem;
    }
    .signing-out-heading {
      font-family: 'Playfair Display', serif;
      font-size: 1.75rem; font-weight: 700;
      color: #f0faf4; margin-bottom: 0.4rem;
    }
    .signing-out-sub {
      font-size: 0.85rem; color: rgba(183,228,199,0.4);
      font-weight: 300;
    }

    body.fading { animation: pageFade 0.7s ease forwards; }
    @keyframes pageFade { to { opacity: 0; } }
  </style>
</head>
<body>

<div class="card">
  <div class="spinner-wrap">
    <div class="spinner-ring"></div>
    <div class="spinner-ring r2"></div>
    <div class="spinner-dot"></div>
  </div>
  <p class="signing-out-label">Please wait</p>
  <h1 class="signing-out-heading">Signing out&hellip;</h1>
  <p class="signing-out-sub">See you soon, <strong style="color:#74c69d;font-weight:500;"><?= htmlspecialchars($org_name) ?></strong></p>
</div>

<script>
  setTimeout(() => {
    document.body.classList.add('fading');
    setTimeout(() => { window.location.href = '../../index.php'; }, 750);
  }, 2000);
</script>
</body>
</html>