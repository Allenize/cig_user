// settings.js

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
    const icons  = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
    document.querySelectorAll('.settings-toast').forEach(t => t.remove());
    const t = document.createElement('div');
    t.className = 'settings-toast';
    t.style.background = colors[type] || colors.success;
    t.innerHTML = `<i class="fas ${icons[type]}"></i><span>${msg}</span>`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}

function setLoading(btn, loading) {
    if (loading) {
        btn._orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';
    } else {
        btn.disabled = false;
        btn.innerHTML = btn._orig || btn.innerHTML;
    }
}

// ── Topbar Live Update Helpers ────────────────────────────────────────────────
function updateTopbarAvatar(imgSrc) {
    const link = document.getElementById('topbarAvatarLink');
    if (!link) return;
    link.innerHTML = `<img src="${imgSrc}" alt="Logo" class="avatar" id="topbarAvatarImg">`;
}

function updateTopbarInitials(name) {
    const link = document.getElementById('topbarAvatarLink');
    if (!link) return;
    link.innerHTML = `<div class="avatar avatar-initials" id="topbarAvatarInitials">${name.substring(0,2).toUpperCase()}</div>`;
}

function updateTopbarOrgCode(code) {
    const el = document.getElementById('topbarOrgCode');
    if (el && code) el.textContent = code;
}

// ── Logo Upload ───────────────────────────────────────────────────────────────
const logoFileInput = document.getElementById('logoFileInput');
const uploadLogoBtn = document.getElementById('uploadLogoBtn');
const removeLogoBtn = document.getElementById('removeLogoBtn');

if (uploadLogoBtn) {
    uploadLogoBtn.addEventListener('click', () => logoFileInput.click());
}

if (logoFileInput) {
    logoFileInput.addEventListener('change', async function () {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            showToast('Image must be under 2MB.', 'error'); return;
        }

        // Instant local preview in settings card + topbar
        const reader = new FileReader();
        reader.onload = e => {
            // Settings card preview
            const wrap = document.getElementById('logoPreviewWrap');
            if (wrap) wrap.innerHTML = `<img src="${e.target.result}" alt="Logo preview" id="logoPreviewImg" style="width:100%;height:100%;object-fit:cover;">`;
            // Topbar live preview
            updateTopbarAvatar(e.target.result);
        };
        reader.readAsDataURL(file);

        // Upload to server
        uploadLogoBtn.disabled = true;
        uploadLogoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading…';
        const fd = new FormData();
        fd.append('action', 'upload_logo');
        fd.append('logo', file);
        try {
            const res  = await fetch('settings_save.php', { method: 'POST', credentials: 'same-origin', body: fd });
            const json = await res.json();
            if (json.success) {
                showToast('Logo updated!', 'success');
                // Swap topbar preview to the real server URL
                if (json.logo_url) updateTopbarAvatar(json.logo_url);
                // Show remove button if not there
                if (!document.getElementById('removeLogoBtn')) {
                    const btnRow = document.querySelector('.logo-btn-row');
                    if (btnRow) {
                        const rb = document.createElement('button');
                        rb.type = 'button'; rb.id = 'removeLogoBtn';
                        rb.className = 'btn-remove-logo';
                        rb.innerHTML = '<i class="fas fa-trash-alt"></i> Remove';
                        rb.addEventListener('click', removeLogo);
                        btnRow.appendChild(rb);
                    }
                }
            } else {
                showToast(json.message || 'Upload failed.', 'error');
            }
        } catch (e) {
            showToast('Server error.', 'error');
        }
        uploadLogoBtn.disabled = false;
        uploadLogoBtn.innerHTML = '<i class="fas fa-camera"></i> Change Logo';
        this.value = '';
    });
}

async function removeLogo() {
    if (!confirm('Remove the organization logo?')) return;
    try {
        const res  = await fetch('settings_save.php', {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove_logo'
        });
        const json = await res.json();
        if (json.success) {
            const orgName = document.getElementById('orgName')?.value || 'O';
            // Settings card: restore initials
            const wrap = document.getElementById('logoPreviewWrap');
            if (wrap) wrap.innerHTML = `<div class="logo-initials" id="logoInitials">${orgName.substring(0,2).toUpperCase()}</div>`;
            // Topbar: restore initials
            updateTopbarInitials(orgName);
            document.getElementById('removeLogoBtn')?.remove();
            showToast('Logo removed.', 'info');
        } else {
            showToast(json.message || 'Failed to remove logo.', 'error');
        }
    } catch (e) {
        showToast('Server error.', 'error');
    }
}
if (removeLogoBtn) removeLogoBtn.addEventListener('click', removeLogo);

// ── Org Code: auto-uppercase while typing ────────────────────────────────────
const orgCodeInput = document.getElementById('orgCode');
if (orgCodeInput) {
    orgCodeInput.addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
}

// ── Profile Form ──────────────────────────────────────────────────────────────
document.getElementById('profileForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn     = document.getElementById('saveProfileBtn');
    const newName = document.getElementById('orgName').value.trim();
    const newCode = document.getElementById('orgCode').value.trim().toUpperCase();
    setLoading(btn, true);
    const body = new URLSearchParams({
        action:         'save_profile',
        org_name:       newName,
        org_code:       newCode,
        description:    document.getElementById('description').value,
        contact_person: document.getElementById('contactPerson').value,
        phone:          document.getElementById('phone').value,
    });
    try {
        const res  = await fetch('settings_save.php', { method: 'POST', credentials: 'same-origin', body });
        const json = await res.json();
        showToast(json.message, json.success ? 'success' : 'error');
        if (json.success) {
            // Settings card: update org name + code labels and initials fallback
            const logoName = document.querySelector('.logo-org-name');
            if (logoName) logoName.textContent = newName;
            const logoCode = document.querySelector('.logo-org-code');
            if (logoCode) logoCode.textContent = newCode;
            const initials = document.getElementById('logoInitials');
            if (initials) initials.textContent = newName.substring(0,2).toUpperCase();
            // Topbar: update org code label
            updateTopbarOrgCode(json.org_code || newCode);
            // Topbar: update initials if no logo image shown
            const topbarImg = document.getElementById('topbarAvatarImg');
            if (!topbarImg) updateTopbarInitials(newName);
        }
    } catch { showToast('Server error.', 'error'); }
    setLoading(btn, false);
});