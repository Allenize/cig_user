// ============================================
//  Settings - Profile & Security
// ============================================

// ---------- DOM ELEMENTS ----------
const logoInput = document.getElementById('orgLogo');
const logoImage = document.getElementById('logoImage');
const settingsForm = document.getElementById('settingsForm');
const successMessage = document.getElementById('successMessage');

// ---------- LOGO PREVIEW ----------
logoInput.addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(event) {
      logoImage.src = event.target.result;
    };
    reader.readAsDataURL(file);
  }
});

// ---------- LOAD SAVED SETTINGS FROM LOCALSTORAGE (demo) ----------
function loadSettings() {
  const saved = localStorage.getItem('orgSettings');
  if (saved) {
    try {
      const settings = JSON.parse(saved);
      document.getElementById('orgDescription').value = settings.description || '';
      document.getElementById('orgEmail').value = settings.email || '';
      document.getElementById('orgPhone').value = settings.phone || '';
      document.getElementById('orgAddress').value = settings.address || '';
      document.getElementById('orgWebsite').value = settings.website || '';
      document.getElementById('adviserName').value = settings.adviser || '';
      // Logo is file, we can't prefill from localStorage (would need base64, but for demo we keep placeholder)
      if (settings.logo) {
        logoImage.src = settings.logo; // if stored as data URL
      }
    } catch (e) {}
  }
}

// ---------- SAVE SETTINGS ----------
settingsForm.addEventListener('submit', function(e) {
  e.preventDefault();

  // Collect form data
  const settings = {
    description: document.getElementById('orgDescription').value.trim(),
    email: document.getElementById('orgEmail').value.trim(),
    phone: document.getElementById('orgPhone').value.trim(),
    address: document.getElementById('orgAddress').value.trim(),
    website: document.getElementById('orgWebsite').value.trim(),
    adviser: document.getElementById('adviserName').value.trim(),
    logo: logoImage.src // store as data URL (in real app, you'd upload file separately)
  };

  // Password change (demo validation)
  const current = document.getElementById('currentPassword').value;
  const newPass = document.getElementById('newPassword').value;
  const confirm = document.getElementById('confirmPassword').value;

  if (current || newPass || confirm) {
    // Simple validation
    if (!current) {
      alert('Please enter your current password.');
      return;
    }
    if (newPass.length < 8 || !/[A-Za-z]/.test(newPass) || !/[0-9]/.test(newPass)) {
      alert('New password must be at least 8 characters and contain a letter and a number.');
      return;
    }
    if (newPass !== confirm) {
      alert('New passwords do not match.');
      return;
    }
    // In real app, verify current password with backend
    alert('Password changed successfully (demo).');
    // Clear password fields
    document.getElementById('currentPassword').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
  }

  // Save to localStorage (demo)
  localStorage.setItem('orgSettings', JSON.stringify(settings));

  // Show success message
  successMessage.classList.add('show');
  setTimeout(() => {
    successMessage.classList.remove('show');
  }, 3000);
});


// ---------- INITIAL LOAD ----------
loadSettings();