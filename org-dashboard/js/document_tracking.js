// document_tracking.js

// ─── Template definitions ───────────────────────────────────────────────────
const templates = {
    'meeting_minutes': {
        name: 'Meeting Minutes',
        fields: {
            'meeting_date': 'Meeting Date',
            'meeting_time': 'Meeting Time',
            'location': 'Location',
            'attendees': 'Attendees (comma-separated)',
            'agenda': 'Agenda',
            'discussion': 'Discussion Summary',
            'action_items': 'Action Items',
            'next_meeting': 'Next Meeting Date'
        }
    },
    'event_proposal': {
        name: 'Event Proposal',
        fields: {
            'event_name': 'Event Name',
            'event_date': 'Proposed Date',
            'event_time': 'Event Time',
            'location': 'Location/Venue',
            'objective': 'Event Objective',
            'target_audience': 'Target Audience',
            'expected_attendance': 'Expected Number of Attendees',
            'budget': 'Estimated Budget',
            'description': 'Event Description',
            'requirements': 'Special Requirements'
        }
    },
    'financial_report': {
        name: 'Financial Report',
        fields: {
            'report_period': 'Reporting Period',
            'opening_balance': 'Opening Balance',
            'total_income': 'Total Income',
            'total_expenses': 'Total Expenses',
            'expense_breakdown': 'Expense Breakdown',
            'closing_balance': 'Closing Balance',
            'remarks': 'Remarks/Notes'
        }
    },
    'incident_report': {
        name: 'Incident Report',
        fields: {
            'incident_date': 'Incident Date',
            'incident_time': 'Incident Time',
            'location': 'Location',
            'incident_description': 'Incident Description',
            'individuals_involved': 'Individuals Involved',
            'witnesses': 'Witnesses',
            'action_taken': 'Action Taken',
            'recommendations': 'Recommendations'
        }
    },
    'membership_form': {
        name: 'Membership Form',
        fields: {
            'full_name': 'Full Name',
            'email': 'Email Address',
            'phone': 'Phone Number',
            'course_year': 'Course and Year',
            'date_joined': 'Date Joined',
            'membership_role': 'Membership Role',
            'skills': 'Skills/Expertise',
            'availability': 'Availability for Activities'
        }
    },
    'project_proposal': {
        name: 'Project Proposal',
        fields: {
            'proposal_date': 'Date',
            'recipient_1': 'First Recipient Name & Title',
            'recipient_2': 'Second Recipient Name & Title',
            'dear_opening': 'Dear [Recipient - Full Name with Title]',
            'opening_statement': 'Opening Statement',
            'organization': 'Organization',
            'project_title': 'Project Title',
            'project_type': 'Type of Project (Curricular / Non-Curricular / Off-Campus)',
            'project_involvement': 'Project Involvement (Host / Collaboration / Participant)',
            'project_location': 'Project Location',
            'proposed_start_date': 'Proposed Start Date & Time',
            'proposed_end_date': 'Proposed Completion Date',
            'number_participants': 'Number of Participants',
            'project_summary': 'A. SUMMARY OF THE PROJECT',
            'project_goal': 'Goal',
            'project_objectives': 'Objectives (numbered, one per line)',
            'expected_outputs': 'C. EXPECTED OUTPUTS (bulleted)',
            'budget_source': 'Source of Fund',
            'budget_partner': 'Partner/Donation/Subsidy',
            'budget_total': 'Total Project Cost',
            'monitoring_details': 'Monitoring (bulleted)',
            'evaluation_details': 'Evaluation Strategy (bulleted)',
            'security_plan': 'V. SECURITY PLAN (bulleted)',
            'closing_statement': 'Closing Statement',
            'sender_name': 'Sender Name & Title',
            'noted_by': 'Noted by (comma-separated names with titles)',
            'endorsed_by': 'Endorsed by (name and title)'
        }
    }
};

// ─── DOM refs ────────────────────────────────────────────────────────────────
const uploadModal    = document.getElementById('uploadModal');
const openUploadBtn  = document.getElementById('openUploadModal');
const closeUploadBtn = document.getElementById('closeUploadModal');
const uploadForm     = document.getElementById('uploadForm');
const templateForm   = document.getElementById('templateForm');
const submitBtn      = document.getElementById('submitBtn');
const tabButtons     = document.querySelectorAll('.tab-button');
const tabContents    = document.querySelectorAll('.tab-content');
const searchInput    = document.getElementById('searchInput');
const statusFilter   = document.getElementById('statusFilter');
const dateFilter     = document.getElementById('dateFilter');

// ─── Upload modal open / close ───────────────────────────────────────────────
openUploadBtn.onclick = () => { uploadModal.style.display = 'flex'; };

function closeUploadModal() {
    uploadModal.style.display = 'none';
    uploadForm.reset();
    templateForm.reset();
    document.getElementById('templateFieldsContainer').innerHTML = '';
    tabButtons.forEach(b => b.classList.remove('active'));
    tabContents.forEach(c => c.classList.remove('active'));
    document.querySelector('[data-tab="regular-upload"]').classList.add('active');
    document.getElementById('regular-upload').classList.add('active');
    document.querySelector('.upload-modal-content').classList.remove('landscape-mode', 'template-expanded');
    submitBtn.disabled    = false;
    submitBtn.textContent = 'Upload Document';
}

