<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../db.php';

// Handle accessory deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $accessory_id = (int) $_GET['delete'];

    $img_query = $conn->prepare('SELECT image FROM accessory WHERE accessory_id = ?');
    $img_query->bind_param('i', $accessory_id);
    $img_query->execute();
    $img_result = $img_query->get_result();
    if ($img_row = $img_result->fetch_assoc()) {
        $image_name = $img_row['image'];
        if (!empty($image_name)) {
            $paths_to_check = [
                $_SERVER['DOCUMENT_ROOT'] . '/public/assets/img/' . $image_name,
                dirname(__DIR__, 2) . '/public/assets/img/' . $image_name,
                __DIR__ . '/../public/assets/img/' . $image_name
            ];

            foreach ($paths_to_check as $path) {
                if (file_exists($path)) {
                    unlink($path);
                    break;
                }
            }
        }
    }
    $img_query->close();

    $delete_stmt = $conn->prepare('DELETE FROM accessory WHERE accessory_id = ?');
    $delete_stmt->bind_param('i', $accessory_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    echo "<script>window.location.href='" . base_url('/admin/list_accessory?deleted=1') . "';</script>";
    exit();
}

$success_message = '';
if (isset($_GET['deleted'])) {
    $success_message = 'Accessory deleted successfully!';
}
if (isset($_GET['added'])) {
    $success_message = 'Accessory added successfully!';
}
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : 'all';

$allAccessoryCount = (int)($conn->query("SELECT COUNT(*) as total FROM accessory")->fetch_assoc()['total'] ?? 0);
$inStockCount = (int)($conn->query("SELECT COUNT(*) as total FROM accessory WHERE quantity > 0")->fetch_assoc()['total'] ?? 0);
$outOfStockCount = (int)($conn->query("SELECT COUNT(*) as total FROM accessory WHERE quantity <= 0 OR quantity IS NULL")->fetch_assoc()['total'] ?? 0);

$whereSql = "";
if ($stockFilter === 'instock') {
    $whereSql = "WHERE quantity > 0";
} elseif ($stockFilter === 'outofstock') {
    $whereSql = "WHERE quantity <= 0 OR quantity IS NULL";
}

$totalRows = ($stockFilter === 'instock') ? $inStockCount : (($stockFilter === 'outofstock') ? $outOfStockCount : $allAccessoryCount);
$totalPages = max(1, ceil($totalRows / $limit));

$query = "SELECT * FROM accessory $whereSql ORDER BY created_at DESC LIMIT $offset, $limit";
$accessories = $conn->query($query);
?>

