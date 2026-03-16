<?php
// restriction_banner.php
// No overlay — unverified users can see pages but navbar logo redirects them.
// This file intentionally left as a no-op redirect guard.
// If you want to re-enable the overlay, restore the previous version.
if (!isset($_SESSION['user_id']) || !empty($_SESSION['credentials_verified']) || ($_SESSION['role'] ?? '') === 'admin') return;
// Silently do nothing — restriction is handled via navbar lock + logo redirect
?>