<?php include 'db.php';

// ======================= DASHBOARD STATS (from orders table) =======================
// Total sales (sum of total from orders where status != cancelled)
$salesQuery = "SELECT COALESCE(SUM(total), 0) as total_sales FROM orders WHERE status != 'cancelled'";
$salesRes = mysqli_query($conn, $salesQuery);
$totalSales = mysqli_fetch_assoc($salesRes)['total_sales'] ?? 0;

// Total revenue (same logic but using all non-cancelled)
$revenueQuery = "SELECT COALESCE(SUM(total), 0) as revenue FROM orders WHERE status NOT IN ('cancelled')";
$revRes = mysqli_query($conn, $revenueQuery);
$totalRevenue = mysqli_fetch_assoc($revRes)['revenue'] ?? 0;

// Total orders count
$ordersCountQuery = "SELECT COUNT(*) as total_orders FROM orders";
$ordersCountRes = mysqli_query($conn, $ordersCountQuery);
$totalOrders = mysqli_fetch_assoc($ordersCountRes)['total_orders'] ?? 0;

// Unique customers
$customersQuery = "SELECT COUNT(DISTINCT email) as unique_customers FROM orders";
$custRes = mysqli_query($conn, $customersQuery);
$uniqueCustomers = mysqli_fetch_assoc($custRes)['unique_customers'] ?? 0;

// trend (last 30 days vs previous 30 days)
$trendQuery = "SELECT 
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN total ELSE 0 END) as current_period,
    SUM(CASE WHEN created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY) THEN total ELSE 0 END) as previous_period
    FROM orders WHERE status != 'cancelled'";
$trendRes = mysqli_query($conn, $trendQuery);
$trend = mysqli_fetch_assoc($trendRes);
$currentPeriod = $trend['current_period'] ?? 0;
$prevPeriod = $trend['previous_period'] ?? 0;
$percentageChange = ($prevPeriod > 0) ? (($currentPeriod - $prevPeriod) / $prevPeriod) * 100 : 0;
$arrow = $percentageChange > 0 ? 'fa-arrow-up' : ($percentageChange < 0 ? 'fa-arrow-down' : '');
$changeClass = $percentageChange > 0 ? 'change-up' : ($percentageChange < 0 ? 'change-down' : 'change-neutral');

// trend chart data from last 6 orders (for minimal chart)
$chartTotals = [];
$chartQuery = "SELECT total FROM orders ORDER BY id DESC LIMIT 6";
$chartRes = mysqli_query($conn, $chartQuery);
while($row = mysqli_fetch_assoc($chartRes)) { $chartTotals[] = $row['total']; }
while(count($chartTotals) < 6) $chartTotals[] = 0;
$maxChart = max($chartTotals) ?: 1;

