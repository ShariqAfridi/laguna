<?php
// Extract stats passed from DashboardController
$totalSales = $stats['total_sales'] ?? 0;
$totalRevenue = $stats['total_revenue'] ?? 0;
$totalOrders = $stats['total_orders'] ?? 0;
$uniqueCustomers = $stats['total_customers'] ?? 0;
$totalProducts = $stats['total_products'] ?? 0;
$totalFragrances = $stats['total_fragrances'] ?? 0;
$totalCategories = $stats['total_categories'] ?? 0;
$totalBoxes = $stats['total_boxes'] ?? 0;
$percentageChange = $stats['percentage_change'] ?? 0;
$chartTotals = $stats['chart_totals'] ?? [];
while (count($chartTotals) < 6) {
    $chartTotals[] = 0;
}
$maxChart = max($chartTotals) ?: 1;

$conn = \get_db_connection();

// ======================= ORDERS LISTING =======================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$priceSort = isset($_GET['price_sort']) ? $_GET['price_sort'] : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

$sql = 'SELECT 
            oi.order_id, 
            oi.product_id, 
            oi.product_name, 
            oi.scent,
            oi.quantity, 
            oi.price as item_price,
            oi.subtotal,
            o.created_at as order_date,
            o.order_number,
            o.name as customer_name,
            o.total as order_total,
            o.status,
            o.payment_method,
            p.image as product_image
        FROM order_items oi
        LEFT JOIN orders o ON oi.order_id = o.id
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE 1=1';

if (!empty($search)) {
    $searchEscaped = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (o.order_number LIKE '%$searchEscaped%' OR o.name LIKE '%$searchEscaped%' OR oi.product_name LIKE '%$searchEscaped%')";
}

if ($priceSort == 'asc') {
    $sql .= ' ORDER BY oi.price ASC';
} elseif ($priceSort == 'desc') {
    $sql .= ' ORDER BY oi.price DESC';
} else {
    $sql .= ' ORDER BY oi.order_id DESC';
}

$countSql = 'SELECT COUNT(DISTINCT oi.order_id) as total FROM order_items oi LEFT JOIN orders o ON oi.order_id = o.id WHERE 1=1';
if (!empty($search)) {
    $countSql .= " AND (o.order_number LIKE '%$searchEscaped%' OR o.name LIKE '%$searchEscaped%' OR oi.product_name LIKE '%$searchEscaped%')";
}
$countResult = mysqli_query($conn, $countSql);
$totalRows = ($countResult) ? mysqli_fetch_assoc($countResult)['total'] : 0;
$totalPages = ($limit > 0) ? ceil($totalRows / $limit) : 1;
$sql .= " LIMIT $offset, $limit";

$result = mysqli_query($conn, $sql);
$orderItems = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orderItems[] = $row;
    }
}
?>

