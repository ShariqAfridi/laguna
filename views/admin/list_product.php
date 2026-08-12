<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../db.php';

// Handle single product deletion (via POST or GET)
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

// Handle bulk product deletion (via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_delete_ids']) && is_array($_POST['bulk_delete_ids'])) {
    $ids_to_delete = array_map('intval', $_POST['bulk_delete_ids']);
    $ids_to_delete = array_filter($ids_to_delete, function($id) { return $id > 0; });

    if (!empty($ids_to_delete)) {
        $in_clause = implode(',', $ids_to_delete);

        // Delete images
        $img_query = $conn->query("SELECT image FROM products WHERE product_id IN ($in_clause)");
        if ($img_query) {
            while ($img_row = $img_query->fetch_assoc()) {
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
        }

        // Delete records
        $conn->query("DELETE FROM products WHERE product_id IN ($in_clause)");
        $count_deleted = count($ids_to_delete);
        echo "<script>window.location.href='" . base_url("/admin/list_product?deleted_count=$count_deleted") . "';</script>";
        exit();
    }
}

// Get success message
$success_message = '';
if (isset($_GET['deleted'])) {
    $success_message = 'Product deleted successfully!';
} elseif (isset($_GET['deleted_count'])) {
    $c = (int)$_GET['deleted_count'];
    $success_message = "$c product(s) deleted successfully!";
}

