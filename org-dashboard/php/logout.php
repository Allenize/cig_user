<?php
session_start();
$org_name = $_SESSION['org_name'] ?? 'your organization';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #a8e6cf;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    /* Subtle radial gradient for depth */
    body::before {
      content: '';
      position: fixed; inset: 0;
      background: radial-gradient(ellipse at 30% 40%, rgba(45,106,79,0.25) 0%, transparent 60%),
                  radial-gradient(ellipse at 75% 70%, rgba(26,61,43,0.2) 0%, transparent 55%);
      pointer-events: none;
    }

    .card {
      position: relative;
      text-align: center;
      background: #fff;
      border-radius: 24px;
      padding: 3rem 3.5rem;
      box-shadow: 0 20px 60px rgba(26,61,43,0.18), 0 0 0 1px rgba(45,106,79,0.08);
      animation: fadeUp 0.5s cubic-bezier(0.16,1,0.3,1) both;
      min-width: 300px;
    }

    @keyframes fadeUp {
      from { opacity:0; transform:translateY(20px); }
      to   { opacity:1; transform:translateY(0); }
    }

    /* OrgHub logo mark */
    .brand-icon {
      width: 64px; height: 64px;
      background: linear-gradient(135deg, #1a3d2b, #2d6a4f);
      border-radius: 18px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.6rem;
      box-shadow: 0 6px 20px rgba(26,61,43,0.3);
    }
    .brand-icon svg {
      width: 32px; height: 32px;
      fill: none; stroke: #52b788; stroke-width: 2;
      stroke-linecap: round; stroke-linejoin: round;
    }

    /* Spinner */
    .spinner-wrap {
      width: 56px; height: 56px;
      position: relative; margin: 0 auto 1.6rem;
    }
    .spinner-ring {
      position: absolute; inset: 0;
      border-radius: 50%;
      border: 3px solid #e3f2eb;
      border-top-color: #2d6a4f;
      animation: spin 0.9s linear infinite;
    }
    .spinner-ring.r2 {
      inset: 8px;
      border-top-color: #52b788;
      animation-duration: 1.5s;
      animation-direction: reverse;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .label {
      font-size: 0.72rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #9ab5ac;
      margin-bottom: 0.45rem;
      font-weight: 600;
    }
    .heading {
      font-size: 1.5rem;
      font-weight: 800;
      color: #1a3d2b;
      margin-bottom: 0.4rem;
    }
    .sub {
      font-size: 0.85rem;
      color: #6b8f7a;
      font-weight: 400;
    }
    .sub strong {
      color: #2d6a4f;
      font-weight: 600;
    }

    /* Progress bar at bottom of card */
    .progress-bar {
      margin-top: 2rem;
      height: 3px;
      background: #e3f2eb;
      border-radius: 20px;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, #2d6a4f, #52b788);
      border-radius: 20px;
      animation: fillBar 2s ease-in-out forwards;
    }
    @keyframes fillBar { to { width: 100%; } }

    body.fading {
      animation: pageFade 0.5s ease forwards;
    }
    @keyframes pageFade { to { opacity: 0; } }
  </style>
</head>
<body>

<div class="card">
  <div class="brand-icon">
    <!-- Simple layers icon for OrgHub -->
    <svg viewBox="0 0 24 24">
      <rect x="3" y="3" width="18" height="5" rx="2"/>
      <rect x="3" y="10" width="18" height="5" rx="2"/>
      <rect x="3" y="17" width="18" height="4" rx="2"/>
    </svg>
  </div>

  <div class="spinner-wrap">
    <div class="spinner-ring"></div>
    <div class="spinner-ring r2"></div>
  </div>

  <p class="label">Please wait</p>
  <h1 class="heading">Signing out&hellip;</h1>
  <p class="sub">See you soon, <strong><?= htmlspecialchars($org_name) ?></strong></p>

  <div class="progress-bar">
    <div class="progress-fill"></div>
  </div>
</div>

<script>
  // Redirect to login after 2 seconds
  setTimeout(function() {
    document.body.classList.add('fading');
    setTimeout(function() {
      window.location.replace('index.php');
    }, 500);
  }, 2000);
</script>

<script>
window.__PAGE_TYPE = 'logout';
window.__LOGIN_URL = 'index.php';
</script>
<script src="../js/no_back.js"></script>
</body>
</html>