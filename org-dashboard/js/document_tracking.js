// ─── Org taglines map (keyed by org logo filename stem) ──────────────────────
const ORG_TAGLINES = {
    'ACES': 'Advancing Competency and Excellence in Service',
    'AIS':  'Advancing Information Systems for Progress',
    'ALIW': 'Aliw: Joy, Service, and Excellence',
    'APA':  'Advancing Public Administration',
    'ASO':  'Advocating Student Organization',
    'ATMS': 'Advancing Tourism and Management Studies',
    'BS':   'Building Scholars for the Nation',
    'CAS':  'College of Arts and Sciences – Excellence in Service',
    'CBAM': 'College of Business Administration and Management',
    'CCSE': 'Computing for Change, Service, and Excellence',
    'CIG':  'Community Innovation and Growth',
    'CMH':  'College of Medicine and Health Sciences',
    'COA':  'College of Accountancy',
    'CTED': 'College of Teacher Education',
    'CTHM': 'College of Tourism and Hospitality Management',
    'ECOS': 'Empowered and committed organization of service.',
    'FMS':  'Future Management Students',
    'HMS':  'Health and Medical Sciences Student Organization',
    'HRDMS':'Human Resource Development and Management Society',
    'ISC':  'Information Systems Circle',
    'ITS':  'IT Students – Innovate to Serve',
    'JPIA': 'Junior Philippine Institute of Accountants',
    'MAPAP':'Management and Public Administration Professionals',
    'MAS':  'Mathematics and Sciences Society',
    'MMS':  'Marketing Management Society',
    'MS':   'Management Society',
    'NSS':  'Natural Sciences Society',
    'OAS':  'Outstanding Academic Society',
    'OSAS': 'Office of Student Affairs and Services',
    'PC':   'Philippine Collegians',
    'PCL':  'Philippine Collegiate League',
    'PDC':  'Philippine Debate Circle',
    'POLSO':'Political Science Organization',
    'RC':   'Red Cross Youth',
    'SASI': 'Science and Social Issues',
    'SFCE': 'School of Financial and Commercial Education',
    'SFIE': 'School of Financial and International Education',
    'SJE':  'Social Justice and Equality',
    'TVEA': 'Tourism, Values, Excellence, and Advocacy',
    'UAEE': 'Unity, Advocacy, and Environmental Excellence',
    'USAF': 'University Student Affairs Federation',
    'USP':  'University of San Pablo',
    'YES':  'Youth Empowerment Society',
    'YMCA': 'Young Men\'s Christian Association',
};

const templates = {
    'meeting_minutes': {
        name: 'Meeting Minutes',
        fields: {
            'meeting_date': 'Meeting Date',
            'meeting_time': 'Meeting Time',
            'location': 'Location',
            'attendees': 'Attendees',
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
            'proposal_date':       'Date',
            'recipient_1':         'Vice President for Academic Affairs (Full Name)',
            'recipient_2':         'Dean, Office of Student Affairs and Services (Full Name)',
            'opening_statement':   'Opening Statement',
            'organization':        'Organization',
            'project_title':       'Project Title',
            'project_type':        'Type of Project',
            'project_involvement': 'Project Involvement',
            'project_location':    'Project Location',
            'proposed_start_date': 'Proposed Start Date & Time',
            'proposed_end_date':   'Proposed Completion Date',
            'number_participants': 'Number of Participants',
            'project_summary':     'A. Summary of the Project',
            'project_goal':        'Goal',
            'project_objectives':  'Objectives',
            'expected_outputs':    'C. Expected Outputs',
            'budget_source':       'Source of Fund',
            'budget_partner':      'Partner/Donation/Subsidy',
            'budget_total':        'Total Project Cost',
            'monitoring_details':  'Monitoring',
            'evaluation_details':  'Evaluation Strategy',
            'security_plan':       'V. Security Plan',
            'closing_statement':   'Closing Statement',
            'sender_name':         'Submitted by (Name on line 1, Title/Role on line 2)',
            'adviser_name':        'Noted by - Adviser (Name on line 1, Title on line 2, Org on line 3)',
            'co_adviser_name':     'Noted by - Co-Adviser (Name on line 1, Title on line 2, Org on line 3)',
            'additional_signer_1': 'Additional Noted by #1 (Name, Title, Org — one per line, optional)',
            'additional_signer_2': 'Additional Noted by #2 (Name, Title, Org — one per line, optional)',
            'endorsed_by':         'Endorsed by (Name on line 1, Title on line 2)'
        }
    }
};

// ─── Helper: get the currently active tab name ───────────────────────────────
function getActiveTab() {
    const activeBtn = document.querySelector('.tab-button.active');
    return activeBtn ? activeBtn.getAttribute('data-tab') : 'regular-upload';
}

// ─── DOM refs ────────────────────────────────────────────────────────────────
const uploadModal    = document.getElementById('uploadModal');
const openUploadBtn  = document.getElementById('openUploadModal');
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
    const titleEl = document.getElementById('templateTitle');
    if (titleEl) titleEl.value = '';
    tabButtons.forEach(b => b.classList.remove('active'));
    tabContents.forEach(c => c.classList.remove('active'));
    document.querySelector('[data-tab="regular-upload"]').classList.add('active');
    document.getElementById('regular-upload').classList.add('active');
    document.querySelector('.upload-modal-content').classList.remove('landscape-mode', 'template-expanded');
    submitBtn.disabled    = false;
    submitBtn.style.display = ''; // always restore visibility
    submitBtn.textContent = 'Upload Document';
}

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
        // If the inline project proposal wizard is active, use its direct submit
        const wiz = document.getElementById('inlinePWizard');
        if (wiz && wiz.classList.contains('pw-active')) {
            // Wizard nav button (ipw-next-btn) handles submit on final step;
            // if submitBtn is visible and clicked, trigger the same final step submit
            if (typeof _ipwDoDirectSubmit === 'function') {
                _ipwDoDirectSubmit();
            }
        } else {
            templateForm.dispatchEvent(new Event('submit'));
        }
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
    'attendees','skills','availability',
    'sender_name','adviser_name','co_adviser_name',
    'additional_signer_1','additional_signer_2','endorsed_by'
]);

// Checkbox groups for project_proposal
const checkboxFields = {
    'project_type': ['Curricular', 'Non-Curricular', 'Off-Campus'],
    'project_involvement': ['Host', 'Collaboration', 'Participant']
};

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
        // ── Checkbox fields ──
        if (checkboxFields[fieldId]) {
            const options = checkboxFields[fieldId];
            html += `<div class="form-group">
                <label>${fieldLabel} <span>*</span></label>
                <div class="checkbox-group" id="chk_group_${fieldId}">`;
            options.forEach(opt => {
                const optId = `chk_${fieldId}_${opt.replace(/[^a-z0-9]/gi,'_')}`;
                html += `<label class="checkbox-option" for="${optId}">
                    <input type="checkbox" id="${optId}" name="${fieldId}_chk" value="${opt}" onchange="syncCheckboxHidden('${fieldId}')">
                    <span>${opt}</span>
                </label>`;
            });
            html += `</div>
                <input type="hidden" id="${fieldId}" name="${fieldId}" required>
            </div>`;
            return;
        }

        // ── Date/time fields ──
        if (fieldId === 'proposed_start_date') {
            html += `<div class="form-group">
                <label for="${fieldId}_date">${fieldLabel} <span>*</span></label>
                <div style="display:flex;gap:8px;">
                    <input type="date" id="${fieldId}_date" style="flex:1;" placeholder="Date" required>
                    <input type="time" id="${fieldId}_time" style="flex:1;" placeholder="Time" required>
                </div>
                <input type="hidden" id="${fieldId}" name="${fieldId}" required>
            </div>`;
            return;
        }

        if (fieldId === 'proposed_end_date' || fieldId === 'meeting_date' || fieldId === 'event_date'
            || fieldId === 'date_joined' || fieldId === 'next_meeting' || fieldId.endsWith('_date')) {
            html += `<div class="form-group">
                <label for="${fieldId}">${fieldLabel} <span>*</span></label>
                <input type="date" id="${fieldId}" name="${fieldId}" required>
            </div>`;
            return;
        }

        // ── Number of Participants → textbox ──
        if (fieldId === 'number_participants') {
            html += `<div class="form-group">
                <label for="${fieldId}">${fieldLabel} <span>*</span></label>
                <input type="text" id="${fieldId}" name="${fieldId}" placeholder="e.g. 50 students, all members" required>
            </div>`;
            return;
        }

        // ── Textarea fields ──
        const isTA = textareaFields.has(fieldId);
        if (isTA) {
            html += `<div class="form-group">
                <label for="${fieldId}">${fieldLabel} <span>*</span></label>
                <textarea id="${fieldId}" name="${fieldId}" rows="3" placeholder="Enter ${fieldLabel.toLowerCase()}" required></textarea>
            </div>`;
            return;
        }

        // ── Standard text input ──
        html += `<div class="form-group">
            <label for="${fieldId}">${fieldLabel} <span>*</span></label>
            <input type="text" id="${fieldId}" name="${fieldId}" placeholder="Enter ${fieldLabel.toLowerCase()}" required>
        </div>`;
    });

    container.innerHTML = html;
    mc.classList.add('template-expanded');

    // Attach date+time sync for proposed_start_date
    const sdDate = document.getElementById('proposed_start_date_date');
    const sdTime = document.getElementById('proposed_start_date_time');
    const sdHid  = document.getElementById('proposed_start_date');
    if (sdDate && sdTime && sdHid) {
        const syncSD = () => {
            if (sdDate.value && sdTime.value) {
                // Format: "Month DD, YYYY at HH:MM AM/PM"
                const d = new Date(sdDate.value + 'T' + sdTime.value);
                const opts = { year:'numeric', month:'long', day:'2-digit' };
                const datePart = d.toLocaleDateString('en-US', opts);
                const timePart = d.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });
                sdHid.value = datePart + ' at ' + timePart;
            } else if (sdDate.value) {
                const d = new Date(sdDate.value);
                sdHid.value = d.toLocaleDateString('en-US', { year:'numeric', month:'long', day:'2-digit' });
            } else {
                sdHid.value = '';
            }
            validateTemplateForm();
        };
        sdDate.addEventListener('change', syncSD);
        sdTime.addEventListener('change', syncSD);
    }

    container.querySelectorAll('input, textarea').forEach(f => {
        f.addEventListener('input', validateTemplateForm);
        f.addEventListener('change', validateTemplateForm);
    });
    validateTemplateForm();
}

