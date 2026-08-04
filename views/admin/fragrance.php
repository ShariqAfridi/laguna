<?php
require_once __DIR__ . '/../../db.php';

/* ======================
    DELETE HANDLER
====================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM fragrances WHERE fragrance_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>window.location.href='" . base_url('/admin/fragrance') . "';</script>";
    exit;
}

/* ======================
    FETCH FRAGRANCES
====================== */
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;

$allCount = (int)($conn->query("SELECT COUNT(*) as total FROM fragrances")->fetch_assoc()['total'] ?? 0);
$activeCount = (int)($conn->query("SELECT COUNT(*) as total FROM fragrances WHERE status = 1")->fetch_assoc()['total'] ?? 0);
$inactiveCount = (int)($conn->query("SELECT COUNT(*) as total FROM fragrances WHERE status = 0")->fetch_assoc()['total'] ?? 0);

$whereSql = "";
if ($statusFilter === 1) { $whereSql = "WHERE status = 1"; }
elseif ($statusFilter === 0) { $whereSql = "WHERE status = 0"; }

$totalRows = ($statusFilter === 1) ? $activeCount : (($statusFilter === 0) ? $inactiveCount : $allCount);
$totalPages = max(1, ceil($totalRows / $limit));

$fragrances = $conn->query("SELECT * FROM fragrances $whereSql ORDER BY sort_order ASC, fragrance_id DESC LIMIT $offset, $limit");
?>

<div class="admin-wrapper">
    
    <div class="admin-header">
        <div>
            <h2 class="admin-title">Candle Fragrance Profiles</h2>
            <p class="admin-subtitle">Manage candle scents, olfactory descriptions, and fragrance notes.</p>
        </div>
        <div>
            <a href="<?= base_url('/admin/fragrance/add'); ?>" class="admin-btn-primary">
                <span>+</span> Add New Fragrance Profile
            </a>
        </div>
    </div>

    <!-- Status Filter Pills -->
    <div class="status-filters">
        <a href="<?= base_url('/admin/fragrance'); ?>" class="status-pill <?= $statusFilter === -1 ? 'active' : ''; ?>">
            All Fragrance Scents <span class="count"><?= $allCount; ?></span>
        </a>
        <a href="<?= base_url('/admin/fragrance?status=1'); ?>" class="status-pill <?= $statusFilter === 1 ? 'active' : ''; ?>">
            Active <span class="count"><?= $activeCount; ?></span>
        </a>
        <a href="<?= base_url('/admin/fragrance?status=0'); ?>" class="status-pill <?= $statusFilter === 0 ? 'active' : ''; ?>">
            Inactive <span class="count"><?= $inactiveCount; ?></span>
        </a>
    </div>

    <!-- TABLE LIST -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Fragrance Image</th>
                    <th>Scent Notes Image</th>
                    <th>Fragrance Name</th>
                    <th>Description / Scent Notes</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($fragrances && $fragrances->num_rows > 0): ?>
                <?php while($row = $fragrances->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['fragrance_image'])): ?>
                                <img src="<?= htmlspecialchars(base_url('/' . ltrim($row['fragrance_image'], '/'))); ?>" alt="Fragrance Profile" style="width:110px; height:110px; object-fit:contain; border-radius:8px; background:#fff; border:1px solid #d1d5db; padding:4px; box-shadow:0 2px 5px rgba(0,0,0,0.05); display:block;">
                            <?php else: ?>
                                <div class="admin-no-thumb" style="width:110px; height:110px; font-size:12px;">No<br>Image</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($row['scent_note_image'])): ?>
                                <img src="<?= htmlspecialchars(base_url('/' . ltrim($row['scent_note_image'], '/'))); ?>" alt="Scent Notes" style="width:110px; height:110px; object-fit:contain; border-radius:8px; background:#fff; border:1px solid #d1d5db; padding:4px; box-shadow:0 2px 5px rgba(0,0,0,0.05); display:block;">
                            <?php else: ?>
                                <div class="admin-no-thumb" style="width:110px; height:110px; font-size:12px;">No<br>Image</div>
                            <?php endif; ?>
                        </td>
                        <td><strong style="color:#111827; font-size:15px;"><?= htmlspecialchars($row['fragrance_name']); ?></strong></td>
                        <td style="max-width:280px; font-size:13px; color:#4b5563; line-height:1.5;">
                            <?php if (!empty($row['fragrance_description'])): ?>
                                <?= nl2br(htmlspecialchars($row['fragrance_description'])); ?>
                            <?php else: ?>
                                <span style="color:#9ca3af; italic;">— No Description —</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($row['status'] ?? 1) == 1): ?>
                                <span class="admin-badge-active">Active</span>
                            <?php else: ?>
                                <span class="admin-badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="<?= base_url('/admin/fragrance/edit?id=' . $row['fragrance_id']); ?>" class="admin-btn-edit">
                               ✏️ Edit
                            </a>

                            <a href="javascript:void(0);" class="admin-btn-delete" onclick="confirmDelete(<?= $row['fragrance_id']; ?>)">
                               🗑️ Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:40px; color:#9ca3af;">No fragrance profiles created yet. Click "Add New Fragrance Profile" above.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="admin-pagination">
                <div>Showing <?= min($offset + 1, $totalRows); ?> to <?= min($offset + $limit, $totalRows); ?> of <?= $totalRows; ?> fragrances</div>
                <div class="admin-pagination-pages">
                    <a href="<?= base_url('/admin/fragrance?page=' . max(1, $page - 1)); ?>" class="admin-page-link <?= $page <= 1 ? 'disabled' : ''; ?>">&laquo; Prev</a>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?= base_url('/admin/fragrance?page=' . $p); ?>" class="admin-page-link <?= $p == $page ? 'active' : ''; ?>"><?= $p; ?></a>
                    <?php endfor; ?>
                    <a href="<?= base_url('/admin/fragrance?page=' . min($totalPages, $page + 1)); ?>" class="admin-page-link <?= $page >= $totalPages ? 'disabled' : ''; ?>">Next &raquo;</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function confirmDelete(id) {
    if(confirm('Are you sure you want to delete this fragrance profile?')) {
        window.location.href = '<?= base_url('/admin/fragrance'); ?>?action=delete&id=' + id;
    }
}
</script>