<?php
require_once __DIR__ . '/../../db.php';

/* ======================
    DELETE HANDLER
====================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM colors WHERE color_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>window.location.href='" . base_url('/admin/colors') . "';</script>";
    exit;
}

/* ======================
    FETCH COLORS
====================== */
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;

$allCount = (int)($conn->query("SELECT COUNT(*) as total FROM colors")->fetch_assoc()['total'] ?? 0);
$activeCount = (int)($conn->query("SELECT COUNT(*) as total FROM colors WHERE status = 1")->fetch_assoc()['total'] ?? 0);
$inactiveCount = (int)($conn->query("SELECT COUNT(*) as total FROM colors WHERE status = 0")->fetch_assoc()['total'] ?? 0);

$whereSql = "";
if ($statusFilter === 1) { $whereSql = "WHERE status = 1"; }
elseif ($statusFilter === 0) { $whereSql = "WHERE status = 0"; }

$totalRows = ($statusFilter === 1) ? $activeCount : (($statusFilter === 0) ? $inactiveCount : $allCount);
$totalPages = max(1, ceil($totalRows / $limit));

$result = $conn->query("SELECT * FROM colors $whereSql ORDER BY sort_order ASC, color_id DESC LIMIT $offset, $limit");
?>

<div class="admin-wrapper">
    
    <div class="admin-header">
        <div>
            <h2 class="admin-title">Candle Color Variants</h2>
            <p class="admin-subtitle">Manage candle color swatches, hex codes, and variant pictures.</p>
        </div>
        <div>
            <a href="<?= base_url('/admin/colors/add'); ?>" class="admin-btn-primary">
                <span>+</span> Add New Color Variant
            </a>
        </div>
    </div>

    <!-- Status Filter Pills -->
    <div class="status-filters">
        <a href="<?= base_url('/admin/colors'); ?>" class="status-pill <?= $statusFilter === -1 ? 'active' : ''; ?>">
            All Color Swatches <span class="count"><?= $allCount; ?></span>
        </a>
        <a href="<?= base_url('/admin/colors?status=1'); ?>" class="status-pill <?= $statusFilter === 1 ? 'active' : ''; ?>">
            Active <span class="count"><?= $activeCount; ?></span>
        </a>
        <a href="<?= base_url('/admin/colors?status=0'); ?>" class="status-pill <?= $statusFilter === 0 ? 'active' : ''; ?>">
            Inactive <span class="count"><?= $inactiveCount; ?></span>
        </a>
    </div>

    <!-- TABLE LIST -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Swatch</th>
                    <th>Candle Image</th>
                    <th>Color Name</th>
                    <th>HEX Code</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $rawHex = trim($row['color_hex'] ?? '#000000');
                    $cleanHex = preg_replace('/[^0-9A-Fa-f]/', '', $rawHex);
                    if (strlen($cleanHex) === 3) {
                        $formattedHex = '#' . $cleanHex[0].$cleanHex[0].$cleanHex[1].$cleanHex[1].$cleanHex[2].$cleanHex[2];
                    } elseif (strlen($cleanHex) >= 6) {
                        $formattedHex = '#' . substr($cleanHex, 0, 6);
                    } else {
                        $formattedHex = '#' . str_pad($cleanHex, 6, '0');
                    }
                    $formattedHex = strtoupper($formattedHex);
                ?>
                    <tr>
                        <td>
                            <div style="width:40px; height:24px; border-radius:6px; background:<?= htmlspecialchars($formattedHex); ?>; border:1px solid rgba(0,0,0,0.15);" title="<?= htmlspecialchars($formattedHex); ?>"></div>
                        </td>
                        <td>
                            <?php if (!empty($row['color_image'])): ?>
                                <img src="<?= htmlspecialchars(base_url('/' . ltrim($row['color_image'], '/'))); ?>" alt="Candle Variant" class="admin-thumb">
                            <?php else: ?>
                                <div class="admin-no-thumb">No<br>Image</div>
                            <?php endif; ?>
                        </td>
                        <td><strong style="color:#111827; font-size:15px;"><?= htmlspecialchars($row['color_name']); ?></strong></td>
                        <td><code style="background:#f1f3f5; padding:3px 8px; border-radius:4px; font-family:monospace;"><?= htmlspecialchars($formattedHex); ?></code></td>
                        <td>
                            <?php if (($row['status'] ?? 1) == 1): ?>
                                <span class="admin-badge-active">Active</span>
                            <?php else: ?>
                                <span class="admin-badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="<?= base_url('/admin/colors/edit?id=' . $row['color_id']); ?>" class="admin-btn-edit">
                               ✏️ Edit
                            </a>

                            <a href="javascript:void(0);" class="admin-btn-delete" onclick="confirmDelete(<?= $row['color_id']; ?>)">
                               🗑️ Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:40px; color:#9ca3af;">No candle color variants created yet. Click "Add New Color Variant" above.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="admin-pagination">
                <div>Showing <?= min($offset + 1, $totalRows); ?> to <?= min($offset + $limit, $totalRows); ?> of <?= $totalRows; ?> colors</div>
                <div class="admin-pagination-pages">
                    <a href="<?= base_url('/admin/colors?page=' . max(1, $page - 1)); ?>" class="admin-page-link <?= $page <= 1 ? 'disabled' : ''; ?>">&laquo; Prev</a>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?= base_url('/admin/colors?page=' . $p); ?>" class="admin-page-link <?= $p == $page ? 'active' : ''; ?>"><?= $p; ?></a>
                    <?php endfor; ?>
                    <a href="<?= base_url('/admin/colors?page=' . min($totalPages, $page + 1)); ?>" class="admin-page-link <?= $page >= $totalPages ? 'disabled' : ''; ?>">Next &raquo;</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function confirmDelete(id) {
    if(confirm('Are you sure you want to delete this color option?')) {
        window.location.href = '<?= base_url('/admin/colors'); ?>?action=delete&id=' + id;
    }
}
</script>