// Sync checkbox selections into hidden input
function syncCheckboxHidden(fieldId) {
    const checked = Array.from(
        document.querySelectorAll(`[name="${fieldId}_chk"]:checked`)
    ).map(c => c.value);
    const hidden = document.getElementById(fieldId);
    if (hidden) {
        hidden.value = checked.join(', ');
        validateTemplateForm();
    }
}

function validateTemplateForm() {
    const sel     = document.getElementById('templateSelect');
    const title   = document.getElementById('templateTitle');
    const orgName = document.getElementById('organizationName');
    if (!sel || !sel.value || !title || !title.value.trim() || !orgName || !orgName.value.trim()) {
        submitBtn.disabled = true; return;
    }
    const allRequired = templateForm.querySelectorAll('.template-fields-container input[required], .template-fields-container textarea[required]');
    submitBtn.disabled = [...allRequired].some(f => !f.value.trim());
}

document.getElementById('templateSelect')?.addEventListener('change', validateTemplateForm);
document.getElementById('templateTitle')?.addEventListener('input', validateTemplateForm);
document.getElementById('organizationName')?.addEventListener('input', validateTemplateForm);

// ─── Regular upload submit ───────────────────────────────────────────────────
uploadForm.onsubmit = function (e) {
    e.preventDefault();
    const formData = new FormData(uploadForm);
    const origText = submitBtn.textContent;

    const fileInput = document.getElementById('fileUpload');
    const ext = fileInput.files.length
        ? fileInput.files[0].name.split('.').pop().toLowerCase()
        : 'pdf';

    submitBtn.textContent = 'Uploading…';
    submitBtn.disabled    = true;

    fetch('../php/upload_document.php', { method:'POST', body:formData })
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            try {
                return JSON.parse(jsonStart >= 0 ? trimmed.slice(jsonStart) : trimmed);
            } catch(e) {
                const preview = trimmed.substring(0, 500) || '(empty response)';
                throw new Error('Server error: ' + preview);
            }
        })
        .then(data => {
            if (data.success) {
                showToast('Document uploaded successfully!', true);
                addTableRow(document.getElementById('docTitle').value, data.submitted_by || 'You', data.submission_id, ext, false, null);
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

    // ── Guard: project_proposal MUST go through the wizard ──
    // If wizard is not active, re-open it instead of submitting broken data
    if (id === 'project_proposal') {
        const wiz = document.getElementById('inlinePWizard');
        if (!wiz || !wiz.classList.contains('pw-active')) {
            openInlinePWizard();
            return;
        }
        // Wizard is active but submitted via templateForm dispatch — route to direct submit
        _ipwDoDirectSubmit();
        return;
    }

    const formData = new FormData();
    formData.append('template_id', id);
    formData.append('title', customTitle);
    formData.append('organization_name',    document.getElementById('organizationName').value);

    // Auto-set tagline based on org logo selection
    const orgLogoSel = document.getElementById('orgLogoSelect');
    let taglineVal = document.getElementById('organizationTagline')?.value?.trim() || '';
    if (!taglineVal && orgLogoSel && orgLogoSel.value) {
        const stem = orgLogoSel.value.replace(/\.[^.]+$/, '').toUpperCase();
        taglineVal = ORG_TAGLINES[stem] || '';
    }
    formData.append('organization_tagline', taglineVal || ' ');

    const logo = document.getElementById('collaboratedLogo')?.value || '';
    if (logo) formData.append('collaborated_logo', logo);

    Object.keys(templates[id].fields).forEach(fieldId => {
        const el = document.getElementById(fieldId);
        if (el) formData.append(fieldId, el.value);
    });

    const origText = submitBtn.textContent;
    submitBtn.textContent = 'Generating…';
    submitBtn.disabled    = true;

    fetch('../php/upload_document.php', { method:'POST', body:formData })
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            try {
                return JSON.parse(jsonStart >= 0 ? trimmed.slice(jsonStart) : trimmed);
            } catch(e) {
                // PHP returned non-JSON — show the raw error for debugging
                const preview = trimmed.substring(0, 500) || '(empty response)';
                throw new Error('Server error: ' + preview);
            }
        })
        .then(data => {
            if (data.success) {
                showToast('Document generated and submitted!', true);
                const outExt = (data.filename || '').split('.').pop().toLowerCase() || 'docx';
                let subData = data.submission_data || null;
                if (subData) {
                    try {
                        const parsed = JSON.parse(subData);
                        const collabVal = document.getElementById('collaboratedLogoHidden') && document.getElementById('useColloborated') && document.getElementById('useColloborated').checked
                            ? document.getElementById('collaboratedLogo').value
                            : '';
                        if (collabVal) parsed.collaborated_logo = collabVal;
                        subData = JSON.stringify(parsed);
                    } catch(e) {}
                }
                addTableRow(customTitle, data.submitted_by || 'You', data.submission_id, outExt, true, subData);
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
function addTableRow(title, submittedBy, submissionId, ext, isTemplate, submissionDataJson) {
    const tbody = document.querySelector('#documentsTable tbody');
    const empty = tbody.querySelector('tr td[colspan]');
    if (empty) empty.closest('tr').remove();

    const today = new Date().toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' });
    const extColors = { pdf:'#e74c3c', docx:'#2980b9', xlsx:'#27ae60' };
    const extIcons  = { pdf:'fa-file-pdf', docx:'fa-file-word', xlsx:'fa-file-excel' };
    const color     = extColors[ext] || '#7f8c8d';
    const icon      = extIcons[ext]  || 'fa-file-alt';

    const safeData = submissionDataJson
        ? submissionDataJson.replace(/"/g, '&quot;').replace(/'/g, '&#39;')
        : '';

    const viewBtn = isTemplate
        ? `<button class="btn-action btn-view" onclick="openTemplatePreviewById(${submissionId}, this.closest('tr').getAttribute('data-submission-data'), '${escHtml(title)}')">
               <i class="fas fa-eye"></i>
           </button>`
        : `<button class="btn-action btn-view" onclick="openPreviewModal(${submissionId},'${ext}','${escHtml(title)}')">
               <i class="fas fa-eye"></i>
           </button>`;

    const tr = document.createElement('tr');
    tr.setAttribute('data-title', title.toLowerCase());
    tr.setAttribute('data-status', 'pending');
    tr.setAttribute('data-date', new Date().toISOString().split('T')[0]);
    tr.setAttribute('data-submission-id', submissionId);
    tr.setAttribute('data-is-template', isTemplate ? '1' : '0');
    tr.setAttribute('data-title-raw', escHtml(title));
    tr.setAttribute('data-remarks', '');
    if (isTemplate && safeData) tr.setAttribute('data-submission-data', safeData);
    tr.innerHTML = `
        <td class="row-num">—</td>
        <td>
            <div class="doc-name-cell">
                <i class="fas ${icon}" style="color:${color};margin-right:8px;"></i>
                <div class="doc-meta-text">
                    <span class="doc-title">${escHtml(title)}</span>
                    <span class="doc-sub"><span class="file-type-badge" style="background:${color};">${ext.toUpperCase()}</span></span>
                </div>
            </div>
        </td>
        <td class="date-cell">${today}</td>
        <td><span class="submitter-name">${escHtml(submittedBy)}</span></td>
        <td><span class="status-badge pending">Pending</span></td>
        <td class="remarks-cell">Awaiting review</td>
        <td class="actions-cell">
            <div class="doc-actions">${viewBtn}</div>
        </td>`;
    tbody.insertBefore(tr, tbody.firstChild);
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function showToast(message, success) {
    const existing = document.querySelector('.upload-toast');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'upload-toast';
    toast.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:10px;font-size:.9rem;font-weight:600;color:#fff;background:${success?'#2d6a4f':'#e74c3c'};box-shadow:0 4px 20px rgba(0,0,0,.2);transition:opacity .3s;`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity='0'; setTimeout(() => toast.remove(), 300); }, 3500);
}

/* ════════════════════════════════════════════════════════════════════════
   PERSISTENT AUTOSAVE — survives page reload, browser close, and
   successful submissions. Next upload session pre-fills from last entry.
   ════════════════════════════════════════════════════════════════════════ */
(function () {
    const DRAFT_KEY    = 'upload_modal_draft_v2';
    const HISTORY_KEY  = 'upload_modal_last_session';

    // ── Fields to track ─────────────────────────────────────────────────
    const REGULAR_FIELDS   = ['docTitle','docDescription','relatedEvent'];
    const TEMPLATE_FIELDS  = ['templateTitle','templateSelect','organizationName','organizationTagline','collaboratedLogo'];

    // ── Collect current form state ───────────────────────────────────────
    function collectState() {
        const state = {
            activeTab: getActiveTab(),
            regular:   {},
            template:  {},
            dynamic:   {},
            ts:        Date.now()
        };
        REGULAR_FIELDS.forEach(id => {
            const el = document.getElementById(id);
            if (el) state.regular[id] = el.value;
        });
        TEMPLATE_FIELDS.forEach(id => {
            const el = document.getElementById(id);
            if (el) state.template[id] = el.value;
        });
        // Checkbox states
        document.querySelectorAll('#templateFieldsContainer input[type="checkbox"]').forEach(cb => {
            state.dynamic['chk_' + cb.id] = cb.checked;
        });
        // Dynamic template fields
        document.querySelectorAll('#templateFieldsContainer input:not([type="checkbox"]), #templateFieldsContainer textarea, #templateFieldsContainer select').forEach(el => {
            if (el.id) state.dynamic[el.id] = el.value;
        });
        // Collaborated checkbox
        const collabCb = document.getElementById('useColloborated');
        if (collabCb) state.template.useColloborated = collabCb.checked;
        return state;
    }

    // ── Save draft to localStorage ───────────────────────────────────────
    function saveDraft() {
        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify(collectState()));
        } catch(e) {}
        showIndicator('✓ Autosaved');
    }

    // ── Save completed session to history ───────────────────────────────
    function saveToHistory() {
        try {
            const state = collectState();
            state.savedAt = new Date().toLocaleString();
            localStorage.setItem(HISTORY_KEY, JSON.stringify(state));
            localStorage.removeItem(DRAFT_KEY); // clear in-progress draft
        } catch(e) {}
    }

    // ── Restore state into form fields ──────────────────────────────────
    function applyState(state, label) {
        if (!state) return;

        // Switch to saved tab
        if (state.activeTab) {
            const tabBtn = document.querySelector('[data-tab="' + state.activeTab + '"]');
            if (tabBtn && !tabBtn.classList.contains('active')) tabBtn.click();
        }

        // Regular fields
        if (state.regular) {
            REGULAR_FIELDS.forEach(id => {
                const el = document.getElementById(id);
                if (el && state.regular[id] !== undefined) el.value = state.regular[id];
            });
        }

        // Template fields — restore title FIRST, then unlock select, then load template
        if (state.template) {
            // Step 1: restore title so title gate unlocks
            const titleEl = document.getElementById('templateTitle');
            if (titleEl && state.template['templateTitle'] !== undefined) {
                titleEl.value = state.template['templateTitle'];
                // Fire applyTitleGate immediately so select gets unlocked
                if (typeof window._applyTitleGate === 'function') window._applyTitleGate();
            }

            // Step 2: restore all other template fields
            TEMPLATE_FIELDS.forEach(id => {
                if (id === 'templateTitle') return; // already done above
                const el = document.getElementById(id);
                if (!el || state.template[id] === undefined) return;
                el.value = state.template[id];
                if (id === 'templateSelect' && state.template[id]) {
                    // Unlock select before calling loadTemplateFields
                    el.disabled = false;
                    if (typeof loadTemplateFields === 'function') {
                        loadTemplateFields();
                        setTimeout(() => {
                            if (state.dynamic) restoreDynamic(state.dynamic);
                            if (typeof validateTemplateForm === 'function') validateTemplateForm();
                        }, 300);
                    }
                }
            });

            const collabCb = document.getElementById('useColloborated');
            if (collabCb && state.template.useColloborated !== undefined) {
                collabCb.checked = state.template.useColloborated;
                if (typeof toggleColloboratedPicker === 'function') toggleColloboratedPicker(collabCb);
            }
        }

        if (state.dynamic) restoreDynamic(state.dynamic);

        showIndicator(label || '✓ Draft restored');
    }

    function restoreDynamic(dynamic) {
        Object.entries(dynamic).forEach(([key, val]) => {
            if (key.startsWith('chk_')) {
                const cb = document.getElementById(key.slice(4));
                if (cb && cb.type === 'checkbox') {
                    cb.checked = val;
                    cb.dispatchEvent(new Event('change'));
                }
            } else {
                const el = document.getElementById(key);
                if (el && el.type !== 'checkbox') el.value = val;
            }
        });
    }

    // ── Visual indicator ─────────────────────────────────────────────────
    let _indTimer = null;
    function showIndicator(text) {
        const el = document.getElementById('autosave-indicator');
        if (!el) return;
        el.textContent = text;
        el.style.opacity = '1';
        clearTimeout(_indTimer);
        _indTimer = setTimeout(() => { el.style.opacity = '0'; }, 2200);
    }

    // ── Watch for input changes and save ─────────────────────────────────
    let _saveTimer = null;
    function scheduleSave() {
        clearTimeout(_saveTimer);
        _saveTimer = setTimeout(saveDraft, 800);
    }

    function attachListeners() {
        const allIds = [...REGULAR_FIELDS, ...TEMPLATE_FIELDS];
        allIds.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', scheduleSave);
        });
    }

    // Watch for dynamically added fields inside template container
    function observeDynamic() {
        const container = document.getElementById('templateFieldsContainer');
        if (!container) return;
        new MutationObserver(() => {
            container.querySelectorAll('input, textarea, select').forEach(el => {
                if (el.dataset.autosaveWired) return;
                el.dataset.autosaveWired = '1';
                el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', scheduleSave);
            });
        }).observe(container, { childList: true, subtree: true });
    }

    // ── On modal open: load draft → fallback to last session ────────────
    function onModalOpen() {
        setTimeout(() => {
            let raw, label;
            try { raw = localStorage.getItem(DRAFT_KEY); label = '✓ Draft restored'; } catch(e) {}
            if (!raw) {
                try { raw = localStorage.getItem(HISTORY_KEY); label = '✓ Last session restored'; } catch(e) {}
            }
            if (!raw) return;
            try { applyState(JSON.parse(raw), label); } catch(e) {}
        }, 80);
    }

    // ── After successful submit: save to history ─────────────────────────
    function patchShowToast() {
        const orig = window.showToast;
        if (typeof orig !== 'function') return;
        window.showToast = function(msg, success) {
            if (success) saveToHistory();
            orig(msg, success);
        };
    }

    // ── Save on accidental navigation ───────────────────────────────────
    window.addEventListener('beforeunload', () => {
        const modal = document.getElementById('uploadModal');
        if (modal && modal.style.display === 'flex') saveDraft();
    });

    // ── Init ─────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        attachListeners();
        observeDynamic();
        patchShowToast();

        const openBtn = document.getElementById('openUploadModal');
        if (openBtn) openBtn.addEventListener('click', onModalOpen);

        // Remove old close-button listener if present
        const closeBtn = document.getElementById('closeUploadModal');
        if (closeBtn) closeBtn.addEventListener('click', saveDraft);
    });

    window.clearUploadDraft = function() {
        try { localStorage.removeItem(DRAFT_KEY); } catch(e) {}
    };

}());
// ════════════════════════════════════════════════════════
// UNREAD HIGHLIGHT
// ════════════════════════════════════════════════════════
(function initUnread() {
    const KEY = 'dt_read_subs_v2';
    const NEW_MS = 7 * 24 * 60 * 60 * 1000; // 7 days

    function getRead() {
        try { return new Set(JSON.parse(localStorage.getItem(KEY) || '[]')); }
        catch(e) { return new Set(); }
    }
    function saveRead(s) {
        try { localStorage.setItem(KEY, JSON.stringify([...s])); } catch(e) {}
    }

    window.markDocRead = function(id) {
        const r = getRead();
        if (r.has(String(id))) return;
        r.add(String(id));
        saveRead(r);
        const row = document.querySelector(`tr[data-submission-id="${id}"]`);
        if (row) row.classList.remove('doc-unread');
    };

    // Apply highlights on load
    const read = getRead();
    const now  = Date.now();
    document.querySelectorAll('#documentsTable tbody tr[data-submission-id]').forEach(row => {
        const id  = row.getAttribute('data-submission-id');
        const sat = parseInt(row.getAttribute('data-submitted-at'), 10) * 1000;
        if (!read.has(id) && (now - sat) < NEW_MS) {
            row.classList.add('doc-unread');
        }
    });

    // Mark read on view click
    document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-action.btn-view');
        if (!btn) return;
        const row = btn.closest('tr[data-submission-id]');
        if (row) window.markDocRead(row.getAttribute('data-submission-id'));
    });

    // Patch openPreviewModal
    const _orig = window.openPreviewModal;
    if (_orig) {
        window.openPreviewModal = function(id, ext, title) {
            window.markDocRead(String(id));
            return _orig.apply(this, arguments);
        };
    }
}());


