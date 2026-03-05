// notifications.js
// Located at: cig_user/org-dashboard/js/notifications.js

document.addEventListener('DOMContentLoaded', function () {
    const bell             = document.getElementById('notificationBell');
    const dropdown         = document.getElementById('notificationDropdown');
    const badge            = document.getElementById('notificationBadge');
    const notificationList = document.getElementById('notificationList');
    const markAllBtn       = document.getElementById('markAllRead');

    if (!bell || !dropdown || !notificationList) return;

    // ── Persistent read-state for announcements (survives page refresh) ──
    const READ_KEY = 'ann_read_ids';
    function getReadIds() {
        try { return new Set(JSON.parse(localStorage.getItem(READ_KEY) || '[]')); }
        catch { return new Set(); }
    }
    function saveReadId(id) {
        const ids = getReadIds();
        ids.add(String(id));
        try { localStorage.setItem(READ_KEY, JSON.stringify([...ids])); } catch {}
    }
    function markAllAnnouncementsRead(ids) {
        const existing = getReadIds();
        ids.forEach(id => existing.add(String(id)));
        try { localStorage.setItem(READ_KEY, JSON.stringify([...existing])); } catch {}
    }

    let submissionNotifications  = [];
    let announcementNotifications = [];

    async function loadSubmissionNotifications() {
        try {
            const res  = await fetch('get_notifications.php', { credentials: 'same-origin' });
            const data = await res.json();

            if (data.success && data.notifications.length > 0) {
                submissionNotifications = data.notifications.map(n => {
                    const isApproved = n.type === 'success';
                    const isRejected = n.type === 'error';
                    return {
                        id:      'notif-' + n.id,
                        dbId:    n.id,
                        icon:    isApproved ? 'fa-check-circle' : isRejected ? 'fa-times-circle' : 'fa-info-circle',
                        title:   n.title,
                        preview: n.message,
                        time:    formatTime(n.created_at),
                        unread:  !n.is_read,   // DB is source of truth for submissions
                        type:    n.type === 'success' ? 'approved' : n.type === 'error' ? 'rejected' : 'info'
                    };
                });
            }
        } catch (err) {
            console.warn('Could not load submission notifications:', err);
        }
    }

    function formatTime(dateStr) {
        const local   = String(dateStr).replace(' ', 'T');
        const date    = new Date(local);
        const now     = new Date();
        const diff    = Math.floor((now - date) / 1000);
        const timeStr = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

        if (diff < 60)     return 'Just now';
        if (diff < 3600)   return Math.floor(diff / 60) + ' min ago · ' + timeStr;
        if (diff < 86400)  return Math.floor(diff / 3600) + ' hr ago · ' + timeStr;
        if (diff < 172800) return 'Yesterday · ' + timeStr;
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' · ' + timeStr;
    }

    async function loadAnnouncements() {
        try {
            const res  = await fetch('get_announcements.php', { credentials: 'same-origin' });
            const data = await res.json();

            if (data.success && data.announcements.length > 0) {
                const readIds = getReadIds();   // ← persisted read state
                announcementNotifications = data.announcements.map(ann => ({
                    id:          'ann-' + ann.id,
                    dbAnnId:     ann.id,
                    icon:        ann.is_pinned ? 'fa-thumbtack' : 'fa-bullhorn',
                    title:       ann.title,
                    priority:    ann.priority  || 'low',
                    category:    ann.category  || 'general',
                    is_pinned:   ann.is_pinned || false,
                    expires_at:  ann.expires_at || null,
                    preview:     ann.content.length > 80 ? ann.content.substring(0, 80) + '…' : ann.content,
                    fullContent: ann.content,
                    time:        formatTime(ann.created_at),
                    unread:      !readIds.has(String(ann.id)),  // ← restored from localStorage
                    type:        'announcement'
                }));
                announcementNotifications.sort((a, b) => {
                    if (b.is_pinned !== a.is_pinned) return b.is_pinned ? 1 : -1;
                    const order = { urgent: 0, high: 1, low: 2 };
                    return (order[a.priority] ?? 2) - (order[b.priority] ?? 2);
                });
            }
        } catch (err) {
            console.warn('Could not load announcements:', err);
        }

        await loadSubmissionNotifications();
        renderAll();
        updateBadge();
    }

    function allNotifications() {
        return [...submissionNotifications, ...announcementNotifications];
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

        if (submissionNotifications.length > 0) {
            html += sectionHeader('fa-file-alt', '#10b981', 'Document Updates');
            submissionNotifications.forEach(n => { html += buildItem(n); });
        }

        if (announcementNotifications.length > 0) {
            html += sectionHeader('fa-bullhorn', '#f59e0b', 'Announcements');
            announcementNotifications.forEach(n => { html += buildItem(n); });
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
            approved:     '#10b981',
            rejected:     '#ef4444',
            info:         '#3b82f6',
            document:     '#3b82f6',
            event:        '#10b981',
            warning:      '#ef4444'
        };
        const priorityBadgeMap = {
            urgent: { label: 'Urgent', color: '#c0392b', bg: '#fde8e8' },
            high:   { label: 'High',   color: '#b7770d', bg: '#fff3cd' },
            low:    { label: 'Low',    color: '#555',    bg: '#f0f0f0' },
        };
        const categoryBadgeMap = {
            event:    { label: 'Event',    color: '#1d4ed8', bg: '#dbeafe' },
            deadline: { label: 'Deadline', color: '#b91c1c', bg: '#fee2e2' },
            policy:   { label: 'Policy',   color: '#6d28d9', bg: '#ede9fe' },
            general:  { label: 'General',  color: '#065f46', bg: '#d1fae5' },
        };

        const iconColor   = n.is_pinned ? '#f59e0b' : (iconColorMap[n.type] || '#6366f1');
        const unreadClass = n.unread ? 'unread' : '';

        let badgesHtml = '';
        if (n.type === 'announcement') {
            const pb = priorityBadgeMap[n.priority] || priorityBadgeMap.low;
            const cb = categoryBadgeMap[n.category] || categoryBadgeMap.general;
            badgesHtml = `
                <span style="display:inline-flex;align-items:center;gap:3px;font-size:0.62rem;
                    font-weight:700;padding:1px 6px;border-radius:20px;margin-right:3px;
                    text-transform:uppercase;background:${cb.bg};color:${cb.color};">
                    ${cb.label}</span>
                <span style="display:inline-flex;align-items:center;gap:3px;font-size:0.62rem;
                    font-weight:700;padding:1px 6px;border-radius:20px;margin-right:4px;
                    text-transform:uppercase;background:${pb.bg};color:${pb.color};">
                    ${pb.label}</span>`;
        }

        const pinHtml = n.is_pinned
            ? `<span style="font-size:0.65rem;font-weight:700;color:#b45309;
                  background:#fef3c7;padding:1px 7px;border-radius:20px;margin-right:4px;">
                  📌 Pinned</span>`
            : '';

        const expiryHtml = (n.type === 'announcement' && n.expires_at)
            ? `<span style="font-size:0.7rem;color:#b91c1c;margin-left:6px;">
                  <i class="fas fa-hourglass-end"></i> Expires ${n.expires_at}</span>`
            : '';

        const expandHtml = (n.type === 'announcement' && n.fullContent && n.fullContent.length > 80)
            ? `<button class="expand-notif-btn" data-id="${n.id}"
                   style="background:none;border:none;color:#3b82f6;font-size:12px;
                          cursor:pointer;padding:2px 0;margin-top:4px;">
                   <i class="fas fa-chevron-down"></i> Read More
               </button>
               <div class="notif-full-content" data-id="${n.id}"
                    style="display:none;font-size:12px;color:#555;margin-top:6px;line-height:1.5;
                           word-break:break-word;overflow-wrap:anywhere;">
                   ${n.fullContent}
               </div>`
            : '';

        return `
            <div class="notification-item ${unreadClass}" data-id="${n.id}" data-type="${n.type}"
                 style="min-width:0;overflow:hidden;${n.is_pinned ? 'border-left:3px solid #f59e0b;background:#fffdf0;' : ''}">
                <div class="notification-icon" style="color:${iconColor};flex-shrink:0;">
                    <i class="fas ${n.icon}"></i>
                </div>
                <div class="notification-content" style="min-width:0;overflow:hidden;flex:1;">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:3px;margin-bottom:3px;">
                        ${pinHtml}${badgesHtml}
                    </div>
                    <div class="notification-title" style="
                        word-break:break-word;overflow-wrap:anywhere;
                        min-width:0;overflow:hidden;">
                        ${n.title}
                    </div>
                    <div class="notification-preview" style="
                        word-break:break-word;overflow-wrap:anywhere;
                        white-space:normal;overflow:hidden;">${n.preview}</div>
                    <div class="notification-meta">
                        <span class="notification-time"><i class="far fa-clock"></i> ${n.time}</span>
                        ${expiryHtml}
                        <span class="status-indicator ${n.unread ? '' : 'read'}"></span>
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
        const id    = item.dataset.id;
        const notif = submissionNotifications.find(n => n.id === id)
                   || announcementNotifications.find(n => n.id === id);

        if (notif && notif.unread) {
            notif.unread = false;
            item.classList.remove('unread');
            const dot = item.querySelector('.status-indicator');
            if (dot) dot.classList.add('read');
            updateBadge();

            if (notif.type === 'announcement' && notif.dbAnnId) {
                // Persist to localStorage so it survives refresh
                saveReadId(notif.dbAnnId);
                // Also fire read receipt
                fetch('mark_read.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ announcement_id: notif.dbAnnId })
                }).catch(() => {});
            } else if (notif.dbId) {
                // Submission notification — persist to DB
                fetch('mark_notifications.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: notif.dbId })
                }).catch(() => {});
            }
        }
    });

    // ── Mark all as read ──
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            [...submissionNotifications, ...announcementNotifications].forEach(n => n.unread = false);

            // Persist all announcement IDs to localStorage
            markAllAnnouncementsRead(
                announcementNotifications.map(n => n.dbAnnId).filter(Boolean)
            );

            renderAll();
            updateBadge();

            // Persist submission notifications to DB
            fetch('mark_notifications.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ all: true })
            }).catch(() => {});
        });
    }

    // ── Init ──
    loadAnnouncements();
});