// ============================================
//  Organization Login - PHP Backend Auth
// ============================================

// ---------- LOGO CAROUSEL ----------
const logoImages = [
  "https://placehold.co/600x600/2d3748/white?text=ACME",
  "https://placehold.co/600x600/2d3748/white?text=GLOBAL",
  "https://placehold.co/600x600/2d3748/white?text=AEGIS",
  "https://placehold.co/600x600/2d3748/white?text=NEXUM",
  "https://placehold.co/600x600/2d3748/white?text=SENTINEL",
  "https://placehold.co/600x600/2d3748/white?text=ORION",
  "https://placehold.co/600x600/2d3748/white?text=CORTEX",
  "https://placehold.co/600x600/2d3748/white?text=TERRA"
];

const logoImage = document.getElementById('logoImage');
let currentIndex = 0;

function initCarousel() {
  if (logoImage) logoImage.src = logoImages[currentIndex];
}

function changeLogo() {
  if (!logoImage) return;
  logoImage.classList.add('fade-out');
  setTimeout(() => {
    currentIndex = (currentIndex + 1) % logoImages.length;
    logoImage.src = logoImages[currentIndex];
    logoImage.classList.remove('fade-out');
  }, 400);
}

initCarousel();
setInterval(changeLogo, 2800);

// ---------- CHECK EXISTING SESSION ----------
// If already logged in, skip straight to dashboard
fetch('auth/session.php')
  .then(r => r.json())
  .then(data => {
    if (data.loggedIn) {
      window.location.href = 'dashboard.php';
    }
  })
  .catch(() => {}); // silently ignore if PHP not running

// ---------- UI HELPERS ----------
function showError(msg) {
  let el = document.getElementById('loginError');
  if (!el) {
    el = document.createElement('p');
    el.id = 'loginError';
    el.style.cssText = [
      'color:#e53e3e',
      'font-size:0.875rem',
      'margin-top:-8px',
      'margin-bottom:12px',
      'text-align:center',
      'background:#fff5f5',
      'border:1px solid #fed7d7',
      'border-radius:8px',
      'padding:8px 12px'
    ].join(';');
    const btn = document.querySelector('.btn');
    btn.parentNode.insertBefore(el, btn);
  }
  el.textContent = msg;
  el.style.display = 'block';
}

function clearError() {
  const el = document.getElementById('loginError');
  if (el) el.style.display = 'none';
}

function setLoading(loading) {
  const btn = document.querySelector('.btn');
  if (!btn) return;
  btn.disabled    = loading;
  btn.textContent = loading ? 'Signing in...' : 'Log in';
  btn.style.opacity = loading ? '0.7' : '1';
}

// ---------- LOGIN FORM ----------
const loginForm = document.getElementById('loginForm');
if (loginForm) {
  loginForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    clearError();

    const identifier = document.getElementById('email').value.trim();
    const password   = document.getElementById('password').value;

    if (!identifier || !password) {
      showError('Please enter your email/username and password.');
      return;
    }

    setLoading(true);

    try {
      const res  = await fetch('auth/login.php', {
        method:      'POST',
        headers:     { 'Content-Type': 'application/json' },
        body:        JSON.stringify({ identifier, password }),
        credentials: 'same-origin'  // send/receive session cookie
      });

      const data = await res.json();

      if (data.success) {
        window.location.href = 'dashboard.php';
      } else {
        setLoading(false);
        showError(data.message || 'Invalid credentials. Please try again.');
        document.getElementById('password').value = '';
      }
    } catch (err) {
      setLoading(false);
      showError('Unable to reach the server. Please try again.');
    }
  });
}