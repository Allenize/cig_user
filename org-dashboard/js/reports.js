/**
 * reports.js
 * Handles: PDF export, Excel export, Save Report modal, Delete Report
 */

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const C = { success:'#10b981', error:'#ef4444', info:'#3b82f6', warning:'#f59e0b' };
    const I = { success:'fa-check-circle', error:'fa-times-circle', info:'fa-info-circle', warning:'fa-exclamation-triangle' };
    const old = document.getElementById('rp-toast');
    if (old) old.remove();
    const t = document.createElement('div');
    t.id = 'rp-toast';
    t.style.cssText = `position:fixed;top:1.5rem;right:1.5rem;z-index:99999;
        padding:.85rem 1.4rem;border-radius:14px;font-size:.93rem;font-weight:600;
        box-shadow:0 4px 20px rgba(0,0,0,.18);color:#fff;max-width:360px;
        display:flex;align-items:center;gap:.6rem;background:${C[type]};
        animation:rpToastIn .3s ease;`;
    if (!document.getElementById('rp-toast-style')) {
        const s = document.createElement('style');
        s.id = 'rp-toast-style';
        s.textContent = '@keyframes rpToastIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}';
        document.head.appendChild(s);
    }
    t.innerHTML = `<i class="fas ${I[type]}"></i><span>${msg}</span>`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

// ── PDF Export ────────────────────────────────────────────────────────────────
document.getElementById('btnExportPDF').addEventListener('click', () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const d   = REPORT_DATA;
    const now = d.generated;

    // Header
    doc.setFillColor(45, 106, 79);
    doc.rect(0, 0, 210, 32, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(18); doc.setFont('helvetica', 'bold');
    doc.text('Organization Report', 14, 14);
    doc.setFontSize(9); doc.setFont('helvetica', 'normal');
    doc.text(d.orgName, 14, 21);
    doc.text(`Generated: ${now}`, 14, 27);

    // Stats table
    doc.setTextColor(30, 58, 58);
    doc.setFontSize(12); doc.setFont('helvetica', 'bold');
    doc.text('Summary Statistics', 14, 42);

    doc.autoTable({
        startY: 46,
        head: [['Metric', 'Value']],
        body: [
            ['Total Submissions', d.stats.total],
            ['Approved',          d.stats.approved],
            ['Pending / In Review', d.stats.pending],
            ['Rejected',          d.stats.rejected],
            ['Total Members',     d.stats.members],
            ['Approval Rate',     d.stats.rate + '%'],
        ],
        headStyles:  { fillColor: [45, 106, 79], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [240, 249, 244] },
        styles: { fontSize: 10, cellPadding: 4 },
        margin: { left: 14, right: 14 },
    });

    // Monthly table
    if (d.monthly.length > 0) {
        doc.setFontSize(12); doc.setFont('helvetica', 'bold');
        doc.text('Monthly Breakdown', 14, doc.lastAutoTable.finalY + 12);
        doc.autoTable({
            startY: doc.lastAutoTable.finalY + 16,
            head: [['Month', 'Total', 'Approved', 'Pending', 'Rejected']],
            body: d.monthly.map(m => [m.month, m.total, m.approved, m.pending, m.rejected]),
            headStyles: { fillColor: [45, 106, 79], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [240, 249, 244] },
            styles: { fontSize: 10, cellPadding: 4 },
            margin: { left: 14, right: 14 },
        });
    }

    // Recent submissions
    if (d.recent.length > 0) {
        doc.setFontSize(12); doc.setFont('helvetica', 'bold');
        doc.text('Recent Submissions', 14, doc.lastAutoTable.finalY + 12);
        doc.autoTable({
            startY: doc.lastAutoTable.finalY + 16,
            head: [['Title', 'Status', 'Submitted']],
            body: d.recent.map(r => [
                r.title,
                r.status.charAt(0).toUpperCase() + r.status.slice(1),
                new Date(r.submitted_at).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' })
            ]),
            headStyles: { fillColor: [45, 106, 79], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [240, 249, 244] },
            styles: { fontSize: 9, cellPadding: 3 },
            margin: { left: 14, right: 14 },
        });
    }

    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8); doc.setTextColor(150);
        doc.text(`Page ${i} of ${pageCount}  ·  OrgHub Report`, 105, 292, { align: 'center' });
    }

    doc.save(`${d.orgName.replace(/\s+/g,'-')}_report.pdf`);
    showToast('PDF exported successfully!', 'success');
});

