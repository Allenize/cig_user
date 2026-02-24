// ============================================
//  Announcements - Data & Functionality
// ============================================

// ---------- MOCK ANNOUNCEMENTS DATA ----------
const announcementsData = [
  {
    id: 1,
    title: 'New Office Hours',
    date: '2026-02-18',
    description: 'Effective March 1, office hours will be 8:30 AM to 5:30 PM Monday–Friday.'
  },
  {
    id: 2,
    title: 'Annual General Meeting',
    date: '2026-02-15',
    description: 'The AGM will be held on March 10 at 10:00 AM in the Main Hall. All members are requested to attend.'
  },
  {
    id: 3,
    title: 'Holiday Schedule',
    date: '2026-02-10',
    description: 'The office will be closed on February 28 for National Day. Regular operations resume March 1.'
  },
  {
    id: 4,
    title: 'New Document Management System',
    date: '2026-02-20',
    description: 'We have launched a new document management system. Please refer to the training materials.'
  },
  {
    id: 5,
    title: 'Security Update',
    date: '2026-02-21',
    description: 'Two-factor authentication will become mandatory starting next week. Please set it up in your settings.'
  }
];

// ---------- READ STATUS STORAGE ----------
// Use localStorage to persist which announcements the user has marked as read.
// Key: "announcements_read" – array of read IDs.
let readIds = new Set();

function loadReadStatus() {
  const stored = localStorage.getItem('announcements_read');
  if (stored) {
    try {
      readIds = new Set(JSON.parse(stored));
    } catch (e) {
      readIds = new Set();
    }
  }
}

function saveReadStatus() {
  localStorage.setItem('announcements_read', JSON.stringify([...readIds]));
}

// Mark an announcement as read
function markAsRead(id) {
  readIds.add(id);
  saveReadStatus();
  renderAnnouncements(); // re-render to update UI
}

// ---------- DOM ELEMENTS ----------
const announcementsGrid = document.getElementById('announcementsGrid');

// ---------- RENDER ANNOUNCEMENTS ----------
function renderAnnouncements() {
  // Determine which announcements are new (not read)
  const announcements = announcementsData.map(a => ({
    ...a,
    isNew: !readIds.has(a.id)
  }));

  if (announcements.length === 0) {
    announcementsGrid.innerHTML = '<p class="no-announcements">No announcements at this time.</p>';
    return;
  }

  let html = '';
  announcements.forEach(a => {
    const newClass = a.isNew ? 'new' : '';
    const newBadge = a.isNew ? '<span class="announcement-badge">NEW</span>' : '';
    // Format date nicely
    const formattedDate = new Date(a.date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    
    html += `
      <div class="announcement-card ${newClass}" data-id="${a.id}">
        ${newBadge}
        <div class="announcement-title">${a.title}</div>
        <div class="announcement-date"><i class="fas fa-calendar-alt"></i> ${formattedDate}</div>
        <div class="announcement-description">${a.description}</div>
        <div class="announcement-footer">
          ${a.isNew ? `<button class="btn-mark-read" onclick="markAsRead(${a.id})"><i class="fas fa-check-circle"></i> Mark as Read</button>` : ''}
        </div>
      </div>
    `;
  });
  announcementsGrid.innerHTML = html;
}

// ---------- MAKE MARK AS READ GLOBALLY AVAILABLE ----------
window.markAsRead = markAsRead;

// ---------- LOGOUT (demo) ----------
document.getElementById('logoutLink')?.addEventListener('click', (e) => {
  e.preventDefault();
  alert('You have been logged out (demo).');
});

// ---------- INITIAL RENDER ----------
loadReadStatus();
renderAnnouncements();