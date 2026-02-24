// notifications.js

document.addEventListener('DOMContentLoaded', function() {
    const bell = document.getElementById('notificationBell');
    const dropdown = document.getElementById('notificationDropdown');
    const badge = document.getElementById('notificationBadge');
    const notificationList = document.getElementById('notificationList');
    const markAllBtn = document.getElementById('markAllRead');

    // Sample notification data (replace with real data from server)
    let notifications = [
        {
            id: 1,
            icon: 'fa-file-alt',
            title: 'New Document Uploaded',
            preview: 'Q2 Financial Report has been uploaded by John.',
            time: '5 min ago',
            unread: true,
            type: 'Document'
        },
        {
            id: 2,
            icon: 'fa-calendar-alt',
            title: 'Event Reminder',
            preview: 'General Assembly starts in 1 hour.',
            time: '1 hour ago',
            unread: true,
            type: 'Event'
        },
        {
            id: 3,
            icon: 'fa-bullhorn',
            title: 'Announcement',
            preview: 'New policy updates effective next week.',
            time: '3 hours ago',
            unread: false,
            type: 'Announcement'
        },
        {
            id: 4,
            icon: 'fa-exclamation-triangle',
            title: 'System Maintenance',
            preview: 'Scheduled downtime on Sunday 2 AM.',
            time: 'Yesterday',
            unread: true,
            type: 'Warning'
        },
        {
            id: 5,
            icon: 'fa-file-alt',
            title: 'Report Approved',
            preview: 'Your Q1 report has been approved.',
            time: '2 days ago',
            unread: false,
            type: 'Document'
        }
    ];

    // Update badge count
    function updateBadge() {
        const unreadCount = notifications.filter(n => n.unread).length;
        badge.textContent = unreadCount;
        if (unreadCount === 0) {
            badge.style.display = 'none';
        } else {
            badge.style.display = 'block';
        }
    }

    // Render notifications list
    function renderNotifications() {
        if (notifications.length === 0) {
            notificationList.innerHTML = '<div class="notification-item" style="justify-content:center;">No notifications</div>';
            return;
        }
        let html = '';
        notifications.forEach(n => {
            const unreadClass = n.unread ? 'unread' : '';
            const statusClass = n.unread ? '' : 'read';
            html += `
                <div class="notification-item ${unreadClass}" data-id="${n.id}">
                    <div class="notification-icon"><i class="fas ${n.icon}"></i></div>
                    <div class="notification-content">
                        <div class="notification-title">${n.title}</div>
                        <div class="notification-preview">${n.preview}</div>
                        <div class="notification-meta">
                            <span class="notification-time"><i class="far fa-clock"></i> ${n.time}</span>
                            <span class="status-indicator ${statusClass}"></span>
                        </div>
                    </div>
                </div>
            `;
        });
        notificationList.innerHTML = html;
    }

    // Toggle dropdown
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Mark single notification as read when clicked
    notificationList.addEventListener('click', function(e) {
        const item = e.target.closest('.notification-item');
        if (!item) return;
        const id = item.dataset.id;
        const notification = notifications.find(n => n.id == id);
        if (notification && notification.unread) {
            notification.unread = false;
            item.classList.remove('unread');
            item.querySelector('.status-indicator').classList.add('read');
            updateBadge();
        }
        // Optional: perform additional action (e.g., navigate to notification detail)
    });

    // Mark all as read
    markAllBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notifications.forEach(n => n.unread = false);
        renderNotifications(); // re-render to update all items
        updateBadge();
    });

    // Initial render
    renderNotifications();
    updateBadge();
});