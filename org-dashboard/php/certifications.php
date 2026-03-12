<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/db_connection.php';

$org_id   = (int) $_SESSION['user_id'];
$org_name = htmlspecialchars($_SESSION['org_name'] ?? 'Organization');

$certs = [];

if ($conn) {
    $tbl_check = mysqli_query($conn, "SHOW TABLES LIKE 'certifications'");
    if ($tbl_check && mysqli_num_rows($tbl_check) > 0) {
        $r = mysqli_query($conn, "
            SELECT *,
                   DATEDIFF(expiry_date, CURDATE()) AS days_left
            FROM certifications
            WHERE org_id = $org_id
            ORDER BY issued_date DESC
        ");
        if ($r) while ($row = mysqli_fetch_assoc($r)) $certs[] = $row;
    }
}

$type_icons = [
    'accreditation' => 'fa-star',
    'recognition'   => 'fa-award',
    'achievement'   => 'fa-trophy',
    'completion'    => 'fa-check-double',
    'other'         => 'fa-certificate',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certifications – OrgHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/topbar.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <link rel="stylesheet" href="../css/certifications.css">
</head>
<body>

<?php include '../php/navbar.php'; ?>
    <?php include '../php/topbar.php'; ?>

    <div class="cert-container">

        <!-- Page Header -->
        <div class="cert-header">
            <div class="cert-header-left">
                <div class="cert-header-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <div>
                    <h1>Certifications</h1>
                    <p>Official certificates and accreditations received by your organization</p>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="cert-toolbar">
            <div class="cert-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="certSearch" placeholder="Search certifications…">
            </div>
        </div>

        <!-- Table Card -->
        <div class="table-card">
            <?php if (empty($certs)): ?>
            <div class="cert-empty">
                <div class="cert-empty-icon"><i class="fas fa-certificate"></i></div>
                <h3>No certifications yet</h3>
                <p>Certifications issued by the PLSP OSAS/CIG Office will appear here once received.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="cert-table" id="certTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Issued By</th>
                            <th>Date Received</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($certs as $i => $c):
                        $days_left  = $c['days_left'];
                        $has_expiry = !empty($c['expiry_date']);
                        $issued_fmt = date('M d, Y', strtotime($c['issued_date']));
                        $expiry_fmt = $has_expiry ? date('M d, Y', strtotime($c['expiry_date'])) : '—';
                        $type_label = ucfirst($c['cert_type']);
                        $type_icon  = $type_icons[$c['cert_type']] ?? 'fa-certificate';

                        // Status logic
                        if ($c['status'] === 'revoked') {
                            $status_class = 'status-revoked'; $status_label = 'Revoked';
                        } elseif ($c['status'] === 'expired' || ($has_expiry && $days_left < 0)) {
                            $status_class = 'status-expired'; $status_label = 'Expired';
                        } elseif ($has_expiry && $days_left <= 30) {
                            $status_class = 'status-expiring'; $status_label = 'Expiring Soon';
                        } else {
                            $status_class = 'status-active'; $status_label = 'Active';
                        }

                        // File URL
                        $file_url = null;
                        if ($c['file_path']) {
                            $abs = dirname(dirname(__DIR__)) . '/' . ltrim($c['file_path'], './');
                            if (file_exists($abs))
                                $file_url = '../../' . ltrim($c['file_path'], './');
                        }
                    ?>
                    <tr data-title="<?= strtolower(htmlspecialchars($c['title'])) ?>">
                        <td class="row-num"><?= $i + 1 ?></td>
                        <td class="title-cell">
                            <span class="item-title"><?= htmlspecialchars($c['title']) ?></span>
                            <?php if ($c['description']): ?>
                            <span class="item-desc"><?= htmlspecialchars(mb_strimwidth($c['description'], 0, 60, '…')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="type-badge type-<?= $c['cert_type'] ?>">
                                <i class="fas <?= $type_icon ?>"></i> <?= $type_label ?>
                            </span>
                        </td>
                        <td class="issuer-cell"><?= htmlspecialchars($c['issued_by']) ?></td>
                        <td class="date-cell"><?= $issued_fmt ?></td>
<td class="actions-cell">
                            <button class="btn-action btn-view"
                                    title="View details"
                                    onclick="viewCert(this)"
                                    data-title="<?= htmlspecialchars($c['title']) ?>"
                                    data-type="<?= $type_label ?>"
                                    data-issuer="<?= htmlspecialchars($c['issued_by']) ?>"
                                    data-desc="<?= htmlspecialchars($c['description'] ?? '—') ?>"
                                    data-issued="<?= $issued_fmt ?>"
                                    data-expiry="<?= $expiry_fmt ?>"
                                    data-status="<?= $status_label ?>"
                                    data-fileurl="<?= htmlspecialchars($file_url ?? '') ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($file_url): ?>
                            <a href="<?= htmlspecialchars($file_url) ?>" download
                               class="btn-action btn-download" title="Download certificate">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <span id="rowCount"><?= count($certs) ?> certificate(s)</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="cert-no-results" id="certNoResults">
            <i class="fas fa-search"></i>
            <p>No certifications match your search.</p>
        </div>

    </div><!-- /.cert-container -->
</main>

<!-- View Details Modal -->
<div id="viewModal" class="cert-modal">
    <div class="cert-modal-content">
        <div class="cert-modal-header">
            <div class="cert-modal-header-icon"><i class="fas fa-certificate"></i></div>
            <h2>Certificate Details</h2>
            <span class="cert-modal-close" id="closeModal">&times;</span>
        </div>
        <div id="modalBody" class="cert-modal-body"></div>
        <div class="cert-modal-actions" id="modalActions"></div>
    </div>
</div>

<script src="../js/navbar.js"></script>
<script src="../js/notifications.js"></script>
<script>
    // ── Search ────────────────────────────────────────────────────────────
    const rows      = document.querySelectorAll('.cert-table tbody tr');
    const noResults = document.getElementById('certNoResults');
    const rowCount  = document.getElementById('rowCount');

    document.getElementById('certSearch')?.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        let visible = 0;
        rows.forEach(row => {
            const show = !term || row.dataset.title.includes(term);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noResults) noResults.style.display = visible === 0 ? 'flex' : 'none';
        if (rowCount)  rowCount.textContent = visible + ' certificate(s)';
    });

    // ── View Modal ────────────────────────────────────────────────────────
    function viewCert(btn) {
        const d = btn.dataset;
        document.getElementById('modalBody').innerHTML = `
            <div class="detail-row"><span class="detail-label">Title</span><span class="detail-value">${d.title}</span></div>
            <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value">${d.type}</span></div>
            <div class="detail-row"><span class="detail-label">Issued By</span><span class="detail-value">${d.issuer}</span></div>
            <div class="detail-row"><span class="detail-label">Description</span><span class="detail-value">${d.desc}</span></div>
            <div class="detail-row"><span class="detail-label">Date Received</span><span class="detail-value">${d.issued}</span></div>
        `;
        const actions = document.getElementById('modalActions');
        actions.innerHTML = `<button class="btn-modal-close" onclick="closeModal()"><i class="fas fa-times"></i> Close</button>`;
        if (d.fileurl) {
            actions.innerHTML += `<a href="${d.fileurl}" download class="btn-modal-download"><i class="fas fa-download"></i> Download</a>`;
        }
        document.getElementById('viewModal').style.display = 'flex';
    }

    function closeModal() { document.getElementById('viewModal').style.display = 'none'; }

    document.getElementById('closeModal').addEventListener('click', closeModal);
    document.getElementById('viewModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>
</body>
</html>