// ════════════════════════════════════════════════════════
// INLINE PROJECT PROPOSAL WIZARD
// Renders inside #inlinePWizard (inside the template-upload tab)
// ════════════════════════════════════════════════════════
const IPW_STEPS = [
    {
        label: 'I. Header',
        roman: 'I. Letter Header',
        fields: [
            { id:'proposal_date',     label:'Date',                            type:'date',     req:true },
            { id:'recipient_1',       label:'VP for Academic Affairs (Name)',   type:'text',     req:true, placeholder:'e.g. Dr. Preciosa Villacruel' },
            { id:'recipient_2',       label:'Dean, OSAS (Name)',                type:'text',     req:true, placeholder:'e.g. Sherwin D. Quizon, MSIT' },
            { id:'opening_statement', label:'Opening Statement',                type:'textarea', req:true, placeholder:'This is to express our intent to seek your approval…' },
        ]
    },
    {
        label: 'II. Identifying Info',
        roman: 'II. Identifying Information',
        fields: [
            { id:'organization',        label:'Organization',               type:'text',     req:true },
            { id:'project_title',       label:'Project Title',              type:'text',     req:true },
            { id:'project_type',        label:'Type of Project',            type:'checkbox', options:['Curricular','Non-Curricular','Off-Campus'], req:true },
            { id:'project_involvement', label:'Project Involvement',        type:'checkbox', options:['Host','Collaboration','Participant'], req:true },
            { id:'project_location',    label:'Project Location',           type:'text',     req:true },
            { id:'proposed_start_date', label:'Proposed Start Date & Time', type:'datetime', req:true },
            { id:'proposed_end_date',   label:'Proposed Completion Date',   type:'date',     req:true },
            { id:'number_participants', label:'Number of Participants',     type:'text',     req:true, placeholder:'e.g. 50 students' },
        ]
    },
    {
        label: 'III. Project Details',
        roman: 'III. Project Summary & Goals',
        fields: [
            { id:'project_summary',    label:'A. Summary of the Project', type:'textarea', req:true },
            { id:'project_goal',       label:'Goal',                      type:'textarea', req:true },
            { id:'project_objectives', label:'Objectives (one per line)', type:'textarea', req:true },
            { id:'expected_outputs',   label:'C. Expected Outputs',       type:'textarea', req:true },
        ]
    },
    {
        label: 'IV. Budget',
        roman: 'IV. Budget',
        fields: [
            { id:'budget_source',  label:'Source of Fund',               type:'text', req:true },
            { id:'budget_partner', label:'Partner / Donation / Subsidy', type:'text', req:false, placeholder:'Optional' },
            { id:'budget_total',   label:'Total Project Cost',           type:'text', req:true },
        ]
    },
    {
        label: 'V. Monitoring',
        roman: 'V. Monitoring, Evaluation & Security',
        fields: [
            { id:'monitoring_details', label:'Monitoring Details',   type:'textarea', req:true },
            { id:'evaluation_details', label:'Evaluation Strategy',  type:'textarea', req:true },
            { id:'security_plan',      label:'V. Security Plan',     type:'textarea', req:true },
            { id:'closing_statement',  label:'Closing Statement',    type:'textarea', req:true },
        ]
    },
    {
        label: 'VI. Signatories',
        roman: 'VI. Signatories',
        fields: [
            { id:'sender_name',         label:'Submitted by (Name & Title, one per line)',    type:'textarea', req:true },
            { id:'adviser_name',        label:'Noted by – Adviser (Name, Title, Org)',        type:'textarea', req:true },
            { id:'co_adviser_name',     label:'Noted by – Co-Adviser (Name, Title, Org)',     type:'textarea', req:false, placeholder:'Optional' },
            { id:'additional_signer_1', label:'Additional Noted by #1',                       type:'textarea', req:false, placeholder:'Optional' },
            { id:'additional_signer_2', label:'Additional Noted by #2',                       type:'textarea', req:false, placeholder:'Optional' },
            { id:'endorsed_by',         label:'Endorsed by (Name & Title, one per line)',     type:'textarea', req:true },
        ]
    },
];