closeUploadBtn.onclick = closeUploadModal;
document.getElementById('cancelBtn').onclick = closeUploadModal;
window.addEventListener('click', e => { if (e.target === uploadModal) closeUploadModal(); });

// ─── Tab switching ───────────────────────────────────────────────────────────
tabButtons.forEach(btn => {
    btn.addEventListener('click', function () {
        const tabName = this.getAttribute('data-tab');
        tabButtons.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(tabName).classList.add('active');
        const mc = document.querySelector('.upload-modal-content');
        if (tabName === 'template-upload') {
            mc.classList.add('landscape-mode');
            submitBtn.textContent = 'Generate & Submit';
            validateTemplateForm();
        } else {
            mc.classList.remove('landscape-mode', 'template-expanded');
            submitBtn.textContent = 'Upload Document';
            submitBtn.disabled    = false;
        }
    });
});

// ─── Submit routing ──────────────────────────────────────────────────────────
submitBtn.addEventListener('click', e => {
    e.preventDefault();
    const active = document.querySelector('.tab-content.active');
    if (active && active.id === 'template-upload') {
        templateForm.dispatchEvent(new Event('submit'));
    } else {
        uploadForm.dispatchEvent(new Event('submit'));
    }
});

// ─── Template fields loader ──────────────────────────────────────────────────
const textareaFields = new Set([
    'agenda','discussion','action_items','description','requirements',
    'expense_breakdown','remarks','incident_description','individuals_involved',
    'witnesses','action_taken','recommendations','opening_statement',
    'project_summary','project_goal','project_objectives','expected_outputs',
    'monitoring_details','evaluation_details','security_plan','closing_statement',
    'attendees','skills','availability'
]);

function loadTemplateFields() {
    const templateSelect = document.getElementById('templateSelect');
    const templateId     = templateSelect.value;
    const container      = document.getElementById('templateFieldsContainer');
    const mc             = document.querySelector('.upload-modal-content');

    container.innerHTML = '';
    if (!templateId || !templates[templateId]) {
        mc.classList.remove('template-expanded');
        validateTemplateForm();
        return;
    }

    const tmpl = templates[templateId];
    let html = '';
    Object.entries(tmpl.fields).forEach(([fieldId, fieldLabel]) => {
        const isTA  = textareaFields.has(fieldId);
        const ftype = fieldId.includes('date') ? 'date'
                    : fieldId.includes('time') ? 'time'
                    : (fieldId.includes('budget') || fieldId.includes('attendance') || fieldId === 'number_participants') ? 'number'
                    : 'text';
        html += `
        <div class="form-group">
            <label for="${fieldId}">${fieldLabel}</label>
            ${isTA
                ? `<textarea id="${fieldId}" name="${fieldId}" rows="3" placeholder="Enter ${fieldLabel.toLowerCase()}" required></textarea>`
                : `<input type="${ftype}" id="${fieldId}" name="${fieldId}" placeholder="Enter ${fieldLabel.toLowerCase()}" required>`
            }
        </div>`;
    });
    container.innerHTML = html;
    mc.classList.add('template-expanded');
    container.querySelectorAll('input, textarea').forEach(f => {
        f.addEventListener('input', validateTemplateForm);
        f.addEventListener('change', validateTemplateForm);
    });
    validateTemplateForm();
}

function validateTemplateForm() {
    const sel     = document.getElementById('templateSelect');
    const title   = document.getElementById('templateTitle');
    const orgName = document.getElementById('organizationName');
    const orgTag  = document.getElementById('organizationTagline');
    if (!sel.value || !title.value.trim() || !orgName.value.trim() || !orgTag.value.trim()) {
        submitBtn.disabled = true; return;
    }
    const allFields = templateForm.querySelectorAll('.template-fields-container input, .template-fields-container textarea');
    submitBtn.disabled = [...allFields].some(f => !f.value.trim());
}

document.getElementById('templateSelect')?.addEventListener('change', validateTemplateForm);
document.getElementById('templateTitle')?.addEventListener('input', validateTemplateForm);
document.getElementById('organizationName')?.addEventListener('input', validateTemplateForm);
document.getElementById('organizationTagline')?.addEventListener('input', validateTemplateForm);

// ─── Regular upload submit ───────────────────────────────────────────────────
uploadForm.onsubmit = function (e) {
    e.preventDefault();
    const formData = new FormData(uploadForm);
    const origText = submitBtn.textContent;

    // Detect extension from chosen file
    const fileInput = document.getElementById('fileUpload');
    const ext = fileInput.files.length
        ? fileInput.files[0].name.split('.').pop().toLowerCase()
        : 'pdf';

    submitBtn.textContent = 'Uploading…';
    submitBtn.disabled    = true;

    fetch('../php/upload_document.php', { method:'POST', body:formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Document uploaded successfully!', true);
                addTableRow(document.getElementById('docTitle').value, data.submitted_by || 'You', data.submission_id, ext);
                closeUploadModal();
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast('Error: ' + (data.message || 'Upload failed.'), false);
            }
        })
        .catch(err => showToast('Upload error: ' + err.message, false))
        .finally(() => { submitBtn.textContent = origText; submitBtn.disabled = false; });
};

