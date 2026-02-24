// ============================================
//  Event Management - Data & Functionality
// ============================================

// ---------- MOCK EVENTS DATA ----------
let events = [
  {
    id: 1,
    title: 'Annual Strategy Meeting',
    date: '2026-03-15',
    location: 'Main Conference Hall',
    status: 'Approved',
    proposal: null
  },
  {
    id: 2,
    title: 'Team Building Workshop',
    date: '2026-03-22',
    location: 'Rooftop Garden',
    status: 'Pending',
    proposal: null
  },
  {
    id: 3,
    title: 'Product Launch Webinar',
    date: '2026-04-05',
    location: 'Online (Zoom)',
    status: 'Completed',
    proposal: null
  },
  {
    id: 4,
    title: 'Quarterly Review',
    date: '2026-03-10',
    location: 'Conference Room B',
    status: 'Approved',
    proposal: null
  }
];

// ---------- DOM ELEMENTS ----------
const eventsGrid = document.getElementById('eventsGrid');
const createEventBtn = document.getElementById('createEventBtn');
const modal = document.getElementById('eventModal');
const closeModal = document.getElementById('closeModal');
const cancelModal = document.getElementById('cancelModal');
const eventForm = document.getElementById('eventForm');
const modalTitle = document.getElementById('modalTitle');
const eventId = document.getElementById('eventId');
const titleInput = document.getElementById('title');
const dateInput = document.getElementById('date');
const locationInput = document.getElementById('location');
const statusSelect = document.getElementById('status');
const proposalInput = document.getElementById('proposal');

let editingId = null;

// ---------- RENDER EVENT CARDS ----------
function renderEvents() {
  let html = '';
  events.forEach(event => {
    const statusClass = event.status.toLowerCase();
    html += `
      <div class="event-card" data-id="${event.id}">
        <div class="event-title">${event.title}</div>
        <div class="event-detail"><i class="fas fa-calendar-alt"></i> ${formatDate(event.date)}</div>
        <div class="event-detail"><i class="fas fa-map-marker-alt"></i> ${event.location}</div>
        <div class="event-status">
          <span class="status-badge ${statusClass}">${event.status}</span>
        </div>
        <div class="event-actions">
          <i class="fas fa-edit" onclick="editEvent(${event.id})"></i>
          <i class="fas fa-trash-alt" onclick="deleteEvent(${event.id})"></i>
        </div>
      </div>
    `;
  });
  eventsGrid.innerHTML = html;
}

// Helper to format date nicely (YYYY-MM-DD to locale)
function formatDate(dateStr) {
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateStr).toLocaleDateString(undefined, options);
}

// ---------- OPEN MODAL (Create) ----------
createEventBtn.addEventListener('click', () => {
  editingId = null;
  modalTitle.innerText = 'Create Event';
  eventForm.reset();
  eventId.value = '';
  statusSelect.value = 'Pending'; // default
  proposalInput.value = ''; // clear file
  modal.classList.add('show');
});

// ---------- EDIT EVENT ----------
window.editEvent = function(id) {
  const event = events.find(e => e.id === id);
  if (!event) return;

  editingId = id;
  modalTitle.innerText = 'Edit Event';
  eventId.value = event.id;
  titleInput.value = event.title;
  dateInput.value = event.date;
  locationInput.value = event.location;
  statusSelect.value = event.status;
  // File input cannot be pre-filled for security reasons
  proposalInput.value = '';
  modal.classList.add('show');
};

// ---------- DELETE EVENT ----------
window.deleteEvent = function(id) {
  if (confirm('Are you sure you want to delete this event?')) {
    events = events.filter(e => e.id !== id);
    renderEvents();
  }
};

// ---------- CLOSE MODAL ----------
function closeModalFunc() {
  modal.classList.remove('show');
  eventForm.reset();
}

closeModal.addEventListener('click', closeModalFunc);
cancelModal.addEventListener('click', closeModalFunc);
window.addEventListener('click', (e) => {
  if (e.target === modal) closeModalFunc();
});

// ---------- SAVE EVENT (FORM SUBMIT) ----------
eventForm.addEventListener('submit', (e) => {
  e.preventDefault();

  // Basic validation
  if (!titleInput.value || !dateInput.value || !locationInput.value) {
    alert('Please fill all required fields.');
    return;
  }

  const newEvent = {
    id: editingId || Date.now(), // simple ID
    title: titleInput.value.trim(),
    date: dateInput.value,
    location: locationInput.value.trim(),
    status: statusSelect.value,
    // In a real app, you'd upload the file and store reference
    proposal: proposalInput.files.length ? proposalInput.files[0].name : null
  };

  if (editingId) {
    // Update existing
    const index = events.findIndex(e => e.id === editingId);
    if (index !== -1) events[index] = newEvent;
  } else {
    // Add new
    events.push(newEvent);
  }

  renderEvents();
  closeModalFunc();
});

// ---------- LOGOUT (demo) ----------
document.getElementById('logoutLink')?.addEventListener('click', (e) => {
  e.preventDefault();
  alert('You have been logged out (demo).');
});

// ---------- INITIAL RENDER ----------
renderEvents();