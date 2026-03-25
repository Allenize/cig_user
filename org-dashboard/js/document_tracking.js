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
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Document uploaded successfully!', true);
                addTableRow(document.getElementById('docTitle').value, data.submitted_by || 'You', data.submission_id, ext, false);
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
        .then(r => r.json())
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
        ? `<button class="btn-view" onclick="openTemplatePreviewById(${submissionId}, this.closest('tr').getAttribute('data-submission-data'), '${escHtml(title)}')">
               <i class="fas fa-eye"></i> View
           </button>`
        : `<button class="btn-view" onclick="openPreviewModal(${submissionId},'${ext}','${escHtml(title)}')">
               <i class="fas fa-eye"></i> View
           </button>`;

    const tr = document.createElement('tr');
    tr.setAttribute('data-title', title.toLowerCase());
    tr.setAttribute('data-status', 'pending');
    tr.setAttribute('data-date', new Date().toISOString().split('T')[0]);
    if (isTemplate && safeData) tr.setAttribute('data-submission-data', safeData);
    tr.innerHTML = `
        <td>
            <div class="doc-name-cell">
                <i class="fas ${icon}" style="color:${color};margin-right:8px;"></i>
                <span class="doc-title">${escHtml(title)}</span>
            </div>
        </td>
        <td>${today}</td>
        <td>${escHtml(submittedBy)}</td>
        <td><span class="status-badge status-pending">Pending</span></td>
        <td>
            <div class="doc-actions">
                ${viewBtn}
            </div>
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

        // Template fields
        if (state.template) {
            TEMPLATE_FIELDS.forEach(id => {
                const el = document.getElementById(id);
                if (!el || state.template[id] === undefined) return;
                el.value = state.template[id];
                if (id === 'templateSelect' && state.template[id]) {
                    // Load dynamic fields, then restore their values
                    if (typeof loadTemplateFields === 'function') {
                        loadTemplateFields();
                        setTimeout(() => {
                            if (state.dynamic) restoreDynamic(state.dynamic);
                            if (typeof validateTemplateForm === 'function') validateTemplateForm();
                        }, 200);
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