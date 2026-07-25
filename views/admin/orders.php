<?php
require_once __DIR__ . '/../../db.php';

// ─── Handle status update ─────────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_order_id'], $_POST['update_status'])) {
    $orderId   = (int) $_POST['update_order_id'];
    $newStatus = trim($_POST['update_status']);
    $allowed   = ['processing', 'shipped', 'delivered', 'cancelled', 'pending', 'refunded'];

    if ($orderId > 0 && in_array($newStatus, $allowed)) {
        $s = mysqli_real_escape_string($conn, $newStatus);
        mysqli_query($conn, "UPDATE orders SET status = '$s' WHERE id = $orderId");
        $updateMsg = mysqli_affected_rows($conn) >= 0 ? 'success' : 'error';
    } else {
        $updateMsg = 'error';
    }

    $redirect = '?updated=' . $updateMsg;
    if (!empty($_POST['current_page']))   $redirect .= '&page='   . (int)$_POST['current_page'];
    if (!empty($_POST['current_status'])) $redirect .= '&status=' . urlencode($_POST['current_status']);
    if (!empty($_POST['current_search'])) $redirect .= '&search=' . urlencode($_POST['current_search']);
   
echo "<script>window.location.href='/admin/orders';</script>";
exit();
}

// ─── Pagination & filters ─────────────────────────────────────────────────────
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 10;
$offset = ($page - 1) * $limit;

$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$searchTerm   = isset($_GET['search']) ? trim($_GET['search']) : '';

// ─── Main query ───────────────────────────────────────────────────────────────
$sql = "SELECT o.id, o.order_number, o.total, o.status, o.created_at,
               o.email, o.name as full_name, o.address, o.city,
               o.state, o.zip, o.phone, o.notes, o.promo_code,
               o.subtotal, o.shipping, o.discount, o.payment_method,
               COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE 1=1";

$se = ''; $st = '';
if (!empty($statusFilter) && $statusFilter != 'all') {
    $se = mysqli_real_escape_string($conn, $statusFilter);
    $sql .= " AND o.status = '$se'";
}
if (!empty($searchTerm)) {
    $st = mysqli_real_escape_string($conn, $searchTerm);
    $sql .= " AND (o.order_number LIKE '%$st%' OR o.name LIKE '%$st%'
                OR o.email LIKE '%$st%' OR o.address LIKE '%$st%')";
}
$sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset";

$countSql = "SELECT COUNT(DISTINCT o.id) as total FROM orders o WHERE 1=1";
if (!empty($se)) $countSql .= " AND o.status = '$se'";
if (!empty($st)) $countSql .= " AND (o.order_number LIKE '%$st%' OR o.name LIKE '%$st%'
                                   OR o.email LIKE '%$st%' OR o.address LIKE '%$st%')";

$countResult = mysqli_query($conn, $countSql);
$totalOrders = $countResult ? mysqli_fetch_assoc($countResult)['total'] : 0;
$totalPages  = ceil($totalOrders / $limit);

$result = mysqli_query($conn, $sql);
$orders = [];
if ($result && mysqli_num_rows($result) > 0)
    while ($row = mysqli_fetch_assoc($result)) $orders[] = $row;

// ─── For each order, pre-fetch its items ─────────────────────────────────────
$orderItems = [];
foreach ($orders as $o) {
    $oid  = (int)$o['id'];
    $iRes = mysqli_query($conn,
        "SELECT oi.product_name, oi.scent, oi.quantity, oi.price, oi.subtotal,
                p.image, p.color_id, p.size_id, p.fragrance_id
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.product_id
         WHERE oi.order_id = $oid");
    $orderItems[$oid] = [];
    if ($iRes) while ($row = mysqli_fetch_assoc($iRes)) $orderItems[$oid][] = $row;
}

// ─── Status counts ────────────────────────────────────────────────────────────
$statusCounts = [];
$statusResult = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = mysqli_fetch_assoc($statusResult)) $statusCounts[$row['status']] = $row['count'];
$totalAll = array_sum($statusCounts);

