<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - OrgHub</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Core Styles -->
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/settings.css">
</head>
<body>
    <!-- Include navbar -->
    <?php include '../php/navbar.php'; ?>

    <!-- Main content -->
    <main class="main-content">
        <div class="settings-container">
            <!-- Header -->
            <div class="settings-header">
                <h1><i class="fas fa-cog"></i> Settings</h1>
                <p class="settings-subtitle">Manage your organization profile and preferences</p>
            </div>

            <!-- Organization Profile Card -->
            <div class="settings-card">
                <div class="card-header">
                    <i class="fas fa-building"></i>
                    <h2>Organization Profile</h2>
                </div>
                <div class="card-body">
                    <form id="profileForm">
                        <!-- Organization Name -->
                        <div class="form-group">
                            <label for="orgName">Organization Name <span>*</span></label>
                            <input type="text" id="orgName" value="OrgHub" required>
                        </div>

                        <!-- Logo Upload with Preview -->
                        <div class="form-group logo-group">
                            <label>Organization Logo</label>
                            <div class="logo-upload">
                                <div class="logo-preview" id="logoPreview">
                                    <img src="https://placehold.co/100x100/2d6a4f/white?text=OH" alt="Logo preview">
                                </div>
                                <div class="upload-controls">
                                    <button type="button" class="btn-upload" id="uploadBtn"><i class="fas fa-upload"></i> Choose Image</button>
                                    <input type="file" id="logoInput" accept="image/*" style="display: none;">
                                    <p class="upload-hint">Recommended: 200x200px, PNG or JPG</p>
                                </div>
                            </div>
                        </div>

                        <!-- Description / Mission -->
                        <div class="form-group">
                            <label for="mission">Description / Mission</label>
                            <textarea id="mission" rows="3">Empowering organizations with efficient management tools.</textarea>
                        </div>

                        <!-- Contact Email -->
                        <div class="form-group">
                            <label for="email">Contact Email</label>
                            <input type="email" id="email" value="contact@orghub.org">
                        </div>

                        <!-- Contact Number -->
                        <div class="form-group">
                            <label for="phone">Contact Number</label>
                            <input type="tel" id="phone" value="+1 234 567 8900">
                        </div>

                        <!-- Adviser Name -->
                        <div class="form-group">
                            <label for="adviser">Adviser Name</label>
                            <input type="text" id="adviser" value="Dr. Maria Santos">
                        </div>

                        <!-- Save Button -->
                        <div class="form-actions">
                            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Optional: Additional settings cards can be added later -->
        </div>
    </main>

    <!-- External JavaScript -->
    <script src="../js/script.js"></script> <!-- sidebar toggle -->
    <script src="../js/settings.js"></script> <!-- settings page JS -->
</body>
</html>