// ======================= ORDERS LISTING (with filters, joins order_items + products) =======================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$priceSort = isset($_GET['price_sort']) ? $_GET['price_sort'] : '';
$viewAll = isset($_GET['view_all']) ? $_GET['view_all'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = ($viewAll == '1') ? 1000 : 8;
$offset = ($page - 1) * $limit;

// Base query: join order_items with products, also fetch order metadata
$sql = "SELECT 
            oi.order_id, 
            oi.product_id, 
            oi.product_name, 
            oi.scent,
            oi.quantity, 
            oi.price as item_price,
            oi.subtotal,
            o.created_at as order_date,
            o.order_number,
            o.total as order_total,
            o.status,
            o.payment_method,
            p.image as product_image
        FROM order_items oi
        LEFT JOIN orders o ON oi.order_id = o.id
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE 1=1";

if (!empty($search)) {
    $searchEscaped = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (o.order_number LIKE '%$searchEscaped%' OR o.name LIKE '%$searchEscaped%' OR oi.product_name LIKE '%$searchEscaped%')";
}
if ($priceSort == 'asc') {
    $sql .= " ORDER BY oi.price ASC";
} elseif ($priceSort == 'desc') {
    $sql .= " ORDER BY oi.price DESC";
} else {
    $sql .= " ORDER BY oi.order_id DESC";
}

$countSql = "SELECT COUNT(DISTINCT oi.order_id) as total FROM order_items oi LEFT JOIN orders o ON oi.order_id = o.id WHERE 1=1";
if (!empty($search)) {
    $countSql .= " AND (o.order_number LIKE '%$searchEscaped%' OR o.name LIKE '%$searchEscaped%' OR oi.product_name LIKE '%$searchEscaped%')";
}
$countResult = mysqli_query($conn, $countSql);
$totalRows = ($countResult) ? mysqli_fetch_assoc($countResult)['total'] : 0;
$totalPages = ($limit > 0) ? ceil($totalRows / $limit) : 1;
$sql .= " LIMIT $offset, $limit";

$result = mysqli_query($conn, $sql);
$orderItems = [];
if($result && mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $orderItems[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Dashboard | LVB Atelier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f1f3f6;
            margin: 0;
            padding: 20px;
            padding-left: 280px;
        }

        /* MAIN CONTENT */
        .main-content {
            transition: all 0.3s;
        }

        /* Stats Container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            border: 1px solid #eaeaea;
            transition: all 0.3s ease;
            min-height: 150px;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .card.sales { border-top: 4px solid #2ecc71; }
        .card.income { border-top: 4px solid #e67e22; }
        .card.orders { border-top: 4px solid #3498db; }
        .card.visitors { border-top: 4px solid #9b59b6; }

        .card-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex: 1;
        }

        .card-text { flex: 1; }

        .card-title {
            font-size: 14px;
            font-weight: 500;
            color: #7f8c8d;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-value {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: #2c3e50;
        }

        .card-change {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            font-weight: 500;
        }

        .change-up {
            color: #2ecc71;
            background: rgba(46, 204, 113, 0.1);
            padding: 3px 8px;
            border-radius: 12px;
        }

        .change-down {
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
            padding: 3px 8px;
            border-radius: 12px;
        }

        .change-neutral {
            color: #95a5a6;
            background: rgba(149, 165, 166, 0.1);
            padding: 3px 8px;
            border-radius: 12px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-left: 15px;
        }

        .sales .card-icon { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
        .income .card-icon { background: rgba(231, 126, 34, 0.1); color: #e67e22; }
        .orders .card-icon { background: rgba(52, 152, 219, 0.1); color: #3498db; }
        .visitors .card-icon { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }

        .chart-area {
            margin-top: 20px;
            height: 40px;
            position: relative;
            overflow: hidden;
        }

        .chart-line {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(0,0,0,0.1);
        }

        .chart-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            clip-path: polygon(0% 100%, 100% 100%, 100% 0%, 0% 100%);
        }

        .sales .chart-fill { background: linear-gradient(to top, rgba(46, 204, 113, 0.2), rgba(46, 204, 113, 0)); height: 35px; }
        .income .chart-fill { background: linear-gradient(to top, rgba(231, 126, 34, 0.2), rgba(231, 126, 34, 0)); height: 30px; }
        .orders .chart-fill { background: linear-gradient(to top, rgba(52, 152, 219, 0.2), rgba(52, 152, 219, 0)); height: 25px; }
        .visitors .chart-fill { background: linear-gradient(to top, rgba(155, 89, 182, 0.2), rgba(155, 89, 182, 0)); height: 32px; }

        .chart-dots {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            display: flex;
            justify-content: space-between;
            padding: 0 5px;
        }

        .chart-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: currentColor;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
        }

        .sales .chart-dots .chart-dot { color: #2ecc71; }
        .income .chart-dots .chart-dot { color: #e67e22; }
        .orders .chart-dots .chart-dot { color: #3498db; }
        .visitors .chart-dots .chart-dot { color: #9b59b6; }

        /* orders section */
        .orders-section {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
            transition: box-shadow 0.3s ease;
        }

        .orders-section:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 30px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 25px;
            border: 1px solid #ddd;
            padding: 5px 15px;
        }

        .search-box input {
            border: none;
            padding: 8px 10px;
            outline: none;
            width: 180px;
            font-size: 14px;
        }

        .search-box button {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }

        .sort-select {
            padding: 8px 15px;
            border-radius: 25px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-view-all {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view-all:hover { background: #2980b9; }

        .btn-reset {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-reset:hover { background: #7f8c8d; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            color: #3498db;
            background: #f1f3f6;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #3498db;
            color: white;
        }

        .pagination .active {
            background: #3498db;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th {
            text-align: left;
            font-size: 13px;
            color: #6c757d;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }

        th.numeric-column, td.numeric-column {
            text-align: right;
        }

        td {
            padding: 15px 0;
            border-bottom: 1px solid #f1f3f6;
            font-size: 14px;
            color: #495057;
            transition: background-color 0.3s ease;
        }

        tr:hover td { background-color: #f8f9fa; }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-img {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #dee2e6, #adb5bd);
            border-radius: 8px;
            transition: transform 0.3s ease;
            object-fit: cover;
        }

        tr:hover .product-img { transform: scale(1.05); }

        .info-text {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .order-number {
            font-family: monospace;
            font-weight: 600;
            background: #f1f5f9;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-block;
        }

        /* Status Badges */
        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fed7aa; color: #b45309; }
        .badge-pending_payment { background: #fed7aa; color: #b45309; }
        .badge-processing { background: #dbeafe; color: #1e40af; }
        .badge-shipped { background: #dbeafe; color: #1e40af; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-refunded { background: #fce7f3; color: #9d174d; }
        .badge-default { background: #e2e8f0; color: #334155; }

        @media (max-width: 1200px) {
            body { padding-left: 20px; }
            .stats-container { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            body { padding-left: 20px; padding-right: 20px; }
            .stats-container { grid-template-columns: 1fr; }
            .orders-header { flex-direction: column; align-items: flex-start; }
            .filter-bar { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <!-- STATS CARDS - EXACT STYLE FROM FIRST EXAMPLE -->
    <div class="stats-container">
        <!-- Sales Card -->
        <div class="card sales">
            <div class="card-content">
                <div class="card-text">
                    <p class="card-title">Total Sales</p>
                    <p class="card-value">$<?= number_format($totalSales, 2) ?></p>
                    <div class="card-change">
                        <span class="<?= $changeClass ?>">
                            <?php if($arrow) echo '<i class="fas '.$arrow.'"></i>'; ?>
                            <?= number_format(abs($percentageChange), 2) ?>%
                        </span>
                    </div>
                </div>
                <div class="card-icon"><i class="fas fa-shopping-bag"></i></div>
            </div>
            <div class="chart-area">
                <div class="chart-fill" style="height:<?= max(array_map(function($h) use ($maxChart) { return ($maxChart > 0) ? ($h / $maxChart) * 40 : 5; }, $chartTotals)) ?>px;"></div>
                <div class="chart-line"></div>
                <div class="chart-dots">
                    <?php foreach($chartTotals as $h): ?>
                        <?php $color = ($h < $maxChart*0.33) ? '#e74c3c' : (($h < $maxChart*0.66) ? '#f1c40f' : '#2ecc71'); ?>
                        <div class="chart-dot" style="color:<?= $color ?>;"></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Income Card -->
        <div class="card income">
            <div class="card-content">
                <div class="card-text">
                    <p class="card-title">Total Income</p>
                    <p class="card-value">$<?= number_format($totalRevenue, 2) ?></p>
                    <div class="card-change">
                        <span class="<?= $changeClass ?>">
                            <?php if($arrow) echo '<i class="fas '.$arrow.'"></i>'; ?>
                            <?= number_format(abs($percentageChange), 2) ?>%
                        </span>
                    </div>
                </div>
                <div class="card-icon"><i class="fas fa-dollar-sign"></i></div>
            </div>
            <div class="chart-area">
                <div class="chart-fill" style="height:<?= max(array_map(function($h) use ($maxChart) { return ($maxChart > 0) ? ($h / $maxChart) * 40 : 5; }, $chartTotals)) ?>px;"></div>
                <div class="chart-line"></div>
                <div class="chart-dots">
                    <?php foreach($chartTotals as $h): ?>
                        <?php $color = ($h < $maxChart*0.33) ? '#e74c3c' : (($h < $maxChart*0.66) ? '#f1c40f' : '#2ecc71'); ?>
                        <div class="chart-dot" style="color:<?= $color ?>;"></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Orders Card -->
        <div class="card orders">
            <div class="card-content">
                <div class="card-text">
                    <p class="card-title">Orders Paid</p>
                    <p class="card-value"><?= $totalOrders ?></p>
                    <div class="card-change">
                        <span class="<?= $changeClass ?>">
                            <?php if($arrow) echo '<i class="fas '.$arrow.'"></i>'; ?>
                            <?= number_format(abs($percentageChange), 2) ?>%
                        </span>
                    </div>
                </div>
                <div class="card-icon"><i class="far fa-file-alt"></i></div>
            </div>
            <div class="chart-area">
                <div class="chart-fill" style="height:<?= max(array_map(function($h) use ($maxChart) { return ($maxChart > 0) ? ($h / $maxChart) * 40 : 5; }, $chartTotals)) ?>px;"></div>
                <div class="chart-line"></div>
                <div class="chart-dots">
                    <?php foreach($chartTotals as $h): ?>
                        <?php $color = ($h < $maxChart*0.33) ? '#e74c3c' : (($h < $maxChart*0.66) ? '#f1c40f' : '#2ecc71'); ?>
                        <div class="chart-dot" style="color:<?= $color ?>;"></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Visitors Card -->
        <div class="card visitors">
            <div class="card-content">
                <div class="card-text">
                    <p class="card-title">Total Visitor</p>
                    <p class="card-value"><?= number_format($uniqueCustomers) ?></p>
                    <div class="card-change">
                        <span class="change-up"><i class="fas fa-arrow-up"></i> 2.34%</span>
                    </div>
                </div>
                <div class="card-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="chart-area">
                <div class="chart-fill"></div>
                <div class="chart-line"></div>
                <div class="chart-dots">
                    <div class="chart-dot"></div>
                    <div class="chart-dot"></div>
                    <div class="chart-dot"></div>
                    <div class="chart-dot"></div>
                    <div class="chart-dot"></div>
                    <div class="chart-dot"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ORDERS SECTION -->
    <div class="orders-section">
        <div class="orders-header">
            <h2 style="margin:0; font-size: 18px;">Recent Orders</h2>

            <div class="filter-bar">
                <form method="GET" action="" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search by order #, customer..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>

                    <select name="price_sort" class="sort-select" onchange="this.form.submit()">
                        <option value="">Sort by Price</option>
                        <option value="asc" <?= $priceSort == 'asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="desc" <?= $priceSort == 'desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>

                    <?php if($viewAll == '1'): ?>
                        <input type="hidden" name="view_all" value="1">
                        <a href="?<?= http_build_query(array_merge($_GET, ['view_all' => '0', 'page' => 1])) ?>" class="btn-view-all">Show 8</a>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['view_all' => '1', 'page' => 1])) ?>" class="btn-view-all">View All</a>
                    <?php endif; ?>

                    <?php if(!empty($search) || !empty($priceSort)): ?>
                        <a href="?" class="btn-reset">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Order Date</th>
                    <th>Product</th>
                    <th class="numeric-column">Quantity</th>
                    <th class="numeric-column">Price</th>
                  
                </tr>
            </thead>
            <tbody>
            <?php if(!empty($orderItems)): ?>
                <?php 
                $counter = 0;
                foreach($orderItems as $row): 
                    $counter++;
                    // Determine status badge class
                    $statusClass = 'badge-default';
                    $statusText = ucfirst($row['status'] ?? 'pending');
                    if (strtolower($row['status']) == 'paid' || strtolower($row['status']) == 'completed') {
                        $statusClass = 'badge-paid';
                    } elseif (strtolower($row['status']) == 'pending' || strtolower($row['status']) == 'pending_payment') {
                        $statusClass = 'badge-pending';
                    } elseif (strtolower($row['status']) == 'processing') {
                        $statusClass = 'badge-processing';
                    } elseif (strtolower($row['status']) == 'shipped') {
                        $statusClass = 'badge-shipped';
                    } elseif (strtolower($row['status']) == 'cancelled') {
                        $statusClass = 'badge-cancelled';
                    } elseif (strtolower($row['status']) == 'refunded') {
                        $statusClass = 'badge-refunded';
                    }
                    
                    $productImg = !empty($row['product_image']) ? 'img/' . $row['product_image'] : 'https://via.placeholder.com/50';
                ?>
                    <tr>
                        <td><span class="order-number">#<?= htmlspecialchars($row['order_number'] ?? $row['order_id']) ?></span></td>
                        <td>
                            <span class="order-date">
                                <?= !empty($row['order_date']) ? date('M d, Y', strtotime($row['order_date'])) : 'N/A' ?>
                            </span>
                        </td>
                        <td class="product-cell">
                            <img class="product-img" src="<?= htmlspecialchars($productImg) ?>" alt="<?= htmlspecialchars($row['product_name']) ?>">
                            <div>
                                <?= htmlspecialchars($row['product_name']) ?>
                                <?php if(!empty($row['scent'])): ?>
                                    <br><small style="color:#6c757d;"><?= htmlspecialchars($row['scent']) ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="numeric-column"><?= htmlspecialchars($row['quantity']) ?></td>
                        <td class="numeric-column">$<?= number_format($row['item_price'], 2) ?></td>
                       
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="info-text">No orders found matching your criteria.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if($viewAll != '1' && $totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Previous</a>
            <?php endif; ?>

            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <?php if($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>