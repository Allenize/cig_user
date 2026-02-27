// document_tracking.js

// Template data definitions
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
    }
};

// ========== UPLOAD MODAL FUNCTIONALITY ==========
const uploadModal = document.getElementById('uploadModal');
const openUploadBtn = document.getElementById('openUploadModal');
const closeUploadBtn = document.getElementById('closeUploadModal');
const cancelUploadBtn = document.getElementById('cancelUpload');
const cancelTemplateBtn = document.getElementById('cancelTemplate');
const uploadForm = document.getElementById('uploadForm');
const templateForm = document.getElementById('templateForm');

// Tab switching
const tabButtons = document.querySelectorAll('.tab-button');
const tabContents = document.querySelectorAll('.tab-content');

tabButtons.forEach(button => {
    button.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        
        // Remove active class from all buttons and contents
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));
        
        // Add active class to clicked button and corresponding content
        this.classList.add('active');
        document.getElementById(tabName).classList.add('active');
        
        // Toggle landscape mode for template tab
        const modalContent = document.querySelector('.upload-modal-content');
        if (tabName === 'template-upload') {
            modalContent.classList.add('landscape-mode');
        } else {
            modalContent.classList.remove('landscape-mode');
        }
    });
});

// Open modal
openUploadBtn.onclick = function() {
    uploadModal.style.display = 'block';
};

// Close modal functions
function closeUploadModal() {
    uploadModal.style.display = 'none';
    uploadForm.reset();
    templateForm.reset();
    document.getElementById('templateFieldsContainer').innerHTML = '';
    
    // Reset tabs to regular upload
    tabButtons.forEach(btn => btn.classList.remove('active'));
    tabContents.forEach(content => content.classList.remove('active'));
    document.querySelector('[data-tab="regular-upload"]').classList.add('active');
    document.getElementById('regular-upload').classList.add('active');
    document.querySelector('.upload-modal-content').classList.remove('landscape-mode');
}
closeUploadBtn.onclick = closeUploadModal;
cancelUploadBtn.onclick = closeUploadModal;
cancelTemplateBtn.onclick = closeUploadModal;

// Close when clicking outside modal
window.onclick = function(event) {
    if (event.target == uploadModal) {
        closeUploadModal();
    }
};

// Load template fields
function loadTemplateFields() {
    const templateSelect = document.getElementById('templateSelect');
    const templateId = templateSelect.value;
    const container = document.getElementById('templateFieldsContainer');
    
    if (!templateId || !templates[templateId]) {
        container.innerHTML = '';
        return;
    }
    
    const template = templates[templateId];
    let fieldsHTML = '';
    
    Object.entries(template.fields).forEach(([fieldId, fieldLabel]) => {
        const fieldType = fieldId.includes('date') ? 'date' : 
                         fieldId.includes('time') ? 'time' : 
                         fieldId.includes('budget') || fieldId.includes('attendance') ? 'number' : 'text';
        const isTextarea = fieldId === 'agenda' || fieldId === 'discussion' || 
                          fieldId === 'action_items' || fieldId === 'description' || 
                          fieldId === 'requirements' || fieldId === 'expense_breakdown' ||
                          fieldId === 'remarks' || fieldId === 'incident_description' ||
                          fieldId === 'individuals_involved' || fieldId === 'witnesses' ||
                          fieldId === 'action_taken' || fieldId === 'recommendations';
        
        fieldsHTML += `
            <div class="form-group">
                <label for="${fieldId}">${fieldLabel}</label>
                ${isTextarea ? 
                    `<textarea id="${fieldId}" name="${fieldId}" rows="3" placeholder="Enter ${fieldLabel.toLowerCase()}"></textarea>` :
                    `<input type="${fieldType}" id="${fieldId}" name="${fieldId}" placeholder="Enter ${fieldLabel.toLowerCase()}">`
                }
            </div>
        `;
    });
    
    container.innerHTML = fieldsHTML;
}

// Handle template form submission
templateForm.onsubmit = function(e) {
    e.preventDefault();
    
    const documentTitle = document.getElementById('documentTitle').value;
    const templateId = document.getElementById('templateSelect').value;
    
    if (!templateId) {
        alert('Please select a template');
        return;
    }
    
    // Collect all form data
    const formData = new FormData();
    formData.append('template_id', templateId);
    formData.append('title', documentTitle);
    
    // Add collaborated logo if selected
    const collaboratedLogo = document.getElementById('collaboratedLogo').value;
    if (collaboratedLogo) {
        formData.append('collaborated_logo', collaboratedLogo);
    }
    
    // Get all template fields
    const template = templates[templateId];
    Object.keys(template.fields).forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element) {
            formData.append(fieldId, element.value);
        }
    });
    
    // Show loading state
    const submitBtn = templateForm.querySelector('.btn-submit');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Generating...';
    submitBtn.disabled = true;
    
    // Send to server
    fetch('../php/generate_docx.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            // Get filename from Content-Disposition header or use default
            const filename = documentTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.docx';
            
            // Create blob and download
            return response.blob().then(blob => {
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
                
                closeUploadModal();
            });
        } else {
            throw new Error('Failed to generate document');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating document: ' + error.message);
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
};

// Handle regular form submission (existing)
uploadForm.onsubmit = function(e) {
    e.preventDefault();
    // In a real app, you'd send the form data via fetch or regular POST
    alert('Document uploaded successfully! (simulated)');
    closeUploadModal();
};

// ========== SEARCH & FILTER ==========
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