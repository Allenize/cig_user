// ============================================
//  Dashboard – PHP session guard + user display
// ============================================

// Check session on page load
async function checkSession() {
  try {
    const res  = await fetch('auth/session.php', { credentials: 'same-origin' });
    const data = await res.json();

    if (!data.loggedIn) {
      // No valid session — boot back to login
      window.location.href = 'index.php';
      return;
    }

    populateUser(data.user);
  } catch (err) {
    // If PHP server unreachable, redirect to login
    window.location.href = 'index.php';
  }
}

function populateUser(user) {
  // Top-bar name
  const nameEl = document.querySelector('.user-name');
  if (nameEl) nameEl.textContent = user.name || user.username;

  // Top-bar avatar
  const avatarEl = document.querySelector('.avatar');
  if (avatarEl) {
    avatarEl.src = `https://placehold.co/40x40/2d3748/white?text=${user.avatar || 'U'}`;
    avatarEl.alt = user.name;
  }

  // Welcome greeting org name
  const orgEl = document.querySelector('.org-name');
  if (orgEl) orgEl.textContent = user.org || user.name;
}

// Logout: call PHP to destroy session
document.getElementById('logoutLink')?.addEventListener('click', async (e) => {
  e.preventDefault();
  try {
    await fetch('auth/logout.php', { credentials: 'same-origin' });
  } finally {
    window.location.href = 'index.php';
  }
});

// Run on load
checkSession();