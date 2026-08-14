<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../app/Models/Coupon.php';

/* ======================
    DELETE HANDLER
====================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int) $_GET['id'];
    $stmt = $conn->prepare('DELETE FROM coupons WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo "<script>window.location.href='" . base_url('/admin/coupons') . "';</script>";
    exit;
}

/* ======================
    TOGGLE STATUS HANDLER
====================== */
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    $id = (int) $_GET['id'];
    $stmt = $conn->prepare('UPDATE coupons SET status = IF(status = 1, 0, 1) WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo "<script>window.location.href='" . base_url('/admin/coupons') . "';</script>";
    exit;
}

/* ======================
    FETCH STATS & FILTERS
====================== */
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : -1;
$typeFilter = trim($_GET['type'] ?? '');

$allCount = (int) ($conn->query('SELECT COUNT(*) as total FROM coupons')->fetch_assoc()['total'] ?? 0);
$activeCount = (int) ($conn->query('SELECT COUNT(*) as total FROM coupons WHERE status = 1')->fetch_assoc()['total'] ?? 0);
$inactiveCount = (int) ($conn->query('SELECT COUNT(*) as total FROM coupons WHERE status = 0')->fetch_assoc()['total'] ?? 0);
$totalUsed = (int) ($conn->query('SELECT COALESCE(SUM(used_count), 0) as total FROM coupons')->fetch_assoc()['total'] ?? 0);

$whereClauses = [];
$params = [];
$types = '';

if ($statusFilter === 1) {
    $whereClauses[] = "status = 1";
} elseif ($statusFilter === 0) {
    $whereClauses[] = "status = 0";
}

if (!empty($typeFilter) && in_array($typeFilter, ['percentage', 'fixed'])) {
    $whereClauses[] = "type = '" . $conn->real_escape_string($typeFilter) . "'";
}