let _ipwStep = 0;
let _ipwData = {};

// ── Open: show wizard inside the modal, hide generic fields ──────────────────
function openInlinePWizard() {
    _ipwStep = 0;
    _ipwData = {};

    const tfc = document.getElementById('templateFieldsContainer');
    const wiz = document.getElementById('inlinePWizard');
    if (tfc) tfc.style.display = 'none';
    if (wiz) wiz.classList.add('pw-active');

    // Expand modal to 2× size for comfortable editing
    const mc = document.querySelector('.upload-modal-content');
    if (mc) mc.classList.add('landscape-mode', 'template-expanded', 'pw-expanded');

    // Hide the main footer submit button – wizard nav drives submission
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) submitBtn.style.display = 'none';

    _ipwRender();
}

// ── Close / reset ─────────────────────────────────────────────────────────────
function closeInlinePWizard() {
    const tfc = document.getElementById('templateFieldsContainer');
    const wiz = document.getElementById('inlinePWizard');
    if (tfc) { tfc.style.display = ''; tfc.innerHTML = ''; }
    if (wiz) { wiz.classList.remove('pw-active'); document.getElementById('ipw-body').innerHTML = ''; }

    // Remove expanded modal class
    const mc = document.querySelector('.upload-modal-content');
    if (mc) mc.classList.remove('pw-expanded');

    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) { submitBtn.style.display = ''; submitBtn.disabled = false; }

    _ipwStep = 0;
    _ipwData = {};
}

// ── Step indicator ────────────────────────────────────────────────────────────
function _ipwRenderSteps() {
    const cont = document.getElementById('ipw-steps');
    if (!cont) return;
    let html = '';
    IPW_STEPS.forEach((s, i) => {
        const cls = i < _ipwStep ? 'done' : i === _ipwStep ? 'active' : '';
        const dot = i < _ipwStep
            ? '<i class="fas fa-check" style="font-size:.6rem"></i>'
            : (i + 1);
        html += `<div class="ipw-step ${cls}">
            <div class="ipw-step-dot">${dot}</div>
            <span class="ipw-step-label">${s.label}</span>
        </div>`;
        if (i < IPW_STEPS.length - 1) {
            html += `<div class="ipw-step-sep ${i < _ipwStep ? 'done' : ''}"></div>`;
        }
    });
    cont.innerHTML = html;
}

