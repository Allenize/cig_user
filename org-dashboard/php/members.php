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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Core Styles -->
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">  <!-- for top-bar, main layout -->
    <link rel="stylesheet" href="../css/members.css">
    <link rel="stylesheet" href="../css/topbar.css">  
     <link rel="stylesheet" href="../css/notifications.css">      <!-- page-specific styles -->
</head>
<body>
    <!-- Include navbar -->
    <?php include '../php/navbar.php'; ?>
    <?php include '../php/topbar.php'; ?>

    <!-- Main content -->
    <main class="main-content">
        <div class="members-container">
            <!-- Header with title and actions -->
            <div class="members-header">
                <h1><i class="fas fa-users"></i> Members Management</h1>
                <div class="header-actions">
                    <button class="btn-add" id="openAddModal"><i class="fas fa-plus"></i> Add Member</button>
                    <button class="btn-export"><i class="fas fa-file-pdf"></i> PDF</button>
                    <button class="btn-export"><i class="fas fa-file-excel"></i> Excel</button>
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
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Members Table -->
            <div class="table-responsive">
                <table class="members-table" id="membersTable">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Position</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sample Data Rows -->
                        <tr>
                            <td>John Michael Santos</td>
                            <td>President</td>
                            <td>+63 912 345 6789</td>
                            <td>john.santos@example.com</td>
                            <td><span class="status-badge active">Active</span></td>
                            <td class="actions">
                                <button class="btn-edit" onclick="editMember(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn-delete" onclick="deleteMember(this)"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td>Maria Consuelo Reyes</td>
                            <td>Secretary</td>
                            <td>+63 923 456 7890</td>
                            <td>maria.reyes@example.com</td>
                            <td><span class="status-badge active">Active</span></td>
                            <td class="actions">
                                <button class="btn-edit" onclick="editMember(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn-delete" onclick="deleteMember(this)"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td>Robert Lim</td>
                            <td>Treasurer</td>
                            <td>+63 934 567 8901</td>
                            <td>robert.lim@example.com</td>
                            <td><span class="status-badge active">Active</span></td>
                            <td class="actions">
                                <button class="btn-edit" onclick="editMember(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn-delete" onclick="deleteMember(this)"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td>Anna Marie Villanueva</td>
                            <td>Member</td>
                            <td>+63 945 678 9012</td>
                            <td>anna.villanueva@example.com</td>
                            <td><span class="status-badge inactive">Inactive</span></td>
                            <td class="actions">
                                <button class="btn-edit" onclick="editMember(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn-delete" onclick="deleteMember(this)"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td>Carlos Mendoza</td>
                            <td>Vice President</td>
                            <td>+63 956 789 0123</td>
                            <td>carlos.mendoza@example.com</td>
                            <td><span class="status-badge active">Active</span></td>
                            <td class="actions">
                                <button class="btn-edit" onclick="editMember(this)"><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn-delete" onclick="deleteMember(this)"><i class="fas fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add/Edit Member Modal -->
    <div id="memberModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">&times;</span>
            <h2 id="modalTitle">Add New Member</h2>
            <form id="memberForm">
                <input type="hidden" id="memberId" value="">
                <div class="form-group">
                    <label for="fullName">Full Name <span>*</span></label>
                    <input type="text" id="fullName" required>
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
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="tel" id="contact" placeholder="+63 XXX XXX XXXX">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Save Member</button>
                    <button type="button" class="btn-cancel" id="cancelModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../js/script.js"></script> <!-- sidebar toggle -->
    <script>
        // ========== MODAL FUNCTIONALITY ==========
        const modal = document.getElementById('memberModal');
        const openAddBtn = document.getElementById('openAddModal');
        const closeModalBtn = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelModal');
        const modalTitle = document.getElementById('modalTitle');
        const memberForm = document.getElementById('memberForm');
        const memberIdField = document.getElementById('memberId');

        // Open modal for Add
        openAddBtn.onclick = function() {
            modalTitle.innerText = 'Add New Member';
            memberForm.reset();
            memberIdField.value = '';
            modal.style.display = 'block';
        }

        // Close modal
        function closeModal() {
            modal.style.display = 'none';
        }
        closeModalBtn.onclick = closeModal;
        cancelBtn.onclick = closeModal;
        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }

        // Simulate Edit: populate form with row data and open modal
        window.editMember = function(button) {
            const row = button.closest('tr');
            const cells = row.querySelectorAll('td');
            const name = cells[0].innerText;
            const position = cells[1].innerText;
            const contact = cells[2].innerText;
            const email = cells[3].innerText;
            const status = cells[4].querySelector('.status-badge').innerText;

            modalTitle.innerText = 'Edit Member';
            document.getElementById('fullName').value = name;
            document.getElementById('position').value = position;
            document.getElementById('contact').value = contact;
            document.getElementById('email').value = email;
            document.getElementById('status').value = status;
            memberIdField.value = name; // simple placeholder

            modal.style.display = 'block';
        }

        // Handle form submit (simulated)
        memberForm.onsubmit = function(e) {
            e.preventDefault();
            alert('Member saved (simulated). In production, this would send data to the server.');
            closeModal();
        }

        // Simulate Delete with confirmation
        window.deleteMember = function(button) {
            if (confirm('Are you sure you want to delete this member?')) {
                const row = button.closest('tr');
                row.remove(); // In real app, you'd send AJAX request
                alert('Member deleted (simulated).');
            }
        }

        // ========== SEARCH & FILTER ==========
        const searchInput = document.getElementById('searchInput');
        const positionFilter = document.getElementById('positionFilter');
        const statusFilter = document.getElementById('statusFilter');
        const tableRows = document.querySelectorAll('#membersTable tbody tr');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const positionVal = positionFilter.value.toLowerCase();
            const statusVal = statusFilter.value.toLowerCase();

            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const name = cells[0].innerText.toLowerCase();
                const position = cells[1].innerText.toLowerCase();
                const email = cells[3].innerText.toLowerCase();
                const status = cells[4].innerText.toLowerCase();

                const matchesSearch = name.includes(searchTerm) || position.includes(searchTerm) || email.includes(searchTerm);
                const matchesPosition = positionVal === '' || position === positionVal;
                const matchesStatus = statusVal === '' || status.includes(statusVal);

                if (matchesSearch && matchesPosition && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterTable);
        positionFilter.addEventListener('change', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>