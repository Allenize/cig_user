// ============================================
//  Reports Submission - Data & Functionality
// ============================================

// ---------- MOCK EVENTS DATA (for dropdown) ----------
const eventsList = [
  { id: 1, title: 'Annual Strategy Meeting' },
  { id: 2, title: 'Team Building Workshop' },
  { id: 3, title: 'Product Launch Webinar' },
  { id: 4, title: 'Quarterly Review' }
];

// ---------- MOCK REPORTS DATA ----------
let reports = [
  {
    id: 1,
    title: 'Q1 Accomplishment Report',
    eventId: 1,
    eventTitle: 'Annual Strategy Meeting',
    submittedDate: '2026-02-15',
    status: 'Approved',
    fileName: 'q1_report.pdf'
  },
  {
    id: 2,
    title: 'Workshop Summary',
    eventId: 2,
    eventTitle: 'Team Building Workshop',
    submittedDate: '2026-02-18',
    status: 'Under Review',
    fileName: 'workshop_summary.docx'
  },
  {
    id: 3,
    title: 'Webinar Feedback',
    eventId: 3,
    eventTitle: 'Product Launch Webinar',
    submittedDate: '2026-02-20',
    status: 'Submitted',
    fileName: 'webinar_feedback.pdf'
  }
];

// ---------- DOM ELEMENTS ----------
const tableBody = document.getElementById('tableBody');
const uploadReportBtn = document.getElementById('uploadReportBtn');
const modal = document.getElementById('reportModal');
const closeModal = document.getElementById('closeModal');
const cancelModal = document.getElementById('cancelModal');
const reportForm = document.getElementById('reportForm');
const modalTitle = document.getElementById('modalTitle');
const reportId = document.getElementById('reportId');
const titleInput = document.getElementById('title');
const eventSelect = document.getElementById('event');
const fileInput = document.getElementById('file');
const statusSelect = document.getElementById('status');

let editingId = null;

// ---------- POPULATE EVENT DROPDOWN ----------
function populateEventDropdown() {
  eventSelect.innerHTML = '<option value="">Select Event</option>';
  eventsList.forEach(event => {
    const option = document.createElement('option');
    option.value = event.id;
    option.textContent = event.title;
    eventSelect.appendChild(option);
  });
}

// ---------- RENDER TABLE ----------
function renderTable() {
  let html = '';
  reports.forEach(report => {
    const statusClass = report.status.toLowerCase().replace(' ', '-');
    html += `
      <tr>
        <td>${report.title}</td>
        <td>${report.eventTitle}</td>
        <td>${formatDate(report.submittedDate)}</td>
        <td><span class="status-badge ${statusClass}">${report.status}</span></td>
        <td>
          <div class="action-icons">
            <i class="fas fa-eye" onclick="viewFile(${report.id})" title="View file"></i>
            <i class="fas fa-edit" onclick="editReport(${report.id})" title="Edit"></i>
            <i class="fas fa-trash-alt" onclick="deleteReport(${report.id})" title="Delete"></i>
          </div>
        </td>
      </tr>
    `;
  });
  tableBody.innerHTML = html;
}

// Helper to format date
function formatDate(dateStr) {
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateStr).toLocaleDateString(undefined, options);
}

// ---------- VIEW FILE (simulate) ----------
window.viewFile = function(id) {
  const report = reports.find(r => r.id === id);
  if (report) {
    alert(`Viewing file: ${report.fileName} (demo)`);
    // In real app, open file URL
  }
};

// ---------- OPEN MODAL (Upload) ----------
uploadReportBtn.addEventListener('click', () => {
  editingId = null;
  modalTitle.innerText = 'Upload Report';
  reportForm.reset();
  reportId.value = '';
  statusSelect.value = 'Submitted'; // default
  fileInput.required = true; // file required for new
  modal.classList.add('show');
});

// ---------- EDIT REPORT ----------
window.editReport = function(id) {
  const report = reports.find(r => r.id === id);
  if (!report) return;

  editingId = id;
  modalTitle.innerText = 'Edit Report';
  reportId.value = report.id;
  titleInput.value = report.title;
  eventSelect.value = report.eventId;
  statusSelect.value = report.status;
  // File input cannot be pre-filled; make it optional
  fileInput.required = false;
  modal.classList.add('show');
};

// ---------- DELETE REPORT ----------
window.deleteReport = function(id) {
  if (confirm('Are you sure you want to delete this report?')) {
    reports = reports.filter(r => r.id !== id);
    renderTable();
  }
};

// ---------- CLOSE MODAL ----------
function closeModalFunc() {
  modal.classList.remove('show');
  reportForm.reset();
  fileInput.required = true; // reset for next new
}

closeModal.addEventListener('click', closeModalFunc);
cancelModal.addEventListener('click', closeModalFunc);
window.addEventListener('click', (e) => {
  if (e.target === modal) closeModalFunc();
});

// ---------- SAVE REPORT (FORM SUBMIT) ----------
reportForm.addEventListener('submit', (e) => {
  e.preventDefault();

  if (!titleInput.value || !eventSelect.value) {
    alert('Please fill all required fields.');
    return;
  }

  // For new reports, file is required
  if (!editingId && !fileInput.files.length) {
    alert('Please select a file to upload.');
    return;
  }

  const selectedEvent = eventsList.find(e => e.id == eventSelect.value);
  const fileName = fileInput.files.length ? fileInput.files[0].name : (editingId ? reports.find(r => r.id === editingId).fileName : '');

  const newReport = {
    id: editingId || Date.now(),
    title: titleInput.value.trim(),
    eventId: parseInt(eventSelect.value),
    eventTitle: selectedEvent ? selectedEvent.title : '',
    submittedDate: editingId ? reports.find(r => r.id === editingId).submittedDate : new Date().toISOString().split('T')[0],
    status: statusSelect.value,
    fileName: fileName
  };

  if (editingId) {
    // Update existing
    const index = reports.findIndex(r => r.id === editingId);
    if (index !== -1) {
      // Keep original submitted date
      newReport.submittedDate = reports[index].submittedDate;
      reports[index] = newReport;
    }
  } else {
    // Add new
    reports.push(newReport);
  }

  renderTable();
  closeModalFunc();
});


// ---------- INITIAL RENDER ----------
populateEventDropdown();
renderTable();