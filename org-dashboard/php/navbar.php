<?php
// navbar.php — opens its own DB connection, never relies on parent $conn

$_nb_user = [];
if (isset($_SESSION['user_id'])) {
    $_nb_id   = (int) $_SESSION['user_id'];
    $_nb_conn = @mysqli_connect("localhost", "root", "", "cig_system");
    if ($_nb_conn) {
        $_nb_r = mysqli_query($_nb_conn, "SELECT org_name, org_code, logo_path FROM users WHERE user_id = $_nb_id LIMIT 1");
        if ($_nb_r) $_nb_user = mysqli_fetch_assoc($_nb_r) ?: [];
        mysqli_close($_nb_conn);
    }
}

$_nb_org_name  = htmlspecialchars($_nb_user['org_name']  ?? ($_SESSION['org_name']  ?? 'OrgHub'));
$_nb_org_code  = htmlspecialchars($_nb_user['org_code']  ?? ($_SESSION['org_code']  ?? ''));
$_nb_logo_path = $_nb_user['logo_path'] ?? null;
$_nb_initials  = strtoupper(substr($_nb_user['org_name'] ?? $_nb_org_code, 0, 2));

$_nb_logo_url = null;
if ($_nb_logo_path) {
    $abs = dirname(dirname(__DIR__)) . '/' . ltrim($_nb_logo_path, './');
    if (file_exists($abs)) {
        $_nb_logo_url = '../../' . ltrim($_nb_logo_path, './');
    }
}

