<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$userId = $_SESSION['user_id'];

// Get current user's org_code
$orgCode = null;
$stmt = $conn->prepare("SELECT org_code FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($orgCode);
$stmt->fetch();
$stmt->close();

// Fetch all members with same org_code — excluding the org account itself
$members = [];
if ($orgCode) {
    $stmt = $conn->prepare("
        SELECT user_id, full_name, email, phone, role, status, created_at
        FROM users
        WHERE org_code = ?
          AND user_id != ?
          AND role != 'org'
        ORDER BY
            FIELD(role, 'president','vice_president','secretary','treasurer','member','admin') ASC,
            full_name ASC
    ");
    $stmt->bind_param("si", $orgCode, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $members[] = $row;
    $stmt->close();
}

$total      = count($members);
$officers   = count(array_filter($members, fn($m) => !in_array(strtolower($m['role'] ?? ''), ['member',''])));
$regularMem = count(array_filter($members, fn($m) => strtolower($m['role'] ?? 'member') === 'member'));
$active     = count(array_filter($members, fn($m) => strtolower($m['status'] ?? 'active') === 'active'));

// Avatar colour palette (cycles by index)
$avatarColors = ['#2d6a4f','#1d4ed8','#7c3aed','#b45309','#0e7490','#be185d','#065f46'];

function positionClass(string $role): string {
    return match(strtolower(str_replace(' ', '_', $role))) {
        'president'       => 'pos-president',
        'vice_president'  => 'pos-vice-president',
        'secretary'       => 'pos-secretary',
        'treasurer'       => 'pos-treasurer',
        'member'          => 'pos-member',
        default           => 'pos-default',
    };
}
function positionLabel(string $role): string {
    return ucwords(str_replace('_', ' ', $role));
}
function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $i = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= strtoupper(substr(end($parts), 0, 1));
    return $i;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members – OrgHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <link rel="stylesheet" href="../css/members.css">
</head>
<body>

<?php include '../php/navbar.php'; ?>
<?php include '../php/topbar.php'; ?>

<div class="members-container">

    <!-- Page Header -->
    <div class="members-header">
        <div class="members-header-left">
            <div class="members-header-icon">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <h1>Members</h1>
                <p>Manage your organization's members</p>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn-export" onclick="exportPDF()">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
            <button class="btn-export" onclick="exportExcel()">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button class="btn-add" id="openAddModal">
                <i class="fas fa-plus"></i> Add Member
            </button>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="members-stats-bar">
        <div class="m-stat">
            <div class="m-stat-icon" style="background:#e3f2eb;color:#2d6a4f;">
                <i class="fas fa-users"></i>
            </div>
            <div class="m-stat-body">
                <span class="m-stat-num"><?= $total ?></span>
                <span class="m-stat-label">Total Members</span>
            </div>
        </div>
        <div class="m-stat-divider"></div>
        <div class="m-stat">
            <div class="m-stat-icon" style="background:#fef9c3;color:#ca8a04;">
                <i class="fas fa-star"></i>
            </div>
            <div class="m-stat-body">
                <span class="m-stat-num"><?= $officers ?></span>
                <span class="m-stat-label">Officers</span>
            </div>
        </div>
        <div class="m-stat-divider"></div>
        <div class="m-stat">
            <div class="m-stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                <i class="fas fa-user"></i>
            </div>
            <div class="m-stat-body">
                <span class="m-stat-num"><?= $regularMem ?></span>
                <span class="m-stat-label">Regular Members</span>
            </div>
        </div>
        <div class="m-stat-divider"></div>
        <div class="m-stat">
            <div class="m-stat-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="m-stat-body">
                <span class="m-stat-num"><?= $active ?></span>
                <span class="m-stat-label">Active</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="members-toolbar">
        <div class="members-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name or email…">
        </div>
        <div style="display:flex;gap:0.7rem;align-items:center;flex-wrap:wrap;">
            <select id="positionFilter" class="members-filter-select">
                <option value="">All Positions</option>
                <option value="president">President</option>
                <option value="vice_president">Vice President</option>
                <option value="secretary">Secretary</option>
                <option value="treasurer">Treasurer</option>
                <option value="member">Member</option>
            </select>
            <select id="statusFilter" class="members-filter-select">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <?php if (empty($members)): ?>
        <div class="members-empty">
            <div class="members-empty-icon"><i class="fas fa-users"></i></div>
            <h3>No members yet</h3>
            <p>Add your first member to get started.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="members-table" id="membersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th>Position</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $i => $m):
                    $name    = htmlspecialchars($m['full_name'] ?? '—');
                    $email   = htmlspecialchars($m['email'] ?? '');
                    $phone   = htmlspecialchars($m['phone'] ?? '—');
                    $role    = $m['role'] ?? 'member';
                    $status  = strtolower($m['status'] ?? 'active');
                    $joined  = !empty($m['created_at']) ? date('M d, Y', strtotime($m['created_at'])) : '—';
                    $color   = $avatarColors[$i % count($avatarColors)];
                    $init    = initials($m['full_name'] ?? 'U');
                    $posClass = positionClass($role);
                    $posLabel = positionLabel($role);
                ?>
                <tr data-id="<?= $m['user_id'] ?>"
                    data-name="<?= strtolower($m['full_name'] ?? '') ?>"
                    data-email="<?= strtolower($m['email'] ?? '') ?>"
                    data-role="<?= strtolower($role) ?>"
                    data-status="<?= $status ?>">
                    <td class="row-num"><?= $i + 1 ?></td>
                    <td>
                        <div class="member-name-cell">
                            <div class="member-avatar" style="background:<?= $color ?>;">
                                <?= $init ?>
                            </div>
                            <div>
                                <span class="member-name-text"><?= $name ?></span>
                                <span class="member-email-text"><?= $email ?></span>
                            </div>
                        </div>
                    </td>
                    <td><span class="position-badge <?= $posClass ?>"><?= $posLabel ?></span></td>
                    <td class="contact-cell">
                        <?php if ($phone && $phone !== '—'): ?>
                            <i class="fas fa-phone" style="color:#2d6a4f;margin-right:5px;font-size:0.75rem;"></i><?= $phone ?>
                        <?php else: ?>
                            <span style="color:#d1d5db;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-pill <?= $status === 'active' ? 'pill-active' : 'pill-inactive' ?>">
                            <span class="status-dot"></span>
                            <?= ucfirst($status) ?>
                        </span>
                    </td>
                    <td class="date-cell"><?= $joined ?></td>
                    <td class="actions-cell">
                        <button class="btn-action btn-edit" title="Edit member"
                            onclick="openEditModal(<?= $m['user_id'] ?>,'<?= addslashes($m['full_name'] ?? '') ?>','<?= addslashes($role) ?>','<?= addslashes($m['phone'] ?? '') ?>','<?= addslashes($m['email'] ?? '') ?>','<?= $status ?>')">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn-action btn-delete" title="Remove member"
                            onclick="openDeleteModal(<?= $m['user_id'] ?>,'<?= addslashes($m['full_name'] ?? '') ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span id="rowCount"><?= $total ?> member(s)</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- No results -->
    <div id="noResults" style="display:none;flex-direction:column;align-items:center;justify-content:center;padding:3rem;gap:0.6rem;color:#8aaa92;font-size:0.9rem;">
        <i class="fas fa-search" style="font-size:1.8rem;"></i>
        <p>No members match your search or filters.</p>
        <button onclick="clearFilters()" style="margin-top:0.4rem;background:#2d6a4f;color:white;border:none;padding:0.5rem 1.2rem;border-radius:40px;font-size:0.82rem;font-weight:600;cursor:pointer;font-family:inherit;">
            Clear Filters
        </button>
    </div>

</div><!-- /.members-container -->
</main>

<!-- ── Add / Edit Member Modal ───────────────────────────────── -->
<div id="memberModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" id="closeModal">&times;</button>
        <div class="modal-header">
            <h2><i class="fas fa-user-plus" id="modalIcon"></i> <span id="modalTitle">Add New Member</span></h2>
        </div>
        <div class="modal-body">
            <input type="hidden" id="memberId">
            <div class="form-group">
                <label for="fullName">Full Name <span>*</span></label>
                <input type="text" id="fullName" placeholder="Enter full name">
            </div>
            <div class="form-group">
                <label for="position">Position</label>
                <select id="position">
                    <option value="president">President</option>
                    <option value="vice_president">Vice President</option>
                    <option value="secretary">Secretary</option>
                    <option value="treasurer">Treasurer</option>
                    <option value="member" selected>Member</option>
                </select>
            </div>
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" placeholder="+63 XXX XXX XXXX">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="text" id="email" placeholder="email@example.com">
            </div>
            <div class="form-group" id="statusGroup" style="display:none;">
                <label for="memberStatus">Status</label>
                <select id="memberStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelModal">Cancel</button>
            <button class="btn-submit" id="submitBtn">Save Member</button>
        </div>
    </div>
</div>

<!-- ── Delete Confirm Modal ──────────────────────────────────── -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <button class="close-modal" id="closeDeleteModal">&times;</button>
        <div class="modal-body" style="padding:2rem 1.8rem 1rem;">
            <div class="delete-modal-icon"><i class="fas fa-trash"></i></div>
            <div class="delete-modal-text">
                <h3>Remove Member?</h3>
                <p>Are you sure you want to remove <strong id="deleteTargetName"></strong>? This action cannot be undone.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelDeleteModal">Cancel</button>
            <button class="btn-delete-confirm" id="confirmDeleteBtn">Yes, Remove</button>
        </div>
    </div>
</div>

<script src="../js/script.js"></script>
<script src="../js/navbar.js"></script>
<script src="../js/notifications.js"></script>
<script>
/* ── Modal helpers ─────────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Add modal
document.getElementById('openAddModal').onclick = function() {
    document.getElementById('modalTitle').textContent = 'Add New Member';
    document.getElementById('modalIcon').className = 'fas fa-user-plus';
    document.getElementById('memberId').value  = '';
    document.getElementById('fullName').value  = '';
    document.getElementById('position').value  = 'member';
    document.getElementById('contact').value   = '';
    document.getElementById('email').value     = '';
    document.getElementById('statusGroup').style.display = 'none';
    openModal('memberModal');
};
document.getElementById('closeModal').onclick  = () => closeModal('memberModal');
document.getElementById('cancelModal').onclick = () => closeModal('memberModal');
document.getElementById('memberModal').onclick = function(e) { if(e.target===this) closeModal('memberModal'); };

// Edit modal
window.openEditModal = function(id, name, role, phone, email, status) {
    document.getElementById('modalTitle').textContent = 'Edit Member';
    document.getElementById('modalIcon').className = 'fas fa-user-edit';
    document.getElementById('memberId').value  = id;
    document.getElementById('fullName').value  = name;
    document.getElementById('position').value  = role;
    document.getElementById('contact').value   = phone;
    document.getElementById('email').value     = email;
    document.getElementById('memberStatus').value = status;
    document.getElementById('statusGroup').style.display = 'block';
    openModal('memberModal');
};

// Submit — no database, pure DOM
document.getElementById('submitBtn').onclick = function() {
    const name   = document.getElementById('fullName').value.trim();
    if (!name) { document.getElementById('fullName').focus(); return; }

    const btn      = this;
    const memberId = document.getElementById('memberId').value || '0';
    const role     = document.getElementById('position').value || 'member';
    const phone    = document.getElementById('contact').value.trim();
    const email    = document.getElementById('email').value.trim();
    const status   = document.getElementById('memberStatus')?.value || 'active';

    const posLabel = role.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
    const posClass = 'pos-' + role.toLowerCase().replace(/\s+/g,'_');
    const pillClass = status === 'active' ? 'pill-active' : 'pill-inactive';

    if (memberId !== '0') {
        // ── EDIT: update existing row in DOM ──
        const row = document.querySelector(`tr[data-id="${memberId}"]`);
        if (row) {
            row.querySelector('.member-name-text').textContent  = name;
            row.querySelector('.member-email-text').textContent = email || '';
            row.dataset.name   = name.toLowerCase();
            row.dataset.email  = email.toLowerCase();
            row.dataset.role   = role.toLowerCase();
            row.dataset.status = status.toLowerCase();

            const posBadge = row.querySelector('.position-badge');
            if (posBadge) { posBadge.textContent = posLabel; posBadge.className = 'position-badge ' + posClass; }

            const statusPill = row.querySelector('.status-pill');
            if (statusPill) {
                statusPill.className = 'status-pill ' + pillClass;
                statusPill.innerHTML = `<span class="status-dot"></span>${status.charAt(0).toUpperCase()+status.slice(1)}`;
            }

            const contactCell = row.querySelector('.contact-cell');
            if (contactCell) {
                contactCell.innerHTML = phone
                    ? `<i class="fas fa-phone" style="color:#2d6a4f;margin-right:5px;font-size:0.75rem;"></i>${phone}`
                    : `<span style="color:#d1d5db;">—</span>`;
            }

            // Update edit button onclick with new values
            row.querySelector('.btn-edit').setAttribute('onclick',
                `openEditModal(${memberId},'${name.replace(/'/g,"\\'")}','${role}','${phone.replace(/'/g,"\\'")}','${email.replace(/'/g,"\\'")}','${status}')`);
        }

    } else {
        // ── ADD: build and insert a new row ──
        const avatarColors = ['#2d6a4f','#1d4ed8','#7c3aed','#b45309','#0e7490','#be185d','#065f46'];
        const tbody  = document.querySelector('#membersTable tbody');
        const rowCount = tbody.querySelectorAll('tr').length;
        const tempId = 'new_' + Date.now();
        const color  = avatarColors[rowCount % avatarColors.length];
        const initials = name.split(' ').map(w=>w[0]||'').join('').toUpperCase().slice(0,2) || 'U';
        const today  = new Date().toLocaleDateString('en-US', {month:'short', day:'2-digit', year:'numeric'});

        const tr = document.createElement('tr');
        tr.dataset.id     = tempId;
        tr.dataset.name   = name.toLowerCase();
        tr.dataset.email  = email.toLowerCase();
        tr.dataset.role   = role.toLowerCase();
        tr.dataset.status = status.toLowerCase();

        tr.innerHTML = `
            <td class="row-num">${rowCount + 1}</td>
            <td>
                <div class="member-name-cell">
                    <div class="member-avatar" style="background:${color};">${initials}</div>
                    <div>
                        <span class="member-name-text">${name}</span>
                        <span class="member-email-text">${email}</span>
                    </div>
                </div>
            </td>
            <td><span class="position-badge ${posClass}">${posLabel}</span></td>
            <td class="contact-cell">
                ${phone ? `<i class="fas fa-phone" style="color:#2d6a4f;margin-right:5px;font-size:0.75rem;"></i>${phone}` : '<span style="color:#d1d5db;">—</span>'}
            </td>
            <td>
                <span class="status-pill ${pillClass}">
                    <span class="status-dot"></span>
                    ${status.charAt(0).toUpperCase()+status.slice(1)}
                </span>
            </td>
            <td class="date-cell">${today}</td>
            <td class="actions-cell">
                <button class="btn-action btn-edit" title="Edit member"
                    onclick="openEditModal('${tempId}','${name.replace(/'/g,"\\'")}','${role}','${phone.replace(/'/g,"\\'")}','${email.replace(/'/g,"\\'")}','${status}')">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="btn-action btn-delete" title="Remove member"
                    onclick="openDeleteModal('${tempId}','${name.replace(/'/g,"\\'")}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>`;

        tbody.appendChild(tr);

        // Update row count display
        const rowCountEl = document.getElementById('rowCount');
        if (rowCountEl) rowCountEl.textContent = (rowCount + 1) + ' member(s)';

        // Hide no-results if visible
        const noResults = document.getElementById('noResults');
        if (noResults) noResults.style.display = 'none';
    }

    closeModal('memberModal');
    applyFilters();
};

// Delete modal — wired to delete_member.php
let _deleteId = null;
window.openDeleteModal = function(id, name) {
    _deleteId = id;
    document.getElementById('deleteTargetName').textContent = name;
    openModal('deleteModal');
};
document.getElementById('closeDeleteModal').onclick  = () => closeModal('deleteModal');
document.getElementById('cancelDeleteModal').onclick = () => closeModal('deleteModal');
document.getElementById('deleteModal').onclick = function(e) { if(e.target===this) closeModal('deleteModal'); };
document.getElementById('confirmDeleteBtn').onclick = function() {
    if (!_deleteId) return;
    const row = document.querySelector(`tr[data-id="${_deleteId}"]`);
    if (row) row.remove();
    closeModal('deleteModal');
    applyFilters();
};

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeModal('memberModal'); closeModal('deleteModal'); }
});

/* ── Search & filter ───────────────────────────────────────── */
const rows      = document.querySelectorAll('#membersTable tbody tr');
const rowCount  = document.getElementById('rowCount');
const noResults = document.getElementById('noResults');

function applyFilters() {
    const q  = document.getElementById('searchInput').value.toLowerCase().trim();
    const pos = document.getElementById('positionFilter').value.toLowerCase();
    const st  = document.getElementById('statusFilter').value.toLowerCase();
    let visible = 0;
    rows.forEach(row => {
        const nameMatch   = !q   || row.dataset.name.includes(q) || row.dataset.email.includes(q);
        const posMatch    = !pos || row.dataset.role === pos;
        const statusMatch = !st  || row.dataset.status === st;
        const show = nameMatch && posMatch && statusMatch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    if (rowCount)  rowCount.textContent = visible + ' member(s)';
    if (noResults) noResults.style.display = (visible === 0 && rows.length > 0) ? 'flex' : 'none';
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('positionFilter').value = '';
    document.getElementById('statusFilter').value = '';
    applyFilters();
}

document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('positionFilter').addEventListener('change', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);

/* ── Export stubs ──────────────────────────────────────────── */
function exportPDF()   { alert('PDF export — wire to export handler in production.'); }
function exportExcel() { alert('Excel export — wire to export handler in production.'); }
</script>
</body>
</html>