// ─── Template upload submit ──────────────────────────────────────────────────
templateForm.onsubmit = function (e) {
    e.preventDefault();
    if (submitBtn.disabled) { alert('Please fill in all required fields'); return; }

    const sel   = document.getElementById('templateSelect');
    const titleField = document.getElementById('templateTitle');
    const customTitle = titleField ? titleField.value.trim() : '';
    const id    = sel.value;
    
    if (!id) { alert('Please select a template'); return; }
    if (!customTitle) { alert('Please enter a document title'); return; }

    const formData = new FormData();
    formData.append('template_id', id);
    formData.append('title', customTitle);
    formData.append('organization_name',    document.getElementById('organizationName').value);
    formData.append('organization_tagline', document.getElementById('organizationTagline').value);
    const logo = document.getElementById('collaboratedLogo').value;
    if (logo) formData.append('collaborated_logo', logo);
    Object.keys(templates[id].fields).forEach(fieldId => {
        const el = document.getElementById(fieldId);
        if (el) formData.append(fieldId, el.value);
    });

    const origText = submitBtn.textContent;
    submitBtn.textContent = 'Generating…';
    submitBtn.disabled    = true;

    fetch('../php/upload_document.php', { method:'POST', body:formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Document generated and submitted!', true);
                addTableRow(customTitle, data.submitted_by || 'You', data.submission_id, 'pdf');
                closeUploadModal();
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast('Error: ' + (data.message || 'Unknown error occurred.'), false);
            }
        })
        .catch(err => {
            console.error('Upload error:', err);
            showToast('Error: ' + err.message, false);
        })
        .finally(() => { submitBtn.textContent = origText; submitBtn.disabled = false; });
};

