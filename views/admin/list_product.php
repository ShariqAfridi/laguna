<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../db.php';

// Handle product deletion (via POST or GET)
$delete_target_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $delete_target_id = (int)$_POST['delete_id'];
} elseif (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_target_id = (int)$_GET['delete'];
}

if ($delete_target_id > 0) {
    $product_id = $delete_target_id;

    // Get image filename before deleting
    $img_query = $conn->prepare('SELECT image FROM products WHERE product_id = ?');
    $img_query->bind_param('i', $product_id);
    $img_query->execute();
    $img_result = $img_query->get_result();
    if ($img_row = $img_result->fetch_assoc()) {
        if (!empty($img_row['image'])) {
            $image_name = basename($img_row['image']);
            $paths_to_check = [
                dirname(__DIR__, 2) . '/public/uploads/products/' . $image_name,
                dirname(__DIR__, 2) . '/public/assets/img/' . $image_name,
                $_SERVER['DOCUMENT_ROOT'] . '/public/uploads/products/' . $image_name,
                $_SERVER['DOCUMENT_ROOT'] . '/public/assets/img/' . $image_name
            ];

            foreach ($paths_to_check as $path) {
                if (file_exists($path)) {
                    @unlink($path);
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
    echo "<script>window.location.href='" . base_url('/admin/list_product?deleted=1') . "';</script>";
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
        f.fragrance_name
    FROM products p
    LEFT JOIN fragrances f ON p.fragrance_id = f.fragrance_id
    $whereSql
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        .page-main-content { padding: 28px 32px; }

        @media (max-width: 960px) {
            .page-main-content { margin-left: 0; padding: 16px; }
        }

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

        .header-actions { display: flex; gap: 12px; }

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

        .btn-primary:hover { background: var(--blue-h); }

        .btn-edit {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }

        .btn-edit:hover {
            background: var(--blue);
            color: #fff;
            border-color: var(--blue);
        }

        .btn-duplicate {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }

        .btn-duplicate:hover {
            background: #16a34a;
            color: #fff;
            border-color: #16a34a;
        }

        .btn-delete {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.15s;
        }

        .btn-delete:hover {
            background: var(--danger);
            color: #fff;
            border-color: var(--danger);
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #86efac;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
        }

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
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafbfc; }

        .product-image {
            width: 54px;
            height: 54px;
            border-radius: 8px;
            object-fit: cover;
            background: #f1f5f9;
            border: 1px solid var(--border);
        }

        .admin-no-thumb {
            width: 54px;
            height: 54px;
            border-radius: 8px;
            background: #f1f5f9;
            border: 1px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            font-weight: 600;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
            margin: 2px;
        }

        .size-price-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .wick-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .wick-single { background: #fef3c7; color: #92400e; }
        .wick-double { background: #e0e7ff; color: #3730a3; }
        .wick-triple { background: #fae8ff; color: #86198f; }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .admin-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            background: #fff;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        .admin-pagination-pages { display: flex; gap: 4px; }

        .admin-page-link {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--border);
            color: var(--text);
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .admin-page-link.active {
            background: var(--blue);
            color: #fff;
            border-color: var(--blue);
        }

        .admin-page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: var(--muted);
        }
    </style>
</head>
<body>

<div class="page-main-content">

    <div class="page-header">
        <div>
            <h2>🕯️ Product List</h2>
            <p style="color: var(--muted); margin-top: 4px;">Manage candle products, size variants, fragrances, colors, and inventory.</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo $base; ?>/admin/add_product" class="btn-primary">+ Add New Product</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <!-- Search & Filters Bar -->
    <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <form method="GET" action="<?= base_url('/admin/list_product'); ?>" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
            
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; flex: 1;">
                <!-- Search Input -->
                <div style="position: relative; min-width: 260px; flex: 1;">
                    <input type="text" name="search" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="🔍 Search by product name, SKU, description..." style="width: 100%; padding: 9px 14px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; outline: none; transition: border 0.2s;" onfocus="this.style.borderColor='var(--blue)'" onblur="this.style.borderColor='var(--border)'">
                </div>

                <!-- Vessel / Category Filter -->
                <select name="category" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="0">All Vessels</option>
                    <?php foreach ($filter_categories as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= $categoryFilter == $c['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($c['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Fragrance Filter -->
                <select name="fragrance" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="0">All Fragrances</option>
                    <?php foreach ($filter_fragrances as $f): ?>
                        <option value="<?= $f['fragrance_id']; ?>" <?= $fragranceFilter == $f['fragrance_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($f['fragrance_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Wick Filter -->
                <select name="wick" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="">All Wicks</option>
                    <option value="single" <?= $wickFilter === 'single' ? 'selected' : ''; ?>>Single Wick</option>
                    <option value="double" <?= $wickFilter === 'double' ? 'selected' : ''; ?>>Double Wick</option>
                    <option value="triple" <?= $wickFilter === 'triple' ? 'selected' : ''; ?>>Triple Wick</option>
                </select>

                <!-- Stock Status Filter -->
                <select name="stock" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="all" <?= $stockFilter === 'all' ? 'selected' : ''; ?>>All Stock (<?= $allProductsCount ?>)</option>
                    <option value="instock" <?= $stockFilter === 'instock' ? 'selected' : ''; ?>>In Stock (<?= $inStockCount ?>)</option>
                    <option value="outofstock" <?= $stockFilter === 'outofstock' ? 'selected' : ''; ?>>Out of Stock (<?= $outOfStockCount ?>)</option>
                </select>

                <button type="submit" class="btn-primary" style="padding: 9px 18px; font-size: 13px;">Filter</button>

                <?php if (!empty($searchQuery) || $categoryFilter > 0 || $fragranceFilter > 0 || !empty($wickFilter) || $stockFilter !== 'all'): ?>
                    <a href="<?= base_url('/admin/list_product'); ?>" style="font-size: 13px; color: var(--danger); text-decoration: none; font-weight: 600; padding: 6px 10px;">Reset Filters</a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <div class="products-table">
        <?php if ($products && $products->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Details</th>
                        <th>Description</th>
                        <th>Vessel &amp; Price</th>
                        <th>Wick</th>
                        <th>Colors</th>
                        <th>Packaging</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <?php
                        $size_prices = json_decode($row['size_prices'], true) ?: [];
                        $size_qtys   = json_decode($row['size_qtys'], true) ?: [];
                        $size_ids    = json_decode($row['size_id'], true) ?: [];
                        $color_ids   = json_decode($row['color_id'], true) ?: [];
                        $box_ids     = json_decode($row['box_id'], true) ?: [];
                        $wick_type   = $row['wick_type'] ?? 'single';
                        ?>
                        <tr>
                            
                            <!-- Image Column -->
                            <td>
                                <?php
                                $image_file = $row['image'] ?? '';
                                $img_url = null;

                                if (!empty($image_file)) {
                                    if (preg_match('#^https?://#i', $image_file)) {
                                        $img_url = $image_file;
                                    } else {
                                        $clean_base = basename($image_file);
                                        $upload_disk = dirname(__DIR__, 2) . '/public/uploads/products/' . $clean_base;
                                        $asset_disk = dirname(__DIR__, 2) . '/public/assets/img/' . $clean_base;

                                        if (file_exists($upload_disk)) {
                                            $img_url = base_url('/public/uploads/products/' . $clean_base);
                                        } elseif (file_exists($asset_disk)) {
                                            $img_url = base_url('/public/assets/img/' . $clean_base);
                                        } else {
                                            $img_url = base_url('/public/' . ltrim($image_file, '/'));
                                        }
                                    }
                                }

                                if ($img_url) {
                                    echo '<img src="' . htmlspecialchars($img_url) . '" class="product-image" alt="' . htmlspecialchars($row['product_name'] ?? '') . '">';
                                } else {
                                    echo '<div class="admin-no-thumb">No<br>Image</div>';
                                }
                                ?>
                            </td>
                            
                            <!-- Product Details Column -->
                            <td>
                                <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                                <?php if (!empty($row['sku'])): ?>
                                    <div style="font-size: 10px; font-family: monospace; color: #1e3a8a; background: #eff6ff; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; font-weight: 600; text-transform: uppercase;">
                                        SKU: <?= htmlspecialchars($row['sku']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($row['fragrance_name'])): ?>
                                    <div style="font-size: 11px; color: var(--muted); margin-top: 4px;">
                                        🏷️ <?= htmlspecialchars($row['fragrance_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Description Column -->
                            <td style="max-width: 200px; color: #475569;">
                                <?= htmlspecialchars(substr($row['description'], 0, 75)) ?>
                                <?= strlen($row['description']) > 75 ? '...' : '' ?>
                            </td>
                            
                            <!-- Vessel Category & Price Column -->
                            <td>
                                <div>
                                    <?php
                                    $display_price = is_numeric($row['size_prices']) ? (float)$row['size_prices'] : (is_array($size_prices) && !empty($size_prices) ? (float)reset($size_prices) : 29.00);
                                    $display_qty   = (int)($row['qty'] ?? (is_numeric($row['size_qtys']) ? $row['size_qtys'] : (is_array($size_qtys) && !empty($size_qtys) ? (int)reset($size_qtys) : 100)));

                                    $size_name = '';
                                    if (!empty($size_ids) && is_array($size_ids)) {
                                        $first_sid = (int)$size_ids[0];
                                        $size_query = $conn->prepare('SELECT category_name FROM categories WHERE id = ?');
                                        $size_query->bind_param('i', $first_sid);
                                        $size_query->execute();
                                        if ($srow = $size_query->get_result()->fetch_assoc()) {
                                            $size_name = $srow['category_name'];
                                        }
                                        $size_query->close();
                                    }

                                    echo '<span class="size-price-badge">';
                                    echo ($size_name ? htmlspecialchars($size_name) . ': ' : '') . '$' . number_format($display_price, 2);
                                    if ($display_qty > 0) {
                                        echo ' · ' . number_format($display_qty) . ' pcs';
                                    }
                                    echo '</span>';
                                    ?>
                                </div>
                            </td>
                            
                            <!-- Wick Type Column -->
                            <td>
                                <?php
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
                                }

                                echo '<span class="wick-badge ' . $wick_class . '">';
                                echo $wick_icon . ' ' . $wick_label;
                                echo '</span>';
                                ?>
                            </td>
                            
                            <!-- Colors Column -->
                            <td>
                                <?php
                                if (!empty($color_ids) && is_array($color_ids)) {
                                    foreach ($color_ids as $color_id) {
                                        $color_name = '';
                                        $color_hex = '';
                                        $cq = $conn->prepare('SELECT color_name, color_hex FROM colors WHERE color_id = ?');
                                        $cq->bind_param('i', $color_id);
                                        $cq->execute();
                                        $cres = $cq->get_result();
                                        if ($crow = $cres->fetch_assoc()) {
                                            $color_name = $crow['color_name'];
                                            $color_hex  = $crow['color_hex'];
                                        }
                                        $cq->close();

                                        if ($color_name) {
                                            echo '<span class="badge" style="';
                                            if ($color_hex) echo 'background: ' . $color_hex . '20; border-left: 3px solid ' . $color_hex . ';';
                                            echo '">';
                                            if ($color_hex) echo '<span style="display: inline-block; width: 8px; height: 8px; background: ' . $color_hex . '; border-radius: 2px; margin-right: 4px;"></span>';
                                            echo htmlspecialchars($color_name) . '</span>';
                                        }
                                    }
                                } else {
                                    echo '<span style="color: var(--muted); font-size: 12px;">No colors</span>';
                                }
                                ?>
                            </td>
                            
                            <!-- Packaging Column -->
                            <td>
                                <?php
                                if (!empty($box_ids) && is_array($box_ids)) {
                                    foreach ($box_ids as $box_id) {
                                        $box_name = '';
                                        $bq = $conn->prepare('SELECT box_name FROM boxes WHERE box_id = ?');
                                        $bq->bind_param('i', $box_id);
                                        $bq->execute();
                                        $bres = $bq->get_result();
                                        if ($brow = $bres->fetch_assoc()) {
                                            $box_name = $brow['box_name'];
                                        }
                                        $bq->close();

                                        if ($box_name) {
                                            echo '<span class="badge">📦 ' . htmlspecialchars($box_name) . '</span>';
                                        }
                                    }
                                } else {
                                    echo '<span style="color: var(--muted); font-size: 12px;">No packaging</span>';
                                }
                                ?>
                            </td>
                            
                            <!-- Stock Column -->
                            <td style="text-align: center;">
                                <?php
                                $total_qty_display = (int)$row['qty'];
                                if (!empty($size_qtys) && is_array($size_qtys)) {
                                    $total_qty_display = array_sum($size_qtys);
                                }
                                ?>
                                <span class="badge" style="background: #dbeafe; color: #1e40af;">
                                    <?= number_format($total_qty_display) ?> units
                                </span>
                            </td>
                            
                            <!-- Actions Column -->
                            <td>
                                <div class="action-buttons">
                                    <a href="<?php echo $base; ?>/admin/edit_product?id=<?= $row['product_id'] ?>" class="btn-edit" title="Edit product">✏️ Edit</a>
                                    <a href="<?php echo $base; ?>/admin/add_product?duplicate_id=<?= $row['product_id'] ?>" class="btn-duplicate" title="Duplicate product">📋 Duplicate</a>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="delete_id" value="<?= $row['product_id'] ?>">
                                        <button type="submit" class="btn-delete" title="Delete product">🗑️ Delete</button>
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
</script>

</body>
</html>