<div class="admin-wrapper">
    
    <!-- Executive Header -->
    <div class="admin-header">
        <div>
            <h1 class="admin-title">Laguna Vibe Atelier Dashboard</h1>
            <p class="admin-subtitle">Real-time candle sales, fragrance popularity, vessel inventory & business performance overview.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="<?= base_url('/admin/add_product'); ?>" class="admin-btn-primary">+ Add Candle Product</a>
            <a href="<?= base_url('/admin/fragrance/add'); ?>" class="admin-btn-secondary">+ New Scent Profile</a>
        </div>
    </div>

    <!-- Executive KPI Grid -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:24px;">
        
        <div style="background:#fff; padding:22px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.04); border-top:4px solid #10b981;">
            <div style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">Total Candle Revenue</div>
            <div style="font-size:28px; font-weight:800; color:#1f2c35; margin:10px 0 4px 0;">$<?= number_format($totalRevenue, 2); ?></div>
            <div style="font-size:12px; color:#10b981; font-weight:600;">📈 Total sales across all candle orders</div>
        </div>

        <div style="background:#fff; padding:22px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.04); border-top:4px solid #3b82f6;">
            <div style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">Total Orders</div>
            <div style="font-size:28px; font-weight:800; color:#1f2c35; margin:10px 0 4px 0;"><?= number_format($totalOrders); ?></div>
            <div style="font-size:12px; color:#3b82f6; font-weight:600;">📦 Completed & active customer orders</div>
        </div>

        <div style="background:#fff; padding:22px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.04); border-top:4px solid #8b5cf6;">
            <div style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">Candle Products</div>
            <div style="font-size:28px; font-weight:800; color:#1f2c35; margin:10px 0 4px 0;"><?= number_format($totalProducts); ?></div>
            <div style="font-size:12px; color:#8b5cf6; font-weight:600;">🕯️ Products in atelier catalog</div>
        </div>

        <div style="background:#fff; padding:22px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.04); border-top:4px solid #f59e0b;">
            <div style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">Fragrances & Vessels</div>
            <div style="font-size:28px; font-weight:800; color:#1f2c35; margin:10px 0 4px 0;"><?= $totalFragrances; ?> Scents / <?= $totalCategories; ?> Vessels</div>
            <div style="font-size:12px; color:#f59e0b; font-weight:600;">🌸 Active fragrance profiles & vessel sizes</div>
        </div>

    </div>

    <!-- Quick Action Hub & Revenue Chart -->
    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px; margin-bottom:24px;">
        
        <!-- Sales Performance Bar Chart -->
        <div class="admin-card" style="margin-bottom:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="font-family:'Cinzel', serif; font-size:18px; color:#1f2c35;">Candle Sales Performance Trend</h3>
                <span style="font-size:12px; background:#f3f4f6; padding:4px 10px; border-radius:6px; color:#4b5563; font-weight:600;">Recent Transactions</span>
            </div>
            
            <div style="display:flex; align-items:flex-end; gap:16px; height:180px; padding-top:20px; border-bottom:1px solid #e5e7eb;">
                <?php
                foreach ($chartTotals as $idx => $val):
                    $heightPct = round(($val / $maxChart) * 100);
                    if ($heightPct < 15)
                        $heightPct = 15;    
                    ?>
                    <div style="flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end;">
                        <div style="font-size:11px; font-weight:700; color:#374151; margin-bottom:6px;">$<?= number_format($val, 0); ?></div>
                        <div style="width:100%; max-width:40px; height:<?= $heightPct; ?>%; background:linear-gradient(to top, #1f2c35, #374151); border-radius:6px 6px 0 0;"></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex; justify-content:space-around; margin-top:10px; font-size:12px; color:#9ca3af;">
                <span>Order #1</span><span>Order #2</span><span>Order #3</span><span>Order #4</span><span>Order #5</span><span>Order #6</span>
            </div>
        </div>

        <!-- Atelier Management Quick Hub -->
        <div class="admin-card" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <h3 style="font-family:'Cinzel', serif; font-size:18px; color:#1f2c35; margin-bottom:16px;">Quick Management Hub</h3>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <a href="<?= base_url('/admin/categories'); ?>" style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f9fafb; border-radius:8px; text-decoration:none; color:#1f2c35; font-weight:600; font-size:14px; border:1px solid #e5e7eb;">
                        <span>🏺 Vessel Categories</span>
                        <span style="color:#6b7280; font-size:12px;"><?= $totalCategories; ?> Active &rarr;</span>
                    </a>
                    <a href="<?= base_url('/admin/fragrance'); ?>" style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f9fafb; border-radius:8px; text-decoration:none; color:#1f2c35; font-weight:600; font-size:14px; border:1px solid #e5e7eb;">
                        <span>🌸 Fragrance Scents</span>
                        <span style="color:#6b7280; font-size:12px;"><?= $totalFragrances; ?> Profiles &rarr;</span>
                    </a>
                    <a href="<?= base_url('/admin/colors'); ?>" style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f9fafb; border-radius:8px; text-decoration:none; color:#1f2c35; font-weight:600; font-size:14px; border:1px solid #e5e7eb;">
                        <span>🎨 Color Variants</span>
                        <span style="color:#6b7280; font-size:12px;">Manage Swatches &rarr;</span>
                    </a>
                    <a href="<?= base_url('/admin/boxes'); ?>" style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f9fafb; border-radius:8px; text-decoration:none; color:#1f2c35; font-weight:600; font-size:14px; border:1px solid #e5e7eb;">
                        <span>🎁 Packaging Boxes</span>
                        <span style="color:#6b7280; font-size:12px;"><?= $totalBoxes; ?> Designs &rarr;</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Candle Orders Data Table -->
    <div class="admin-table-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
            <h3 style="font-family:'Cinzel', serif; font-size:18px; color:#1f2c35;">Recent Candle Orders</h3>
            
            <form method="get" action="<?= base_url('/admin/dashboard'); ?>" style="display:flex; gap:10px;">
                <input type="text" name="search" class="admin-input" value="<?= htmlspecialchars($search); ?>" placeholder="Search Order # or Customer..." style="padding:8px 12px; width:220px;">
                <button type="submit" class="admin-btn-primary" style="padding:8px 16px;">Search</button>
            </form>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Candle Product</th>
                    <th>Scent / Profile</th>
                    <th>Total ($)</th>
                    <th>Status</th>
                    <th style="text-align:right;">Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($orderItems)): ?>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td><strong style="color:#111827;"><?= htmlspecialchars($item['order_number'] ?? ('#' . $item['order_id'])); ?></strong></td>
                        <td style="color:#374151;"><?= htmlspecialchars($item['customer_name'] ?? 'Guest Customer'); ?></td>
                        <td style="color:#111827; font-weight:500;"><?= htmlspecialchars($item['product_name']); ?></td>
                        <td style="color:#6b7280;"><?= htmlspecialchars($item['scent'] ?? 'Custom Blend'); ?></td>
                        <td style="font-weight:700; color:#111827;">$<?= number_format((float) ($item['subtotal'] ?? $item['item_price']), 2); ?></td>
                        <td>
                            <?php
                            $st = strtolower($item['status'] ?? 'pending');
                            $class = 'admin-badge-inactive';
                            if ($st === 'completed' || $st === 'delivered') {
                                $class = 'admin-badge-active';
                            }
                            ?>
                            <span class="<?= $class; ?>" style="text-transform:capitalize;"><?= htmlspecialchars($st); ?></span>
                        </td>
                        <td style="text-align:right; color:#6b7280; font-size:13px;"><?= date('M d, Y', strtotime($item['order_date'] ?? 'now')); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:30px; color:#9ca3af;">No recent orders found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="admin-pagination">
                <div>Showing <?= min($offset + 1, $totalRows); ?> to <?= min($offset + $limit, $totalRows); ?> of <?= $totalRows; ?> orders</div>
                <div class="admin-pagination-pages">
                    <a href="<?= base_url('/admin/dashboard?page=' . max(1, $page - 1) . ($search ? '&search=' . urlencode($search) : '')); ?>" class="admin-page-link <?= $page <= 1 ? 'disabled' : ''; ?>">&laquo; Prev</a>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?= base_url('/admin/dashboard?page=' . $p . ($search ? '&search=' . urlencode($search) : '')); ?>" class="admin-page-link <?= $p == $page ? 'active' : ''; ?>"><?= $p; ?></a>
                    <?php endfor; ?>
                    <a href="<?= base_url('/admin/dashboard?page=' . min($totalPages, $page + 1) . ($search ? '&search=' . urlencode($search) : '')); ?>" class="admin-page-link <?= $page >= $totalPages ? 'disabled' : ''; ?>">Next &raquo;</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>