// ─── Add optimistic row ──────────────────────────────────────────────────────
function addTableRow(title, submittedBy, submissionId, ext) {
    const tbody = document.querySelector('#documentsTable tbody');
    const empty = tbody.querySelector('tr td[colspan]');
    if (empty) empty.closest('tr').remove();

    const today = new Date().toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' });
    const extColors = { pdf:'#e74c3c', docx:'#2980b9', xlsx:'#27ae60' };
    const extIcons  = { pdf:'fa-file-pdf', docx:'fa-file-word', xlsx:'fa-file-excel' };
    const color     = extColors[ext] || '#7f8c8d';
    const icon      = extIcons[ext]  || 'fa-file-alt';

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <div class="doc-name-cell">
                <i class="fas ${icon} doc-icon" style="color:${color};font-size:1.3rem;flex-shrink:0"></i>
                <div class="doc-meta-text">
                    <strong>${escHtml(title)}</strong>
                    <small><span class="file-type-badge" style="background:${color}">${ext.toUpperCase()}</span></small>
                </div>
            </div>
        </td>
        <td>${today}</td>
        <td>${escHtml(submittedBy)}</td>
        <td><span class="status-badge pending">Pending</span></td>
        <td>Awaiting review</td>
        <td>
            <div class="action-btns">
                <button class="btn-view" onclick="openPreviewModal(${submissionId},'${ext}','${escHtml(title)}')">
                    <i class="fas fa-eye"></i> View
                </button>
            </div>
        </td>`;
    tbody.insertBefore(tr, tbody.firstChild);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
}

// ─── Toast ───────────────────────────────────────────────────────────────────
function showToast(msg, success) {
    const old = document.getElementById('dt-toast');
    if (old) old.remove();
    const t = document.createElement('div');
    t.id = 'dt-toast';
    t.style.cssText = `
        position:fixed;top:1.5rem;right:1.5rem;z-index:99999;
        padding:.85rem 1.4rem;border-radius:12px;font-size:.93rem;font-weight:600;
        box-shadow:0 4px 20px rgba(0,0,0,.2);color:#fff;max-width:360px;
        display:flex;align-items:center;gap:.6rem;
        background:${success ? '#27ae60' : '#e74c3c'};`;
    t.innerHTML = `<i class="fas ${success ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i><span>${msg}</span>`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}

// ═══════════════════════════════════════════════════════════════════════════
// PREVIEW MODAL  (inject once at runtime so no changes to .php needed)
// ═══════════════════════════════════════════════════════════════════════════
(function buildPreviewModal() {
    const style = document.createElement('style');
    style.textContent = `
    #previewModal {
        display:none;position:fixed;inset:0;z-index:5000;
        background:rgba(0,0,0,.65);align-items:center;justify-content:center;padding:1rem;
    }
    #previewModal.pm-open { display:flex; }
    #pm-box {
        background:#fff;border-radius:20px;overflow:hidden;
        width:92vw;max-width:1100px;height:88vh;
        display:flex;flex-direction:column;
        box-shadow:0 24px 60px rgba(0,0,0,.4);
    }
    #pm-header {
        background:#1a3c2f;color:#fff;
        padding:.75rem 1.25rem;
        display:flex;align-items:center;gap:.7rem;flex-shrink:0;
    }
    #pm-title {
        flex:1;font-size:1rem;font-weight:600;
        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    }
    .pm-hbtn {
        display:inline-flex;align-items:center;gap:.35rem;
        padding:.4rem 1rem;border-radius:20px;font-size:.82rem;font-weight:600;
        border:none;cursor:pointer;text-decoration:none;transition:filter .15s;
    }
    .pm-hbtn:hover { filter:brightness(1.15); }
    #pm-dl-btn   { background:#27ae60;color:#fff; }
    #pm-close-btn{ background:rgba(255,255,255,.18);color:#fff; }
    #pm-body {
        flex:1;overflow:hidden;position:relative;background:#e8e8e8;
    }
    #pm-body iframe {
        position:absolute;inset:0;width:100%;height:100%;border:none;
    }
    #pm-docx-wrap {
        position:absolute;inset:0;overflow:auto;padding:20px 0;background:#e8e8e8;
    }
    #pm-docx-wrap .docx-wrapper {
        margin:0 auto;
    }
    /* Page sheet */
    #pm-docx-wrap .docx-wrapper > section {
        background:white;
        box-shadow:0 2px 16px rgba(0,0,0,.18);
        margin:0 auto 24px auto;
        box-sizing:border-box;
        position:relative;
        display:flex;
        flex-direction:column;
    }
    /* Header — fixed at top, full width */
    #pm-docx-wrap header {
        display:block !important;
        position:relative !important;
        width:100% !important;
        overflow:visible !important;
        flex-shrink:0;
        margin:0 !important;
        padding:0 !important;
    }
    /* Header table must span full width with equal columns */
    #pm-docx-wrap header table {
        width:100% !important;
        table-layout:fixed !important;
        border-collapse:collapse !important;
    }
    #pm-docx-wrap header td {
        vertical-align:middle !important;
        text-align:center !important;
        padding:4px !important;
        overflow:visible !important;
    }
    /* Images inside header — contained, not absolute */
    #pm-docx-wrap header img {
        position:relative !important;
        display:block !important;
        margin:0 auto !important;
        max-width:100% !important;
        max-height:120px !important;
        width:auto !important;
        height:auto !important;
        visibility:visible !important;
    }
    /* Footer — fixed at bottom */
    #pm-docx-wrap footer {
        display:block !important;
        position:relative !important;
        width:100% !important;
        overflow:visible !important;
        flex-shrink:0;
        margin-top:auto !important;
        padding:0 !important;
        border-top:1px solid #ccc;
    }
    #pm-docx-wrap footer table {
        width:100% !important;
        table-layout:fixed !important;
        border-collapse:collapse !important;
    }
    #pm-docx-wrap footer td {
        vertical-align:middle !important;
        text-align:center !important;
        padding:4px !important;
    }
    #pm-docx-wrap footer img {
        position:relative !important;
        display:block !important;
        margin:0 auto !important;
        max-width:100% !important;
        max-height:60px !important;
        width:auto !important;
        height:auto !important;
        visibility:visible !important;
    }
    /* Body content area */
    #pm-docx-wrap article, #pm-docx-wrap main,
    #pm-docx-wrap .docx-wrapper > section > div:not(header):not(footer) {
        flex:1;
    }
    /* All images visible by default */
    #pm-docx-wrap img {
        visibility:visible !important;
    }
    /* Absolutely positioned images — reset to relative */
    #pm-docx-wrap header img[style*="position:absolute"],
    #pm-docx-wrap header img[style*="position: absolute"] {
        position:relative !important;
        left:auto !important;
        top:auto !important;
        transform:none !important;
    }
    /* Tables in body */
    #pm-docx-wrap table {
        border-collapse:collapse;
    }
    #pm-docx-wrap td, #pm-docx-wrap th {
        vertical-align:top;
        word-break:break-word;
    }
    #pm-xlsx-wrap {
        position:absolute;inset:0;overflow:auto;background:#fff;padding:1rem;
    }
    #pm-xlsx-wrap table  { border-collapse:collapse;font-size:.82rem;min-width:100%; }
    #pm-xlsx-wrap th,
    #pm-xlsx-wrap td     { border:1px solid #ccc;padding:.3rem .6rem;white-space:nowrap; }
    #pm-xlsx-wrap th     { background:#2d6a4f;color:#fff;position:sticky;top:0; }
    #pm-xlsx-wrap tr:nth-child(even) td { background:#f9fbf9; }
    #pm-loading {
        position:absolute;inset:0;background:rgba(255,255,255,.88);
        display:flex;flex-direction:column;align-items:center;justify-content:center;
        gap:.8rem;font-size:.95rem;color:#555;z-index:9;
    }
    #pm-loading.pm-hidden { display:none; }
    .pm-spin {
        width:38px;height:38px;border:4px solid #dde;
        border-top-color:#2d6a4f;border-radius:50%;
        animation:pmSpin .7s linear infinite;
    }
    @keyframes pmSpin { to { transform:rotate(360deg); } }
    #pm-err {
        display:none;position:absolute;inset:0;
        align-items:center;justify-content:center;
        flex-direction:column;gap:.6rem;padding:2rem;
        text-align:center;color:#c0392b;font-weight:600;background:#fff;
    }
    #pm-err.pm-show { display:flex; }
    #pm-err i { font-size:2.5rem; }
    `;
    document.head.appendChild(style);

    const modal = document.createElement('div');
    modal.id = 'previewModal';
    modal.innerHTML = `
    <div id="pm-box">
        <div id="pm-header">
            <span id="pm-title">Document</span>
            <button id="pm-close-btn" class="pm-hbtn"><i class="fas fa-times"></i> Close</button>
        </div>
        <div id="pm-body">
            <div id="pm-loading">
                <div class="pm-spin"></div>
                <span id="pm-load-txt">Loading…</span>
            </div>
            <div id="pm-err"><i class="fas fa-exclamation-triangle"></i><span id="pm-err-txt"></span></div>
        </div>
    </div>`;
    document.body.appendChild(modal);

    document.getElementById('pm-close-btn').onclick = closePreviewModal;
    modal.addEventListener('click', e => { if (e.target === modal) closePreviewModal(); });
}());

let _docxReady = false, _xlsxReady = false;

function closePreviewModal() {
    document.getElementById('previewModal').classList.remove('pm-open');
    // Tear down dynamic content
    ['pm-docx-wrap','pm-xlsx-wrap'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.remove();
    });
    const iframe = document.querySelector('#pm-body iframe');
    if (iframe) iframe.remove();
    // Reset states
    document.getElementById('pm-loading').classList.remove('pm-hidden');
    document.getElementById('pm-err').classList.remove('pm-show');
}

