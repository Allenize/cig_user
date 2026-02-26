// archive.js

// ========== TAB FUNCTIONALITY ==========
const tabBtns = document.querySelectorAll('.tab-btn');
const tableRows = document.querySelectorAll('#archiveTable tbody tr');

tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        tabBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const tab = btn.dataset.tab;
        filterByTab(tab);
    });
});

function filterByTab(tab) {
    tableRows.forEach(row => {
        const category = row.dataset.category;
        if (tab === 'all' || category.toLowerCase() === tab.replace('s', '')) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    filterTable();
}

// ========== SEARCH & FILTER ==========
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const dateFilter = document.getElementById('dateFilter');

function filterTable() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusVal = statusFilter.value.toLowerCase();
    const dateVal = dateFilter.value;

    tableRows.forEach(row => {
        if (row.style.display === 'none') return;

        const cells = row.querySelectorAll('td');
        const title = cells[0].innerText.toLowerCase();
        const dateArchived = cells[2].innerText;
        const status = cells[4].innerText.toLowerCase();

        const matchesSearch = title.includes(searchTerm);
        const matchesStatus = statusVal === '' || status.includes(statusVal);
        const matchesDate = dateVal === '' || dateArchived === dateVal;

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

// ========== VIEW MODAL ==========
const viewModal = document.getElementById('viewModal');
const closeViewModal = document.getElementById('closeViewModal');
const viewModalBody = document.getElementById('viewModalBody');

closeViewModal.onclick = function() {
    viewModal.style.display = 'none';
};

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target == viewModal) {
        viewModal.style.display = 'none';
    }
};

window.viewArchiveItem = function(button) {
    const row = button.closest('tr');
    const cells = row.querySelectorAll('td');
    const title = cells[0].innerText;
    const category = cells[1].innerText;
    const dateArchived = cells[2].innerText;
    const originalDate = cells[3].innerText;
    const status = cells[4].innerText;

    const content = `
        <div class="detail-row">
            <span class="detail-label">Title:</span>
            <span class="detail-value">${title}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Category:</span>
            <span class="detail-value">${category}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date Archived:</span>
            <span class="detail-value">${dateArchived}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Original Date:</span>
            <span class="detail-value">${originalDate}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status:</span>
            <span class="detail-value">${status}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Description:</span>
            <span class="detail-value">Sample description or additional metadata would appear here.</span>
        </div>
    `;

    viewModalBody.innerHTML = content;
    viewModal.style.display = 'block';
};