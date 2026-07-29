<?php
require_once __DIR__ . '/../../db.php';

/* ======================
    DELETE HANDLER
====================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int) $_GET['id'];
    $stmt = $conn->prepare('DELETE FROM categories WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo "<script>window.location.href='" . base_url('/admin/categories') . "';</script>";
    exit;
}

/* ======================
    FETCH CATEGORIES
====================== */
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : -1;

$allCount = (int) ($conn->query('SELECT COUNT(*) as total FROM categories')->fetch_assoc()['total'] ?? 0);
$activeCount = (int) ($conn->query('SELECT COUNT(*) as total FROM categories WHERE status = 1')->fetch_assoc()['total'] ?? 0);
$inactiveCount = (int) ($conn->query('SELECT COUNT(*) as total FROM categories WHERE status = 0')->fetch_assoc()['total'] ?? 0);

$whereSql = '';
if ($statusFilter === 1) {
    $whereSql = 'WHERE status = 1';
} elseif ($statusFilter === 0) {
    $whereSql = 'WHERE status = 0';
}

$totalRows = ($statusFilter === 1) ? $activeCount : (($statusFilter === 0) ? $inactiveCount : $allCount);
$totalPages = max(1, ceil($totalRows / $limit));

$result = $conn->query("SELECT * FROM categories $whereSql ORDER BY sort_order ASC, id DESC LIMIT $offset, $limit");
?>

<div class="admin-wrapper">
    
    <div class="admin-header">
        <div>
            <h2 class="admin-title">Vessel Categories</h2>
            <p class="admin-subtitle">Manage candle vessel collections, sizes, and specifications.</p>
        </div>
        <div>
            <a href="<?= base_url('/admin/categories/add'); ?>" class="admin-btn-primary">
                <span>+</span> Add New Category
            </a>
        </div>
    </div>

    <!-- Status Filter Pills -->
    <div class="status-filters">
        <a href="<?= base_url('/admin/categories'); ?>" class="status-pill <?= $statusFilter === -1 ? 'active' : ''; ?>">
            All Categories <span class="count"><?= $allCount; ?></span>
        </a>
        <a href="<?= base_url('/admin/categories?status=1'); ?>" class="status-pill <?= $statusFilter === 1 ? 'active' : ''; ?>">
            Active <span class="count"><?= $activeCount; ?></span>
        </a>
        <a href="<?= base_url('/admin/categories?status=0'); ?>" class="status-pill <?= $statusFilter === 0 ? 'active' : ''; ?>">
            Inactive <span class="count"><?= $inactiveCount; ?></span>
        </a>
    </div>

    <!-- TABLE LIST -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Category Name</th>
                    <th>Dimensions</th>
                    <th>Burn Time</th>
                    <th>Wick Type</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['image'])): ?>
                                <img src="<?= htmlspecialchars(base_url('/' . ltrim($row['image'], '/'))); ?>" alt="Vessel Category" class="admin-thumb">
                            <?php else: ?>
                                <div class="admin-no-thumb">No<br>Image</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color:#111827; font-size:15px;"><?= htmlspecialchars($row['category_name']); ?></strong>
                            <div style="font-size:12px; color:#6b7280; margin-top:2px;">/<?= htmlspecialchars($row['slug']); ?></div>
                            <?php if (!empty($row['description'])): ?>
                                <div style="font-size:12px; color:#4b5563; margin-top:4px; max-width:280px;"><?= htmlspecialchars($row['description']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="color:#4b5563;">
                            <?= htmlspecialchars($row['dimensions_subtitle'] ?? '-'); ?>
                        </td>
                        <td>
                            <?= !empty($row['burn_time_badge']) ? htmlspecialchars($row['burn_time_badge']) : '-'; ?>
                        </td>
                        <td>
                            <?= !empty($row['wick_type']) ? '🕯 ' . htmlspecialchars($row['wick_type']) : '-'; ?>
                        </td>
                        <td>
                            <?php if (($row['status'] ?? 1) == 1): ?>
                                <span class="admin-badge-active">Active</span>
                            <?php else: ?>
                                <span class="admin-badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="<?= base_url('/admin/categories/edit?id=' . $row['id']); ?>" class="admin-btn-edit">
                               ✏️ Edit
                            </a>

                            <a href="javascript:void(0);" class="admin-btn-delete" onclick="confirmDelete(<?= $row['id']; ?>)">
                               🗑️ Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:40px; color:#9ca3af;">
                        No vessel categories created yet. Click "Add New Category" above to create one.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="admin-pagination">
                <div>Showing <?= min($offset + 1, $totalRows); ?> to <?= min($offset + $limit, $totalRows); ?> of <?= $totalRows; ?> categories</div>
                <div class="admin-pagination-pages">
                    <a href="<?= base_url('/admin/categories?page=' . max(1, $page - 1)); ?>" class="admin-page-link <?= $page <= 1 ? 'disabled' : ''; ?>">&laquo; Prev</a>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?= base_url('/admin/categories?page=' . $p); ?>" class="admin-page-link <?= $p == $page ? 'active' : ''; ?>"><?= $p; ?></a>
                    <?php endfor; ?>
                    <a href="<?= base_url('/admin/categories?page=' . min($totalPages, $page + 1)); ?>" class="admin-page-link <?= $page >= $totalPages ? 'disabled' : ''; ?>">Next &raquo;</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function confirmDelete(id) {
    if(confirm('Are you sure you want to delete this category?')) {
        window.location.href = '<?= base_url('/admin/categories'); ?>?action=delete&id=' + id;
    }
}
</script>
