<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$org_id = (int) $_SESSION['user_id'];
$user   = [];

if ($conn) {
    $r = mysqli_query($conn, "SELECT user_id, username, email, full_name, org_name, org_code, description, contact_person, phone, logo_path FROM users WHERE user_id = $org_id LIMIT 1");
    if ($r) $user = mysqli_fetch_assoc($r) ?: [];
    mysqli_close($conn);
}

$logo_path  = $user['logo_path'] ?? null;
$avatar_src = $logo_path && file_exists(dirname(dirname(__DIR__)) . '/' . ltrim($logo_path, './'))
    ? '../../' . ltrim($logo_path, './')
    : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - OrgHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <link rel="stylesheet" href="../css/settings.css">
</head>
<body>

<?php include '../php/navbar.php'; ?>

    <?php include '../php/topbar.php'; ?>
    <div class="settings-container">

        <!-- ── Page Header ───────────────────────────────────────────────── -->
        <div class="settings-header">
            <div class="settings-header-left">
                <div class="settings-header-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <div>
                    <h1>Settings</h1>
                    <p>Manage your organization profile and preferences</p>
                </div>
            </div>
        </div>

        <!-- ── Organization Profile Card ─────────────────────────────────── -->
        <div class="settings-card">

            <!-- Card header -->
            <div class="card-header">
                <div class="card-header-icon"><i class="fas fa-building"></i></div>
                <div>
                    <h2>Organization Profile</h2>
                    <p>Update your organization's information</p>
                </div>
            </div>

            <div class="card-body">

                <!-- ── Logo Section ───────────────────────────────────────── -->
                <div class="logo-section">
                    <div class="logo-preview-wrap" id="logoPreviewWrap">
                        <?php if ($avatar_src): ?>
                            <img src="<?= htmlspecialchars($avatar_src) ?>" alt="Logo" id="logoPreviewImg">
                        <?php else: ?>
                            <div class="logo-initials" id="logoInitials">
                                <?= strtoupper(substr($user['org_name'] ?? 'O', 0, 2)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="logo-upload-info">
                        <p class="logo-org-name"><?= htmlspecialchars($user['org_name'] ?? '') ?></p>
                        <p class="logo-org-code"><?= htmlspecialchars($user['org_code'] ?? '') ?></p>
                        <div class="logo-btn-row">
                            <button type="button" class="btn-upload-logo" id="uploadLogoBtn">
                                <i class="fas fa-camera"></i> Change Logo
                            </button>
                            <?php if ($logo_path): ?>
                            <button type="button" class="btn-remove-logo" id="removeLogoBtn">
                                <i class="fas fa-trash-alt"></i> Remove
                            </button>
                            <?php endif; ?>
                        </div>
                        <p class="upload-hint">PNG, JPG up to 2MB · Recommended 200×200px</p>
                        <input type="file" id="logoFileInput" accept="image/*" style="display:none">
                    </div>
                </div>

                <div class="divider"></div>

                <!-- ── Profile Form ───────────────────────────────────────── -->
                <form id="profileForm" novalidate>
                    <div class="form-group">
                        <label for="description">Organization Tagline / Mission</label>
                        <textarea id="description" name="description" rows="3" placeholder="Your organization's tagline or mission statement…"><?= htmlspecialchars($user['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactPerson">Contact Person</label>
                            <input type="text" id="contactPerson" name="contact_person"
                                   value="<?= htmlspecialchars($user['contact_person'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save" id="saveProfileBtn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>

            </div><!-- /.card-body -->
        </div><!-- /.settings-card -->

    </div><!-- /.settings-container -->
</main>

<script src="../js/navbar.js"></script>
<script src="../js/script.js"></script>
<script src="../js/notifications.js"></script>
<script src="../js/settings.js"></script>
</body>
</html>