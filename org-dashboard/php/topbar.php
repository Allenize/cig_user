<?php
// topbar.php
// This file contains the top navigation bar with notification bell, dropdown, and modal.
// It assumes session is already started and user is logged in.
?>
<header class="top-bar">
    
    <div class="user-info">
        <!-- Notification Bell -->
        <div class="notification-bell" id="notificationBell">
            <i class="fas fa-bell"></i>
            <span class="badge" id="notificationBadge">3</span>
        </div>
        <!-- Notification Dropdown Panel -->
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="dropdown-header">
                <h3>Notifications</h3>
                <button class="mark-read" id="markAllRead">Mark all as read</button>
            </div>
            <div class="notification-list" id="notificationList">
                <!-- Notifications will be inserted by JavaScript -->
            </div>
            <div class="dropdown-footer">
                <a href="notifications.php">View all notifications</a>
            </div>
        </div>
        <!-- User Info -->
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
        <img src="https://placehold.co/40x40/2d3748/white?text=JD" alt="User avatar" class="avatar">
    </div>
</header>

<!-- Notification Details Modal -->
<div id="notificationModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" id="closeNotificationModal">&times;</span>
        <h2><i class="fas fa-bell"></i> Notification Details</h2>
        <div id="notificationModalBody" class="modal-body">
            <!-- Dynamic content will be inserted here -->
        </div>
    </div>
</div>
<script src="../js/notifications.js"></script>