// ── Render current step ───────────────────────────────────────────────────────
function _ipwRender() {
    _ipwRenderSteps();

    const step    = IPW_STEPS[_ipwStep];
    const body    = document.getElementById('ipw-body');
    const prog    = document.getElementById('ipw-progress');
    const nextBtn = document.getElementById('ipw-next-btn');
    const backBtn = document.getElementById('ipw-back-btn');
    if (!body || !prog || !nextBtn || !backBtn) return;

    prog.textContent = `Step ${_ipwStep + 1} of ${IPW_STEPS.length}`;
    const fill = document.getElementById('ipw-progress-bar-fill');
    if (fill) fill.style.width = `${((_ipwStep + 1) / IPW_STEPS.length) * 100}%`;
    backBtn.style.display = _ipwStep === 0 ? 'none' : '';
    nextBtn.innerHTML = _ipwStep === IPW_STEPS.length - 1
        ? '<i class="fas fa-paper-plane"></i> Generate & Submit'
        : 'Next <i class="fas fa-arrow-right"></i>';

    let html = `<div class="pw-section-title">${step.roman}</div>`;

    step.fields.forEach(f => {
        const saved = _ipwData[f.id] || '';
        const req   = f.req
            ? '<span>*</span>'
            : '<small style="color:#9ca3af;font-weight:400">(optional)</small>';

        // ── Checkbox group ──
        if (f.type === 'checkbox') {
            html += `<div class="form-group">
                <label>${f.label} ${req}</label>
                <div class="checkbox-group" id="ipw_chk_group_${f.id}">`;
            (f.options || []).forEach(opt => {
                const optId   = `ipw_chk_${f.id}_${opt.replace(/[^a-z0-9]/gi, '_')}`;
                const checked = saved.split(', ').includes(opt) ? 'checked' : '';
                html += `<label class="checkbox-option" for="${optId}">
                    <input type="checkbox" id="${optId}" value="${opt}" ${checked}
                           onchange="ipwSyncCheckbox('${f.id}')">
                    <span>${opt}</span>
                </label>`;
            });
            html += `</div>
                <input type="hidden" id="ipw_${f.id}" value="${saved}">
            </div>`;
            return;
        }

        // ── Date + Time ──
        if (f.type === 'datetime') {
            const parts    = saved.split(' at ');
            const datePart = parts[0] ? (() => { try { return new Date(parts[0]).toISOString().split('T')[0]; } catch(e){ return ''; } })() : '';
            const timePart = parts[1] ? _ipwTo24(parts[1]) : '';
            html += `<div class="form-group">
                <label>${f.label} ${req}</label>
                <div class="ipw-row-2">
                    <input type="date" id="ipw_${f.id}_date" value="${datePart}"
                           onchange="ipwSyncDateTime('${f.id}')">
                    <input type="time" id="ipw_${f.id}_time" value="${timePart}"
                           onchange="ipwSyncDateTime('${f.id}')">
                </div>
                <input type="hidden" id="ipw_${f.id}" value="${saved}">
            </div>`;
            return;
        }

        // ── Textarea ──
        if (f.type === 'textarea') {
            html += `<div class="form-group">
                <label for="ipw_${f.id}">${f.label} ${req}</label>
                <textarea id="ipw_${f.id}" rows="3"
                    placeholder="${f.placeholder || ''}">${saved}</textarea>
            </div>`;
            return;
        }

        // ── Date only ──
        if (f.type === 'date') {
            html += `<div class="form-group">
                <label for="ipw_${f.id}">${f.label} ${req}</label>
                <input type="date" id="ipw_${f.id}" value="${saved}">
            </div>`;
            return;
        }

        // ── Text (default) ──
        html += `<div class="form-group">
            <label for="ipw_${f.id}">${f.label} ${req}</label>
            <input type="text" id="ipw_${f.id}"
                   value="${saved}" placeholder="${f.placeholder || ''}">
        </div>`;
    });

    body.innerHTML = html;

    // Scroll modal body back to wizard top
    const modalBody = document.querySelector('.modal-main-body');
    if (modalBody) modalBody.scrollTop = 0;

    _ipwValidate();
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function _ipwTo24(timeStr) {
    const m = timeStr.match(/(\d+):(\d+)\s*(AM|PM)/i);
    if (!m) return timeStr.substring(0, 5);
    let h = parseInt(m[1]);
    const min = m[2], period = m[3].toUpperCase();
    if (period === 'PM' && h !== 12) h += 12;
    if (period === 'AM' && h === 12) h = 0;
    return String(h).padStart(2, '0') + ':' + min;
}

function ipwSyncCheckbox(fieldId) {
    const checked = Array.from(
        document.querySelectorAll(`#ipw_chk_group_${fieldId} input[type=checkbox]:checked`)
    ).map(c => c.value);
    const hid = document.getElementById(`ipw_${fieldId}`);
    if (hid) hid.value = checked.join(', ');
    _ipwValidate();
}

function ipwSyncDateTime(fieldId) {
    const d = document.getElementById(`ipw_${fieldId}_date`);
    const t = document.getElementById(`ipw_${fieldId}_time`);
    const h = document.getElementById(`ipw_${fieldId}`);
    if (!d || !t || !h) return;
    if (d.value) {
        const dt       = new Date(d.value + (t.value ? 'T' + t.value : 'T00:00'));
        const datePart = dt.toLocaleDateString('en-US', { year:'numeric', month:'long', day:'2-digit' });
        const timePart = t.value
            ? dt.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' })
            : '';
        h.value = timePart ? datePart + ' at ' + timePart : datePart;
    } else {
        h.value = '';
    }
    _ipwValidate();
}

function _ipwValidate() {
    const nextBtn = document.getElementById('ipw-next-btn');
    if (!nextBtn) return;
    const allOk = IPW_STEPS[_ipwStep].fields.every(f => {
        if (!f.req) return true;
        const el = document.getElementById(`ipw_${f.id}`);
        return el && el.value.trim() !== '';
    });
    nextBtn.disabled = !allOk;
}

function _ipwCollect() {
    IPW_STEPS[_ipwStep].fields.forEach(f => {
        const el = document.getElementById(`ipw_${f.id}`);
        if (el) _ipwData[f.id] = el.value;
    });
}

// ── Navigation ────────────────────────────────────────────────────────────────
function ipwBack() {
    _ipwCollect();
    if (_ipwStep > 0) { _ipwStep--; _ipwRender(); }
}

function ipwNext() {
    _ipwCollect();
    if (_ipwStep < IPW_STEPS.length - 1) {
        _ipwStep++;
        _ipwRender();
    } else {
        _ipwSubmit();
    }
}

// ── Submit: inject hidden fields then fire templateForm ───────────────────────
function _ipwSubmit() {
    const tfc = document.getElementById('templateFieldsContainer');
    if (tfc) tfc.innerHTML = '';

    Object.entries(_ipwData).forEach(([k, v]) => {
        let el = document.getElementById(k);
        if (!el) {
            el = document.createElement('input');
            el.type = 'hidden'; el.id = k; el.name = k;
            if (tfc) tfc.appendChild(el);
        }
        el.value = v;
    });

    // Ensure template_id is set (set value before triggering any change events)
    const sel = document.getElementById('templateSelect');
    if (sel) {
        sel.disabled = false; // ensure select is enabled so value can be read
        sel.value = 'project_proposal';
    }

    // Hide wizard, restore submit button
    const wiz = document.getElementById('inlinePWizard');
    if (wiz) wiz.classList.remove('pw-active');

    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) { submitBtn.style.display = ''; submitBtn.disabled = false; }

    // ── FIX: Bypass the submitBtn.disabled guard by calling the handler directly ──
    // Dispatch a plain event but also manually invoke the submission logic to avoid
    // race conditions where validateTemplateForm() re-disables the button.
    _ipwDoDirectSubmit();
}

// ── Direct IPW submission: builds FormData from _ipwData and POSTs directly ──
function _ipwDoDirectSubmit() {
    const submitBtn = document.getElementById('submitBtn');
    const sel       = document.getElementById('templateSelect');
    const titleField = document.getElementById('templateTitle');
    const customTitle = titleField ? titleField.value.trim() : '';
    const id = 'project_proposal';

    if (!customTitle) { alert('Please enter a document title'); return; }

    const formData = new FormData();
    formData.append('template_id', id);
    formData.append('title', customTitle);
    formData.append('organization_name',    document.getElementById('organizationName')?.value || '');
    formData.append('organization_tagline', document.getElementById('organizationTagline')?.value || ' ');

    const logo = document.getElementById('collaboratedLogo')?.value || '';
    if (logo) formData.append('collaborated_logo', logo);

    // Append all collected wizard field values
    Object.keys(templates[id].fields).forEach(fieldId => {
        const val = _ipwData[fieldId] !== undefined ? _ipwData[fieldId] : '';
        formData.append(fieldId, val);
    });

    const origText = submitBtn ? submitBtn.textContent : 'Generate & Submit';
    if (submitBtn) { submitBtn.textContent = 'Generating…'; submitBtn.disabled = true; }

    fetch('../php/upload_document.php', { method: 'POST', body: formData })
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            try {
                return JSON.parse(jsonStart >= 0 ? trimmed.slice(jsonStart) : trimmed);
            } catch(e) {
                const preview = trimmed.substring(0, 500) || '(empty response)';
                throw new Error('Server error: ' + preview);
            }
        })
        .then(data => {
            if (data.success) {
                showToast('Document generated and submitted!', true);
                const outExt = (data.filename || '').split('.').pop().toLowerCase() || 'docx';
                let subData = data.submission_data || null;
                if (subData) {
                    try {
                        const parsed = JSON.parse(subData);
                        const collabVal = document.getElementById('useColloborated')?.checked
                            ? (document.getElementById('collaboratedLogo')?.value || '')
                            : '';
                        if (collabVal) parsed.collaborated_logo = collabVal;
                        subData = JSON.stringify(parsed);
                    } catch(e) {}
                }
                addTableRow(customTitle, data.submitted_by || 'You', data.submission_id, outExt, true, subData);
                closeUploadModal();
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast('Error: ' + (data.message || 'Unknown error occurred.'), false);
            }
        })
        .catch(err => {
            console.error('IPW submit error:', err);
            showToast('Error: ' + err.message, false);
        })
        .finally(() => {
            if (submitBtn) { submitBtn.textContent = origText; submitBtn.disabled = false; }
        });
}

// ── Validate on any input inside wizard body ──────────────────────────────────
document.addEventListener('input',  e => { if (e.target.closest('#ipw-body')) _ipwValidate(); });
document.addEventListener('change', e => { if (e.target.closest('#ipw-body')) _ipwValidate(); });

// ── Override loadTemplateFields to intercept project_proposal ─────────────────
(function () {
    const _orig = window.loadTemplateFields;
    window.loadTemplateFields = function () {
        const sel = document.getElementById('templateSelect');

        if (sel && sel.value === 'project_proposal') {
            // Make sure upload modal is open
            const modal = document.getElementById('uploadModal');
            if (modal) modal.style.display = 'flex';
            openInlinePWizard();
            return;
        }

        // Any other template: close wizard if open, restore normal fields
        closeInlinePWizard();
        if (typeof _orig === 'function') _orig.apply(this, arguments);
    };
}());

// ── Reset inline wizard when the upload modal is closed ───────────────────────
(function () {
    const origClose = window.closeUploadModal;
    window.closeUploadModal = function () {
        closeInlinePWizard();
        if (typeof origClose === 'function') origClose();
    };
}());
/* ════════════════════════════════════════════════════════════════════════
   TITLE GATE — lock template select until document title is filled
   ════════════════════════════════════════════════════════════════════════ */
(function () {
    function applyTitleGate() {
        const titleEl = document.getElementById('templateTitle');
        const grp     = document.getElementById('templateSelectGroup');
        const selEl   = document.getElementById('templateSelect');
        if (!titleEl || !grp || !selEl) return;

        const filled = titleEl.value.trim().length > 0;
        selEl.disabled = !filled;
        grp.classList.toggle('select-locked', !filled);

        // If title was cleared after a template was already picked, reset it
        if (!filled && selEl.value) {
            selEl.value = '';
            if (typeof loadTemplateFields === 'function') loadTemplateFields();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const titleEl = document.getElementById('templateTitle');
        if (!titleEl) return;
        titleEl.addEventListener('input', applyTitleGate);
        // Re-apply when modal opens — use longer delay so draft restore runs first
        const openBtn = document.getElementById('openUploadModal');
        if (openBtn) openBtn.addEventListener('click', () => setTimeout(applyTitleGate, 250));
        applyTitleGate();
    });

    window._applyTitleGate = applyTitleGate;
}());

/* ════════════════════════════════════════════════════════════════════════
   PROJECT PROPOSAL AUTOFILL — org name + project title from header fields
   ════════════════════════════════════════════════════════════════════════ */