$perPageParam = isset($_GET['per_page']) ? trim($_GET['per_page']) : '10';
if ($perPageParam === 'all') {
    $limit = 99999;
} else {
    $limit = max(1, (int)$perPageParam);
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$wickFilter = isset($_GET['wick']) ? trim($_GET['wick']) : '';
$fragranceFilter = isset($_GET['fragrance']) ? (int)$_GET['fragrance'] : 0;
$colorFilter = isset($_GET['color']) ? (int)$_GET['color'] : 0;
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sortOption = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Stock condition clause helper
$stockWhere = "";
if ($stockFilter === 'instock') {
    $stockWhere = "(p.qty > 0 OR (p.size_qtys IS NOT NULL AND p.size_qtys != '' AND p.size_qtys != '0' AND p.size_qtys != '[]' AND p.size_qtys != '{}'))";
} elseif ($stockFilter === 'outofstock') {
    $stockWhere = "((p.qty <= 0 OR p.qty IS NULL) AND (p.size_qtys IS NULL OR p.size_qtys = '' OR p.size_qtys = '0' OR p.size_qtys = '[]' OR p.size_qtys = '{}'))";
}

// Fetch Categories (Vessels) synced with active filters & available stock
$cat_where = [];
if ($stockWhere) $cat_where[] = $stockWhere;
if ($fragranceFilter > 0) $cat_where[] = "p.fragrance_id = $fragranceFilter";
if ($colorFilter > 0) $cat_where[] = "(p.color_id LIKE '%\"$colorFilter\"%' OR p.color_id LIKE '%[$colorFilter]%' OR p.color_id LIKE '%$colorFilter%')";
if (!empty($wickFilter)) {
    $wEsc = $conn->real_escape_string($wickFilter);
    $cat_where[] = "p.wick_type = '$wEsc'";
}
if (!empty($searchQuery)) {
    $sEsc = $conn->real_escape_string($searchQuery);
    $cat_where[] = "(p.product_name LIKE '%$sEsc%' OR p.description LIKE '%$sEsc%' OR p.sku LIKE '%$sEsc%')";
}
$cat_sql_where = !empty($cat_where) ? 'WHERE ' . implode(' AND ', $cat_where) : '';

$filter_categories = [];
$res_cat = $conn->query("
    SELECT DISTINCT c.id, c.category_name 
    FROM categories c 
    INNER JOIN products p ON (p.size_id LIKE CONCAT('%\"', c.id, '\"%') OR p.size_id LIKE CONCAT('%[', c.id, ']%') OR p.size_id LIKE CONCAT('%', c.id, '%')) 
    $cat_sql_where
    ORDER BY c.category_name ASC
");
if ($res_cat) {
    while ($row = $res_cat->fetch_assoc()) {
        $filter_categories[] = $row;
    }
}

// Fetch Fragrances synced with active filters & available stock
$frag_where = [];
if ($stockWhere) $frag_where[] = $stockWhere;
if ($categoryFilter > 0) $frag_where[] = "(p.size_id LIKE '%\"$categoryFilter\"%' OR p.size_id LIKE '%[$categoryFilter]%' OR p.size_id LIKE '%$categoryFilter%')";
if ($colorFilter > 0) $frag_where[] = "(p.color_id LIKE '%\"$colorFilter\"%' OR p.color_id LIKE '%[$colorFilter]%' OR p.color_id LIKE '%$colorFilter%')";
if (!empty($wickFilter)) {
    $wEsc = $conn->real_escape_string($wickFilter);
    $frag_where[] = "p.wick_type = '$wEsc'";
}
if (!empty($searchQuery)) {
    $sEsc = $conn->real_escape_string($searchQuery);
    $frag_where[] = "(p.product_name LIKE '%$sEsc%' OR p.description LIKE '%$sEsc%' OR p.sku LIKE '%$sEsc%')";
}
$frag_sql_where = !empty($frag_where) ? 'WHERE ' . implode(' AND ', $frag_where) : '';

$filter_fragrances = [];
$res_frag = $conn->query("
    SELECT DISTINCT f.fragrance_id, f.fragrance_name 
    FROM fragrances f 
    INNER JOIN products p ON p.fragrance_id = f.fragrance_id 
    $frag_sql_where
    ORDER BY f.fragrance_name ASC
");
if ($res_frag) {
    while ($row = $res_frag->fetch_assoc()) {
        $filter_fragrances[] = $row;
    }
}

// Fetch Colors synced with active filters & available stock
$col_where = [];
if ($stockWhere) $col_where[] = $stockWhere;
if ($categoryFilter > 0) $col_where[] = "(p.size_id LIKE '%\"$categoryFilter\"%' OR p.size_id LIKE '%[$categoryFilter]%' OR p.size_id LIKE '%$categoryFilter%')";
if ($fragranceFilter > 0) $col_where[] = "p.fragrance_id = $fragranceFilter";
if (!empty($wickFilter)) {
    $wEsc = $conn->real_escape_string($wickFilter);
    $col_where[] = "p.wick_type = '$wEsc'";
}
if (!empty($searchQuery)) {
    $sEsc = $conn->real_escape_string($searchQuery);
    $col_where[] = "(p.product_name LIKE '%$sEsc%' OR p.description LIKE '%$sEsc%' OR p.sku LIKE '%$sEsc%')";
}
$col_sql_where = !empty($col_where) ? 'WHERE ' . implode(' AND ', $col_where) : '';

$filter_colors = [];
$res_col = $conn->query("
    SELECT DISTINCT c.color_id, c.color_name 
    FROM colors c 
    INNER JOIN products p ON (p.color_id LIKE CONCAT('%\"', c.color_id, '\"%') OR p.color_id LIKE CONCAT('%[', c.color_id, ']%') OR p.color_id LIKE CONCAT('%', c.color_id, '%')) 
    $col_sql_where
    ORDER BY c.color_name ASC
");
if ($res_col) {
    while ($row = $res_col->fetch_assoc()) {
        $filter_colors[] = $row;
    }
}

// Fetch Wicks synced with active filters & available stock
$wick_where = [];
$wick_where[] = "(p.wick_type IS NOT NULL AND p.wick_type != '')";
if ($stockWhere) $wick_where[] = $stockWhere;
if ($categoryFilter > 0) $wick_where[] = "(p.size_id LIKE '%\"$categoryFilter\"%' OR p.size_id LIKE '%[$categoryFilter]%' OR p.size_id LIKE '%$categoryFilter%')";
if ($fragranceFilter > 0) $wick_where[] = "p.fragrance_id = $fragranceFilter";
if ($colorFilter > 0) $wick_where[] = "(p.color_id LIKE '%\"$colorFilter\"%' OR p.color_id LIKE '%[$colorFilter]%' OR p.color_id LIKE '%$colorFilter%')";
if (!empty($searchQuery)) {
    $sEsc = $conn->real_escape_string($searchQuery);
    $wick_where[] = "(p.product_name LIKE '%$sEsc%' OR p.description LIKE '%$sEsc%' OR p.sku LIKE '%$sEsc%')";
}
$wick_sql_where = 'WHERE ' . implode(' AND ', $wick_where);

$filter_wicks = [];
$res_wick = $conn->query("
    SELECT DISTINCT p.wick_type 
    FROM products p 
    $wick_sql_where
    ORDER BY CASE p.wick_type WHEN 'single' THEN 1 WHEN 'double' THEN 2 WHEN 'triple' THEN 3 ELSE 4 END ASC
");
if ($res_wick) {
    while ($row = $res_wick->fetch_assoc()) {
        $filter_wicks[] = $row['wick_type'];
    }
}

$allProductsCount = (int) ($conn->query('SELECT COUNT(*) as total FROM products')->fetch_assoc()['total'] ?? 0);
$inStockCount = (int) ($conn->query("SELECT COUNT(*) as total FROM products WHERE qty > 0 OR (size_qtys IS NOT NULL AND size_qtys != '' AND size_qtys != '0' AND size_qtys != '[]' AND size_qtys != '{}')")->fetch_assoc()['total'] ?? 0);
$outOfStockCount = max(0, $allProductsCount - $inStockCount);

// Build dynamic WHERE clauses for product listing query
$whereClauses = [];

if ($stockWhere) {
    $whereClauses[] = $stockWhere;
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
    $whereClauses[] = "(p.color_id LIKE '%\"$colorFilter\"%' OR p.color_id LIKE '%[$colorFilter]%' OR p.color_id LIKE '%$colorFilter%')";
}

if ($categoryFilter > 0) {
    $whereClauses[] = "(p.size_id LIKE '%\"$categoryFilter\"%' OR p.size_id LIKE '%[$categoryFilter]%' OR p.size_id LIKE '%$categoryFilter%')";
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Compute sorting clause
$sortSql = "p.created_at DESC";
if ($sortOption === 'oldest') {
    $sortSql = "p.created_at ASC";
} elseif ($sortOption === 'sku_asc') {
    $sortSql = "p.sku ASC";
} elseif ($sortOption === 'sku_desc') {
    $sortSql = "p.sku DESC";
} elseif ($sortOption === 'name_asc') {
    $sortSql = "p.product_name ASC";
} elseif ($sortOption === 'name_desc') {
    $sortSql = "p.product_name DESC";
} elseif ($sortOption === 'price_asc') {
    $sortSql = "CAST(p.size_prices AS DECIMAL(10,2)) ASC";
} elseif ($sortOption === 'price_desc') {
    $sortSql = "CAST(p.size_prices AS DECIMAL(10,2)) DESC";
} elseif ($sortOption === 'stock_desc') {
    $sortSql = "p.qty DESC";
} elseif ($sortOption === 'stock_asc') {
    $sortSql = "p.qty ASC";
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
    ORDER BY $sortSql
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

        .header-actions { display: flex; gap: 12px; align-items: center; }

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

        .btn-bulk-delete {
            background: #ef4444;
            color: #ffffff;
            border: 1px solid #dc2626;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 1px 2px rgba(239,68,68,0.2);
            transition: all 0.15s;
        }

        .btn-bulk-delete:hover {
            background: #dc2626;
            border-color: #b91c1c;
        }

        .bulk-actions-banner {
            display: none;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            padding: 12px 20px;
            border-radius: var(--radius-lg);
            margin-bottom: 16px;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.08);
            animation: fadeIn 0.2s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
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

        .custom-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--blue);
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

    <!-- Bulk Selection Action Banner (shows at top when products are checked) -->
    <div id="bulkActionsBanner" class="bulk-actions-banner">
        <div style="display: flex; align-items: center; gap: 8px; color: #991b1b; font-weight: 600; font-size: 14px;">
            <span>⚠️</span>
            <span id="selectedCountText">0 products selected</span>
        </div>
        <div>
            <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete()">
                🗑️ Delete Selected Products
            </button>
        </div>
    </div>

    <!-- Hidden Form for Bulk Delete -->
    <form id="bulkDeleteForm" method="POST" action="">
        <div id="bulkDeleteInputs"></div>
    </form>

    <!-- Search & Filters Bar -->
    <div style="background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <form method="GET" action="<?= base_url('/admin/list_product'); ?>" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; flex: 1;">
                <!-- Search Input -->
                <div style="position: relative; min-width: 220px; flex: 1;">
                    <input type="text" name="search" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="🔍 Search by product name, SKU..." style="width: 100%; padding: 9px 14px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; outline: none; transition: border 0.2s;" onfocus="this.style.borderColor='var(--blue)'" onblur="this.style.borderColor='var(--border)'">
                </div>

                <!-- Vessel / Category Filter (DYNAMIC CASCADING DEPENDENT) -->
                <select name="category" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="0">All Vessels</option>
                    <?php foreach ($filter_categories as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= $categoryFilter == $c['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($c['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Fragrance Filter (DYNAMIC CASCADING DEPENDENT) -->
                <select name="fragrance" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="0">All Fragrances</option>
                    <?php foreach ($filter_fragrances as $f): ?>
                        <option value="<?= $f['fragrance_id']; ?>" <?= $fragranceFilter == $f['fragrance_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($f['fragrance_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Color Filter (DYNAMIC CASCADING DEPENDENT) -->
                <select name="color" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="0">All Colors</option>
                    <?php foreach ($filter_colors as $cl): ?>
                        <option value="<?= $cl['color_id']; ?>" <?= $colorFilter == $cl['color_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($cl['color_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Wick Filter (DYNAMIC CASCADING DEPENDENT) -->
                <select name="wick" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="">All Wicks</option>
                    <?php foreach ($filter_wicks as $w): ?>
                        <option value="<?= $w; ?>" <?= $wickFilter === $w ? 'selected' : ''; ?>>
                            <?= ucfirst($w); ?> Wick
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Stock Status Filter -->
                <select name="stock" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="all" <?= $stockFilter === 'all' ? 'selected' : ''; ?>>All Products (<?= $allProductsCount ?>)</option>
                    <option value="instock" <?= $stockFilter === 'instock' ? 'selected' : ''; ?>>In Stock (<?= $inStockCount ?>)</option>
                    <option value="outofstock" <?= $stockFilter === 'outofstock' ? 'selected' : ''; ?>>Out of Stock (<?= $outOfStockCount ?>)</option>
                </select>

                <!-- Sort By Dropdown -->
                <select name="sort" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid #bfdbfe; font-size: 13px; color: #1e40af; background: #eff6ff; font-weight: 600; outline: none; cursor: pointer;">
                    <option value="newest" <?= $sortOption === 'newest' ? 'selected' : ''; ?>>Sort: Newest First</option>
                    <option value="oldest" <?= $sortOption === 'oldest' ? 'selected' : ''; ?>>Sort: Oldest First</option>
                    <option value="stock_desc" <?= $sortOption === 'stock_desc' ? 'selected' : ''; ?>>Sort: Stock (High → Low)</option>
                    <option value="stock_asc" <?= $sortOption === 'stock_asc' ? 'selected' : ''; ?>>Sort: Stock (Low → High)</option>
                    <option value="sku_asc" <?= $sortOption === 'sku_asc' ? 'selected' : ''; ?>>Sort: SKU (A → Z)</option>
                    <option value="sku_desc" <?= $sortOption === 'sku_desc' ? 'selected' : ''; ?>>Sort: SKU (Z → A)</option>
                    <option value="name_asc" <?= $sortOption === 'name_asc' ? 'selected' : ''; ?>>Sort: Name (A → Z)</option>
                    <option value="name_desc" <?= $sortOption === 'name_desc' ? 'selected' : ''; ?>>Sort: Name (Z → A)</option>
                    <option value="price_asc" <?= $sortOption === 'price_asc' ? 'selected' : ''; ?>>Sort: Price (Low → High)</option>
                    <option value="price_desc" <?= $sortOption === 'price_desc' ? 'selected' : ''; ?>>Sort: Price (High → Low)</option>
                </select>

                <!-- Items Per Page Dropdown -->
                <select name="per_page" onchange="this.form.submit()" style="padding: 9px 12px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; color: var(--text); background: white; outline: none; cursor: pointer;">
                    <option value="10" <?= $perPageParam === '10' ? 'selected' : ''; ?>>10 / page</option>
                    <option value="25" <?= $perPageParam === '25' ? 'selected' : ''; ?>>25 / page</option>
                    <option value="50" <?= $perPageParam === '50' ? 'selected' : ''; ?>>50 / page</option>
                    <option value="100" <?= $perPageParam === '100' ? 'selected' : ''; ?>>100 / page</option>
                    <option value="all" <?= $perPageParam === 'all' ? 'selected' : ''; ?>>Show All Products</option>
                </select>

                <button type="submit" class="btn-primary" style="padding: 9px 18px; font-size: 13px;">Filter</button>

                <?php if (!empty($searchQuery) || $categoryFilter > 0 || $fragranceFilter > 0 || $colorFilter > 0 || !empty($wickFilter) || $stockFilter !== 'all' || $sortOption !== 'newest' || $perPageParam !== '10'): ?>
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
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAllCB" class="custom-checkbox" title="Select All Products" onchange="toggleSelectAll(this)">
                        </th>
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
                        <tr id="product-row-<?= $row['product_id'] ?>">
                            <!-- Select Checkbox Column -->
                            <td style="text-align: center;">
                                <input type="checkbox" class="product-cb custom-checkbox" value="<?= $row['product_id'] ?>" onchange="updateBulkSelection()">
                            </td>

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
                                        $frag_disk   = dirname(__DIR__, 2) . '/public/uploads/fragrances/' . $clean_base;
                                        $asset_disk  = dirname(__DIR__, 2) . '/public/assets/img/' . $clean_base;

                                        if (file_exists($upload_disk)) {
                                            $img_url = base_url('/public/uploads/products/' . $clean_base);
                                        } elseif (file_exists($frag_disk)) {
                                            $img_url = base_url('/public/uploads/fragrances/' . $clean_base);
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
                                <?php 
                                static $fragrances_map_cache = null;
                                if ($fragrances_map_cache === null) {
                                    $fragrances_map_cache = [];
                                    $res_f_all = $conn->query("SELECT fragrance_id, fragrance_name FROM fragrances");
                                    if ($res_f_all) {
                                        while ($rf = $res_f_all->fetch_assoc()) {
                                            $fragrances_map_cache[(int)$rf['fragrance_id']] = $rf['fragrance_name'];
                                        }
                                    }
                                }

                                $f_dec = json_decode($row['fragrance_id'], true);
                                $f_names = [];
                                if (is_array($f_dec)) {
                                    foreach ($f_dec as $fid) {
                                        if (isset($fragrances_map_cache[(int)$fid])) {
                                            $f_names[] = $fragrances_map_cache[(int)$fid];
                                        }
                                    }
                                } elseif (is_numeric($row['fragrance_id']) && isset($fragrances_map_cache[(int)$row['fragrance_id']])) {
                                    $f_names[] = $fragrances_map_cache[(int)$row['fragrance_id']];
                                } elseif (!empty($row['fragrance_name'])) {
                                    $f_names[] = $row['fragrance_name'];
                                }
                                ?>
                                <?php if (!empty($f_names)): ?>
                                    <div style="font-size: 11px; color: #004b66; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 4px; font-weight: 500;">
                                        🏷️ <?= htmlspecialchars(implode(' · ', $f_names)) ?>
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

            <?php if ($totalPages > 1 && $perPageParam !== 'all'): ?>
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
            <?php else: ?>
                <div class="admin-pagination">
                    <div>Showing all <?= $totalRows; ?> products</div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>No products found matching your filter criteria.</p>
                <a href="<?= base_url('/admin/list_product'); ?>" style="display: inline-block; margin-top: 16px;" class="btn-primary">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleSelectAll(selectAllCb) {
    const checkboxes = document.querySelectorAll('.product-cb');
    checkboxes.forEach(cb => {
        cb.checked = selectAllCb.checked;
    });
    updateBulkSelection();
}

function updateBulkSelection() {
    const selectedCBs = document.querySelectorAll('.product-cb:checked');
    const allCBs = document.querySelectorAll('.product-cb');
    const banner = document.getElementById('bulkActionsBanner');
    const selectedCountText = document.getElementById('selectedCountText');
    const selectAllCb = document.getElementById('selectAllCB');

    const count = selectedCBs.length;

    if (count > 0) {
        banner.style.display = 'flex';
        selectedCountText.textContent = count + (count === 1 ? ' product selected' : ' products selected');
    } else {
        banner.style.display = 'none';
    }

    if (allCBs.length > 0 && selectedCBs.length === allCBs.length) {
        selectAllCb.checked = true;
        selectAllCb.indeterminate = false;
    } else if (selectedCBs.length > 0) {
        selectAllCb.checked = false;
        selectAllCb.indeterminate = true;
    } else {
        selectAllCb.checked = false;
        selectAllCb.indeterminate = false;
    }
}

function submitBulkDelete() {
    const selectedCBs = document.querySelectorAll('.product-cb:checked');
    const count = selectedCBs.length;

    if (count === 0) return;

    if (!confirm(`Are you sure you want to delete ${count} selected product(s)?\n\nThis action cannot be undone.`)) {
        return;
    }

    const container = document.getElementById('bulkDeleteInputs');
    container.innerHTML = '';

    selectedCBs.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'bulk_delete_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    document.getElementById('bulkDeleteForm').submit();
}
</script>

</body>
</html>