window.openPreviewModal = function (submissionId, ext, title) {
    const modal   = document.getElementById('previewModal');
    const body    = document.getElementById('pm-body');
    const loading = document.getElementById('pm-loading');
    const loadTxt = document.getElementById('pm-load-txt');
    const err     = document.getElementById('pm-err');

    // Reset
    ['pm-docx-wrap','pm-xlsx-wrap'].forEach(id => { const el=document.getElementById(id); if(el) el.remove(); });
    const oldIframe = body.querySelector('iframe');
    if (oldIframe) oldIframe.remove();
    loading.classList.remove('pm-hidden');
    err.classList.remove('pm-show');

    document.getElementById('pm-title').textContent = title || 'Document';
    modal.classList.add('pm-open');

    // Build URL relative to current page location
    const base = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
    const url  = base + 'file_preview.php?submission_id=' + submissionId;

    // ── PDF / image ──────────────────────────────────────────────────────────
    if (ext === 'pdf' || ['jpg','jpeg','png','gif','txt'].includes(ext)) {
        loadTxt.textContent = 'Loading document…';
        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.onload = () => loading.classList.add('pm-hidden');
        iframe.onerror = () => showPmErr('Failed to load document.');
        body.appendChild(iframe);
        return;
    }

    // ── DOCX ─────────────────────────────────────────────────────────────────
    if (ext === 'docx') {
        loadTxt.textContent = 'Loading document…';
        const wrap = document.createElement('div');
        wrap.id = 'pm-docx-wrap';
        body.appendChild(wrap);

        const base = '/org-dashboard/php/get_docx.php?id=' + submissionId;

        // Fetch raw binary
        fetch(base + '&mode=binary', { credentials: 'include' })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.arrayBuffer();
        })
        .then(function(buf) {
            if (buf.byteLength < 100) throw new Error('Empty file');

            // Ensure JSZip + docx-preview loaded before patching
            return new Promise(function(resolve, reject) {
                function loadDocxPreview() {
                    if (typeof docx !== 'undefined') { resolve(buf); return; }
                    loadScript('https://cdn.jsdelivr.net/npm/docx-preview@0.3.2/dist/docx-preview.min.js',
                        function() { resolve(buf); }, reject);
                }
                if (typeof JSZip !== 'undefined') { loadDocxPreview(); return; }
                loadScript('https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
                    loadDocxPreview, reject);
            });
        })
        .then(function(buf) {
            // docx-preview skips wp:anchor (floating) drawings entirely.
            // Fix: detect thin divider images (w/h >= 5) and strip them,
            // then convert all remaining anchors to inline so logos render.
            return JSZip.loadAsync(buf).then(function(zip) {
                // Step 1: find thin horizontal images in word/media
                var mediaChecks = [];
                zip.forEach(function(path, file) {
                    if (/^word\/media\//i.test(path) && !file.dir) {
                        mediaChecks.push(file.async('uint8array').then(function(bytes) {
                            var name = path.replace(/^word\/media\//i, '');
                            var w = 0, h = 0;
                            if (/\.png$/i.test(path) && bytes.length > 24) {
                                w = (bytes[16]<<24)|(bytes[17]<<16)|(bytes[18]<<8)|bytes[19];
                                h = (bytes[20]<<24)|(bytes[21]<<16)|(bytes[22]<<8)|bytes[23];
                            } else if (/\.jpe?g$/i.test(path)) {
                                for (var i = 2; i < bytes.length - 8; i++) {
                                    if (bytes[i]===0xFF && (bytes[i+1]===0xC0||bytes[i+1]===0xC2)) {
                                        h=(bytes[i+5]<<8)|bytes[i+6]; w=(bytes[i+7]<<8)|bytes[i+8]; break;
                                    }
                                }
                            }
                            return (h > 0 && w / h >= 5) ? name : null;
                        }));
                    }
                });
                return Promise.all(mediaChecks).then(function(results) {
                    var dividerFiles = new Set(results.filter(Boolean));
                    // Step 2: map rIds to divider filenames via rels files
                    var relChecks = [];
                    zip.forEach(function(path, file) {
                        if (/\.xml\.rels$/i.test(path) && !file.dir) {
                            relChecks.push(file.async('string').then(function(xml) {
                                var rids = new Set();
                                var re = /Id="([^"]+)"[^>]+Target="media\/([^"]+)"/g, m;
                                while ((m = re.exec(xml)) !== null) {
                                    if (dividerFiles.has(m[2])) rids.add(m[1]);
                                }
                                return { xmlPath: path.replace('_rels/','').replace(/\.rels$/, ''), rids: rids };
                            }));
                        }
                    });
                    return Promise.all(relChecks);
                }).then(function(rels) {
                    var dividerMap = {};
                    rels.forEach(function(r) { if (r.rids.size > 0) dividerMap[r.xmlPath] = r.rids; });
                    // Step 3: patch every XML file
                    var xmlFiles = [];
                    zip.forEach(function(path, file) { if (/\.xml$/i.test(path) && !file.dir) xmlFiles.push(path); });
                    return Promise.all(xmlFiles.map(function(path) {
                        return zip.files[path].async('string').then(function(xml) {
                            // Remove drawing blocks for divider rIds
                            var rids = dividerMap[path];
                            if (rids) {
                                rids.forEach(function(rid) {
                                    var e = rid.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                                    xml = xml.replace(new RegExp('<w:drawing>\\s*<wp:anchor\\b[\\s\\S]*?r:embed="'+e+'"[\\s\\S]*?<\/wp:anchor>\\s*<\/w:drawing>','g'), '');
                                });
                            }
                            // Convert remaining anchors to inline
                            xml = xml
                                .replace(/<wp:anchor\b[^>]*>/g, '<wp:inline distT="0" distB="0" distL="0" distR="0">')
                                .replace(/<\/wp:anchor>/g, '</wp:inline>')
                                .replace(/<wp:wrapNone\s*\/>/g, '')
                                .replace(/<wp:positionH\b[\s\S]*?<\/wp:positionH>/g, '')
                                .replace(/<wp:positionV\b[\s\S]*?<\/wp:positionV>/g, '')
                                .replace(/<wp:simplePos\b[^>]*\/>/g, '')
                                .replace(/<wp14:sizeRelH\b[\s\S]*?<\/wp14:sizeRelH>/g, '')
                                .replace(/<wp14:sizeRelV\b[\s\S]*?<\/wp14:sizeRelV>/g, '');
                            zip.file(path, xml);
                        });
                    })).then(function() { return zip.generateAsync({ type: 'arraybuffer' }); });
                });
            });
        })
        .then(function(patchedBuf) {
            return docx.renderAsync(patchedBuf, wrap, null, {
                className:                   'docx-wrapper',
                inWrapper:                   true,
                ignoreWidth:                 false,
                ignoreHeight:                false,
                breakPages:                  true,
                ignoreLastRenderedPageBreak: true,
                experimental:                true,
                trimXmlDeclaration:          true,
                renderHeaders:               true,
                renderFooters:               true,
                renderFootnotes:             true,
                renderEndnotes:              true,
                useBase64URL:                true,
            });
        })
        .then(function() {
            wrap.querySelectorAll('img').forEach(function(img) {
                img.style.visibility = 'visible';
                img.style.display    = 'inline-block';
                if (img.style.position === 'absolute') {
                    img.style.position = 'relative';
                    img.style.left = 'auto';
                    img.style.top  = 'auto';
                }
            });
            loading.classList.add('pm-hidden');
        })
        .catch(function(e) { showPmErr('Error: ' + e.message); });
        return;
    }

    // ── XLSX ─────────────────────────────────────────────────────────────────
    if (ext === 'xlsx' || ext === 'xls') {
        loadTxt.textContent = 'Loading spreadsheet…';
        const wrap = document.createElement('div');
        wrap.id = 'pm-xlsx-wrap';
        body.appendChild(wrap);

        function renderSheet() {
            fetch(url)
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.arrayBuffer(); })
                .then(buf => {
                    const wb    = XLSX.read(new Uint8Array(buf), { type:'array' });
                    const names = wb.SheetNames;
                    let html = '';
                    if (names.length > 1) {
                        html += '<div style="display:flex;gap:.4rem;padding:.5rem;background:#f4f4f4;border-bottom:1px solid #ccc;flex-wrap:wrap">';
                        names.forEach((n,i) => {
                            html += `<button onclick="pmTab(${i})" id="pm-stab-${i}"
                                style="padding:.25rem .75rem;border:1px solid #ccc;border-radius:4px;cursor:pointer;
                                background:${i===0?'#2d6a4f':'#fff'};color:${i===0?'#fff':'#333'}">${n}</button>`;
                        });
                        html += '</div>';
                    }
                    html += '<div id="pm-sheet"></div>';
                    wrap.innerHTML = html;
                    window._pmWb = wb;
                    pmTab(0);
                    loading.classList.add('pm-hidden');
                })
                .catch(e => showPmErr('Failed to load spreadsheet: ' + e.message));
        }
        if (_xlsxReady) { renderSheet(); return; }
        loadScript('https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js',
            () => { _xlsxReady = true; renderSheet(); },
            () => showPmErr('Could not load spreadsheet renderer.')
        );
        return;
    }

    // ── Unsupported ──────────────────────────────────────────────────────────
    loading.classList.add('pm-hidden');
    showPmErr(`Preview not available for .${ext.toUpperCase()} files — use the Download button.`);
};