// ── Excel Export ──────────────────────────────────────────────────────────────
document.getElementById('btnExportExcel').addEventListener('click', () => {
    const d  = REPORT_DATA;
    const wb = XLSX.utils.book_new();

    // Summary sheet
    const summaryData = [
        ['ORGANIZATION REPORT'],
        ['Organization', d.orgName],
        ['Generated',    d.generated],
        [],
        ['SUMMARY STATISTICS'],
        ['Metric', 'Value'],
        ['Total Submissions', d.stats.total],
        ['Approved',          d.stats.approved],
        ['Pending / In Review', d.stats.pending],
        ['Rejected',          d.stats.rejected],
        ['Total Members',     d.stats.members],
        ['Approval Rate',     d.stats.rate + '%'],
    ];
    const wsSummary = XLSX.utils.aoa_to_sheet(summaryData);
    wsSummary['!cols'] = [{ wch: 28 }, { wch: 18 }];
    XLSX.utils.book_append_sheet(wb, wsSummary, 'Summary');

    // Monthly sheet
    if (d.monthly.length > 0) {
        const monthlyRows = [['Month', 'Total', 'Approved', 'Pending', 'Rejected'],
            ...d.monthly.map(m => [m.month, m.total, m.approved, m.pending, m.rejected])];
        const wsMonthly = XLSX.utils.aoa_to_sheet(monthlyRows);
        wsMonthly['!cols'] = [{ wch: 14 }, { wch: 10 }, { wch: 12 }, { wch: 12 }, { wch: 12 }];
        XLSX.utils.book_append_sheet(wb, wsMonthly, 'Monthly');
    }

    // Recent submissions sheet
    if (d.recent.length > 0) {
        const recentRows = [['#', 'Title', 'Status', 'Submitted'],
            ...d.recent.map((r, i) => [
                i + 1, r.title,
                r.status.charAt(0).toUpperCase() + r.status.slice(1),
                new Date(r.submitted_at).toLocaleDateString('en-US')
            ])];
        const wsRecent = XLSX.utils.aoa_to_sheet(recentRows);
        wsRecent['!cols'] = [{ wch: 5 }, { wch: 36 }, { wch: 14 }, { wch: 16 }];
        XLSX.utils.book_append_sheet(wb, wsRecent, 'Submissions');
    }

    XLSX.writeFile(wb, `${d.orgName.replace(/\s+/g,'-')}_report.xlsx`);
    showToast('Excel exported successfully!', 'success');
});

// ── Save Report Modal ─────────────────────────────────────────────────────────
const saveModal = document.getElementById('saveModal');
document.getElementById('btnSaveReport').addEventListener('click', () => {
    saveModal.style.display = 'flex';
    document.getElementById('reportTitle').focus();
});
document.getElementById('closeSaveModal').addEventListener('click', () => saveModal.style.display = 'none');
document.getElementById('cancelSave').addEventListener('click',     () => saveModal.style.display = 'none');
window.addEventListener('click', e => { if (e.target === saveModal) saveModal.style.display = 'none'; });

document.getElementById('confirmSave').addEventListener('click', async () => {
    const title = document.getElementById('reportTitle').value.trim();
    if (!title) {
        document.getElementById('reportTitle').style.borderColor = '#ef4444';
        return;
    }
    document.getElementById('reportTitle').style.borderColor = '';

    const payload = {
        title,
        report_type: document.getElementById('reportType').value,
        description: document.getElementById('reportNotes').value.trim(),
        data: JSON.stringify(REPORT_DATA),
    };

    try {
        const res  = await fetch('save_report.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (json.success) {
            saveModal.style.display = 'none';
            document.getElementById('reportTitle').value = '';
            showToast('Report saved successfully!', 'success');
            // Inject new saved-report item into the list
            appendSavedReport(json.report);
        } else {
            showToast('Failed to save report.', 'error');
        }
    } catch (e) {
        showToast('Server error. Please try again.', 'error');
    }
});

function appendSavedReport(rp) {
    const list  = document.getElementById('savedReportsList');
    const empty = list.querySelector('.chart-empty');
    if (empty) empty.remove();

    const icons = { submissions:'fa-file-alt', members:'fa-users', overview:'fa-chart-bar' };
    const icon  = icons[rp.report_type] || 'fa-file-alt';
    const div   = document.createElement('div');
    div.className = 'saved-report-item';
    div.dataset.id = rp.report_id;
    div.innerHTML = `
        <div class="saved-report-icon"><i class="fas ${icon}"></i></div>
        <div class="saved-report-info">
            <span class="saved-report-title">${rp.title}</span>
            <span class="saved-report-date">${rp.created_at}</span>
        </div>
        <button class="btn-delete-report" onclick="deleteReport(${rp.report_id}, this)" title="Delete">
            <i class="fas fa-trash"></i>
        </button>`;
    list.prepend(div);
}

// ── Delete Report ─────────────────────────────────────────────────────────────
window.deleteReport = async function (id, btn) {
    if (!confirm('Delete this saved report?')) return;
    try {
        const res  = await fetch('delete_report.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ report_id: id }),
        });
        const json = await res.json();
        if (json.success) {
            btn.closest('.saved-report-item').remove();
            showToast('Report deleted.', 'error');
            if (!document.querySelector('.saved-report-item')) {
                document.getElementById('savedReportsList').innerHTML = `
                    <div class="chart-empty" style="padding:1.5rem 0;">
                        <i class="fas fa-bookmark"></i>
                        <p>No saved reports yet</p>
                        <small>Click "Save Report" to store a snapshot</small>
                    </div>`;
            }
        }
    } catch (e) {
        showToast('Failed to delete report.', 'error');
    }
};