if (!empty($search)) {
    $escapedSearch = '%' . $conn->real_escape_string($search) . '%';
    $whereClauses[] = "(code LIKE '$escapedSearch' OR description LIKE '$escapedSearch')";
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$countSql = "SELECT COUNT(*) as total FROM coupons $whereSql";
$totalRows = (int) ($conn->query($countSql)->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, ceil($totalRows / $limit));

$result = $conn->query("SELECT * FROM coupons $whereSql ORDER BY id DESC LIMIT $offset, $limit");
?>

<div class="admin-wrapper">
    
    <!-- Top Header -->
    <div class="admin-header">
        <div>
            <h2 class="admin-title">Discount Coupons</h2>
            <p class="admin-subtitle">Create and manage promo codes, percentage discounts, and fixed checkout coupons.</p>
        </div>
        <div>
            <a href="<?= base_url('/admin/coupons/add'); ?>" class="admin-btn-primary" style="display:inline-flex; align-items:center; gap:8px; background:#0a2e3f; color:white; padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; box-shadow:0 2px 8px rgba(10,46,63,0.15); transition:background 0.2s;">
                <span>+</span> Add New Coupon
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
        <div style="background:white; border-radius:12px; padding:20px; border:1px solid #eef2f6; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
            <div style="font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Total Coupons</div>
            <div style="font-size:26px; font-weight:700; color:#1e2d38; margin-top:4px;"><?= $allCount; ?></div>
        </div>
        <div style="background:white; border-radius:12px; padding:20px; border:1px solid #eef2f6; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
            <div style="font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Active Coupons</div>
            <div style="font-size:26px; font-weight:700; color:#059669; margin-top:4px;"><?= $activeCount; ?></div>
        </div>
        <div style="background:white; border-radius:12px; padding:20px; border:1px solid #eef2f6; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
            <div style="font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Inactive Coupons</div>
            <div style="font-size:26px; font-weight:700; color:#dc2626; margin-top:4px;"><?= $inactiveCount; ?></div>
        </div>
        <div style="background:white; border-radius:12px; padding:20px; border:1px solid #eef2f6; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
            <div style="font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Total Redemptions</div>
            <div style="font-size:26px; font-weight:700; color:#0284c7; margin-top:4px;"><?= $totalUsed; ?></div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
        <!-- Status Filter Pills -->
        <div class="status-filters" style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="<?= base_url('/admin/coupons'); ?>" class="status-pill <?= ($statusFilter === -1 && empty($typeFilter)) ? 'active' : ''; ?>" style="padding:6px 14px; border-radius:20px; font-size:13px; text-decoration:none; font-weight:500; background:<?= ($statusFilter === -1 && empty($typeFilter)) ? '#0a2e3f' : '#ffffff'; ?>; color:<?= ($statusFilter === -1 && empty($typeFilter)) ? '#ffffff' : '#4b5563'; ?>; border:1px solid #e5e7eb;">
                All Coupons <span class="count" style="margin-left:4px; opacity:0.8;"><?= $allCount; ?></span>
            </a>
            <a href="<?= base_url('/admin/coupons?status=1'); ?>" class="status-pill <?= ($statusFilter === 1) ? 'active' : ''; ?>" style="padding:6px 14px; border-radius:20px; font-size:13px; text-decoration:none; font-weight:500; background:<?= ($statusFilter === 1) ? '#0a2e3f' : '#ffffff'; ?>; color:<?= ($statusFilter === 1) ? '#ffffff' : '#4b5563'; ?>; border:1px solid #e5e7eb;">
                Active <span class="count" style="margin-left:4px; opacity:0.8;"><?= $activeCount; ?></span>
            </a>
            <a href="<?= base_url('/admin/coupons?status=0'); ?>" class="status-pill <?= ($statusFilter === 0) ? 'active' : ''; ?>" style="padding:6px 14px; border-radius:20px; font-size:13px; text-decoration:none; font-weight:500; background:<?= ($statusFilter === 0) ? '#0a2e3f' : '#ffffff'; ?>; color:<?= ($statusFilter === 0) ? '#ffffff' : '#4b5563'; ?>; border:1px solid #e5e7eb;">
                Inactive <span class="count" style="margin-left:4px; opacity:0.8;"><?= $inactiveCount; ?></span>
            </a>
        </div>

        <!-- Search Form -->
        <form method="get" action="<?= base_url('/admin/coupons'); ?>" style="display:flex; gap:8px;">
            <?php if ($statusFilter !== -1): ?>
                <input type="hidden" name="status" value="<?= $statusFilter; ?>">
            <?php endif; ?>
            <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search code or description..." style="padding:8px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; outline:none; min-width:240px;">
            <button type="submit" style="padding:8px 16px; background:#0a2e3f; color:white; border:none; border-radius:8px; font-size:13px; cursor:pointer; font-weight:500;">Search</button>
            <?php if (!empty($search)): ?>
                <a href="<?= base_url('/admin/coupons'); ?>" style="padding:8px 12px; background:#f3f4f6; color:#4b5563; border-radius:8px; font-size:13px; text-decoration:none; display:flex; align-items:center;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABLE LIST -->
    <div class="admin-table-container" style="background:#ffffff; border-radius:12px; padding:20px; box-shadow:0 2px 12px rgba(0,0,0,0.04); overflow-x:auto;">
        <table class="admin-table" style="width:100%; border-collapse:collapse; text-align:left; font-size:14px;">
            <thead>
                <tr style="border-bottom:2px solid #e5e7eb; background:#f9fafb;">
                    <th style="padding:14px 16px; font-size:12px; font-weight:600; text-transform:uppercase; color:#6b7280;">Coupon Code</th>
                    <th style="padding:14px 16px; font-size:12px; font-weight:600; text-transform:uppercase; color:#6b7280;">Discount</th>
                    <th style="padding:14px 16px; font-size:12px; font-weight:600; text-transform:uppercase; color:#6b7280;">Min. Order</th>
                    <th style="padding:14px 16px; font-size:12px; font-weight:600; text-transform:uppercase; color:#6b7280;">Validity / Expiry</th>
                    <th style="padding:14px 16px; font-size:12px; font-weight:600; text-transform:uppercase; color:#6b7280;">Usage</th>
                    <th style="padding:14px 16px; font-size:12px; font-weight:600; text-transform:uppercase; color:#6b7280;">Status</th>
                    <th style="padding:14px 16px; font-size:12px; font-weight:600; text-transform:uppercase; color:#6b7280; text-align:right;">Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $today = date('Y-m-d');
                    $isExpired = (!empty($row['end_date']) && $row['end_date'] < $today);
                    $isFuture = (!empty($row['start_date']) && $row['start_date'] > $today);
                    $isLimitReached = (!empty($row['usage_limit']) && intval($row['usage_limit']) > 0 && intval($row['used_count']) >= intval($row['usage_limit']));
                ?>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:14px 16px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font-family:'Courier New', monospace; font-size:14px; font-weight:700; background:#f1f5f9; color:#0f172a; padding:4px 10px; border-radius:6px; border:1px dashed #cbd5e1; letter-spacing:1px;">
                                    <?= htmlspecialchars($row['code']); ?>
                                </span>
                                <button type="button" onclick="copyCouponCode('<?= htmlspecialchars($row['code']); ?>', this)" title="Copy Code" style="background:none; border:none; cursor:pointer; color:#64748b; font-size:13px; padding:4px;">
                                    📋
                                </button>
                            </div>
                            <?php if (!empty($row['description'])): ?>
                                <div style="font-size:12px; color:#64748b; margin-top:4px; max-width:280px;">
                                    <?= htmlspecialchars($row['description']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 16px;">
                            <?php if ($row['type'] === 'percentage'): ?>
                                <span style="display:inline-block; background:#eff6ff; color:#1d4ed8; font-weight:700; padding:4px 10px; border-radius:6px; font-size:13px; border:1px solid #bfdbfe;">
                                    <?= rtrim(rtrim(number_format($row['value'], 2), '0'), '.'); ?>% OFF
                                </span>
                                <?php if (!empty($row['max_discount_amount'])): ?>
                                    <div style="font-size:11px; color:#6b7280; margin-top:2px;">Max $<?= number_format($row['max_discount_amount'], 2); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="display:inline-block; background:#f0fdf4; color:#15803d; font-weight:700; padding:4px 10px; border-radius:6px; font-size:13px; border:1px solid #bbf7d0;">
                                    $<?= number_format($row['value'], 2); ?> OFF
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 16px; color:#374151;">
                            <?php if (floatval($row['min_order_amount']) > 0): ?>
                                <strong>$<?= number_format($row['min_order_amount'], 2); ?></strong>
                            <?php else: ?>
                                <span style="color:#9ca3af;">No minimum</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 16px; font-size:13px;">
                            <?php if (!empty($row['start_date']) || !empty($row['end_date'])): ?>
                                <?php if (!empty($row['start_date'])): ?>
                                    <div><span style="color:#9ca3af; font-size:11px;">From:</span> <?= date('M j, Y', strtotime($row['start_date'])); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($row['end_date'])): ?>
                                    <div>
                                        <span style="color:#9ca3af; font-size:11px;">Until:</span> 
                                        <span style="<?= $isExpired ? 'color:#dc2626; font-weight:600;' : ''; ?>">
                                            <?= date('M j, Y', strtotime($row['end_date'])); ?>
                                        </span>
                                    </div>
                                    <?php if ($isExpired): ?>
                                        <span style="display:inline-block; background:#fee2e2; color:#b91c1c; font-size:10px; font-weight:600; padding:2px 6px; border-radius:4px; margin-top:2px;">Expired</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#059669; font-weight:500;">✓ Lifetime Active</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-weight:600; color:#1e293b;">
                                <?= (int)$row['used_count']; ?>
                                <span style="font-weight:400; color:#64748b; font-size:12px;">
                                    / <?= !empty($row['usage_limit']) ? (int)$row['usage_limit'] : '∞'; ?>
                                </span>
                            </div>
                            <?php if ($isLimitReached): ?>
                                <span style="display:inline-block; background:#fee2e2; color:#b91c1c; font-size:10px; font-weight:600; padding:2px 6px; border-radius:4px; margin-top:2px;">Limit Reached</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 16px;">
                            <a href="<?= base_url('/admin/coupons?action=toggle&id=' . $row['id']); ?>" 
                               title="Click to toggle status" 
                               style="text-decoration:none; display:inline-block;">
                                <?php if ((int)$row['status'] === 1 && !$isExpired && !$isLimitReached): ?>
                                    <span style="display:inline-flex; align-items:center; gap:4px; background:#dcfce7; color:#15803d; font-size:12px; font-weight:600; padding:4px 10px; border-radius:12px; border:1px solid #bbf7d0;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#22c55e;"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span style="display:inline-flex; align-items:center; gap:4px; background:#f1f5f9; color:#64748b; font-size:12px; font-weight:600; padding:4px 10px; border-radius:12px; border:1px solid #cbd5e1;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#94a3b8;"></span> Inactive
                                    </span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td style="padding:14px 16px; text-align:right;">
                            <div style="display:flex; justify-content:flex-end; gap:8px;">
                                <a href="<?= base_url('/admin/coupons/edit?id=' . $row['id']); ?>" 
                                   style="padding:6px 12px; background:#f8fafc; color:#0a2e3f; border:1px solid #cbd5e1; border-radius:6px; text-decoration:none; font-size:13px; font-weight:500; transition:background 0.2s;">
                                    Edit
                                </a>
                                <a href="<?= base_url('/admin/coupons?action=delete&id=' . $row['id']); ?>" 
                                   onclick="return confirm('Are you sure you want to permanently delete coupon [<?= htmlspecialchars($row['code']); ?>]?');" 
                                   style="padding:6px 12px; background:#fff1f2; color:#e11d48; border:1px solid #fecdd3; border-radius:6px; text-decoration:none; font-size:13px; font-weight:500; transition:background 0.2s;">
                                    Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="padding:40px 20px; text-align:center; color:#6b7280;">
                        <div style="font-size:36px; margin-bottom:8px;">🏷️</div>
                        <div style="font-size:16px; font-weight:600; color:#374151;">No Discount Coupons Found</div>
                        <p style="font-size:13px; color:#9ca3af; margin-top:4px;">Click the button above to create your first discount coupon.</p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:16px; border-top:1px solid #e5e7eb;">
                <div style="font-size:13px; color:#6b7280;">
                    Showing page <strong><?= $page; ?></strong> of <strong><?= $totalPages; ?></strong> (<?= $totalRows; ?> total coupons)
                </div>
                <div style="display:flex; gap:6px;">
                    <?php if ($page > 1): ?>
                        <a href="<?= base_url('/admin/coupons?page=' . ($page - 1) . ($statusFilter !== -1 ? '&status=' . $statusFilter : '') . (!empty($search) ? '&search=' . urlencode($search) : '')); ?>" style="padding:6px 12px; background:white; border:1px solid #d1d5db; border-radius:6px; text-decoration:none; font-size:13px; color:#374151;">&laquo; Previous</a>
                    <?php endif; ?>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?= base_url('/admin/coupons?page=' . $p . ($statusFilter !== -1 ? '&status=' . $statusFilter : '') . (!empty($search) ? '&search=' . urlencode($search) : '')); ?>" style="padding:6px 12px; border-radius:6px; text-decoration:none; font-size:13px; <?= $p === $page ? 'background:#0a2e3f; color:white; font-weight:600;' : 'background:white; border:1px solid #d1d5db; color:#374151;'; ?>"><?= $p; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= base_url('/admin/coupons?page=' . ($page + 1) . ($statusFilter !== -1 ? '&status=' . $statusFilter : '') . (!empty($search) ? '&search=' . urlencode($search) : '')); ?>" style="padding:6px 12px; background:white; border:1px solid #d1d5db; border-radius:6px; text-decoration:none; font-size:13px; color:#374151;">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function copyCouponCode(code, btn) {
    if (!navigator.clipboard) {
        const temp = document.createElement('input');
        temp.value = code;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    } else {
        navigator.clipboard.writeText(code);
    }
    const orig = btn.innerHTML;
    btn.innerHTML = '✓';
    btn.style.color = '#059669';
    setTimeout(() => {
        btn.innerHTML = orig;
        btn.style.color = '';
    }, 1200);
}
</script>