window.pmTab = function (idx) {
    const wb = window._pmWb; if (!wb) return;
    wb.SheetNames.forEach((_,i) => {
        const t = document.getElementById('pm-stab-'+i);
        if (t) { t.style.background = i===idx?'#2d6a4f':'#fff'; t.style.color = i===idx?'#fff':'#333'; }
    });
    const sheet = document.getElementById('pm-sheet');
    if (sheet) sheet.innerHTML = XLSX.utils.sheet_to_html(wb.Sheets[wb.SheetNames[idx]], {editable:false});
};

function showPmErr(msg) {
    document.getElementById('pm-loading').classList.add('pm-hidden');
    const e = document.getElementById('pm-err');
    document.getElementById('pm-err-txt').textContent = msg;
    e.classList.add('pm-show');
}

function loadScript(src, onload, onerror) {
    const s = document.createElement('script');
    s.src = src; s.onload = onload; s.onerror = onerror;
    document.head.appendChild(s);
}


// ── Inject real images into docx-preview rendered output ─────────────────────
function injectDocxImages(submissionId, wrap, callback) {
    fetch('/org-dashboard/php/get_docx.php?id=' + submissionId + '&mode=images', { credentials: 'include' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.images) return;
            var images = data.images;

            // Find all img tags rendered by docx-preview
            var imgs = wrap.querySelectorAll('img');
            imgs.forEach(function(img) {
                // docx-preview sets src to blob: or data: URLs
                // We need to match by order or by filename hint in the src
                var src = img.getAttribute('src') || '';
                var matched = false;

                // Try to match by filename in src
                Object.keys(images).forEach(function(filename) {
                    if (matched) return;
                    if (src.indexOf(filename) !== -1) {
                        var info = images[filename];
                        img.src = 'data:' + info.mime + ';base64,' + info.base64;
                        img.style.visibility = 'visible';
                        img.style.display = 'inline-block';
                        matched = true;
                    }
                });

                // If img is broken/empty, try replacing with images in order
                if (!matched && (img.naturalWidth === 0 || src === '' || src === 'about:blank')) {
                    img.style.visibility = 'visible';
                    img.style.display = 'inline-block';
                }
            });

            // Fix all svg <image> elements (docx-preview uses these for WMF/EMF)
            var svgImgs = wrap.querySelectorAll('image, svg image');
            var imageKeys = Object.keys(images);
            var keyIdx = 0;
            svgImgs.forEach(function(svgImg) {
                var href = svgImg.getAttribute('href') || svgImg.getAttribute('xlink:href') || '';
                var matched = false;

                // Match by filename
                imageKeys.forEach(function(filename) {
                    if (matched) return;
                    if (href.indexOf(filename) !== -1) {
                        var info = images[filename];
                        var dataUrl = 'data:' + info.mime + ';base64,' + info.base64;
                        svgImg.setAttribute('href', dataUrl);
                        svgImg.setAttribute('xlink:href', dataUrl);
                        svgImg.style.visibility = 'visible';
                        matched = true;
                    }
                });

                // Inject next available image if no match
                if (!matched && keyIdx < imageKeys.length) {
                    var info = images[imageKeys[keyIdx]];
                    var dataUrl = 'data:' + info.mime + ';base64,' + info.base64;
                    svgImg.setAttribute('href', dataUrl);
                    svgImg.setAttribute('xlink:href', dataUrl);
                    svgImg.style.visibility = 'visible';
                    keyIdx++;
                }
            });

            // Also fix any broken img tags with empty src using available images
            var brokenImgs = wrap.querySelectorAll('img');
            var remaining = imageKeys.filter(function(k) {
                return !Object.values(data.rels || {}).includes(k) || true;
            });
            var rIdx = 0;
            brokenImgs.forEach(function(img) {
                if (img.naturalWidth === 0 && rIdx < remaining.length) {
                    var info = images[remaining[rIdx]];
                    if (info) {
                        img.src = 'data:' + info.mime + ';base64,' + info.base64;
                        img.style.visibility = 'visible';
                        img.style.display = 'inline-block';
                        rIdx++;
                    }
                }
            });
        })
        .then(function() { if (callback) callback(); })
        .catch(function() { if (callback) callback(); });
}

