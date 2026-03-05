<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - OrgHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/members.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/notifications.css">
</head>
<body>
    <?php include '../php/navbar.php'; ?>
    <?php include '../php/topbar.php'; ?>

    <main class="main-content">
        <div class="members-container">

            <!-- Header -->
            <div class="members-header">
                <h1><i class="fas fa-users"></i> Members Management</h1>
                <div class="header-actions">
                    <button class="btn-add" id="openAddModal"><i class="fas fa-plus"></i> Add Member</button>
                    <button class="btn-export btn-pdf" id="exportPDF"><i class="fas fa-file-pdf"></i> PDF</button>
                    <button class="btn-export btn-excel" id="exportExcel"><i class="fas fa-file-excel"></i> Excel</button>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, email, or position...">
                </div>
                <div class="filter-wrapper">
                    <select id="positionFilter">
                        <option value="">All Positions</option>
                        <option value="President">President</option>
                        <option value="Vice President">Vice President</option>
                        <option value="Secretary">Secretary</option>
                        <option value="Treasurer">Treasurer</option>
                        <option value="Member">Member</option>
                    </select>
                </div>
            </div>

            <!-- Scrollable Table -->
            <div class="table-responsive">
                <!-- Fixed header — never scrolls -->
                <table class="members-table" style="display:table;width:100%;table-layout:fixed;">
                    <thead>
                        <tr>
                            <th data-col="0" class="sortable">Full Name <i class="fas fa-sort sort-icon"></i></th>
                            <th data-col="1" class="sortable">Position <i class="fas fa-sort sort-icon"></i></th>
                            <th data-col="2">Contact Number</th>
                            <th data-col="3" class="sortable">Email <i class="fas fa-sort sort-icon"></i></th>
                            <th data-col="4">Course &amp; Year</th>
                            <th data-col="5" class="sortable">Date Joined <i class="fas fa-sort sort-icon"></i></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
                <!-- Scrollable body -->
                <div class="table-scroll-body">
                    <table class="members-table" id="membersTable" style="display:table;width:100%;table-layout:fixed;">
                        <tbody>
                            <tr data-course="BS Computer Science, 4A" data-joined="2023-06-01" data-photo="">
                                <td><div class="member-name-cell"><span class="avatar-initials" style="background:#3b82f6">JS</span><span>John Michael Santos</span></div></td>
                                <td><span class="position-badge pos-president">President</span></td>
                                <td>+63 912 345 6789</td>
                                <td>john.santos@example.com</td>
                                <td>BS Computer Science, 4A</td>
                                <td>Jun 01, 2023</td>
                                <td class="actions">
                                    <button class="btn-action btn-view-member" onclick="viewMember(this)" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editMember(this)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteMember(this)" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr data-course="BS Accountancy, 3B" data-joined="2023-06-01" data-photo="">
                                <td><div class="member-name-cell"><span class="avatar-initials" style="background:#8b5cf6">MC</span><span>Maria Consuelo Reyes</span></div></td>
                                <td><span class="position-badge pos-secretary">Secretary</span></td>
                                <td>+63 923 456 7890</td>
                                <td>maria.reyes@example.com</td>
                                <td>BS Accountancy, 3B</td>
                                <td>Jun 01, 2023</td>
                                <td class="actions">
                                    <button class="btn-action btn-view-member" onclick="viewMember(this)" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editMember(this)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteMember(this)" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr data-course="BS Business Admin, 2C" data-joined="2023-06-15" data-photo="">
                                <td><div class="member-name-cell"><span class="avatar-initials" style="background:#10b981">RL</span><span>Robert Lim</span></div></td>
                                <td><span class="position-badge pos-treasurer">Treasurer</span></td>
                                <td>+63 934 567 8901</td>
                                <td>robert.lim@example.com</td>
                                <td>BS Business Admin, 2C</td>
                                <td>Jun 15, 2023</td>
                                <td class="actions">
                                    <button class="btn-action btn-view-member" onclick="viewMember(this)" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editMember(this)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteMember(this)" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr data-course="BS Education, 1A" data-joined="2024-01-10" data-photo="">
                                <td><div class="member-name-cell"><span class="avatar-initials" style="background:#f59e0b">AV</span><span>Anna Marie Villanueva</span></div></td>
                                <td><span class="position-badge pos-member">Member</span></td>
                                <td>+63 945 678 9012</td>
                                <td>anna.villanueva@example.com</td>
                                <td>BS Education, 1A</td>
                                <td>Jan 10, 2024</td>
                                <td class="actions">
                                    <button class="btn-action btn-view-member" onclick="viewMember(this)" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editMember(this)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteMember(this)" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr data-course="BS Engineering, 4B" data-joined="2023-06-01" data-photo="">
                                <td><div class="member-name-cell"><span class="avatar-initials" style="background:#ef4444">CM</span><span>Carlos Mendoza</span></div></td>
                                <td><span class="position-badge pos-vp">Vice President</span></td>
                                <td>+63 956 789 0123</td>
                                <td>carlos.mendoza@example.com</td>
                                <td>BS Engineering, 4B</td>
                                <td>Jun 01, 2023</td>
                                <td class="actions">
                                    <button class="btn-action btn-view-member" onclick="viewMember(this)" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editMember(this)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteMember(this)" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <!-- Empty state -->
                    <div class="empty-state" id="emptyState" style="display:none;">
                        <i class="fas fa-user-slash"></i>
                        <p>No members found</p>
                        <small>Try adjusting your search or filter</small>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ── Add/Edit Member Modal ────────────────────────────────────────────── -->
    <div id="memberModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <h2 id="modalTitle"><i class="fas fa-user-plus"></i> Add New Member</h2>
            <form id="memberForm">
                <input type="hidden" id="memberId" value="">

                <!-- Photo upload -->
                <div class="photo-upload-group">
                    <div class="photo-preview" id="photoPreview">
                        <span id="photoInitials">?</span>
                        <img id="photoImg" src="" alt="" style="display:none;">
                    </div>
                    <div class="photo-upload-info">
                        <label class="btn-photo-upload" for="photoUpload">
                            <i class="fas fa-camera"></i> Upload Photo
                        </label>
                        <input type="file" id="photoUpload" accept="image/*" style="display:none;">
                        <small>JPG, PNG up to 2MB</small>
                    </div>
                </div>

                <!-- Row 1: Name + Position -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullName">Full Name <span>*</span></label>
                        <input type="text" id="fullName" placeholder="e.g. Juan Dela Cruz" required>
                    </div>
                    <div class="form-group">
                        <label for="position">Position</label>
                        <select id="position">
                            <option value="President">President</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Treasurer">Treasurer</option>
                            <option value="Member">Member</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Contact + Email -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <input type="tel" id="contact" placeholder="+63 XXX XXX XXXX">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" placeholder="email@example.com">
                    </div>
                </div>

                <!-- Row 3: Course & Year + Date Joined -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="courseYear">Course &amp; Year</label>
                        <input type="text" id="courseYear" placeholder="e.g. BS Computer Science, 3A">
                    </div>
                    <div class="form-group">
                        <label for="dateJoined">Date Joined</label>
                        <input type="date" id="dateJoined">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Save Member</button>
                    <button type="button" class="btn-cancel" id="cancelModal"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── View Profile Modal ───────────────────────────────────────────────── -->
    <div id="viewModal" class="modal">
        <div class="modal-content modal-profile">
            <span class="close-modal" id="closeViewModal">&times;</span>
            <div class="profile-header">
                <div class="profile-avatar" id="viewAvatar"></div>
                <div>
                    <h2 id="viewName"></h2>
                    <span class="position-badge" id="viewPositionBadge"></span>
                </div>
            </div>
            <div class="profile-details">
                <div class="profile-detail-item">
                    <i class="fas fa-envelope"></i>
                    <div><label>Email</label><span id="viewEmail"></span></div>
                </div>
                <div class="profile-detail-item">
                    <i class="fas fa-phone"></i>
                    <div><label>Contact</label><span id="viewContact"></span></div>
                </div>
                <div class="profile-detail-item">
                    <i class="fas fa-graduation-cap"></i>
                    <div><label>Course &amp; Year</label><span id="viewCourse"></span></div>
                </div>
                <div class="profile-detail-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div><label>Date Joined</label><span id="viewJoined"></span></div>
                </div>
            </div>
            <div class="profile-actions">
                <button class="btn-submit" id="viewToEdit"><i class="fas fa-edit"></i> Edit Member</button>
            </div>
        </div>
    </div>

    <!-- ── Inline Delete Confirm (injected dynamically) ─────────────────────── -->
    <div id="deleteConfirmOverlay" class="delete-overlay" style="display:none;">
        <div class="delete-confirm-box">
            <i class="fas fa-exclamation-triangle"></i>
            <p>Delete this member?</p>
            <small id="deleteConfirmName"></small>
            <div class="delete-confirm-actions">
                <button class="btn-confirm-delete" id="confirmDeleteBtn"><i class="fas fa-trash"></i> Delete</button>
                <button class="btn-cancel-delete" id="cancelDeleteBtn">Cancel</button>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <!-- jsPDF + AutoTable for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <!-- SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════════
    const AVATAR_COLORS = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899','#2d6a4f'];

    function getInitials(name) {
        return name.trim().split(/\s+/).map(w => w[0]).slice(0,2).join('').toUpperCase();
    }
    function pickColor(name) {
        let h = 0;
        for (let i = 0; i < name.length; i++) h = (h * 31 + name.charCodeAt(i)) & 0xffffffff;
        return AVATAR_COLORS[Math.abs(h) % AVATAR_COLORS.length];
    }
    function formatDate(iso) {
        if (!iso) return '—';
        const d = new Date(iso + 'T00:00:00');
        return d.toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' });
    }

    function showToast(msg, type = 'success') {
        const colors = { success:'#10b981', error:'#ef4444', info:'#3b82f6', warning:'#f59e0b' };
        const icons  = { success:'fa-check-circle', error:'fa-times-circle', info:'fa-info-circle', warning:'fa-exclamation-triangle' };
        const old = document.getElementById('members-toast');
        if (old) old.remove();
        const t = document.createElement('div');
        t.id = 'members-toast';
        t.style.cssText = `position:fixed;top:1.5rem;right:1.5rem;z-index:99999;
            padding:.85rem 1.4rem;border-radius:14px;font-size:.93rem;font-weight:600;
            box-shadow:0 4px 20px rgba(0,0,0,.18);color:#fff;max-width:360px;
            display:flex;align-items:center;gap:.6rem;
            background:${colors[type]};animation:toastIn .3s ease;`;
        t.innerHTML = `<i class="fas ${icons[type]}"></i><span>${msg}</span>`;
        if (!document.getElementById('toast-style')) {
            const s = document.createElement('style');
            s.id = 'toast-style';
            s.textContent = '@keyframes toastIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}';
            document.head.appendChild(s);
        }
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  MODAL HELPERS
    // ══════════════════════════════════════════════════════════════════════════
    function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
    function closeModalId(id){ document.getElementById(id).style.display = 'none'; }

    document.getElementById('closeModal').onclick    = () => closeModalId('memberModal');
    document.getElementById('cancelModal').onclick   = () => closeModalId('memberModal');
    document.getElementById('closeViewModal').onclick = () => closeModalId('viewModal');
    window.addEventListener('click', e => {
        if (e.target.id === 'memberModal') closeModalId('memberModal');
        if (e.target.id === 'viewModal')   closeModalId('viewModal');
    });

    // ══════════════════════════════════════════════════════════════════════════
    //  PHOTO UPLOAD PREVIEW
    // ══════════════════════════════════════════════════════════════════════════
    document.getElementById('photoUpload').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('photoImg');
            img.src = e.target.result;
            img.style.display = 'block';
            document.getElementById('photoInitials').style.display = 'none';
        };
        reader.readAsDataURL(file);
    });

    function resetPhotoPreview(name) {
        const img = document.getElementById('photoImg');
        img.style.display = 'none';
        img.src = '';
        document.getElementById('photoUpload').value = '';
        const initEl = document.getElementById('photoInitials');
        initEl.style.display = '';
        initEl.textContent = name ? getInitials(name) : '?';
        document.getElementById('photoPreview').style.background = name ? pickColor(name) : '#ccc';
    }

    document.getElementById('fullName').addEventListener('input', function () {
        const img = document.getElementById('photoImg');
        if (img.style.display === 'none') resetPhotoPreview(this.value);
    });

    // ══════════════════════════════════════════════════════════════════════════
    //  ADD MEMBER
    // ══════════════════════════════════════════════════════════════════════════
    document.getElementById('openAddModal').onclick = function () {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add New Member';
        document.getElementById('memberForm').reset();
        document.getElementById('memberId').value = '';
        resetPhotoPreview('');
        openModal('memberModal');
    };

    // ══════════════════════════════════════════════════════════════════════════
    //  EDIT MEMBER
    // ══════════════════════════════════════════════════════════════════════════
    window.editMember = function (button) {
        const row    = button.closest('tr');
        const cells  = row.querySelectorAll('td');
        const name   = cells[0].querySelector('span:last-child').textContent.trim();
        const pos    = cells[1].textContent.trim();
        const cont   = cells[2].textContent.trim();
        const mail   = cells[3].textContent.trim();
        const course = row.dataset.course || '';
        const joined = row.dataset.joined || '';

        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Member';
        document.getElementById('fullName').value   = name;
        document.getElementById('position').value   = pos;
        document.getElementById('contact').value    = cont;
        document.getElementById('email').value      = mail;
        document.getElementById('courseYear').value = course;
        document.getElementById('dateJoined').value = joined;
        document.getElementById('memberId').value   = name;
        resetPhotoPreview(name);
        openModal('memberModal');
    };

    // ══════════════════════════════════════════════════════════════════════════
    //  FORM SUBMIT (save / update row)
    // ══════════════════════════════════════════════════════════════════════════
    document.getElementById('memberForm').onsubmit = function (e) {
        e.preventDefault();
        const name   = document.getElementById('fullName').value.trim();
        const pos    = document.getElementById('position').value;
        const cont   = document.getElementById('contact').value.trim();
        const mail   = document.getElementById('email').value.trim();
        const course = document.getElementById('courseYear').value.trim();
        const joined = document.getElementById('dateJoined').value;
        const isEdit = document.getElementById('memberId').value !== '';
        const photoSrc = document.getElementById('photoImg').src;
        const hasPhoto = document.getElementById('photoImg').style.display !== 'none';

        const tbody  = document.querySelector('#membersTable tbody');
        const existing = isEdit
            ? [...tbody.querySelectorAll('tr')].find(r =>
                r.querySelector('span:last-child')?.textContent.trim() === document.getElementById('memberId').value)
            : null;

        const avatarHtml = hasPhoto
            ? `<img src="${photoSrc}" class="avatar-photo" alt="${name}">`
            : `<span class="avatar-initials" style="background:${pickColor(name)}">${getInitials(name)}</span>`;

        const posBadgeClass = {
            'President':'pos-president','Vice President':'pos-vp',
            'Secretary':'pos-secretary','Treasurer':'pos-treasurer','Member':'pos-member'
        }[pos] || 'pos-member';

        const rowHtml = `
            <td><div class="member-name-cell">${avatarHtml}<span>${name}</span></div></td>
            <td><span class="position-badge ${posBadgeClass}">${pos}</span></td>
            <td>${cont || '—'}</td>
            <td>${mail || '—'}</td>
            <td>${course || '—'}</td>
            <td>${formatDate(joined)}</td>
            <td class="actions">
                <button class="btn-action btn-view-member" onclick="viewMember(this)" title="View"><i class="fas fa-eye"></i></button>
                <button class="btn-action btn-edit" onclick="editMember(this)" title="Edit"><i class="fas fa-edit"></i></button>
                <button class="btn-action btn-delete" onclick="deleteMember(this)" title="Delete"><i class="fas fa-trash"></i></button>
            </td>`;

        if (existing) {
            existing.innerHTML = rowHtml;
            existing.dataset.course = course;
            existing.dataset.joined = joined;
            existing.dataset.photo  = hasPhoto ? photoSrc : '';
        } else {
            const tr = document.createElement('tr');
            tr.dataset.course = course;
            tr.dataset.joined = joined;
            tr.dataset.photo  = hasPhoto ? photoSrc : '';
            tr.innerHTML = rowHtml;
            tbody.insertBefore(tr, tbody.firstChild);
        }

        closeModalId('memberModal');
        checkEmptyState();
        showToast(isEdit ? 'Member updated successfully!' : 'Member added successfully!', 'success');
    };

    // ══════════════════════════════════════════════════════════════════════════
    //  VIEW PROFILE
    // ══════════════════════════════════════════════════════════════════════════
    window.viewMember = function (button) {
        const row    = button.closest('tr');
        const cells  = row.querySelectorAll('td');
        const name   = cells[0].querySelector('span:last-child').textContent.trim();
        const pos    = cells[1].textContent.trim();
        const cont   = cells[2].textContent.trim();
        const mail   = cells[3].textContent.trim();
        const course = row.dataset.course || '—';
        const joined = row.dataset.joined || '';
        const photoSrc = row.dataset.photo || '';

        const avatarEl = document.getElementById('viewAvatar');
        if (photoSrc) {
            avatarEl.innerHTML = `<img src="${photoSrc}" class="avatar-photo-lg" alt="${name}">`;
        } else {
            avatarEl.textContent = getInitials(name);
            avatarEl.style.background = pickColor(name);
        }

        document.getElementById('viewName').textContent    = name;
        const pb = document.getElementById('viewPositionBadge');
        pb.textContent = pos;
        pb.className = 'position-badge ' + ({
            'President':'pos-president','Vice President':'pos-vp',
            'Secretary':'pos-secretary','Treasurer':'pos-treasurer','Member':'pos-member'
        }[pos] || 'pos-member');
        document.getElementById('viewEmail').textContent   = mail || '—';
        document.getElementById('viewContact').textContent = cont || '—';
        document.getElementById('viewCourse').textContent  = course;
        document.getElementById('viewJoined').textContent  = formatDate(joined);

        // "Edit" button inside profile modal
        document.getElementById('viewToEdit').onclick = function () {
            closeModalId('viewModal');
            editMember(button);
        };
        openModal('viewModal');
    };

    // ══════════════════════════════════════════════════════════════════════════
    //  DELETE with inline confirm overlay
    // ══════════════════════════════════════════════════════════════════════════
    let _rowToDelete = null;

    window.deleteMember = function (button) {
        _rowToDelete = button.closest('tr');
        const name = _rowToDelete.querySelector('span:last-child')?.textContent.trim() || 'this member';
        document.getElementById('deleteConfirmName').textContent = name;
        document.getElementById('deleteConfirmOverlay').style.display = 'flex';
    };

    document.getElementById('confirmDeleteBtn').onclick = function () {
        if (_rowToDelete) {
            const name = _rowToDelete.querySelector('span:last-child')?.textContent.trim() || 'Member';
            _rowToDelete.remove();
            _rowToDelete = null;
            checkEmptyState();
            showToast(`${name} has been removed.`, 'error');
        }
        document.getElementById('deleteConfirmOverlay').style.display = 'none';
    };

    document.getElementById('cancelDeleteBtn').onclick = function () {
        _rowToDelete = null;
        document.getElementById('deleteConfirmOverlay').style.display = 'none';
    };

    // ══════════════════════════════════════════════════════════════════════════
    //  SEARCH & FILTER
    // ══════════════════════════════════════════════════════════════════════════
    const searchInput    = document.getElementById('searchInput');
    const positionFilter = document.getElementById('positionFilter');

    function filterTable() {
        const term = searchInput.value.toLowerCase();
        const pos  = positionFilter.value.toLowerCase();
        let visible = 0;

        document.querySelectorAll('#membersTable tbody tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            const name  = cells[0]?.textContent.toLowerCase() || '';
            const posi  = cells[1]?.textContent.toLowerCase() || '';
            const mail  = cells[3]?.textContent.toLowerCase() || '';
            const ok = (!term || name.includes(term) || posi.includes(term) || mail.includes(term))
                    && (!pos  || posi.includes(pos));
            row.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });

        document.getElementById('emptyState').style.display = visible === 0 ? 'block' : 'none';
    }

    function checkEmptyState() {
        const rows = document.querySelectorAll('#membersTable tbody tr');
        document.getElementById('emptyState').style.display = rows.length === 0 ? 'block' : 'none';
    }

    searchInput.addEventListener('input', filterTable);
    positionFilter.addEventListener('change', filterTable);

    // ══════════════════════════════════════════════════════════════════════════
    //  SORTABLE COLUMNS
    // ══════════════════════════════════════════════════════════════════════════
    const sortState = {};

    document.querySelectorAll('th.sortable').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            const col = parseInt(this.dataset.col);
            sortState[col] = sortState[col] === 'asc' ? 'desc' : 'asc';
            const dir = sortState[col];

            // Reset all icons
            document.querySelectorAll('th.sortable .sort-icon').forEach(i => {
                i.className = 'fas fa-sort sort-icon';
            });
            this.querySelector('.sort-icon').className =
                `fas fa-sort-${dir === 'asc' ? 'up' : 'down'} sort-icon`;

            const tbody = document.querySelector('#membersTable tbody');
            const rows  = [...tbody.querySelectorAll('tr')];
            rows.sort((a, b) => {
                const aT = a.querySelectorAll('td')[col]?.textContent.trim().toLowerCase() || '';
                const bT = b.querySelectorAll('td')[col]?.textContent.trim().toLowerCase() || '';
                return dir === 'asc' ? aT.localeCompare(bT) : bT.localeCompare(aT);
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });

    // ══════════════════════════════════════════════════════════════════════════
    //  PDF EXPORT  (jsPDF + AutoTable)
    // ══════════════════════════════════════════════════════════════════════════
    document.getElementById('exportPDF').addEventListener('click', function () {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape' });

        doc.setFontSize(16);
        doc.setTextColor(45, 106, 79);
        doc.text('Members List', 14, 18);
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text('Exported on ' + new Date().toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' }), 14, 25);

        const rows = [...document.querySelectorAll('#membersTable tbody tr')]
            .filter(r => r.style.display !== 'none')
            .map(r => {
                const c = r.querySelectorAll('td');
                return [
                    c[0].querySelector('span:last-child')?.textContent.trim() || '',
                    c[1].textContent.trim(),
                    c[2].textContent.trim(),
                    c[3].textContent.trim(),
                    r.dataset.course || '—',
                    formatDate(r.dataset.joined),
                ];
            });

        doc.autoTable({
            head: [['Full Name','Position','Contact','Email','Course & Year','Date Joined']],
            body: rows,
            startY: 30,
            headStyles: { fillColor: [45, 106, 79], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [240, 250, 245] },
            styles: { fontSize: 9, cellPadding: 4 },
        });

        doc.save('members-list.pdf');
        showToast('PDF exported successfully!', 'success');
    });

    // ══════════════════════════════════════════════════════════════════════════
    //  EXCEL EXPORT  (SheetJS)
    // ══════════════════════════════════════════════════════════════════════════
    document.getElementById('exportExcel').addEventListener('click', function () {
        const rows = [...document.querySelectorAll('#membersTable tbody tr')]
            .filter(r => r.style.display !== 'none')
            .map(r => {
                const c = r.querySelectorAll('td');
                return {
                    'Full Name':     c[0].querySelector('span:last-child')?.textContent.trim() || '',
                    'Position':      c[1].textContent.trim(),
                    'Contact':       c[2].textContent.trim(),
                    'Email':         c[3].textContent.trim(),
                    'Course & Year': r.dataset.course || '',
                    'Date Joined':   formatDate(r.dataset.joined),
                };
            });

        const ws = XLSX.utils.json_to_sheet(rows);
        ws['!cols'] = [30,18,20,30,25,15].map(w => ({ wch: w }));
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Members');
        XLSX.writeFile(wb, 'members-list.xlsx');
        showToast('Excel file exported successfully!', 'success');
    });
    </script>
    <script src="../js/notifications.js"></script>
</body>
</html>