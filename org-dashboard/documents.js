// ============================================
//  Documents Management - Data & Functionality
// ============================================

// ---------- MOCK EVENTS DATA (for dropdown) ----------
const eventsList = [
  { id: 1, title: 'Annual Strategy Meeting' },
  { id: 2, title: 'Team Building Workshop' },
  { id: 3, title: 'Product Launch Webinar' },
  { id: 4, title: 'Quarterly Review' }
];

// ---------- MOCK DOCUMENTS DATA ----------
let documents = [
  {
    id: 1,
    title: 'Company Policy Handbook',
    description: 'Official policies and procedures.',
    category: 'Policies',
    fileType: 'pdf',
    fileSize: '2.4 MB',
    uploadedBy: 'Admin',
    uploadDate: '2026-02-10',
    status: 'Approved',
    visibility: 'Public',
    pinned: true,
    isNew: false,
    isUpdated: true,
    fileUrl: '#',
    relatedEvent: null
  },
  {
    id: 2,
    title: 'Annual Report 2025',
    description: 'Yearly financial report.',
    category: 'Reports',
    fileType: 'pdf',
    fileSize: '5.1 MB',
    uploadedBy: 'John Doe',
    uploadDate: '2026-02-15',
    status: 'Approved',
    visibility: 'Public',
    pinned: true,
    isNew: true,
    isUpdated: false,
    fileUrl: '#',
    relatedEvent: null
  },
  {
    id: 3,
    title: 'Event Registration Form',
    description: 'Template for event sign-ups.',
    category: 'Forms',
    fileType: 'docx',
    fileSize: '1.2 MB',
    uploadedBy: 'Admin',
    uploadDate: '2026-02-05',
    status: 'Approved',
    visibility: 'Public',
    pinned: false,
    isNew: false,
    isUpdated: false,
    fileUrl: '#',
    relatedEvent: null
  },
  {
    id: 4,
    title: 'Workshop Presentation',
    description: 'Slides from team building.',
    category: 'Templates',
    fileType: 'pptx',
    fileSize: '8.3 MB',
    uploadedBy: 'Emily Davis',
    uploadDate: '2026-02-18',
    status: 'Pending',
    visibility: 'Restricted',
    pinned: false,
    isNew: true,
    isUpdated: false,
    fileUrl: '#',
    relatedEvent: 2
  },
  {
    id: 5,
    title: 'Budget Spreadsheet Q1',
    description: 'Detailed budget breakdown.',
    category: 'Reports',
    fileType: 'xlsx',
    fileSize: '1.8 MB',
    uploadedBy: 'John Doe',
    uploadDate: '2026-02-20',
    status: 'Approved',
    visibility: 'Restricted',
    pinned: false,
    isNew: false,
    isUpdated: false,
    fileUrl: '#',
    relatedEvent: null
  }
];

// ---------- DOM ELEMENTS ----------
const pinnedGrid = document.getElementById('pinnedGrid');
const documentsGrid = document.getElementById('documentsGrid');
const searchInput = document.getElementById('searchInput');
const filterCategory = document.getElementById('filterCategory');
const filterDate = document.getElementById('filterDate');
const filterType = document.getElementById('filterType');
const uploadDocBtn = document.getElementById('uploadDocBtn');
const uploadModal = document.getElementById('uploadModal');
const closeUpload = document.getElementById('closeUpload');
const cancelUpload = document.getElementById('cancelUpload');
const uploadForm = document.getElementById('uploadForm');
const relatedEventSelect = document.getElementById('relatedEvent');
const previewModal = document.getElementById('previewModal');
const closePreview = document.getElementById('closePreview');
const closePreviewBtn = document.getElementById('closePreviewBtn');
const previewTitle = document.getElementById('previewTitle');
const previewDesc = document.getElementById('previewDesc');
const previewUploader = document.getElementById('previewUploader');
const previewDate = document.getElementById('previewDate');
const previewSize = document.getElementById('previewSize');
const previewIframe = document.getElementById('previewIframe');
const downloadFromPreview = document.getElementById('downloadFromPreview');