(function () {
    // Poll until openInlinePWizard is defined, then wrap it
    const _poll = setInterval(() => {
        if (typeof openInlinePWizard !== 'function') return;
        clearInterval(_poll);

        const _orig = openInlinePWizard;
        window.openInlinePWizard = function () {
            _orig.apply(this, arguments);

            // Seed organization from fixed hidden field
            const orgVal = (document.getElementById('organizationName') || {}).value || '';
            if (orgVal && !_ipwData['organization']) _ipwData['organization'] = orgVal;

            // Seed project_title from Document Title input
            const titleVal = (document.getElementById('templateTitle') || {}).value || '';
            if (titleVal && !_ipwData['project_title']) _ipwData['project_title'] = titleVal;

            // Re-render so pre-filled values appear in the fields
            if (typeof _ipwRender === 'function') _ipwRender();
        };
    }, 100);

    // Keep project_title synced while user types in the Document Title field
    document.addEventListener('input', e => {
        if (!e.target || e.target.id !== 'templateTitle') return;
        const ipwField = document.getElementById('ipw_project_title');
        if (ipwField && !ipwField.dataset.userEdited) {
            ipwField.value = e.target.value.trim();
            if (typeof _ipwData !== 'undefined') _ipwData['project_title'] = ipwField.value;
            if (typeof _ipwValidate === 'function') _ipwValidate();
        }
    });

    // Once user manually edits project_title inside the wizard, stop overwriting
    document.addEventListener('input', e => {
        if (e.target && e.target.id === 'ipw_project_title') e.target.dataset.userEdited = '1';
    });
}());

/* ════════════════════════════════════════════════════════════════════════
   IPW ENHANCEMENTS — autofill badges, char counters, shake, field errors
   ════════════════════════════════════════════════════════════════════════ */
(function () {

    /* ── Autofill badge on org + project_title after each render ── */
    const AUTOFILL_IDS = ['ipw_organization', 'ipw_project_title'];

    function applyAutofillBadges() {
        AUTOFILL_IDS.forEach(id => {
            const el = document.getElementById(id);
            if (!el || !el.value.trim()) return;
            el.classList.add('ipw-autofilled');
            if (el.parentElement.querySelector('.ipw-autofill-badge')) return;
            const badge = document.createElement('span');
            badge.className = 'ipw-autofill-badge';
            badge.innerHTML = '<i class="fas fa-magic" style="font-size:.62rem;"></i> Auto-filled from your profile';
            el.parentElement.appendChild(badge);
            // Remove styling once user starts editing
            el.addEventListener('input', () => {
                el.classList.remove('ipw-autofilled');
                badge.remove();
                el.dataset.userEdited = '1';
            }, { once: true });
        });
    }

    /* ── Char counter on wizard textareas ── */
    document.addEventListener('input', e => {
        const ta = e.target;
        if (ta.tagName !== 'TEXTAREA' || !ta.closest('#ipw-body')) return;
        let counter = ta.parentElement.querySelector('.ipw-char-count');
        if (!counter) {
            counter = document.createElement('small');
            counter.className = 'ipw-char-count';
            ta.parentElement.appendChild(counter);
        }
        const len = ta.value.length;
        counter.textContent = len > 0 ? `${len} chars` : '';
        counter.classList.toggle('ipw-count-warn', len > 400);
    });

    /* ── Shake Next + highlight empty required fields on blocked click ── */
    document.addEventListener('click', e => {
        const btn = e.target.closest('#ipw-next-btn');
        if (!btn) return;

        // Highlight all empty required fields on the current step
        if (typeof IPW_STEPS !== 'undefined' && typeof _ipwStep !== 'undefined') {
            IPW_STEPS[_ipwStep].fields.forEach(f => {
                if (!f.req) return;
                const el = document.getElementById('ipw_' + f.id);
                if (!el || el.value.trim()) return;
                el.classList.add('ipw-field-error');
                el.addEventListener('input', () => el.classList.remove('ipw-field-error'), { once: true });
            });
        }

        // Shake if disabled
        if (btn.disabled) {
            btn.classList.remove('ipw-shake');
            void btn.offsetWidth; // reflow to restart animation
            btn.classList.add('ipw-shake');
            btn.addEventListener('animationend', () => btn.classList.remove('ipw-shake'), { once: true });
        }
    }, true);

    /* ── Observe #ipw-body mutations to apply autofill badges after each render ── */
    document.addEventListener('DOMContentLoaded', () => {
        const ipwBody = document.getElementById('ipw-body');
        if (!ipwBody) return;
        new MutationObserver(() => setTimeout(applyAutofillBadges, 50))
            .observe(ipwBody, { childList: true });
    });

}());

/* ══════════════════════════════════════════════════════════════════════════════
   FEATURE 1: SILENT FORM RESTORE
   Saves the template form state on every change.
   On modal reopen: auto-restores title, template, and all field values directly
   into each input — no dropdowns, no UI, just pre-filled fields.
   On successful submission: clears saved state so next open starts fresh.
   Signatory fields (names/titles) are saved permanently and always pre-filled.
   ══════════════════════════════════════════════════════════════════════════════ */