// ── Freeze header/footer: render into offscreen canvas then replace ───────────
function freezeHeaderFooter(submissionId, wrap, callback) {
    // Collect all header and footer elements
    var els = [];
    wrap.querySelectorAll('header').forEach(function(el) { els.push({el:el, type:'header'}); });
    wrap.querySelectorAll('footer').forEach(function(el) { els.push({el:el, type:'footer'}); });

    if (els.length === 0) { if(callback) callback(); return; }

    // Load html2canvas
    function doFreeze() {
        var done = 0;
        var total = els.length;

        els.forEach(function(item) {
            var el = item.el;

            // Force every img inside to be visible with correct src
            el.querySelectorAll('img').forEach(function(img) {
                img.style.cssText += ';visibility:visible!important;display:inline-block!important;';
                // If src is blob: try to keep it, if broken set a placeholder
                if (!img.src || img.naturalWidth === 0) {
                    img.style.display = 'none';
                }
            });

            // Get dimensions
            var rect = el.getBoundingClientRect();
            var w = Math.max(el.scrollWidth, rect.width, 200);
            var h = Math.max(el.scrollHeight, rect.height, 20);

            if (h < 5) {
                done++;
                if (done >= total && callback) callback();
                return;
            }

            html2canvas(el, {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                width: w,
                height: h,
                windowWidth: document.documentElement.scrollWidth,
                windowHeight: document.documentElement.scrollHeight,
                logging: false,
                imageTimeout: 5000,
                onclone: function(clonedDoc, clonedEl) {
                    // In the clone, make ALL images visible
                    clonedEl.querySelectorAll('img').forEach(function(im) {
                        if (im.src && im.src !== 'about:blank') {
                            im.style.visibility = 'visible';
                            im.style.display = 'inline-block';
                            im.style.opacity = '1';
                        }
                    });
                    // Fix absolutely positioned elements
                    clonedEl.style.position = 'relative';
                    clonedEl.style.overflow = 'visible';
                }
            }).then(function(canvas) {
                // Only replace if canvas has content
                if (canvas.width > 0 && canvas.height > 0) {
                    var img = document.createElement('img');
                    img.src = canvas.toDataURL('image/png', 1.0);
                    img.style.cssText = 'width:100%;display:block;margin:0;padding:0;border:none;max-width:100%;';
                    if (el.parentNode) el.parentNode.replaceChild(img, el);
                }
                done++;
                if (done >= total && callback) callback();
            }).catch(function(e) {
                console.warn('html2canvas error:', e);
                done++;
                if (done >= total && callback) callback();
            });
        });
    }

    // Wait for all images in the wrap to fully load first
    var allImgs = Array.from(wrap.querySelectorAll('img'));
    var loadPromises = allImgs.map(function(img) {
        return new Promise(function(res) {
            if (img.complete && img.naturalWidth > 0) { res(); return; }
            img.onload  = res;
            img.onerror = res;
            setTimeout(res, 3000); // max wait 3s
        });
    });

    Promise.all(loadPromises).then(function() {
        // Extra delay for browser to paint
        setTimeout(function() {
            if (typeof html2canvas !== 'undefined') {
                doFreeze();
            } else {
                loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
                    function() { doFreeze(); },
                    function() { if(callback) callback(); }
                );
            }
        }, 1000);
    });
}

// ─── Search & Filter ─────────────────────────────────────────────────────────
function filterTable() {
    // Re-query every time so dynamically added rows are included
    const rows       = document.querySelectorAll('#documentsTable tbody tr');
    const searchTerm = searchInput.value.toLowerCase();
    const statusVal  = statusFilter.value.toLowerCase();
    const dateVal    = dateFilter.value;

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        // Skip empty-state colspan rows
        if (!cells.length || (cells.length === 1 && cells[0].hasAttribute('colspan'))) return;

        const title  = cells[0] ? cells[0].textContent.toLowerCase() : '';
        const date   = cells[1] ? cells[1].textContent.trim() : '';
        const status = cells[3] ? cells[3].textContent.trim().toLowerCase() : '';

        const ok = (!searchTerm || title.includes(searchTerm))
                && (!statusVal  || status.includes(statusVal))
                && (!dateVal    || date.includes(dateVal));

        row.style.display = ok ? '' : 'none';
    });
}

searchInput.addEventListener('input',  filterTable);
statusFilter.addEventListener('change', filterTable);
dateFilter.addEventListener('change',  filterTable);