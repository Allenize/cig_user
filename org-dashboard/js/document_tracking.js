// document_tracking.js

// ========== UPLOAD MODAL FUNCTIONALITY ==========
const uploadModal = document.getElementById('uploadModal');
const openUploadBtn = document.getElementById('openUploadModal');
const closeUploadBtn = document.getElementById('closeUploadModal');
const cancelUploadBtn = document.getElementById('cancelUpload');
const uploadForm = document.getElementById('uploadForm');

// Open modal
openUploadBtn.onclick = function() {
    uploadModal.style.display = 'block';
};

// Close modal functions
function closeUploadModal() {
    uploadModal.style.display = 'none';
    uploadForm.reset(); // optional: clear form on close
}
closeUploadBtn.onclick = closeUploadModal;
cancelUploadBtn.onclick = closeUploadModal;

// Close when clicking outside modal
window.onclick = function(event) {
    if (event.target == uploadModal) {
        closeUploadModal();
    }
};

// Handle form submission (simulated)
uploadForm.onsubmit = function(e) {
    e.preventDefault();
    // In a real app, you'd send the form data via fetch or regular POST
    // For demo, show success message
    alert('Document uploaded successfully! (simulated)');
    closeUploadModal();
};

// ========== SEARCH & FILTER (existing) ==========
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const dateFilter = document.getElementById('dateFilter');
const tableRows = document.querySelectorAll('#documentsTable tbody tr');

function filterTable() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusVal = statusFilter.value.toLowerCase();
    const dateVal = dateFilter.value;

    tableRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const title = cells[0].innerText.toLowerCase();
        const date = cells[1].innerText;
        const status = cells[3].innerText.toLowerCase();

        const matchesSearch = title.includes(searchTerm);
        const matchesStatus = statusVal === '' || status === statusVal;
        const matchesDate = dateVal === '' || date === dateVal;

        if (matchesSearch && matchesStatus && matchesDate) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

searchInput.addEventListener('input', filterTable);
statusFilter.addEventListener('change', filterTable);
dateFilter.addEventListener('change', filterTable);