<div class="admin-wrapper">
    <div class="admin-header">
        <div>
            <h2 class="admin-title">Candle Accessories</h2>
            <p class="admin-subtitle">Manage candle care tools, wick trimmers, snuffers, and accessories.</p>
        </div>
        <div>
            <a href="<?= base_url('/admin/add_accessory'); ?>" class="admin-btn-primary">+ Add New Accessory</a>
        </div>
    </div>

    <!-- Stock Filter Pills -->
    <div class="status-filters">
        <a href="<?= base_url('/admin/list_accessory'); ?>" class="status-pill <?= $stockFilter === 'all' ? 'active' : ''; ?>">
            All Accessories <span class="count"><?= $allAccessoryCount; ?></span>
        </a>
        <a href="<?= base_url('/admin/list_accessory?stock=instock'); ?>" class="status-pill <?= $stockFilter === 'instock' ? 'active' : ''; ?>">
            In Stock <span class="count"><?= $inStockCount; ?></span>
        </a>
        <a href="<?= base_url('/admin/list_accessory?stock=outofstock'); ?>" class="status-pill <?= $stockFilter === 'outofstock' ? 'active' : ''; ?>">
            Out of Stock <span class="count"><?= $outOfStockCount; ?></span>
        </a>
    </div>

    <?php if ($success_message): ?>
        <div style="background:#dcfce7; color:#15803d; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:600;">
            ✅ <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Accessory Name</th>
                    <th>Description</th>
                    <th>Price ($)</th>
                    <th>Quantity</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($accessories && $accessories->num_rows > 0): ?>
                    <?php while ($row = $accessories->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['accessory_id'] ?></td>
                            <td>
                                <?php
                                $image_file = $row['image'] ?? '';
                                if (!empty($image_file)) {
                                    $clean_name = ltrim(preg_replace('#^/?(img/)?#i', '', $image_file), '/');
                                    $disk_path = dirname(__DIR__, 2) . '/public/assets/img/' . $clean_name;

                                    if (file_exists($disk_path)) {
                                        $img_url = base_url('/public/assets/img/' . $clean_name);
                                        echo '<img src="' . htmlspecialchars($img_url) . '" class="admin-thumb" alt="' . htmlspecialchars($row['name'] ?? $row['accessory_name'] ?? '') . '">';
                                    } else if (preg_match('#^https?://#i', $image_file)) {
                                        echo '<img src="' . htmlspecialchars($image_file) . '" class="admin-thumb" alt="' . htmlspecialchars($row['name'] ?? $row['accessory_name'] ?? '') . '">';
                                    } else {
                                        echo '<div class="admin-no-thumb">No<br>Image</div>';
                                    }
                                } else {
                                    echo '<div class="admin-no-thumb">No<br>Image</div>';
                                }
                                ?>
                            </td>
                            <td>
                                <strong style="color:#111827; font-size:15px;"><?= htmlspecialchars($row['name'] ?? $row['accessory_name'] ?? '') ?></strong>
                            </td>
                            <td style="color:#4b5563; max-width:280px;">
                                <?= htmlspecialchars(substr($row['description'] ?? '', 0, 80)) ?>
                            </td>
                            <td style="font-weight:700; color:#111827;">
                                $<?= number_format((float) ($row['price'] ?? 0), 2) ?>
                            </td>
                            <td>
                                <?php $accQty = (int) ($row['quantity'] ?? 0); ?>
                                <?php if ($accQty <= 0): ?>
                                    <span style="background:#fee2e2; border:1px solid #fca5a5; padding:4px 10px; border-radius:12px; font-weight:700; font-size:12px; color:#b91c1c; display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fas fa-times-circle"></i> Out of Stock
                                    </span>
                                <?php elseif ($accQty <= 5): ?>
                                    <span style="background:#fef3c7; border:1px solid #fde68a; padding:4px 10px; border-radius:12px; font-weight:600; font-size:12px; color:#92400e; display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fas fa-exclamation-triangle"></i> Low (<?= $accQty ?>)
                                    </span>
                                <?php else: ?>
                                    <span style="background:#dcfce7; border:1px solid #bbf7d0; padding:4px 10px; border-radius:12px; font-weight:600; font-size:12px; color:#15803d; display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fas fa-check-circle"></i> <?= $accQty ?> in stock
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="<?= base_url('/admin/edit_accessory?id=' . $row['accessory_id']) ?>" class="admin-btn-edit">
                                    ✏️ Edit
                                </a>
                                <a href="javascript:void(0);" onclick="confirmDelete(<?= $row['accessory_id'] ?>)" class="admin-btn-delete">
                                    🗑️ Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:#9ca3af;">
                            No accessories created yet. Click "+ Add New Accessory" above.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="admin-pagination">
                <div>Showing <?= min($offset + 1, $totalRows); ?> to <?= min($offset + $limit, $totalRows); ?> of <?= $totalRows; ?> accessories</div>
                <div class="admin-pagination-pages">
                    <a href="<?= base_url('/admin/list_accessory?page=' . max(1, $page - 1)); ?>" class="admin-page-link <?= $page <= 1 ? 'disabled' : ''; ?>">&laquo; Prev</a>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?= base_url('/admin/list_accessory?page=' . $p); ?>" class="admin-page-link <?= $p == $page ? 'active' : ''; ?>"><?= $p; ?></a>
                    <?php endfor; ?>
                    <a href="<?= base_url('/admin/list_accessory?page=' . min($totalPages, $page + 1)); ?>" class="admin-page-link <?= $page >= $totalPages ? 'disabled' : ''; ?>">Next &raquo;</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this accessory?')) {
        window.location.href = '<?= base_url('/admin/list_accessory') ?>?delete=' + id;
    }
}
</script>