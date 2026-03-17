<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/db_connection.php';

$orgId = (int)$_SESSION['user_id'];

// ── Fetch members from org_members table ─────────────────────────────────
$members = [];
$res = mysqli_prepare($conn,
    "SELECT member_id, full_name, email, phone, position, program, status, created_at
     FROM org_members WHERE org_id = ?
     ORDER BY FIELD(position,'president','vice_president','secretary','treasurer',
                    'auditor','pio','representative','adviser','member') ASC,
              full_name ASC");
mysqli_stmt_bind_param($res, 'i', $orgId);
mysqli_stmt_execute($res);
$result = mysqli_stmt_get_result($res);
while ($row = mysqli_fetch_assoc($result)) $members[] = $row;
mysqli_stmt_close($res);

// ── Stats ─────────────────────────────────────────────────────────────────
$total      = count($members);
$officers   = count(array_filter($members, fn($m) => $m['position'] !== 'member'));
$regularMem = count(array_filter($members, fn($m) => $m['position'] === 'member'));
$active     = count(array_filter($members, fn($m) => $m['status'] === 'active'));

$avatarColors = ['#2d6a4f','#1d4ed8','#7c3aed','#b45309','#0e7490','#be185d','#065f46'];

function positionClass(string $p): string {
    return 'pos-' . str_replace('_','-', strtolower($p));
}
function positionLabel(string $p): string {
    return ucwords(str_replace('_',' ',$p));
}
function initials(string $name): string {
    $parts = explode(' ', trim($name));
    $i = strtoupper(substr($parts[0],0,1));
    if (count($parts) > 1) $i .= strtoupper(substr(end($parts),0,1));
    return $i ?: 'M';
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

<?php include 'navbar.php'; ?>
<main class="main-content">
<?php include 'topbar.php'; ?>

<div class="members-container">

    <!-- Page Header -->
    <div class="members-header">
        <div class="members-header-left">
            <div class="members-header-icon"><i class="fas fa-users"></i></div>
            <div>
                <h1>Members</h1>
                <p>Manage your organization's members</p>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn-add" id="openAddModal">
                <i class="fas fa-plus"></i> Add Member
            </button>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="members-stats-bar">
        <div class="m-stat">
            <div class="m-stat-icon" style="background:#e3f2eb;color:#2d6a4f;"><i class="fas fa-users"></i></div>
            <div class="m-stat-body">
                <span class="m-stat-num" id="statTotal"><?= $total ?></span>
                <span class="m-stat-label">Total Members</span>
            </div>
        </div>
        <div class="m-stat-divider"></div>
        <div class="m-stat">
            <div class="m-stat-icon" style="background:#fef9c3;color:#ca8a04;"><i class="fas fa-star"></i></div>
            <div class="m-stat-body">
                <span class="m-stat-num" id="statOfficers"><?= $officers ?></span>
                <span class="m-stat-label">Officers</span>
            </div>
        </div>
        <div class="m-stat-divider"></div>
        <div class="m-stat">
            <div class="m-stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="fas fa-user"></i></div>
            <div class="m-stat-body">
                <span class="m-stat-num" id="statRegular"><?= $regularMem ?></span>
                <span class="m-stat-label">Regular Members</span>
            </div>
        </div>
        <div class="m-stat-divider"></div>
        <div class="m-stat">
            <div class="m-stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
            <div class="m-stat-body">
                <span class="m-stat-num" id="statActive"><?= $active ?></span>
                <span class="m-stat-label">Active</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="members-toolbar">
        <div class="members-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name, email or program…">
        </div>
        <div style="display:flex;gap:0.7rem;align-items:center;flex-wrap:wrap;">
            <select id="positionFilter" class="members-filter-select">
                <option value="">All Positions</option>
                <option value="president">President</option>
                <option value="vice_president">Vice President</option>
                <option value="secretary">Secretary</option>
                <option value="treasurer">Treasurer</option>
                <option value="auditor">Auditor</option>
                <option value="pio">PIO</option>
                <option value="representative">Representative</option>
                <option value="adviser">Adviser</option>
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
        <div class="members-empty" id="emptyState">
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
                        <th>Program</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($members as $i => $m):
                    $name     = htmlspecialchars($m['full_name'] ?? '—');
                    $email    = htmlspecialchars($m['email']     ?? '');
                    $phone    = htmlspecialchars($m['phone']     ?? '');
                    $program  = htmlspecialchars($m['program']   ?? '');
                    $position = $m['position'] ?? 'member';
                    $status   = $m['status']   ?? 'active';
                    $joined   = !empty($m['created_at']) ? date('M d, Y', strtotime($m['created_at'])) : '—';
                    $color    = $avatarColors[$i % count($avatarColors)];
                    $init     = initials($m['full_name'] ?? 'M');
                ?>
                <tr data-id="<?= $m['member_id'] ?>"
                    data-name="<?= strtolower($m['full_name'] ?? '') ?>"
                    data-email="<?= strtolower($m['email']    ?? '') ?>"
                    data-program="<?= strtolower($m['program'] ?? '') ?>"
                    data-role="<?= $position ?>"
                    data-status="<?= $status ?>">
                    <td class="row-num"><?= $i + 1 ?></td>
                    <td>
                        <div class="member-name-cell">
                            <div class="member-avatar" style="background:<?= $color ?>;"><?= $init ?></div>
                            <div>
                                <span class="member-name-text"><?= $name ?></span>
                                <span class="member-email-text"><?= $email ?></span>
                            </div>
                        </div>
                    </td>
                    <td><span class="position-badge <?= positionClass($position) ?>"><?= positionLabel($position) ?></span></td>
                    <td class="program-cell"><?= $program ?: '<span style="color:#d1d5db;">—</span>' ?></td>
                    <td class="contact-cell">
                        <?php if ($phone): ?>
                            <i class="fas fa-phone" style="color:#2d6a4f;margin-right:5px;font-size:0.75rem;"></i><?= $phone ?>
                        <?php else: ?>
                            <span style="color:#d1d5db;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-pill <?= $status === 'active' ? 'pill-active' : 'pill-inactive' ?>">
                            <span class="status-dot"></span><?= ucfirst($status) ?>
                        </span>
                    </td>
                    <td class="date-cell"><?= $joined ?></td>
                    <td class="actions-cell">
                        <button class="btn-action btn-edit" title="Edit"
                            onclick="openEditModal(<?= $m['member_id'] ?>,'<?= addslashes($m['full_name']??'') ?>','<?= $position ?>','<?= addslashes($m['phone']??'') ?>','<?= addslashes($m['email']??'') ?>','<?= addslashes($m['program']??'') ?>','<?= $status ?>')">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn-action btn-delete" title="Remove"
                            onclick="openDeleteModal(<?= $m['member_id'] ?>,'<?= addslashes($m['full_name']??'') ?>')">
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

    <!-- No results row -->
    <div id="noResults" style="display:none;flex-direction:column;align-items:center;justify-content:center;padding:3rem;gap:0.6rem;color:#8aaa92;font-size:0.9rem;">
        <i class="fas fa-search" style="font-size:1.8rem;"></i>
        <p>No members match your search or filters.</p>
        <button onclick="clearFilters()" style="margin-top:0.4rem;background:#2d6a4f;color:white;border:none;padding:0.5rem 1.2rem;border-radius:40px;font-size:0.82rem;font-weight:600;cursor:pointer;font-family:inherit;">Clear Filters</button>
    </div>

</div><!-- /.members-container -->
</main>

<!-- ── Add / Edit Member Modal ─────────────────────────────────────────── -->
<div id="memberModal" class="modal">
    <div class="modal-content">
        <button class="close-modal" id="closeModal">&times;</button>
        <div class="modal-header">
            <h2><i class="fas fa-user-plus" id="modalIcon"></i> <span id="modalTitle">Add New Member</span></h2>
        </div>
        <div class="modal-body">
            <input type="hidden" id="memberId">
            <div id="formAlert" style="display:none;margin-bottom:1rem;padding:0.65rem 0.9rem;border-radius:8px;font-size:0.85rem;font-weight:600;"></div>
            <div class="form-group">
                <label for="fullName">Full Name <span style="color:#dc2626">*</span></label>
                <input type="text" id="fullName" placeholder="Enter full name">
            </div>
            <div class="form-group">
                <label for="position">Position</label>
                <select id="position">
                    <option value="president">President</option>
                    <option value="vice_president">Vice President</option>
                    <option value="secretary">Secretary</option>
                    <option value="treasurer">Treasurer</option>
                    <option value="auditor">Auditor</option>
                    <option value="pio">PIO</option>
                    <option value="representative">Representative</option>
                    <option value="adviser">Adviser</option>
                    <option value="member" selected>Member</option>
                </select>
            </div>
            <div class="form-group">
                <label for="program">Program / College</label>
                <input type="text" id="program" placeholder="e.g. BS Information Technology">
            </div>
            <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" placeholder="+63 XXX XXX XXXX">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" placeholder="email@example.com">
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
            <button class="btn-submit" id="submitBtn">
                <i class="fas fa-save"></i> Save Member
            </button>
        </div>
    </div>
</div>

<!-- ── Delete Confirm Modal ────────────────────────────────────────────── -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <button class="close-modal" id="closeDeleteModal">&times;</button>
        <div class="modal-body" style="padding:2rem 1.8rem 1rem;">
            <div class="delete-modal-icon"><i class="fas fa-trash"></i></div>
            <div class="delete-modal-text">
                <h3>Remove Member?</h3>
                <p>Are you sure you want to remove <strong id="deleteTargetName"></strong>? This cannot be undone.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelDeleteModal">Cancel</button>
            <button class="btn-delete-confirm" id="confirmDeleteBtn">
                <i class="fas fa-trash"></i> Yes, Remove
            </button>
        </div>
    </div>
</div>

<script src="../js/script.js"></script>
<script src="../js/navbar.js"></script>
<script src="../js/notifications.js"></script>
<script>
/* ── Helpers ──────────────────────────────────────────────────────────── */
const avatarColors = ['#2d6a4f','#1d4ed8','#7c3aed','#b45309','#0e7490','#be185d','#065f46'];

function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function showFormAlert(msg, type) {
    const el = document.getElementById('formAlert');
    el.textContent = msg;
    el.style.display = 'block';
    el.style.background = type === 'error' ? '#fee2e2' : '#e8f5ee';
    el.style.color      = type === 'error' ? '#991b1b' : '#166534';
    el.style.border     = '1px solid ' + (type === 'error' ? '#fca5a5' : '#86efac');
}
function hideFormAlert() { document.getElementById('formAlert').style.display = 'none'; }

function posLabel(p) { return p.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()); }
function posClass(p) { return 'pos-' + p.replace(/_/g,'-'); }
function getInitials(name) {
    const parts = name.trim().split(' ');
    let i = (parts[0]||'M')[0].toUpperCase();
    if (parts.length > 1) i += parts[parts.length-1][0].toUpperCase();
    return i;
}

/* ── ADD MODAL ────────────────────────────────────────────────────────── */
document.getElementById('openAddModal').onclick = function() {
    document.getElementById('modalTitle').textContent = 'Add New Member';
    document.getElementById('modalIcon').className    = 'fas fa-user-plus';
    document.getElementById('memberId').value   = '';
    document.getElementById('fullName').value   = '';
    document.getElementById('position').value   = 'member';
    document.getElementById('program').value    = '';
    document.getElementById('contact').value    = '';
    document.getElementById('email').value      = '';
    document.getElementById('statusGroup').style.display = 'none';
    hideFormAlert();
    openModal('memberModal');
};

document.getElementById('closeModal').onclick  = () => closeModal('memberModal');
document.getElementById('cancelModal').onclick = () => closeModal('memberModal');
document.getElementById('memberModal').onclick = function(e) { if(e.target===this) closeModal('memberModal'); };

/* ── EDIT MODAL ───────────────────────────────────────────────────────── */
window.openEditModal = function(id, name, pos, phone, email, program, status) {
    document.getElementById('modalTitle').textContent = 'Edit Member';
    document.getElementById('modalIcon').className    = 'fas fa-user-edit';
    document.getElementById('memberId').value         = id;
    document.getElementById('fullName').value         = name;
    document.getElementById('position').value         = pos;
    document.getElementById('program').value          = program;
    document.getElementById('contact').value          = phone;
    document.getElementById('email').value            = email;
    document.getElementById('memberStatus').value     = status;
    document.getElementById('statusGroup').style.display = 'block';
    hideFormAlert();
    openModal('memberModal');
};

/* ── SUBMIT (Add / Edit) ──────────────────────────────────────────────── */
document.getElementById('submitBtn').onclick = function() {
    const btn      = this;
    const memberId = document.getElementById('memberId').value || '0';
    const fullName = document.getElementById('fullName').value.trim();

    if (!fullName) {
        showFormAlert('Full name is required.', 'error');
        document.getElementById('fullName').focus();
        return;
    }

    const fd = new FormData();
    fd.append('member_id', memberId);
    fd.append('full_name', fullName);
    fd.append('position',  document.getElementById('position').value);
    fd.append('program',   document.getElementById('program').value.trim());
    fd.append('phone',     document.getElementById('contact').value.trim());
    fd.append('email',     document.getElementById('email').value.trim());
    fd.append('status',    document.getElementById('memberStatus')?.value || 'active');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    fetch('save_member.php', { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Member';

        if (!data.success) {
            showFormAlert(data.message || 'Error saving member.', 'error');
            return;
        }

        closeModal('memberModal');
        // Reload page to reflect DB changes
        location.reload();
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Member';
        showFormAlert('Network error. Please try again.', 'error');
    });
};

/* ── DELETE MODAL ─────────────────────────────────────────────────────── */
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
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing…';

    const fd = new FormData();
    fd.append('member_id', _deleteId);

    fetch('delete_member.php', { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash"></i> Yes, Remove';
        closeModal('deleteModal');
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Could not remove member.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash"></i> Yes, Remove';
        alert('Network error. Please try again.');
    });
};