(function () {
    const SAVE_KEY    = 'tpl_last_session_v3';
    const HISTORY_KEY = 'autofill_history_v1';
    // Signatory fields saved permanently — never cleared on submission
    const SIG_KEY     = 'tpl_signatories_v1';
    const SIG_FIELDS  = [
        'sender_name','adviser_name','co_adviser_name',
        'additional_signer_1','additional_signer_2',
        'additional_signer_3','additional_signer_4','additional_signer_5',
        'endorsed_by'
    ];

    /* ── Signatory persistent store (never cleared) ── */
    function getSigStore() {
        try { return JSON.parse(localStorage.getItem(SIG_KEY) || '{}'); } catch(e) { return {}; }
    }
    function saveSigStore(obj) {
        try { localStorage.setItem(SIG_KEY, JSON.stringify(obj)); } catch(e) {}
    }
    function recordSignatories(ipwData) {
        if (!ipwData) return;
        const store = getSigStore();
        SIG_FIELDS.forEach(k => {
            if (ipwData[k] && ipwData[k].trim()) store[k] = ipwData[k].trim();
        });
        saveSigStore(store);
    }
    function getSavedSignatory(fieldId) {
        return getSigStore()[fieldId] || '';
    }

    /* ── Per-field last-value store ── */
    function getFieldHistory() {
        try { return JSON.parse(localStorage.getItem(HISTORY_KEY) || '{}'); } catch(e) { return {}; }
    }
    function saveFieldHistory(h) {
        try { localStorage.setItem(HISTORY_KEY, JSON.stringify(h)); } catch(e) {}
    }
    function recordField(fieldId, value) {
        if (!value || value.trim().length < 2) return;
        const h = getFieldHistory();
        h[fieldId] = value.trim();
        saveFieldHistory(h);
    }
    function getLastValueFor(fieldId) {
        return getFieldHistory()[fieldId] || '';
    }

    /* ── Full snapshot (title + template + all fields) ── */
    function collect() {
        const titleEl = document.getElementById('templateTitle');
        const selEl   = document.getElementById('templateSelect');
        if (!titleEl || !titleEl.value.trim()) return null;

        const snap = {
            docTitle:   titleEl.value.trim(),
            templateId: selEl ? selEl.value : '',
            ipwData:    {},
            fields:     {},
            collabOn:   false,
            collabLogo: '',
        };

        if (typeof _ipwData !== 'undefined') {
            snap.ipwData = Object.assign({}, _ipwData);
        }

        document.querySelectorAll(
            '#templateFieldsContainer input:not([type=hidden]):not([type=checkbox]),' +
            '#templateFieldsContainer textarea'
        ).forEach(el => { if (el.id && el.value) snap.fields[el.id] = el.value; });

        document.querySelectorAll('#templateFieldsContainer input[type=checkbox]').forEach(el => {
            snap.fields['_chk_' + el.id] = el.checked;
        });

        const collabCb  = document.getElementById('useColloborated');
        const collabSel = document.getElementById('collaboratedLogo');
        snap.collabOn   = collabCb  ? collabCb.checked : false;
        snap.collabLogo = collabSel ? collabSel.value  : '';

        return snap;
    }

    function persist() {
        const snap = collect();
        if (!snap) return;
        try { localStorage.setItem(SAVE_KEY, JSON.stringify(snap)); } catch(e) {}

        // Record per-field last values
        if (snap.ipwData) {
            Object.entries(snap.ipwData).forEach(([k, v]) => { if (v) recordField(k, v); });
        }
        Object.entries(snap.fields).forEach(([k, v]) => {
            if (!k.startsWith('_') && v) recordField(k, v);
        });

        // Always persist signatory fields separately (survives submission clear)
        recordSignatories(snap.ipwData);
    }

    function clearSaved() {
        // Only clear the session snapshot — NOT the signatory store
        try { localStorage.removeItem(SAVE_KEY); } catch(e) {}
    }

    /* ── Restore full snapshot into form on modal open ── */
    function restore() {
        let snap;
        try { snap = JSON.parse(localStorage.getItem(SAVE_KEY) || 'null'); } catch(e) {}
        if (!snap || !snap.docTitle) return;

        const titleEl = document.getElementById('templateTitle');
        if (titleEl) {
            titleEl.value = snap.docTitle;
            titleEl.dispatchEvent(new Event('input'));
            if (typeof window._applyTitleGate === 'function') window._applyTitleGate();
        }

        const selEl = document.getElementById('templateSelect');
        if (selEl && snap.templateId) {
            selEl.disabled = false;
            selEl.value    = snap.templateId;
            selEl.dispatchEvent(new Event('change'));
        }

        setTimeout(() => {
            if (snap.templateId === 'project_proposal' &&
                typeof _ipwData !== 'undefined' && Object.keys(snap.ipwData || {}).length) {
                Object.assign(_ipwData, snap.ipwData);
                if (typeof _ipwRender === 'function') _ipwRender();
            }

            Object.entries(snap.fields || {}).forEach(([k, v]) => {
                if (k.startsWith('_chk_')) {
                    const cb = document.getElementById(k.slice(5));
                    if (cb && cb.type === 'checkbox') { cb.checked = !!v; cb.dispatchEvent(new Event('change')); }
                } else {
                    const el = document.getElementById(k);
                    if (el && el.type !== 'checkbox') { el.value = v; el.dispatchEvent(new Event('input')); }
                }
            });

            if (snap.collabOn) {
                const cb = document.getElementById('useColloborated');
                if (cb && !cb.checked) { cb.checked = true; if (typeof toggleColloboratedPicker === 'function') toggleColloboratedPicker(cb); }
                const cs = document.getElementById('collaboratedLogo');
                if (cs && snap.collabLogo) { cs.value = snap.collabLogo; if (typeof updateCollabPreview === 'function') updateCollabPreview(snap.collabLogo); }
            }

            if (typeof validateTemplateForm === 'function') validateTemplateForm();
        }, 350);
    }

    /* ── Pre-fill fields when wizard step renders ──
       Signatory fields always get pre-filled from the permanent store.
       Other fields use the session snapshot or per-field history.            */
    function prefillRenderedFields() {
        let snap;
        try { snap = JSON.parse(localStorage.getItem(SAVE_KEY) || 'null'); } catch(e) {}
        const sigStore = getSigStore();

        document.querySelectorAll(
            '#ipw-body input[type=text], #ipw-body textarea,' +
            '#templateFieldsContainer input[type=text], #templateFieldsContainer textarea'
        ).forEach(el => {
            if (!el.id) return;
            if (el.value.trim()) return; // already has a value — don't overwrite
            const rawId = el.id.replace(/^ipw_/, '');

            let val = '';

            // Signatory fields: always use the persistent store first
            if (SIG_FIELDS.includes(rawId)) {
                val = sigStore[rawId] || '';
            }

            // Fall back to session snapshot, then per-field history
            if (!val && snap && snap.ipwData && snap.ipwData[rawId]) val = snap.ipwData[rawId];
            if (!val && snap && snap.fields && snap.fields[rawId]) val = snap.fields[rawId];
            if (!val) val = getLastValueFor(rawId);

            if (val) {
                el.value = val;
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    }

    /* ── Also seed _ipwData with signatory values before wizard opens ──
       So even step 6 renders pre-filled when user navigates to it.         */
    function seedSignatoriesToIpwData() {
        if (typeof _ipwData === 'undefined') return;
        const store = getSigStore();
        SIG_FIELDS.forEach(k => {
            if (store[k] && !_ipwData[k]) _ipwData[k] = store[k];
        });
    }

    /* ── Auto-save on every change (debounced) ── */
    let _timer = null;
    function schedule() {
        clearTimeout(_timer);
        _timer = setTimeout(persist, 800);
    }

    /* ── Init ── */
    document.addEventListener('DOMContentLoaded', () => {
        document.addEventListener('input',  e => { if (e.target.closest('#template-upload')) schedule(); });
        document.addEventListener('change', e => { if (e.target.closest('#template-upload')) schedule(); });

        const openBtn = document.getElementById('openUploadModal');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                setTimeout(() => {
                    // Always seed signatory store into _ipwData so wizard pre-fills them
                    seedSignatoriesToIpwData();

                    let snap;
                    try { snap = JSON.parse(localStorage.getItem(SAVE_KEY) || 'null'); } catch(e) {}
                    if (!snap || !snap.docTitle) return;

                    const tplTab = document.querySelector('[data-tab="template-upload"]');
                    if (tplTab && !tplTab.classList.contains('active')) tplTab.click();

                    setTimeout(restore, 120);
                }, 80);
            });
        }

        // Pre-fill fields whenever wizard body changes (new step rendered)
        const ipwBody = document.getElementById('ipw-body');
        if (ipwBody) {
            new MutationObserver(() => setTimeout(prefillRenderedFields, 60))
                .observe(ipwBody, { childList: true });
        }
        const tfc = document.getElementById('templateFieldsContainer');
        if (tfc) {
            new MutationObserver(() => setTimeout(prefillRenderedFields, 60))
                .observe(tfc, { childList: true });
        }

        // Also seed _ipwData whenever the inline wizard opens
        const _origOpen = window.openInlinePWizard;
        if (typeof _origOpen === 'function') {
            window.openInlinePWizard = function() {
                _origOpen.apply(this, arguments);
                setTimeout(seedSignatoriesToIpwData, 50);
            };
        }

        // Clear session (but NOT signatories) after successful submission
        const origShowToast = window.showToast;
        if (typeof origShowToast === 'function') {
            window.showToast = function(msg, success) {
                if (success && (
                    msg.toLowerCase().includes('generated') ||
                    msg.toLowerCase().includes('uploaded') ||
                    msg.toLowerCase().includes('submitted')
                )) {
                    // Save current signatories before clearing session
                    if (typeof _ipwData !== 'undefined') recordSignatories(_ipwData);
                    clearSaved();
                }
                origShowToast.apply(this, arguments);
            };
        }
    });

}());

/* ══════════════════════════════════════════════════════════════════════════════
   FEATURE 3: WYSIWYG OUTPUT CONSISTENCY
   Enhances the template preview renderer to exactly match the DOCX output:
   - Numbered objectives (matching PHP's buildProjectProposalContent numbering)
   - Bullet lists for outputs, monitoring, evaluation, security (matching docxBulletList)
   - Tables use consistent label+value layout (matching docxTable)
   - White-space preserved for multi-line text fields
   All changes are ADDITIVE to renderTplPreviewBody — only the PP renderer is patched.
   ══════════════════════════════════════════════════════════════════════════════ */
