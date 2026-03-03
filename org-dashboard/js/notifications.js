// notifications.js
// Located at: cig_user/org-dashboard/js/notifications.js

document.addEventListener('DOMContentLoaded', function () {
    const bell             = document.getElementById('notificationBell');
    const dropdown         = document.getElementById('notificationDropdown');
    const badge            = document.getElementById('notificationBadge');
    const notificationList = document.getElementById('notificationList');
    const markAllBtn       = document.getElementById('markAllRead');

    if (!bell || !dropdown || !notificationList) return;

    // ── Static system notifications ──
    let staticNotifications = [
        {
            id: 'n1',
            icon: 'fa-file-alt',
            title: 'New Document Uploaded',
            preview: 'Q2 Financial Report has been uploaded by John.',
            time: '5 min ago',
            unread: true,
            type: 'document'
        },
        {
            id: 'n2',
            icon: 'fa-calendar-alt',
            title: 'Event Reminder',
            preview: 'General Assembly starts in 1 hour.',
            time: '1 hour ago',
            unread: true,
            type: 'event'
        },
        {
            id: 'n3',
            icon: 'fa-exclamation-triangle',
            title: 'System Maintenance',
            preview: 'Scheduled downtime on Sunday 2 AM.',
            time: 'Yesterday',
            unread: true,
            type: 'warning'
        }
    ];

    // ── Announcements from DB ──
    let announcementNotifications = [];

    async function loadAnnouncements() {
        try {
            // fetch path is relative to the current PAGE URL (php/dashboard.php)
            // so get_announcements.php is in the same php/ folder
            const res  = await fetch('get_announcements.php', { credentials: 'same-origin' });
            const data = await res.json();

            if (data.success && data.announcements.length > 0) {
                announcementNotifications = data.announcements.map(ann => ({
                    id:          'ann-' + ann.id,
                    icon:        'fa-bullhorn',
                    title:       ann.title,
                    preview:     ann.content.length > 80 ? ann.content.substring(0, 80) + '…' : ann.content,
                    fullContent: ann.content,
                    time:        ann.created_at,
                    unread:      true,
                    type:        'announcement'
                }));
            }
        } catch (err) {
            console.warn('Could not load announcements:', err);
        }

        renderAll();
        updateBadge();
    }

    function allNotifications() {
        return [...announcementNotifications, ...staticNotifications];
    }

    // ── Badge ──
    function updateBadge() {
        const count         = allNotifications().filter(n => n.unread).length;
        badge.textContent   = count;
        badge.style.display = count === 0 ? 'none' : 'block';
    }

    // ── Render ──
    function renderAll() {
        const items = allNotifications();

        if (items.length === 0) {
            notificationList.innerHTML = '<div class="notification-item" style="justify-content:center;color:#888;">No notifications</div>';
            return;
        }

        let html = '';

        if (announcementNotifications.length > 0) {
            html += sectionHeader('fa-bullhorn', '#f59e0b', 'Announcements');
            announcementNotifications.forEach(n => { html += buildItem(n); });
        }

        if (staticNotifications.length > 0) {
            html += sectionHeader('fa-bell', '#6366f1', 'System');
            staticNotifications.forEach(n => { html += buildItem(n); });
        }

        notificationList.innerHTML = html;
    }

    function sectionHeader(icon, color, label) {
        return `<div style="padding:8px 16px;font-size:11px;font-weight:700;color:#888;
                    text-transform:uppercase;letter-spacing:.8px;background:#f9f9f9;
                    border-bottom:1px solid #eee;border-top:1px solid #eee;">
                    <i class="fas ${icon}" style="margin-right:5px;color:${color};"></i>${label}
                </div>`;
    }

    function buildItem(n) {
        const iconColorMap = {
            announcement: '#f59e0b',
            document:     '#3b82f6',
            event:        '#10b981',
            warning:      '#ef4444'
        };
        const iconColor   = iconColorMap[n.type] || '#6366f1';
        const unreadClass = n.unread ? 'unread' : '';
        const statusClass = n.unread ? '' : 'read';

        const expandHtml = (n.type === 'announcement' && n.fullContent && n.fullContent.length > 80)
            ? `<button class="expand-notif-btn" data-id="${n.id}"
                   style="background:none;border:none;color:#3b82f6;font-size:12px;
                          cursor:pointer;padding:2px 0;margin-top:4px;">
                   <i class="fas fa-chevron-down"></i> Read More
               </button>
               <div class="notif-full-content" data-id="${n.id}"
                    style="display:none;font-size:12px;color:#555;margin-top:6px;line-height:1.5;">
                   ${n.fullContent}
               </div>`
            : '';

        return `
            <div class="notification-item ${unreadClass}" data-id="${n.id}" data-type="${n.type}">
                <div class="notification-icon" style="color:${iconColor};">
                    <i class="fas ${n.icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${n.title}</div>
                    <div class="notification-preview">${n.preview}</div>
                    <div class="notification-meta">
                        <span class="notification-time"><i class="far fa-clock"></i> ${n.time}</span>
                        <span class="status-indicator ${statusClass}"></span>
                    </div>
                    ${expandHtml}
                </div>
            </div>`;
    }

    // ── Toggle dropdown ──
    bell.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });

    document.addEventListener('click', function (e) {
        if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    // ── Clicks inside the list ──
    notificationList.addEventListener('click', function (e) {
        const expandBtn = e.target.closest('.expand-notif-btn');
        if (expandBtn) {
            e.stopPropagation();
            const id      = expandBtn.dataset.id;
            const fullDiv = notificationList.querySelector(`.notif-full-content[data-id="${id}"]`);
            const isOpen  = fullDiv.style.display !== 'none';
            fullDiv.style.display = isOpen ? 'none' : 'block';
            expandBtn.innerHTML   = isOpen
                ? '<i class="fas fa-chevron-down"></i> Read More'
                : '<i class="fas fa-chevron-up"></i> Show Less';
            return;
        }

        const item = e.target.closest('.notification-item');
        if (!item) return;
        const id   = item.dataset.id;
        const notif = announcementNotifications.find(n => n.id === id)
                   || staticNotifications.find(n => n.id === id);

        if (notif && notif.unread) {
            notif.unread = false;
            item.classList.remove('unread');
            const dot = item.querySelector('.status-indicator');
            if (dot) dot.classList.add('read');
            updateBadge();
        }
    });

    // ── Mark all as read ──
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            [...announcementNotifications, ...staticNotifications].forEach(n => n.unread = false);
            renderAll();
            updateBadge();
        });
    }

    // ── Init ──
    loadAnnouncements();
});