// ---------- POPULATE RELATED EVENT DROPDOWN ----------
function populateEventDropdown() {
  relatedEventSelect.innerHTML = '<option value="">None</option>';
  eventsList.forEach(event => {
    const option = document.createElement('option');
    option.value = event.id;
    option.textContent = event.title;
    relatedEventSelect.appendChild(option);
  });
}

// ---------- RENDER CARDS ----------
function renderCards() {
  const searchTerm = searchInput.value.toLowerCase();
  const catFilter = filterCategory.value;
  const dateFilter = filterDate.value;
  const typeFilter = filterType.value.toLowerCase();

  const filtered = documents.filter(doc => {
    const matchesSearch = doc.title.toLowerCase().includes(searchTerm) ||
                          (doc.description && doc.description.toLowerCase().includes(searchTerm));
    const matchesCat = catFilter === '' || doc.category === catFilter;
    const matchesType = typeFilter === '' || doc.fileType === typeFilter;
    let matchesDate = true;
    if (dateFilter) {
      const docDate = new Date(doc.uploadDate);
      const today = new Date();
      if (dateFilter === 'today') {
        matchesDate = docDate.toDateString() === today.toDateString();
      } else if (dateFilter === 'week') {
        const weekAgo = new Date(today.setDate(today.getDate() - 7));
        matchesDate = docDate >= weekAgo;
      } else if (dateFilter === 'month') {
        const monthAgo = new Date(today.setMonth(today.getMonth() - 1));
        matchesDate = docDate >= monthAgo;
      }
    }
    return matchesSearch && matchesCat && matchesType && matchesDate;
  });

  const pinned = filtered.filter(d => d.pinned);
  const others = filtered.filter(d => !d.pinned);

  renderGrid(pinnedGrid, pinned, true);
  renderGrid(documentsGrid, others, false);
}

function renderGrid(container, docs, isPinnedSection) {
  let html = '';
  docs.forEach(doc => {
    const statusClass = doc.status.toLowerCase().replace(' ', '-');
    const badge = doc.isNew ? '<span class="doc-badge new">NEW</span>' : (doc.isUpdated ? '<span class="doc-badge updated">UPDATED</span>' : '');
    const pinnedClass = doc.pinned ? 'pinned' : '';
    html += `
      <div class="doc-card ${pinnedClass}" data-id="${doc.id}">
        ${badge}
        <div class="doc-title">${doc.title}</div>
        <div class="doc-meta">
          <span><i class="fas fa-folder"></i> ${doc.category}</span>
          <span><i class="fas fa-calendar-alt"></i> ${formatDate(doc.uploadDate)}</span>
          <span><i class="fas fa-user"></i> ${doc.uploadedBy}</span>
          <span><i class="fas fa-file"></i> ${doc.fileType.toUpperCase()} · ${doc.fileSize}</span>
        </div>
        <div class="doc-status">
          <span class="status-badge ${statusClass}">${doc.status}</span>
        </div>
        <div class="doc-actions">
          <button class="btn-small" onclick="previewDocument(${doc.id})"><i class="fas fa-eye"></i> View</button>
          <button class="btn-small" onclick="downloadDocument(${doc.id})"><i class="fas fa-download"></i> Download</button>
        </div>
      </div>
    `;
  });
  container.innerHTML = html || '<p class="no-results">No documents found.</p>';
}

function formatDate(dateStr) {
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateStr).toLocaleDateString(undefined, options);
}