/* ── KEYBOARD ─────────────────────────────────────────────────────────── */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeModal('memberModal'); closeModal('deleteModal'); }
});

/* ── SEARCH & FILTER ──────────────────────────────────────────────────── */
function applyFilters() {
    const q   = document.getElementById('searchInput').value.toLowerCase().trim();
    const pos = document.getElementById('positionFilter').value.toLowerCase();
    const st  = document.getElementById('statusFilter').value.toLowerCase();
    const rows = document.querySelectorAll('#membersTable tbody tr');
    let visible = 0;
    rows.forEach((row, i) => {
        const nameMatch    = !q   || row.dataset.name.includes(q)
                                  || (row.dataset.email||'').includes(q)
                                  || (row.dataset.program||'').includes(q);
        const posMatch     = !pos || row.dataset.role === pos;
        const statusMatch  = !st  || row.dataset.status === st;
        const show = nameMatch && posMatch && statusMatch;
        row.style.display = show ? '' : 'none';
        if (show) {
            row.querySelector('.row-num').textContent = ++visible;
        }
    });
    const rc = document.getElementById('rowCount');
    if (rc) rc.textContent = visible + ' member(s)';
    const nr = document.getElementById('noResults');
    if (nr) nr.style.display = (visible === 0 && rows.length > 0) ? 'flex' : 'none';
}

function clearFilters() {
    document.getElementById('searchInput').value     = '';
    document.getElementById('positionFilter').value  = '';
    document.getElementById('statusFilter').value    = '';
    applyFilters();
}

document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('positionFilter').addEventListener('change', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);
</script>

</body>
</html>