function qs($page, $status, $search) {
    $q = '?page=' . $page;
    if (!empty($status)) $q .= '&status=' . urlencode($status);
    if (!empty($search)) $q .= '&search=' . urlencode($search);
    return $q;
}

function statusBadgeClass($sk) {
    if (in_array($sk, ['delivered','completed','paid'])) return 's-delivered';
    if (in_array($sk, ['processing','shipped']))         return 's-processing';
    if (in_array($sk, ['pending','pending_payment']))    return 's-pending';
    if ($sk === 'cancelled') return 's-cancelled';
    if ($sk === 'refunded')  return 's-refunded';
    return 's-default';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | LVB Atelier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f1f3f6; }
        .main-content { padding:24px 0px; }
        .orders-section { background:#f7f7f7; border-radius:16px; padding:30px; min-height:80vh; color:#1a1a1a; }

        /* Toast */
        .toast { display:none; padding:12px 22px; border-radius:10px; margin-bottom:20px;
                 font-size:.9rem; align-items:center; gap:10px; }
        .toast.show    { display:flex; }
        .toast.success { background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; }
        .toast.error   { background:#ffebee; color:#c62828; border:1px solid #ef9a9a; }

        /* Header */
        .orders-header { display:flex; justify-content:space-between; align-items:center;
                         margin-bottom:25px; flex-wrap:wrap; gap:15px; }
        .orders-header h2 { font-size:1.5rem; font-weight:700; }
        .orders-controls  { display:flex; gap:10px; flex-wrap:wrap; }

        .search-box { display:flex; align-items:center; background:#fff; border-radius:25px;
                      border:1px solid #e0e0e0; padding:5px 15px; }
        .search-box input  { border:none; padding:8px 10px; outline:none; width:200px; font-size:14px; }
        .search-box button { background:none; border:none; color:#888; cursor:pointer; }

        .btn-reset  { background:#e0e0e0; border:none; padding:8px 15px; border-radius:20px;
                      font-size:.8rem; cursor:pointer; color:#555; text-decoration:none;
                      display:inline-flex; align-items:center; gap:6px; }
        .btn-reset:hover { background:#ccc; }
        .export-btn { background:#2ecc71; color:#fff; border:none; padding:8px 15px; border-radius:20px;
                      font-size:.85rem; display:flex; align-items:center; gap:8px; cursor:pointer; }
        .export-btn:hover { background:#27ae60; }

        /* Status pills */
        .status-filters { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
        .status-pill { background:#fff; border:1px solid #e0e0e0; padding:6px 16px; border-radius:30px;
                       font-size:.8rem; color:#555; text-decoration:none; display:inline-block; transition:all .2s; }
        .status-pill:hover  { background:#e8e8e8; }
        .status-pill.active { background:#1a1a1a; color:#fff; border-color:#1a1a1a; }
        .status-pill .count { background:rgba(0,0,0,.1); border-radius:20px; padding:2px 8px; margin-left:8px; font-size:.7rem; }
        .status-pill.active .count { background:rgba(255,255,255,.2); }

        /* Table */
        .orders-card  { background:#fff; border-radius:16px; overflow-x:auto; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .orders-table { width:100%; border-collapse:collapse; min-width:800px; }
        .orders-table th { text-align:left; padding:16px 20px; border-bottom:1px solid #f0f0f0;
                           font-size:.8rem; color:#888; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .orders-table td { padding:16px 20px; border-bottom:1px solid #f5f5f5; font-size:.9rem;
                           color:#333; vertical-align:middle; }
        .orders-table tr:hover td { background:#fafafa; }
        .total-cell   { font-weight:700; white-space:nowrap; }
        .date-cell    { white-space:nowrap; font-size:.85rem; color:#666; }
        .address-cell { max-width:280px; line-height:1.4; font-size:.85rem; color:#555; }

        /* Status badges */
        .status-badge { display:inline-block; padding:4px 12px; border-radius:30px; font-size:.75rem; font-weight:600; white-space:nowrap; }
        .s-delivered,.s-completed,.s-paid { background:#e8f5e9; color:#2e7d32; }
        .s-processing,.s-shipped          { background:#e3f2fd; color:#1565c0; }
        .s-pending,.s-pending_payment     { background:#fff3e0; color:#e65100; }
        .s-cancelled                      { background:#ffebee; color:#c62828; }
        .s-refunded                       { background:#f3e5f5; color:#6a1b9a; }
        .s-default                        { background:#e0e0e0; color:#424242; }

        /* Dropdown */
        .status-wrap { position:relative; display:inline-block; }
        .status-wrap > input[type=checkbox] { display:none; }
        .status-wrap > label { cursor:pointer; background:none; border:none; font-size:1.1rem;
                               color:#999; padding:6px 10px; border-radius:8px; display:inline-block;
                               transition:all .2s; user-select:none; }
        .status-wrap > label:hover { background:#f0f0f0; color:#333; }
        .status-menu { display:none; position:absolute; right:0; top:38px; background:#fff;
                       border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.15);
                       min-width:180px; z-index:999; overflow:hidden; }
        .status-wrap > input[type=checkbox]:checked ~ .status-menu { display:block; }
        .status-menu form   { margin:0; padding:0; }
        .status-menu button { display:flex; align-items:center; gap:10px; width:100%;
                              padding:11px 16px; border:none; background:none; cursor:pointer;
                              font-size:.85rem; color:#333; text-align:left; transition:background .2s; }
        .status-menu button:hover { background:#f5f5f5; }
        .status-menu button i { width:18px; color:#888; font-size:.9rem; }
        .btn-cancel   { color:#c62828 !important; }
        .btn-cancel i { color:#c62828 !important; }

        .view-btn { background:none; border:none; cursor:pointer; font-size:1.1rem;
                    color:#3498db; padding:6px 10px; border-radius:8px; transition:all .2s; }
        .view-btn:hover { background:#e3f2fd; }

        /* Pagination */
        .pagination { display:flex; justify-content:center; gap:8px; margin-top:30px; flex-wrap:wrap; }
        .pagination a,.pagination span { padding:8px 14px; border-radius:10px; text-decoration:none;
                                          color:#555; background:#fff; border:1px solid #e0e0e0; font-size:.85rem; transition:all .2s; }
        .pagination a:hover   { background:#1a1a1a; color:#fff; border-color:#1a1a1a; }
        .pagination .active   { background:#1a1a1a; color:#fff; border-color:#1a1a1a; }
        .pagination .disabled { opacity:.5; pointer-events:none; }

        .empty-state   { text-align:center; padding:60px 20px; color:#888; }
        .empty-state i { font-size:48px; margin-bottom:15px; color:#ccc; display:block; }

        /* ═══════════════════ MODAL ═══════════════════ */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.55); z-index:2000;
            align-items:center; justify-content:center; padding:16px;
        }
        .modal-overlay.open { display:flex; animation:mFadeIn .2s ease; }
        @keyframes mFadeIn { from{opacity:0} to{opacity:1} }

        .modal-box {
            background:#fff; border-radius:20px;
            width:100%; max-width:800px; max-height:92vh; overflow-y:auto;
            box-shadow:0 20px 60px rgba(0,0,0,.25);
            animation:mSlideUp .25s ease;
        }
        @keyframes mSlideUp { from{transform:translateY(28px);opacity:0} to{transform:translateY(0);opacity:1} }

        .modal-header {
            padding:22px 28px 16px; border-bottom:1px solid #f0f0f0;
            display:flex; justify-content:space-between; align-items:flex-start;
            position:sticky; top:0; background:#fff; z-index:10; border-radius:20px 20px 0 0;
        }
        .modal-header h3 { font-size:1.15rem; font-weight:700; color:#1a1a1a; }
        .modal-header .modal-meta { font-size:.82rem; color:#888; margin-top:6px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .modal-close { background:none; border:none; font-size:1.4rem; color:#aaa;
                       cursor:pointer; padding:4px 8px; border-radius:8px; transition:all .2s; flex-shrink:0; }
        .modal-close:hover { background:#f5f5f5; color:#333; }

        .modal-body { padding:24px 28px; }

        /* Customer grid */
        .customer-strip {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr));
            gap:14px; background:#f8f9fa; border-radius:14px; padding:18px 20px; margin-bottom:22px;
        }
        .cs-item label { font-size:.68rem; text-transform:uppercase; letter-spacing:.6px; color:#999; display:block; margin-bottom:4px; }
        .cs-item span  { font-size:.87rem; font-weight:600; color:#1a1a1a; word-break:break-word; }

        /* Totals strip */
        .totals-strip { display:flex; flex-wrap:wrap; border:1px solid #f0f0f0; border-radius:14px; overflow:hidden; margin-bottom:22px; }
        .ts-item { flex:1 1 110px; padding:14px 16px; border-right:1px solid #f0f0f0; text-align:center; }
        .ts-item:last-child { border-right:none; }
        .ts-item label { font-size:.68rem; text-transform:uppercase; letter-spacing:.6px; color:#999; display:block; margin-bottom:5px; }
        .ts-item span  { font-size:1rem; font-weight:700; color:#1a1a1a; }
        .ts-item.highlight span { color:#27ae60; }

        .section-label { font-size:.73rem; text-transform:uppercase; letter-spacing:.7px; color:#999; font-weight:600; margin-bottom:12px; display:block; }

        /* Product cards */
        .product-list { display:flex; flex-direction:column; gap:12px; }
        .product-card {
            display:flex; gap:16px; align-items:flex-start;
            border:1px solid #f0f0f0; border-radius:14px;
            padding:14px 16px; background:#fafafa; transition:box-shadow .2s;
        }
        .product-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); background:#fff; }

        .product-img {
            width:76px; height:76px; object-fit:cover; border-radius:10px;
            flex-shrink:0; border:1px solid #eee; background:#f0f0f0;
        }
        .product-img-placeholder {
            width:76px; height:76px; border-radius:10px; flex-shrink:0;
            background:#f0f0f0; display:flex; align-items:center;
            justify-content:center; color:#ccc; font-size:1.5rem;
        }
        .product-info { flex:1; min-width:0; }
        .product-info h4 { font-size:.95rem; font-weight:700; color:#1a1a1a; margin-bottom:7px; }

        .product-meta { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:9px; }
        .meta-tag { display:inline-flex; align-items:center; gap:5px; background:#fff;
                    border:1px solid #e8e8e8; border-radius:20px; padding:3px 10px; font-size:.74rem; color:#555; }
        .meta-tag i { font-size:.68rem; color:#bbb; }

        .product-price-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:.85rem; }
        .qty-badge  { background:#1a1a1a; color:#fff; border-radius:20px; padding:3px 10px; font-size:.78rem; font-weight:600; }
        .unit-price { color:#888; }
        .line-total  { font-weight:700; color:#1a1a1a; margin-left:auto; font-size:.95rem; }

        /* Info boxes */
        .info-box { border-radius:12px; padding:13px 17px; font-size:.85rem; margin-top:14px; display:flex; gap:10px; align-items:flex-start; }
        .info-box.note  { background:#fffbf0; border:1px solid #fde68a; color:#78350f; }
        .info-box.note  i { color:#f59e0b; margin-top:2px; }
        .info-box.promo { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
        .info-box.promo i { color:#22c55e; margin-top:2px; }

        @media(max-width:768px){
            .main-content { padding:15px; }
            .orders-section { padding:20px; }
            .orders-header  { flex-direction:column; align-items:flex-start; }
            .modal-body     { padding:16px; }
            .modal-header   { padding:16px 16px 12px; }
            .customer-strip { grid-template-columns:1fr 1fr; }
            .ts-item        { flex:1 1 90px; }
        }
    </style>
</head>
<body>
<div class="main-content">
<div class="orders-section">

    <?php if (isset($_GET['updated'])): ?>
        <div class="toast <?= $_GET['updated'] === 'success' ? 'success' : 'error' ?> show">
            <i class="fas <?= $_GET['updated'] === 'success' ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
            <?= $_GET['updated'] === 'success' ? 'Order status updated successfully!' : 'Failed to update status. Please try again.' ?>
        </div>
    <?php endif; ?>

    <div class="orders-header">
        <h2><i class="fas fa-shopping-bag"></i> Orders History</h2>
        <div class="orders-controls">
            <form method="GET" action="" style="display:flex;gap:10px;flex-wrap:wrap;">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by name, order #, address..."
                           value="<?= htmlspecialchars($searchTerm) ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                <button type="button" class="export-btn" onclick="exportToCSV()">
                    <i class="fas fa-download"></i> Export
                </button>
                <?php if (!empty($searchTerm) || !empty($statusFilter)): ?>
                    <a href="?" class="btn-reset"><i class="fas fa-times"></i> Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="status-filters">
        <a href="?" class="status-pill <?= empty($statusFilter) ? 'active' : '' ?>">
            All <span class="count"><?= $totalAll ?></span>
        </a>
        <?php foreach ($statusCounts as $status => $count): ?>
            <a href="?status=<?= urlencode($status) ?><?= !empty($searchTerm) ? '&search='.urlencode($searchTerm) : '' ?>"
               class="status-pill <?= $statusFilter == $status ? 'active' : '' ?>">
                <?= ucfirst(str_replace('_', ' ', $status)) ?>
                <span class="count"><?= $count ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="orders-card">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>S.No</th><th>Full Name</th><th>Address</th>
                    <th>Total Amount</th><th>Date</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($orders)):
                $sn = $offset + 1;
                foreach ($orders as $order):
                    $sk = strtolower($order['status']);
                    $sc = statusBadgeClass($sk);

                    $addr = $order['address'];
                    if (!empty($order['city']))  $addr .= ', '  . $order['city'];
                    if (!empty($order['state'])) $addr .= ', '  . $order['state'];
                    if (!empty($order['zip']))   $addr .= ' - ' . $order['zip'];
            ?>
            <tr>
                <td><?= $sn++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($order['full_name']) ?></strong><br>
                    <small style="color:#888;font-size:.75rem;"><?= htmlspecialchars($order['email']) ?></small>
                </td>
                <td class="address-cell">
                    <i class="fas fa-map-marker-alt" style="color:#aaa;margin-right:5px;"></i>
                    <?= htmlspecialchars($addr) ?>
                    <?php if (!empty($order['phone'])): ?>
                        <br><small style="color:#888;"><i class="fas fa-phone"></i> <?= htmlspecialchars($order['phone']) ?></small>
                    <?php endif; ?>
                </td>
                <td class="total-cell">$ <?= number_format($order['total'], 2) ?></td>
                <td class="date-cell">
                    <i class="far fa-calendar-alt" style="color:#aaa;margin-right:5px;"></i>
                    <?= date('d M Y', strtotime($order['created_at'])) ?>
                </td>
                <td>
                    <span class="status-badge <?= $sc ?>">
                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                    </span>
                </td>
                <td style="white-space:nowrap;">
                    <!-- Eye button opens the pre-built modal for this order -->
                    <button class="view-btn" title="View Order"
                            onclick="openModal('modal-<?= $order['id'] ?>')">
                        <i class="fas fa-eye"></i>
                    </button>

                    <div class="status-wrap">
                        <input type="checkbox" id="cb-<?= $order['id'] ?>">
                        <label for="cb-<?= $order['id'] ?>" title="Change Status">
                            <i class="fas fa-ellipsis-v"></i>
                        </label>
                        <div class="status-menu">
                            <?php
                            $actions = [
                                ['processing','fa-sync-alt',    'Mark Processing',''],
                                ['shipped',   'fa-truck',        'Mark Shipped',   ''],
                                ['delivered', 'fa-check-circle', 'Mark Delivered', ''],
                                ['cancelled', 'fa-ban',          'Cancel Order',   'btn-cancel'],
                            ];
                            foreach ($actions as [$val,$icon,$label,$cls]):
                            ?>
                            <form method="POST" action="">
                                <input type="hidden" name="update_order_id"  value="<?= $order['id'] ?>">
                                <input type="hidden" name="update_status"    value="<?= $val ?>">
                                <input type="hidden" name="current_page"     value="<?= $page ?>">
                                <input type="hidden" name="current_status"   value="<?= htmlspecialchars($statusFilter) ?>">
                                <input type="hidden" name="current_search"   value="<?= htmlspecialchars($searchTerm) ?>">
                                <button type="submit" class="<?= $cls ?>">
                                    <i class="fas <?= $icon ?>"></i> <?= $label ?>
                                </button>
                            </form>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach;
            else: ?>
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No orders found</p>
                    <?php if (!empty($searchTerm) || !empty($statusFilter)): ?>
                        <p style="font-size:.85rem;">Try clearing your filters</p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?= qs($page-1,$statusFilter,$searchTerm) ?>">&laquo; Previous</a>
        <?php else: ?>
            <span class="disabled">&laquo; Previous</span>
        <?php endif; ?>

        <?php for ($i=max(1,$page-2); $i<=min($totalPages,$page+2); $i++): ?>
            <?php if ($i==$page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= qs($i,$statusFilter,$searchTerm) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= qs($page+1,$statusFilter,$searchTerm) ?>">Next &raquo;</a>
        <?php else: ?>
            <span class="disabled">Next &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- /orders-section -->
</div><!-- /main-content -->

<!-- ═══════════════════ MODALS — one per order, built by PHP ═══════════════════ -->
<?php foreach ($orders as $order):
    $oid   = $order['id'];
    $sk    = strtolower($order['status']);
    $sc    = statusBadgeClass($sk);
    $items = $orderItems[$oid] ?? [];

    $addrParts = array_filter([$order['address'], $order['city'], $order['state']]);
    if (!empty($order['zip'])) $addrParts[] = $order['zip'];
    $fullAddr = implode(', ', $addrParts);

    $disc = (float)($order['discount'] ?? 0);
?>
<div class="modal-overlay" id="modal-<?= $oid ?>" onclick="if(event.target===this)closeModal('modal-<?= $oid ?>')">
    <div class="modal-box">

        <!-- Header -->
        <div class="modal-header">
            <div>
                <h3><i class="fas fa-receipt" style="margin-right:8px;color:#888;font-size:1rem;"></i>
                    Order #<?= htmlspecialchars($order['order_number'] ?: $oid) ?>
                </h3>
                <div class="modal-meta">
                    <span class="status-badge <?= $sc ?>"><?= ucfirst(str_replace('_',' ',$order['status'])) ?></span>
                    <span style="color:#ddd;">|</span>
                    <i class="far fa-calendar-alt"></i>
                    <?= date('d M Y', strtotime($order['created_at'])) ?>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal('modal-<?= $oid ?>')" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">

            <!-- Customer info -->
            <div class="customer-strip">
                <div class="cs-item">
                    <label>Customer</label>
                    <span><?= htmlspecialchars($order['full_name']) ?></span>
                </div>
                <div class="cs-item">
                    <label>Email</label>
                    <span><?= htmlspecialchars($order['email'] ?: '—') ?></span>
                </div>
                <div class="cs-item">
                    <label>Phone</label>
                    <span><?= htmlspecialchars($order['phone'] ?: '—') ?></span>
                </div>
                <div class="cs-item">
                    <label>Payment</label>
                    <span><?= htmlspecialchars(ucfirst(str_replace('_',' ',$order['payment_method'] ?: '—'))) ?></span>
                </div>
                <div class="cs-item" style="grid-column:1/-1;">
                    <label>Delivery Address</label>
                    <span><?= htmlspecialchars($fullAddr ?: '—') ?></span>
                </div>
            </div>

            <!-- Totals -->
            <div class="totals-strip">
                <div class="ts-item">
                    <label>Subtotal</label>
                    <span>$ <?= number_format($order['subtotal'] ?? 0, 2) ?></span>
                </div>
                <div class="ts-item">
                    <label>Shipping</label>
                    <span>$ <?= number_format($order['shipping'] ?? 0, 2) ?></span>
                </div>
                <div class="ts-item">
                    <label>Discount</label>
                    <span <?= $disc > 0 ? 'style="color:#e53935;"' : '' ?>>
                        <?= $disc > 0 ? '− Rs '.number_format($disc,2) : '—' ?>
                    </span>
                </div>
                <div class="ts-item highlight">
                    <label>Grand Total</label>
                    <span>$ <?= number_format($order['total'], 2) ?></span>
                </div>
            </div>

            <!-- Items -->
            <span class="section-label">
                <i class="fas fa-box-open" style="margin-right:6px;"></i>
                Items (<?= count($items) ?>)
            </span>
            <div class="product-list">
            <?php if (!empty($items)): foreach ($items as $item): ?>
                <div class="product-card">
                    <?php if (!empty($item['image'])): ?>
                        <img class="product-img"
                             src="../img/<?= htmlspecialchars($item['image']) ?>"
                             alt="<?= htmlspecialchars($item['product_name']) ?>"
                             onerror="this.outerHTML='<div class=\'product-img-placeholder\'><i class=\'fas fa-image\'></i></div>'">
                    <?php else: ?>
                        <div class="product-img-placeholder"><i class="fas fa-image"></i></div>
                    <?php endif; ?>

                    <div class="product-info">
                        <h4><?= htmlspecialchars($item['product_name']) ?></h4>

                        <div class="product-meta">
                            <?php if (!empty($item['scent'])): ?>
                                <span class="meta-tag"><i class="fas fa-wind"></i> Scent: <?= htmlspecialchars($item['scent']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['color_id'])): ?>
                                <span class="meta-tag"><i class="fas fa-palette"></i> Color: <?= htmlspecialchars($item['color_id']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['size_id'])): ?>
                                <span class="meta-tag"><i class="fas fa-ruler"></i> Size: <?= htmlspecialchars($item['size_id']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['fragrance_id'])): ?>
                                <span class="meta-tag"><i class="fas fa-flask"></i> Fragrance: <?= htmlspecialchars($item['fragrance_id']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="product-price-row">
                            <span class="qty-badge">× <?= (int)$item['quantity'] ?></span>
                            <span class="unit-price">$ <?= number_format($item['price'], 2) ?> each</span>
                            <span class="line-total">$ <?= number_format($item['subtotal'], 2) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <p style="color:#aaa;font-size:.9rem;padding:10px 0;">No items found for this order.</p>
            <?php endif; ?>
            </div>

            <!-- Notes -->
            <?php if (!empty($order['notes'])): ?>
                <div class="info-box note">
                    <i class="fas fa-sticky-note"></i>
                    <div><strong>Order Notes:</strong><br><?= htmlspecialchars($order['notes']) ?></div>
                </div>
            <?php endif; ?>

            <!-- Promo -->
            <?php if (!empty($order['promo_code'])): ?>
                <div class="info-box promo">
                    <i class="fas fa-tag"></i>
                    <div><strong>Promo Applied:</strong> <?= htmlspecialchars($order['promo_code']) ?></div>
                </div>
            <?php endif; ?>

        </div><!-- /modal-body -->
    </div><!-- /modal-box -->
</div><!-- /modal-overlay -->
<?php endforeach; ?>

<script>
/* ── Dropdown close on outside click ── */
document.addEventListener('click', function(e) {
    if (!e.target.closest('.status-wrap'))
        document.querySelectorAll('.status-wrap input[type=checkbox]')
                .forEach(cb => cb.checked = false);
});

/* ── Auto-hide toast ── */
window.addEventListener('load', function() {
    const t = document.querySelector('.toast.show');
    if (t) setTimeout(() => t.style.display = 'none', 4000);
});

/* ── Modal open / close ── */
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open')
                .forEach(m => { m.classList.remove('open'); document.body.style.overflow = ''; });
    }
});

/* ── CSV export ── */
function exportToCSV() {
    let csv = "S.No,Full Name,Address,Total Amount,Date,Status\n";
    document.querySelectorAll('.orders-table tbody tr').forEach(row => {
        const c = row.querySelectorAll('td');
        if (c.length < 7) return;
        csv += [
            c[0].innerText.trim(),
            '"' + c[1].innerText.replace(/"/g,'""').trim() + '"',
            '"' + c[2].innerText.replace(/"/g,'""').trim() + '"',
            c[3].innerText.trim(),
            c[4].innerText.trim(),
            c[5].innerText.trim()
        ].join(',') + '\n';
    });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], {type:'text/csv;charset=utf-8;'}));
    a.download = 'orders_export.csv';
    a.click();
}
</script>
</body>
</html>