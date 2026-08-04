<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../db.php';

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int) $_GET['delete'];

    // Get image filename before deleting
    $img_query = $conn->prepare('SELECT image FROM products WHERE product_id = ?');
    $img_query->bind_param('i', $product_id);
    $img_query->execute();
    $img_result = $img_query->get_result();
    if ($img_row = $img_result->fetch_assoc()) {
        if (!empty($img_row['image'])) {
            // Try to delete image file from all possible locations
            $image_name = $img_row['image'];
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

    // Delete product from database
    $delete_stmt = $conn->prepare('DELETE FROM products WHERE product_id = ?');
    $delete_stmt->bind_param('i', $product_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    echo "<script>window.location.href='" . base_url('/admin/list_product') . "';</script>";
    exit();
}

// Get success message
$success_message = '';
if (isset($_GET['deleted'])) {
    $success_message = 'Product deleted successfully!';
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$wickFilter = isset($_GET['wick']) ? trim($_GET['wick']) : '';
$fragranceFilter = isset($_GET['fragrance']) ? (int)$_GET['fragrance'] : 0;
$colorFilter = isset($_GET['color']) ? (int)$_GET['color'] : 0;
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Fetch filter options (fragrances, colors, categories)
$filter_fragrances = [];
$res_frag = $conn->query("SELECT fragrance_id, fragrance_name FROM fragrances ORDER BY fragrance_name ASC");
if ($res_frag) {
    while ($row = $res_frag->fetch_assoc()) {
        $filter_fragrances[] = $row;
    }
}

$filter_colors = [];
$res_col = $conn->query("SELECT color_id, color_name FROM colors ORDER BY color_name ASC");
if ($res_col) {
    while ($row = $res_col->fetch_assoc()) {
        $filter_colors[] = $row;
    }
}

$filter_categories = [];
$res_cat = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
if ($res_cat) {
    while ($row = $res_cat->fetch_assoc()) {
        $filter_categories[] = $row;
    }
}

$allProductsCount = (int) ($conn->query('SELECT COUNT(*) as total FROM products')->fetch_assoc()['total'] ?? 0);
$inStockCount = (int) ($conn->query("SELECT COUNT(*) as total FROM products WHERE qty > 0 OR size_qtys LIKE '%\"[1-9]%' OR size_qtys LIKE '%:[1-9]%'")->fetch_assoc()['total'] ?? 0);
$outOfStockCount = max(0, $allProductsCount - $inStockCount);

// Build dynamic WHERE clauses
$whereClauses = [];

if ($stockFilter === 'instock') {
    $whereClauses[] = "(p.qty > 0 OR p.size_qtys REGEXP ':[1-9]')";
} elseif ($stockFilter === 'outofstock') {
    $whereClauses[] = "((p.qty <= 0 OR p.qty IS NULL) AND (p.size_qtys NOT REGEXP ':[1-9]'))";
}

if (!empty($searchQuery)) {
    $searchEscaped = $conn->real_escape_string($searchQuery);
    $whereClauses[] = "(p.product_name LIKE '%$searchEscaped%' OR p.description LIKE '%$searchEscaped%' OR p.sku LIKE '%$searchEscaped%')";
}

if (!empty($wickFilter)) {
    $wickEscaped = $conn->real_escape_string($wickFilter);
    $whereClauses[] = "p.wick_type = '$wickEscaped'";
}

if ($fragranceFilter > 0) {
    $whereClauses[] = "p.fragrance_id = $fragranceFilter";
}

if ($colorFilter > 0) {
    $whereClauses[] = "(JSON_CONTAINS(p.color_id, '$colorFilter') OR p.color_id LIKE '%\"$colorFilter\"%' OR p.color_id LIKE '%$colorFilter%')";
}

if ($categoryFilter > 0) {
    $whereClauses[] = "(JSON_CONTAINS(p.size_id, '$categoryFilter') OR p.size_id LIKE '%\"$categoryFilter\"%' OR p.size_id LIKE '%$categoryFilter%')";
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Compute total rows matching the filters
$countQuery = "SELECT COUNT(*) as total FROM products p $whereSql";
$totalRows = (int)($conn->query($countQuery)->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, ceil($totalRows / $limit));

// Fetch products with joins to get related data
$query = "
    SELECT 
        p.*,
        GROUP_CONCAT(DISTINCT f.fragrance_name) as fragrance_names,
        GROUP_CONCAT(DISTINCT c.color_name) as color_names,
        GROUP_CONCAT(DISTINCT b.box_name) as box_names
    FROM products p
    LEFT JOIN fragrances f ON FIND_IN_SET(f.fragrance_id, REPLACE(REPLACE(p.fragrance_id, '[', ''), ']', ''))
    LEFT JOIN colors c ON FIND_IN_SET(c.color_id, REPLACE(REPLACE(p.color_id, '[', ''), ']', ''))
    LEFT JOIN boxes b ON FIND_IN_SET(b.box_id, REPLACE(REPLACE(p.box_id, '[', ''), ']', ''))
    $whereSql
    GROUP BY p.product_id
    ORDER BY p.created_at DESC
    LIMIT $offset, $limit
";

$products = $conn->query($query);

// Helper function to build status filter links with other parameters
function build_filter_url($stock = 'all') {
    $params = $_GET;
    $params['stock'] = $stock;
    $params['page'] = 1;
    return base_url('/admin/list_product?' . http_build_query($params));
}

// Helper function to build page links
function build_page_url($p) {
    $params = $_GET;
    $params['page'] = $p;
    return base_url('/admin/list_product?' . http_build_query($params));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List — Candle Shop</title>
    <style>
        :root {
            --blue:        #2563eb;
            --blue-h:      #1d4ed8;
            --blue-l:      #eff6ff;
            --bg:          #f1f5f9;
            --card:        #ffffff;
            --border:      #e2e8f0;
            --text:        #1e293b;
            --muted:       #64748b;
            --danger:      #ef4444;
            --danger-h:    #dc2626;
            --success:     #10b981;
            --radius:      10px;
            --radius-lg:   14px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        .page-main-content {
            padding: 28px 32px;
        }

        @media (max-width: 960px) {
            .page-main-content { 
                margin-left: 0; 
                padding: 16px; 
            }
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        /* Buttons */
        .btn-primary {
            background: var(--blue);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: var(--blue-h);
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s;
        }

        .btn-danger:hover {
            background: var(--danger-h);
        }

        /* Alert */
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
        }

        /* Table */
        .products-table {
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th {
            text-align: left;
            padding: 16px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            color: var(--muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #fafbfc;
        }

        /* Image */
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: #f1f5f9;
        }

        .no-image {
            width: 60px;
            height: 60px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 12px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            background: #e0e7ff;
            color: #3730a3;
            margin: 2px;
        }

        .size-price-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            background: #fef3c7;
            color: #92400e;
            margin: 2px;
        }

        .size-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        /* Wick badge styles */
        .wick-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px;
        }
        
        .wick-single {
    background: #dbeafe;
    color: #1e40af;
}

.wick-double {
    background: #fef3c7;
    color: #92400e;
}

.wick-triple {
    background: #fbcfe8;
    color: #831843;
}

.wick-none {
    background: #e5e7eb;
    color: #6b7280;
}

.wick-unknown {
    background: #e5e7eb;
    color: #6b7280;
}

        /* Actions */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-edit, .btn-delete {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: var(--blue-l);
            color: var(--blue);
        }

        .btn-edit:hover {
            background: var(--blue);
            color: white;
        }

        .btn-delete {
            background: #fef2f2;
            color: var(--danger);
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            white-space: normal;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            th, td {
                padding: 12px;
            }
            
            .badge, .size-price-badge, .wick-badge {
                font-size: 10px;
                padding: 2px 6px;
            }
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <div class="admin-header">
        <div>
            <h2 class="admin-title">Candle Products Catalog</h2>
            <p class="admin-subtitle">Manage candle products, size variants, fragrances, colors, and inventory.</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo $base; ?>/admin/add_product" class="admin-btn-primary">+ Add New Candle Product</a>
        </div>
    </div>

    <!-- Stock Filter Pills -->
    <div class="status-filters">
        <a href="<?= build_filter_url('all'); ?>" class="status-pill <?= $stockFilter === 'all' ? 'active' : ''; ?>">
            All Products <span class="count"><?= $allProductsCount; ?></span>
        </a>
        <a href="<?= build_filter_url('instock'); ?>" class="status-pill <?= $stockFilter === 'instock' ? 'active' : ''; ?>">
            In Stock <span class="count"><?= $inStockCount; ?></span>
        </a>
        <a href="<?= build_filter_url('outofstock'); ?>" class="status-pill <?= $stockFilter === 'outofstock' ? 'active' : ''; ?>">
            Out of Stock <span class="count"><?= $outOfStockCount; ?></span>
        </a>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="<?= base_url('/admin/list_product') ?>" class="filter-form-wrapper" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <!-- Keep stock filter state -->
        <input type="hidden" name="stock" value="<?= htmlspecialchars($stockFilter) ?>">
        
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; align-items:end;">
            <!-- Search Input -->
            <div class="form-group" style="margin-bottom:0;">
                <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Search Product</label>
                <input type="text" name="search" placeholder="Search name, SKU, desc..." value="<?= htmlspecialchars($searchQuery) ?>" style="width:100%; height:40px; padding:0 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; background:#fff;">
            </div>

            <!-- Wick Filter -->
            <div class="form-group" style="margin-bottom:0;">
                <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Wick Type</label>
                <select name="wick" style="width:100%; height:40px; padding:0 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; background:#fff; cursor:pointer;">
                    <option value="">All Wicks</option>
                    <option value="single" <?= $wickFilter === 'single' ? 'selected' : '' ?>>Single Wick</option>
                    <option value="double" <?= $wickFilter === 'double' ? 'selected' : '' ?>>Double Wick</option>
                    <option value="triple" <?= $wickFilter === 'triple' ? 'selected' : '' ?>>Triple Wick</option>
                </select>
            </div>

            <!-- Fragrance Filter -->
            <div class="form-group" style="margin-bottom:0;">
                <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Fragrance</label>
                <select name="fragrance" style="width:100%; height:40px; padding:0 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; background:#fff; cursor:pointer;">
                    <option value="">All Fragrances</option>
                    <?php foreach ($filter_fragrances as $f): ?>
                        <option value="<?= $f['fragrance_id'] ?>" <?= $fragranceFilter === (int)$f['fragrance_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['fragrance_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Color Filter -->
            <div class="form-group" style="margin-bottom:0;">
                <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Color</label>
                <select name="color" style="width:100%; height:40px; padding:0 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; background:#fff; cursor:pointer;">
                    <option value="">All Colors</option>
                    <?php foreach ($filter_colors as $c): ?>
                        <option value="<?= $c['color_id'] ?>" <?= $colorFilter === (int)$c['color_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['color_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Size / Category Filter -->
            <div class="form-group" style="margin-bottom:0;">
                <label style="display:block; font-size:11px; font-weight:600; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Vessel / Size</label>
                <select name="category" style="width:100%; height:40px; padding:0 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; background:#fff; cursor:pointer;">
                    <option value="">All Categories</option>
                    <?php foreach ($filter_categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Buttons -->
            <div style="display:flex; gap:10px;">
                <button type="submit" style="flex:1; height:40px; background:#2563eb; color:#ffffff; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:background 0.2s;">
                    Filter
                </button>
                <a href="<?= base_url('/admin/list_product') ?>" style="flex:1; height:40px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; display:flex; align-items:center; justify-content:center; transition:background 0.2s;">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <?php if ($success_message): ?>
        <div style="background:#dcfce7; color:#15803d; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:600;">✅ <?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <div class="admin-table-container">
        <?php if ($products && $products->num_rows > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Sizes, Prices & Quantities</th>
                        <th>Wick</th>
                        <th>Colors</th>
                        <th>Boxes</th>
                        <th>Total Qty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <?php
                        // Parse JSON fields
                        $size_prices = json_decode($row['size_prices'], true);
                        $size_qtys = json_decode($row['size_qtys'], true);
                        $size_ids = json_decode($row['size_id'], true);
                        // Get wick type, default to 'single' if not set
                        $wick_type = $row['wick_type'] ?? 'single';
                        ?>
                        <tr>
                            <td><?= $row['product_id'] ?></td>
                            
                            <!-- Image Column with proper path handling -->
                            <td>
                                <?php
                                $image_file = $row['image'] ?? '';
                                if (!empty($image_file)) {
                                    $clean_rel = ltrim(str_replace(['public/', 'assets/img/'], ['', 'uploads/products/'], $image_file), '/');
                                    if (strpos($clean_rel, 'uploads/products/') === false && !preg_match('#^https?://#i', $clean_rel)) {
                                        $clean_rel = 'uploads/products/' . $clean_rel;
                                    }

                                    $disk_path1 = dirname(__DIR__, 2) . '/public/' . $clean_rel;
                                    $disk_path2 = dirname(__DIR__, 2) . '/public/assets/img/' . basename($clean_rel);

                                    if (file_exists($disk_path1)) {
                                        $img_url = base_url('/public/' . $clean_rel);
                                        echo '<img src="' . htmlspecialchars($img_url) . '" class="product-image" alt="' . htmlspecialchars($row['product_name'] ?? '') . '">';
                                    } else if (file_exists($disk_path2)) {
                                        $img_url = base_url('/public/assets/img/' . basename($clean_rel));
                                        echo '<img src="' . htmlspecialchars($img_url) . '" class="product-image" alt="' . htmlspecialchars($row['product_name'] ?? '') . '">';
                                    } else if (preg_match('#^https?://#i', $image_file)) {
                                        echo '<img src="' . htmlspecialchars($image_file) . '" class="product-image" alt="' . htmlspecialchars($row['product_name'] ?? '') . '">';
                                    } else {
                                        echo '<div class="admin-no-thumb">No<br>Image</div>';
                                    }
                                } else {
                                    echo '<div class="admin-no-thumb">No<br>Image</div>';
                                }
                                ?>
                            </td>
                            
                            <td>
                                <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                                <?php if (!empty($row['sku'])): ?>
                                    <div style="font-size: 10px; font-family: monospace; color: #1e3a8a; background: #eff6ff; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; font-weight: 600; text-transform: uppercase;">
                                        SKU: <?= htmlspecialchars($row['sku']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($row['fragrance_names']): ?>
                                    <div style="font-size: 11px; color: var(--muted); margin-top: 4px;">
                                        🏷️ <?= htmlspecialchars($row['fragrance_names']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <td style="max-width: 200px;">
                                <?= htmlspecialchars(substr($row['description'], 0, 80)) ?>
                                <?= strlen($row['description']) > 80 ? '...' : '' ?>
                            </td>
                            
                            <td>
                                <div class="size-info">
                                    <?php
                                    if (!empty($size_prices) && is_array($size_prices)) {
                                        foreach ($size_prices as $size_id => $price) {
                                            // Get size name from database
                                            $size_name = '';
                                            $size_query = $conn->prepare('SELECT category_name AS size_name, dimensions_subtitle AS size_details FROM categories WHERE id = ?');
                                            $size_query->bind_param('i', $size_id);
                                            $size_query->execute();
                                            $size_result = $size_query->get_result();
                                            if ($size_row = $size_result->fetch_assoc()) {
                                                $size_name = $size_row['size_name'];
                                                $size_details = $size_row['size_details'];
                                            }
                                            $size_query->close();

                                            if ($size_name) {
                                                // Get quantity for this size from size_qtys JSON
                                                $qty_for_size = isset($size_qtys[$size_id]) ? (int) $size_qtys[$size_id] : 0;

                                                // Display with tooltip showing detailed info
                                                echo '<div class="tooltip">';
                                                echo '<span class="size-price-badge">';
                                                echo htmlspecialchars($size_name) . ': $' . number_format($price, 2);
                                                if ($qty_for_size > 0) {
                                                    echo ' · ' . number_format($qty_for_size) . ' pcs';
                                                }
                                                echo '</span>';
                                                if ($size_details) {
                                                    echo '<span class="tooltip-text">' . htmlspecialchars($size_details) . '</span>';
                                                }
                                                echo '</div>';
                                            }
                                        }
                                    } else {
                                        echo '<span style="color: var(--muted); font-size: 12px;">No sizes</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            
                            <!-- Wick Type Column -->
                            <td>
                                <?php
                                // Determine wick display
                                $wick_icon = '🕯️';
                                $wick_class = 'wick-single';
                                $wick_label = 'Single';
                                if ($wick_type === 'double') {
                                    $wick_icon = '🕯️🕯️';
                                    $wick_class = 'wick-double';
                                    $wick_label = 'Double';
                                } elseif ($wick_type === 'triple') {
                                    $wick_icon = '🕯️🕯️🕯️';
                                    $wick_class = 'wick-triple';
                                    $wick_label = 'Triple';
                                } elseif ($wick_type === 'none') {
                                    $wick_icon = '🚫';
                                    $wick_class = 'wick-none';
                                    $wick_label = 'No Wick';
                                } elseif ($wick_type === 'single' || empty($wick_type)) {
                                    $wick_icon = '🕯️';
                                    $wick_class = 'wick-single';
                                    $wick_label = 'Single';
                                } else {
                                    $wick_class = 'wick-unknown';
                                    $wick_label = 'Unknown';
                                }

                                echo '<span class="wick-badge ' . $wick_class . '">';
                                echo $wick_icon . ' ' . $wick_label;
                                echo '</span>';
                                ?>
                            </td>
                            
                            <td>
                                <?php
                                $color_ids = json_decode($row['color_id'], true);
                                if (!empty($color_ids) && is_array($color_ids)) {
                                    foreach ($color_ids as $color_id) {
                                        $color_name = '';
                                        $color_hex = '';
                                        $color_query = $conn->prepare('SELECT color_name, color_hex FROM colors WHERE color_id = ?');
                                        $color_query->bind_param('i', $color_id);
                                        $color_query->execute();
                                        $color_result = $color_query->get_result();
                                        if ($color_row = $color_result->fetch_assoc()) {
                                            $color_name = $color_row['color_name'];
                                            $color_hex = $color_row['color_hex'];
                                        }
                                        $color_query->close();

                                        if ($color_name) {
                                            echo '<span class="badge" style="';
                                            if ($color_hex)
                                                echo 'background: ' . $color_hex . '20; border-left: 3px solid ' . $color_hex . ';';
                                            echo '">';
                                            if ($color_hex)
                                                echo '<span style="display: inline-block; width: 10px; height: 10px; background: ' . $color_hex . '; border-radius: 2px; margin-right: 4px;"></span>';
                                            echo htmlspecialchars($color_name) . '</span>';
                                        }
                                    }
                                } else {
                                    echo '<span style="color: var(--muted); font-size: 12px;">No colors</span>';
                                }
                                ?>
                            </td>
                            
                            <td>
                                <?php
                                $box_ids = json_decode($row['box_id'], true);
                                if (!empty($box_ids) && is_array($box_ids)) {
                                    foreach ($box_ids as $box_id) {
                                        $box_name = '';
                                        $box_query = $conn->prepare('SELECT box_name FROM boxes WHERE box_id = ?');
                                        $box_query->bind_param('i', $box_id);
                                        $box_query->execute();
                                        $box_result = $box_query->get_result();
                                        if ($box_row = $box_result->fetch_assoc()) {
                                            $box_name = $box_row['box_name'];
                                        }
                                        $box_query->close();

                                        if ($box_name) {
                                            echo '<span class="badge">📦 ' . htmlspecialchars($box_name) . '</span>';
                                        }
                                    }
                                } else {
                                    echo '<span style="color: var(--muted); font-size: 12px;">No boxes</span>';
                                }
                                ?>
                            </td>
                            
                            <td style="text-align: center;">
                                <?php
                                // Calculate total from size_qtys if available, otherwise use qty field
                                $total_qty_display = 0;
                                if (!empty($size_qtys) && is_array($size_qtys)) {
                                    $total_qty_display = array_sum($size_qtys);
                                } else {
                                    $total_qty_display = (int) $row['qty'];
                                }
                                ?>
                                <span class="badge" style="background: #dbeafe; color: #1e40af;">
                                    <?= number_format($total_qty_display) ?> units
                                </span>
                                
                                <?php if (!empty($size_qtys) && is_array($size_qtys) && count($size_qtys) > 1): ?>
                                    <div style="font-size: 10px; color: var(--muted); margin-top: 4px;">
                                        (split across <?= count($size_qtys) ?> sizes)
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <div class="action-buttons">
                                                         <a href="<?php echo $base; ?>/admin/edit_product?id=<?= $row['product_id'] ?>" class="btn-edit">✏️ Edit</a>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="delete_id" value="<?= $row['product_id'] ?>">
                                        <button type="submit" class="btn-delete">🗑️ Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="admin-pagination">
                    <div>Showing <?= min($offset + 1, $totalRows); ?> to <?= min($offset + $limit, $totalRows); ?> of <?= $totalRows; ?> products</div>
                    <div class="admin-pagination-pages">
                        <a href="<?= build_page_url(max(1, $page - 1)); ?>" class="admin-page-link <?= $page <= 1 ? 'disabled' : ''; ?>">&laquo; Prev</a>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <a href="<?= build_page_url($p); ?>" class="admin-page-link <?= $p == $page ? 'active' : ''; ?>"><?= $p; ?></a>
                        <?php endfor; ?>
                        <a href="<?= build_page_url(min($totalPages, $page + 1)); ?>" class="admin-page-link <?= $page >= $totalPages ? 'disabled' : ''; ?>">Next &raquo;</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>No products found in the database.</p>
                <a href="<?php echo $base; ?>/admin/add_product" style="display: inline-block; margin-top: 16px;" class="btn-primary">Add Your First Product</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(productId, productName) {
    if (confirm(`Are you sure you want to delete "${productName}"?\n\nThis action cannot be undone.`)) {
        window.location.href = `?delete=${productId}`;
    }
}

// Add title attributes to buttons for better UX
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.setAttribute('title', 'Edit product');
    });
    
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.setAttribute('title', 'Delete product');
    });
});
</script>

</body>
</html>