// ---------- PREVIEW DOCUMENT ----------
window.previewDocument = function(id) {
  const doc = documents.find(d => d.id === id);
  if (!doc) return;

  previewTitle.textContent = doc.title;
  previewDesc.textContent = doc.description || 'No description';
  previewUploader.textContent = doc.uploadedBy;
  previewDate.textContent = formatDate(doc.uploadDate);
  previewSize.textContent = doc.fileSize;

  // Simulate PDF embed (in real app, you'd use a file URL)
  previewIframe.src = 'about:blank'; // Placeholder
  setTimeout(() => {
    // Fake preview content
    previewIframe.srcdoc = `
      <html><body style="font-family: Arial; padding:20px;">
        <h2>${doc.title}</h2>
        <p><strong>Type:</strong> ${doc.fileType.toUpperCase()}</p>
        <p><strong>Size:</strong> ${doc.fileSize}</p>
        <p><em>Preview simulation - actual file would be embedded here.</em></p>
      </body></html>
    `;
  }, 100);

  downloadFromPreview.onclick = (e) => {
    e.preventDefault();
    downloadDocument(id);
  };

  previewModal.classList.add('show');
};

// ---------- DOWNLOAD DOCUMENT ----------
window.downloadDocument = function(id) {
  const doc = documents.find(d => d.id === id);
  if (doc) {
    alert(`Downloading ${doc.title} (${doc.fileType}) – demo.`);
    // In real app: window.location.href = doc.fileUrl;
  }
};

// ---------- CLOSE PREVIEW ----------
closePreview.addEventListener('click', () => previewModal.classList.remove('show'));
closePreviewBtn.addEventListener('click', () => previewModal.classList.remove('show'));
window.addEventListener('click', (e) => {
  if (e.target === previewModal) previewModal.classList.remove('show');
});

// ---------- OPEN UPLOAD MODAL ----------
uploadDocBtn.addEventListener('click', () => {
  uploadForm.reset();
  uploadModal.classList.add('show');
});

// ---------- CLOSE UPLOAD MODAL ----------
function closeUploadModal() {
  uploadModal.classList.remove('show');
}
closeUpload.addEventListener('click', closeUploadModal);
cancelUpload.addEventListener('click', closeUploadModal);
window.addEventListener('click', (e) => {
  if (e.target === uploadModal) closeUploadModal();
});

// ---------- UPLOAD FORM SUBMIT ----------
uploadForm.addEventListener('submit', (e) => {
  e.preventDefault();

  const title = document.getElementById('docTitle').value.trim();
  const category = document.getElementById('docCategory').value;
  const description = document.getElementById('docDescription').value.trim();
  const relatedEvent = document.getElementById('relatedEvent').value;
  const fileInput = document.getElementById('docFile');
  const file = fileInput.files[0];

  if (!title || !category || !file) {
    alert('Please fill required fields.');
    return;
  }

  // Validate file type
  const allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
  if (!allowedTypes.includes(file.type)) {
    alert('Invalid file type. Only PDF, DOCX, XLSX allowed.');
    return;
  }

  // Validate file size (max 10MB)
  if (file.size > 10 * 1024 * 1024) {
    alert('File size exceeds 10MB.');
    return;
  }

  // Simulate upload (in real app, send to server)
  const newDoc = {
    id: Date.now(),
    title: title,
    description: description,
    category: category,
    fileType: file.name.split('.').pop(),
    fileSize: (file.size / 1024 / 1024).toFixed(2) + ' MB',
    uploadedBy: 'John Doe', // current user
    uploadDate: new Date().toISOString().split('T')[0],
    status: 'Pending',
    visibility: 'Restricted', // default until approved
    pinned: false,
    isNew: true,
    isUpdated: false,
    fileUrl: '#',
    relatedEvent: relatedEvent || null
  };

  documents.push(newDoc);
  renderCards();
  closeUploadModal();
  alert('Document uploaded successfully. It is now pending review.');
});

// ---------- SEARCH & FILTER EVENT LISTENERS ----------
searchInput.addEventListener('input', renderCards);
filterCategory.addEventListener('change', renderCards);
filterDate.addEventListener('change', renderCards);
filterType.addEventListener('change', renderCards);


// ---------- INITIAL RENDER ----------
populateEventDropdown();
renderCards();