(function () {

    /* ── WYSIWYG-accurate value renderer ── */
    function wysiwygValue(key, val) {
        if (!val || !val.trim()) return '<em style="color:#bbb">—</em>';
        const v = val.trim();

        // Numbered list fields (match PHP: objectives get 1. 2. 3.)
        const NUMBERED_KEYS = new Set(['project_objectives']);
        if (NUMBERED_KEYS.has(key)) {
            const lines = v.split('\n').map(l => l.trim()).filter(Boolean);
            if (lines.length > 1) {
                return '<ol class="pp-numbered">' + lines.map(l => `<li>${esc(l)}</li>`).join('') + '</ol>';
            }
            return esc(v);
        }

        // Bullet list fields (match PHP's docxBulletList)
        const BULLET_KEYS2 = new Set(['expected_outputs','monitoring_details','evaluation_details','security_plan']);
        if (BULLET_KEYS2.has(key)) {
            const lines = v.split('\n').map(l => l.trim()).filter(Boolean);
            if (lines.length > 1) {
                return '<ul class="pp-bullets">' + lines.map(l => `<li>${esc(l)}</li>`).join('') + '</ul>';
            }
            return '<div class="pp-wysiwyg-block">' + esc(v) + '</div>';
        }

        // Multi-line preserved (opening statement, summaries, closing)
        const BLOCK_KEYS = new Set(['opening_statement','project_summary','project_goal','closing_statement']);
        if (BLOCK_KEYS.has(key)) {
            return '<div class="pp-wysiwyg-block">' + esc(v) + '</div>';
        }

        // Default inline
        return esc(v);
    }

    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── WYSIWYG Project Proposal body builder ── */
    function buildWysiwygPPBody(data, ctrlNum) {
        const f = data.fields || {};
        const orgName = esc(data.organization_name || f.organization || '');
        let html = '';

        // Control number
        if (ctrlNum) {
            html += `<div style="text-align:right;margin-bottom:8px;">
                <span style="display:inline-block;background:#f0faf4;border:1.5px solid #2d6a4f;border-radius:6px;padding:3px 12px;font-size:.78rem;font-weight:700;color:#1a4731;">
                    <span style="color:#5a9070;font-weight:600;font-size:.72rem;margin-right:4px;">Control No.:</span>${esc(ctrlNum)}
                </span></div>`;
        }

        // Date (bold, left) — format YYYY-MM-DD to readable form
        if (f.proposal_date) {
            let dateDisplay = f.proposal_date;
            // If it looks like YYYY-MM-DD, reformat it to Month DD, YYYY
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateDisplay)) {
                try {
                    const d = new Date(dateDisplay + 'T00:00:00');
                    dateDisplay = d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: '2-digit' });
                } catch(e) {}
            }
            html += `<p style="font-weight:700;margin:0 0 8px;">${esc(dateDisplay)}</p>`;
        }

        // Recipient 1
        if (f.recipient_1) {
            html += `<p style="font-weight:700;margin:0;">${esc(f.recipient_1)}</p>`;
            html += `<p style="margin:0 0 8px;">Vice President for Academic Affairs</p>`;
        }
        // Recipient 2
        if (f.recipient_2) {
            html += `<p style="font-weight:700;margin:0;">${esc(f.recipient_2)}</p>`;
            html += `<p style="margin:0 0 8px;">Dean, Office of Student Affairs and Services</p>`;
        }
        // Dear line
        if (f.recipient_2) {
            html += `<p style="font-weight:700;margin:0 0 8px;">Dear ${esc(f.recipient_2)},</p>`;
        }

        // Opening statement
        if (f.opening_statement) {
            html += `<div class="pp-wysiwyg-label">Opening Statement</div>`;
            html += `<div class="pp-wysiwyg-block">${esc(f.opening_statement)}</div>`;
        }

        // PROJECT PROPOSAL heading
        html += `<p style="font-weight:800;font-size:1rem;text-align:center;margin:12px 0 6px;">PROJECT PROPOSAL</p>`;

        // I. Identifying Information
        html += `<div class="pp-wysiwyg-section">I. Identifying Information</div>`;
        html += `<table class="pp-wysiwyg-table"><tbody>` +
            [['Organization', f.organization || ''],
             ['Project Title', f.project_title || ''],
             ['Type of Project', f.project_type || ''],
             ['Project Involvement', f.project_involvement || ''],
             ['Project Location', f.project_location || ''],
             ['Proposed Start Date & Time', f.proposed_start_date || ''],
             ['Proposed Completion Date', f.proposed_end_date || ''],
             ['Number of Participants', f.number_participants || '']]
            .map(([l, v]) => `<tr><td>${esc(l)}</td><td>${esc(v) || '<em style="color:#bbb">—</em>'}</td></tr>`)
            .join('') + '</tbody></table>';

        // II. Project Description
        html += `<div class="pp-wysiwyg-section">II. Project Description</div>`;

        html += `<div class="pp-wysiwyg-label">A. SUMMARY OF THE PROJECT</div>`;
        html += wysiwygValue('project_summary', f.project_summary || '');

        html += `<div class="pp-wysiwyg-label">B. PROJECT GOAL AND OBJECTIVES</div>`;
        html += `<div style="font-weight:600;font-size:.82rem;margin:4px 0 2px;">Goal:</div>`;
        html += wysiwygValue('project_goal', f.project_goal || '');
        html += `<div style="font-weight:600;font-size:.82rem;margin:6px 0 2px;">Objectives:</div>`;
        html += wysiwygValue('project_objectives', f.project_objectives || '');

        html += `<div class="pp-wysiwyg-label">C. EXPECTED OUTPUTS</div>`;
        html += wysiwygValue('expected_outputs', f.expected_outputs || '');

        // III. Budget
        html += `<div class="pp-wysiwyg-section">III. Budget</div>`;
        html += `<table class="pp-wysiwyg-table"><tbody>` +
            [['Source of Fund', f.budget_source || ''],
             ['Partner/Donation/Subsidy', f.budget_partner || ''],
             ['Total Project Cost', f.budget_total || '']]
            .map(([l, v]) => `<tr><td>${esc(l)}</td><td>${esc(v) || '<em style="color:#bbb">—</em>'}</td></tr>`)
            .join('') + '</tbody></table>';

        // IV. Monitoring and Evaluation
        html += `<div class="pp-wysiwyg-section">IV. Monitoring and Evaluation</div>`;
        html += `<div style="font-weight:600;font-size:.82rem;margin:4px 0 2px;">Monitoring:</div>`;
        html += wysiwygValue('monitoring_details', f.monitoring_details || '');
        html += `<div style="font-weight:600;font-size:.82rem;margin:6px 0 2px;">Evaluation Strategy:</div>`;
        html += wysiwygValue('evaluation_details', f.evaluation_details || '');

        // V. Security Plan
        html += `<div class="pp-wysiwyg-section">V. Security Plan</div>`;
        html += wysiwygValue('security_plan', f.security_plan || '');

        // Closing statement
        if (f.closing_statement) {
            html += `<div class="pp-wysiwyg-block" style="font-style:italic;margin-top:10px;">${esc(f.closing_statement)}</div>`;
        }

        // Sincerely
        html += `<p style="margin:10px 0 6px;">Sincerely,</p>`;

        // Sender
        if (f.sender_name) {
            const lines = f.sender_name.split('\n').map(l => l.trim()).filter(Boolean);
            if (lines.length) {
                html += `<p style="font-weight:700;margin:0;">${esc(lines[0])}</p>`;
                lines.slice(1).forEach(l => { html += `<p style="margin:0;">${esc(l)}</p>`; });
            }
        }

        // Noted by
        ['adviser_name','co_adviser_name','additional_signer_1','additional_signer_2'].forEach(key => {
            if (!f[key] || !f[key].trim()) return;
            const lines = f[key].split('\n').map(l => l.trim()).filter(Boolean);
            if (!lines.length) return;
            html += `<p style="font-weight:700;margin:8px 0 0;">${esc(lines[0])}</p>`;
            lines.slice(1).forEach(l => { html += `<p style="margin:0;">${esc(l)}</p>`; });
        });

        // Endorsed by
        if (f.endorsed_by) {
            const lines = f.endorsed_by.split('\n').map(l => l.trim()).filter(Boolean);
            if (lines.length) {
                html += `<p style="font-weight:700;margin:8px 0 0;">${esc(lines[0])}</p>`;
                lines.slice(1).forEach(l => { html += `<p style="margin:0;">${esc(l)}</p>`; });
            }
        }

        return html;
    }

    /* ── Patch renderTplPreviewBody to use WYSIWYG builder for project_proposal ── */
    const _origRenderTplPreviewBody = window.renderTplPreviewBody;
    window.renderTplPreviewBody = function(data, title, controlNumber) {
        // Use WYSIWYG body only for project_proposal template
        if ((data.template_id === 'project_proposal') && typeof _origRenderTplPreviewBody === 'function') {
            const orgName    = data.organization_name    || '';
            const orgTagline = data.organization_tagline || '';
            const collabLogo = data.collaborated_logo || data.collaborated_logo_value || '';
            const ctrlNum    = controlNumber || data.control_number || '';
            const bodyContent = buildWysiwygPPBody(data, ctrlNum);

            const LOGO_SIZE = '60px';
            const leftCell = (typeof LOGO_ADMISSION !== 'undefined' && LOGO_ADMISSION)
                ? `<td style="width:80px;text-align:center;vertical-align:middle;padding:0 8px 0 0;">
                     <img src="${LOGO_ADMISSION}" alt="PLSP" style="height:${LOGO_SIZE};width:${LOGO_SIZE};object-fit:contain;border-radius:50%;border:2px solid rgba(255,255,255,0.25);display:block;margin:0 auto;">
                   </td>`
                : `<td style="width:80px;"></td>`;

            const centerCell = `<td style="text-align:center;vertical-align:middle;padding:0 6px;">
                <div style="font-size:.6rem;font-weight:700;color:#a8d5b5;letter-spacing:.1em;text-transform:uppercase;margin-bottom:3px;">Pamantasan ng Lungsod ng San Pablo</div>
                ${orgName ? `<div style="font-size:.95rem;font-weight:800;color:#fff;margin-bottom:2px;">${esc(orgName)}</div>` : ''}
                ${orgTagline && orgTagline.trim() ? `<div style="font-size:.68rem;color:rgba(183,228,195,.7);font-style:italic;">"${esc(orgTagline)}"</div>` : ''}
            </td>`;

            let rightInner = '';
            if (typeof LOGO_ORG !== 'undefined' && LOGO_ORG) {
                rightInner += `<img src="${LOGO_ORG}" alt="Org" style="height:${LOGO_SIZE};width:${LOGO_SIZE};object-fit:contain;border-radius:50%;border:2px solid rgba(255,255,255,0.25);display:inline-block;vertical-align:middle;">`;
            }
            if (collabLogo && typeof LOGO_MAP !== 'undefined') {
                const collabSrc = LOGO_MAP[collabLogo] || LOGO_MAP[collabLogo + '.jpg'] || LOGO_MAP[collabLogo + '.png'] || null;
                if (collabSrc) {
                    rightInner += `<img src="${collabSrc}" alt="Collab" style="height:${LOGO_SIZE};width:${LOGO_SIZE};object-fit:contain;border-radius:50%;border:2px solid rgba(255,255,255,0.25);display:inline-block;vertical-align:middle;margin-left:6px;">`;
                }
            }
            const rightCell = rightInner
                ? `<td style="text-align:center;vertical-align:middle;padding:0 0 0 8px;white-space:nowrap;">${rightInner}</td>`
                : `<td style="width:80px;"></td>`;

            return `<div style="background:#fff;max-width:700px;margin:20px auto;border-radius:10px;box-shadow:0 2px 16px rgba(0,0,0,.12);overflow:hidden;">
                <div style="background:#1e3a3a;padding:14px 18px;">
                    <table style="width:100%;border-collapse:collapse;"><tr>${leftCell}${centerCell}${rightCell}</tr></table>
                </div>
                <div style="padding:22px 26px;">${bodyContent}</div>
                <div style="background:#f4faf7;border-top:2px solid #2d6a4f;padding:10px 24px;text-align:center;">
                    <div style="font-size:.75rem;color:#2d6a4f;font-style:italic;">"Primed to Lead and Serve for Progress"</div>
                </div>
            </div>`;
        }
        // All other templates: use original renderer
        return _origRenderTplPreviewBody.apply(this, arguments);
    };

}());