$_nb_page = basename($_SERVER['PHP_SELF']);
function nb_active(string $page): string {
    global $_nb_page;
    return $_nb_page === $page ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">

    <!-- ── Header: logo only, centered ──────────────────────────────────── -->
    <div class="sidebar-header">

        <!-- Logo — centered, click to refresh -->
        <a href="javascript:location.reload()" class="sidebar-org" title="<?= $_nb_org_name ?>">
            <div class="sidebar-org-avatar" id="sidebarAvatar">
                <?php if ($_nb_logo_url): ?>
                    <img src="<?= htmlspecialchars($_nb_logo_url) ?>"
                         alt="<?= $_nb_org_name ?>"
                         class="sidebar-org-img"
                         id="sidebarAvatarImg">
                <?php else: ?>
                    <span class="sidebar-org-initials" id="sidebarAvatarInitials">
                        <?= $_nb_initials ?>
                    </span>
                <?php endif; ?>
            </div>
        </a>

    </div>

    <!-- ── Navigation ─────────────────────────────────────────────────────── -->
    <nav class="sidebar-nav">
        <ul>
            <li class="<?= nb_active('dashboard.php') ?>">
                <a href="dashboard.php" data-tooltip="Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li class="<?= nb_active('members.php') ?>">
                <a href="members.php" data-tooltip="Members">
                    <i class="fas fa-users"></i>
                    <span class="nav-label">Members</span>
                </a>
            </li>
            <li class="<?= nb_active('document_tracking.php') ?>">
                <a href="document_tracking.php" data-tooltip="Documents">
                    <i class="fas fa-folder-open"></i>
                    <span class="nav-label">Documents</span>
                </a>
            </li>
            <li class="<?= nb_active('reports.php') ?>">
                <a href="reports.php" data-tooltip="Reports">
                    <i class="fas fa-file-alt"></i>
                    <span class="nav-label">Reports</span>
                </a>
            </li>
            <li class="<?= nb_active('certifications.php') ?>">
                <a href="certifications.php" data-tooltip="Certifications">
                    <i class="fas fa-certificate"></i>
                    <span class="nav-label">Certifications</span>
                </a>
            </li>
            <li class="<?= nb_active('archive.php') ?>">
                <a href="archive.php" data-tooltip="Archive">
                    <i class="fas fa-archive"></i>
                    <span class="nav-label">Archive</span>
                </a>
            </li>
            <li class="<?= nb_active('settings.php') ?>">
                <a href="settings.php" data-tooltip="Settings">
                    <i class="fas fa-cog"></i>
                    <span class="nav-label">Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- ── Logout + Footer ────────────────────────────────────────────────── -->
    <a href="#" class="logout-btn" id="logoutLink" data-tooltip="Logout" onclick="showLogoutModal(event)">
        <i class="fas fa-sign-out-alt"></i>
        <span class="nav-label">Logout</span>
    </a>
    <hr class="sidebar-divider">
    <div class="sidebar-footer">
        <p>© 2026 OrgHub</p>
    </div>

</aside>

<!-- ── LOGOUT MODAL OVERLAY ───────────────────────────────────────────────── -->
<div id="logoutOverlay" style="
  display:none; position:fixed; inset:0; z-index:99999;
  align-items:center; justify-content:center;
  background:rgba(10,25,18,0.45);
  backdrop-filter:blur(12px) saturate(0.8);
  -webkit-backdrop-filter:blur(12px) saturate(0.8);
  opacity:0; transition:opacity 0.3s ease;
">
  <div id="logoutCard" style="
    background:rgba(20,48,34,0.82);
    border:1px solid rgba(183,228,199,0.13);
    border-radius:28px;
    padding:2.8rem 2.6rem 2.2rem;
    max-width:400px; width:90vw;
    text-align:center;
    backdrop-filter:blur(24px);
    -webkit-backdrop-filter:blur(24px);
    box-shadow:0 32px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.04);
    font-family:'DM Sans','Inter',sans-serif;
    transform:scale(0.9) translateY(16px);
    opacity:0;
    transition:transform 0.38s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease;
  ">

    <!-- Icon -->
    <div style="
      width:72px; height:72px; border-radius:50%;
      background:linear-gradient(145deg,#1e4d35,#2d6a4f);
      display:flex; align-items:center; justify-content:center;
      margin:0 auto 1.4rem;
      box-shadow:0 0 0 10px rgba(45,106,79,0.15), 0 8px 28px rgba(0,0,0,0.35);
    ">
      <svg viewBox="0 0 24 24" fill="none" stroke="#b7e4c7" stroke-width="1.8"
           stroke-linecap="round" stroke-linejoin="round" width="30" height="30">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </div>

    <p style="font-size:0.68rem;letter-spacing:0.22em;text-transform:uppercase;color:rgba(183,228,199,0.4);margin-bottom:0.5rem;font-family:inherit;">ORGHUB</p>
    <h2 style="font-family:'Playfair Display','Georgia',serif;font-size:2rem;font-weight:700;color:#f0faf4;margin-bottom:0.65rem;letter-spacing:-0.01em;">Sign out?</h2>
    <p style="font-size:0.88rem;color:rgba(183,228,199,0.52);line-height:1.65;margin-bottom:2.1rem;font-weight:300;font-family:inherit;">
      You're about to sign out of your organization<br>account. Any unsaved changes will be lost.
    </p>

    <div style="display:flex;gap:0.8rem;">
      <button onclick="hideLogoutModal()" style="
        flex:1; padding:0.85rem;
        background:rgba(183,228,199,0.07);
        border:1px solid rgba(183,228,199,0.16);
        border-radius:14px; color:rgba(200,235,215,0.75);
        font-family:inherit; font-size:0.94rem; font-weight:500;
        cursor:pointer; transition:background 0.2s, color 0.2s;
      "
      onmouseover="this.style.background='rgba(183,228,199,0.13)';this.style.color='#c8ebd7';"
      onmouseout="this.style.background='rgba(183,228,199,0.07)';this.style.color='rgba(200,235,215,0.75)';">
        Stay
      </button>
      <button onclick="doLogout()" style="
        flex:1; padding:0.85rem;
        background:linear-gradient(135deg,#1e4d35 0%,#2d6a4f 55%,#3a8a62 100%);
        border:none; border-radius:14px; color:#f0faf4;
        font-family:inherit; font-size:0.94rem; font-weight:600;
        cursor:pointer; transition:transform 0.18s, box-shadow 0.18s;
        box-shadow:0 6px 20px rgba(45,106,79,0.42);
      "
      onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 10px 26px rgba(45,106,79,0.55)';"
      onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 6px 20px rgba(45,106,79,0.42)';">
        Yes, sign out
      </button>
    </div>
  </div>
</div>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<script>
function showLogoutModal(e) {
  if (e) e.preventDefault();
  const overlay = document.getElementById('logoutOverlay');
  const card    = document.getElementById('logoutCard');
  overlay.style.display = 'flex';
  requestAnimationFrame(() => requestAnimationFrame(() => {
    overlay.style.opacity = '1';
    card.style.transform  = 'scale(1) translateY(0)';
    card.style.opacity    = '1';
  }));
}
function hideLogoutModal() {
  const overlay = document.getElementById('logoutOverlay');
  const card    = document.getElementById('logoutCard');
  card.style.transform = 'scale(0.93) translateY(10px)';
  card.style.opacity   = '0';
  overlay.style.opacity = '0';
  setTimeout(() => { overlay.style.display = 'none'; }, 320);
}
function doLogout() {
  window.location.href = 'logout.php';
}
// Close on backdrop click
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) hideLogoutModal();
});
</script>

<main class="main-content">