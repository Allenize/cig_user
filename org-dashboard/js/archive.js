// archive.js

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const colors = { success: '#10b981', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
    const icons  = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
    document.querySelectorAll('.arch-toast').forEach(t => t.remove());
    const t = document.createElement('div');
    t.className = 'arch-toast';
    t.style.background = colors[type] || colors.success;
    t.innerHTML = `<i class="fas ${icons[type]}"></i><span>${msg}</span>`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ── Rows reference ────────────────────────────────────────────────────────────
function getRows() {
    return Array.from(document.querySelectorAll('#archiveTable tbody tr'));
}

// ── Tabs ──────────────────────────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        applyFilters();
    });
});

// ── Search & Date Filter ──────────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('dateFilter').addEventListener('change', applyFilters);

function applyFilters() {
    const tab      = document.querySelector('.tab-btn.active')?.dataset.tab || 'all';
    const search   = document.getElementById('searchInput').value.toLowerCase().trim();
    const dateVal  = document.getElementById('dateFilter').value;
    let visible    = 0;

    getRows().forEach(row => {
        const title    = row.dataset.title || '';
        const isRecent = row.dataset.recent === 'true';
        const archived = row.dataset.archivedDate || '';

        const tabOk    = tab === 'all' || (tab === 'recent' && isRecent);
        const searchOk = !search || title.includes(search);
        const dateOk   = !dateVal || archived === dateVal;

        const show = tabOk && searchOk && dateOk;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const counter = document.getElementById('rowCount');
    if (counter) counter.textContent = `${visible} item(s)`;
}

// ── View Modal ────────────────────────────────────────────────────────────────
const viewModal    = document.getElementById('viewModal');
const viewBody     = document.getElementById('viewModalBody');
const viewActions  = document.getElementById('viewModalActions');
document.getElementById('closeViewModal').onclick = () => viewModal.style.display = 'none';
window.addEventListener('click', e => { if (e.target === viewModal) viewModal.style.display = 'none'; });

window.viewItem = function(btn) {
    const d = btn.dataset;
    const hasFile = d.file && d.file !== '';
    const filePath = d.filepath || '';

    viewBody.innerHTML = `
        <div class="detail-row"><span class="detail-label">Title</span><span class="detail-value">${d.title}</span></div>
        <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${d.desc}</span></div>
        <div class="detail-row"><span class="detail-label">File</span><span class="detail-value">${hasFile ? d.file : '—'}</span></div>
        <div class="detail-row"><span class="detail-label">Submitted</span><span class="detail-value">${d.submitted}</span></div>
        <div class="detail-row"><span class="detail-label">Archived On</span><span class="detail-value">${d.archived}</span></div>
        <div class="detail-row"><span class="detail-label">Submitted By</span><span class="detail-value">${d.by}</span></div>
    `;

    viewActions.innerHTML = hasFile
        ? `<a href="${filePath}" target="_blank" class="btn-modal-restore" download>
               <i class="fas fa-download"></i> Download File
           </a>`
        : '';

    viewModal.style.display = 'flex';
};

// ── Restore ───────────────────────────────────────────────────────────────────
window.restoreItem = async function(id, btn) {
    if (!confirm('Restore this submission to Pending status?')) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
        const res  = await fetch('archive_action.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restore', submission_id: id }),
        });
        const json = await res.json();
        if (json.success) {
            const row = btn.closest('tr');
            row.style.transition = 'opacity 0.4s, transform 0.4s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(30px)';
            setTimeout(() => { row.remove(); updateCount(); }, 420);
            showToast('Submission restored to Pending.', 'success');
        } else {
            showToast(json.error || 'Restore failed.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-undo-alt"></i>';
        }
    } catch (e) {
        showToast('Server error.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-undo-alt"></i>';
    }
};

// ── Delete (with confirm modal) ───────────────────────────────────────────────
const deleteModal   = document.getElementById('deleteModal');
const confirmDelete = document.getElementById('confirmDelete');
const cancelDelete  = document.getElementById('cancelDelete');
let   _pendingDeleteId  = null;
let   _pendingDeleteBtn = null;

cancelDelete.onclick  = () => deleteModal.style.display = 'none';
window.addEventListener('click', e => { if (e.target === deleteModal) deleteModal.style.display = 'none'; });

window.deleteItem = function(id, btn) {
    _pendingDeleteId  = id;
    _pendingDeleteBtn = btn;
    deleteModal.style.display = 'flex';
};

confirmDelete.onclick = async function() {
    if (!_pendingDeleteId) return;
    deleteModal.style.display = 'none';
    _pendingDeleteBtn.disabled = true;
    _pendingDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
        const res  = await fetch('archive_action.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', submission_id: _pendingDeleteId }),
        });
        const json = await res.json();
        if (json.success) {
            const row = _pendingDeleteBtn.closest('tr');
            row.style.transition = 'opacity 0.35s, transform 0.35s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-30px)';
            setTimeout(() => { row.remove(); updateCount(); }, 370);
            showToast('Item permanently deleted.', 'error');
        } else {
            showToast(json.error || 'Delete failed.', 'error');
            _pendingDeleteBtn.disabled = false;
            _pendingDeleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
        }
    } catch (e) {
        showToast('Server error.', 'error');
        _pendingDeleteBtn.disabled = false;
        _pendingDeleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
    }
    _pendingDeleteId  = null;
    _pendingDeleteBtn = null;
};

// ── Row Counter Sync ──────────────────────────────────────────────────────────
function updateCount() {
    const visible = getRows().filter(r => r.style.display !== 'none').length;
    const counter = document.getElementById('rowCount');
    if (counter) counter.textContent = `${visible} item(s)`;

    // Show empty state if no rows
    if (visible === 0 && getRows().length === 0) {
        const tableCard = document.querySelector('.table-card');
        if (tableCard) {
            tableCard.innerHTML = `
                <div class="archive-empty">
                    <div class="archive-empty-icon"><i class="fas fa-box-open"></i></div>
                    <h3>No Archived Items</h3>
                    <p>Submissions that are archived will appear here.</p>
                </div>`;
        }
    }
}