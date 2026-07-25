<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once("db.php");

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    // Get image filename before deleting
    $img_query = $conn->prepare("SELECT image FROM products WHERE product_id = ?");
    $img_query->bind_param("i", $product_id);
    $img_query->execute();
    $img_result = $img_query->get_result();
    if ($img_row = $img_result->fetch_assoc()) {
        if (!empty($img_row['image'])) {
            // Try to delete image file from all possible locations
            $image_name = $img_row['image'];
            $paths_to_check = [
                $_SERVER['DOCUMENT_ROOT'] . '/img/' . $image_name,
                dirname(__DIR__, 2) . '/img/' . $image_name,
                __DIR__ . '/../img/' . $image_name
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
    $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $delete_stmt->bind_param("i", $product_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    echo "<script>window.location.href='/list_product';</script>";
    exit();
}

// Get success message
$success_message = '';
if (isset($_GET['deleted'])) {
    $success_message = 'Product deleted successfully!';
}

// Fetch all products with joins to get related data
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
    GROUP BY p.product_id
    ORDER BY p.created_at DESC
";

$products = $conn->query($query);
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

<div class="page-main-content">
    <div class="page-header">
        <h2>🕯️ Product Management</h2>
        <div class="header-actions">
            <a href="/add_product" class="btn-primary">+ Add New Product</a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert-success">✅ <?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <div class="products-table">
        <?php if ($products && $products->num_rows > 0): ?>
            <table>
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
                                $image_html = '';
                                $image_file = $row['image'];
                                
                                if (!empty($image_file)) {
                                    // Try multiple possible locations for the image
                                    $found = false;
                                    
                                    // Option 1: Root img folder
                                    $path1 = $_SERVER['DOCUMENT_ROOT'] . '/img/' . $image_file;
                                    if (file_exists($path1)) {
                                        $image_html = '<img src="/img/' . htmlspecialchars($image_file) . '" class="product-image" alt="' . htmlspecialchars($row['product_name']) . '">';
                                        $found = true;
                                    }
                                    
                                    // Option 2: Two levels up (from admin folder)
                                    if (!$found) {
                                        $path2 = dirname(__DIR__, 2) . '/img/' . $image_file;
                                        if (file_exists($path2)) {
                                            $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path2);
                                            $image_html = '<img src="' . htmlspecialchars($relative_path) . '" class="product-image" alt="' . htmlspecialchars($row['product_name']) . '">';
                                            $found = true;
                                        }
                                    }
                                    
                                    // Option 3: Relative img folder
                                    if (!$found) {
                                        $path3 = __DIR__ . '/../img/' . $image_file;
                                        if (file_exists($path3)) {
                                            $image_html = '<img src="../img/' . htmlspecialchars($image_file) . '" class="product-image" alt="' . htmlspecialchars($row['product_name']) . '">';
                                            $found = true;
                                        }
                                    }
                                    
                                    // Option 4: Check if the image field contains a path
                                    if (!$found && (strpos($image_file, '/') !== false || strpos($image_file, 'img/') !== false)) {
                                        $image_html = '<img src="' . htmlspecialchars($image_file) . '" class="product-image" alt="' . htmlspecialchars($row['product_name']) . '">';
                                        $found = true;
                                    }
                                    
                                    if (!$found) {
                                        $image_html = '<div class="no-image">No image</div>';
                                    }
                                } else {
                                    $image_html = '<div class="no-image">No image</div>';
                                }
                                
                                echo $image_html;
                                ?>
                            </td>
                            
                            <td>
                                <strong><?= htmlspecialchars($row['product_name']) ?></strong>
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
                                            $size_query = $conn->prepare("SELECT size_name, size_details FROM sizes WHERE size_id = ?");
                                            $size_query->bind_param("i", $size_id);
                                            $size_query->execute();
                                            $size_result = $size_query->get_result();
                                            if ($size_row = $size_result->fetch_assoc()) {
                                                $size_name = $size_row['size_name'];
                                                $size_details = $size_row['size_details'];
                                            }
                                            $size_query->close();
                                            
                                            if ($size_name) {
                                                // Get quantity for this size from size_qtys JSON
                                                $qty_for_size = isset($size_qtys[$size_id]) ? (int)$size_qtys[$size_id] : 0;
                                                
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
                              // Determine wick display
$wick_icon = '🕯️';
$wick_class = 'wick-single';
$wick_label = 'Single';

if ($wick_type === 'double') {
    $wick_icon = '🕯️🕯️';
    $wick_class = 'wick-double';
    $wick_label = 'Double';
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
                                        $color_query = $conn->prepare("SELECT color_name, color_hex FROM colors WHERE color_id = ?");
                                        $color_query->bind_param("i", $color_id);
                                        $color_query->execute();
                                        $color_result = $color_query->get_result();
                                        if ($color_row = $color_result->fetch_assoc()) {
                                            $color_name = $color_row['color_name'];
                                            $color_hex = $color_row['color_hex'];
                                        }
                                        $color_query->close();
                                        
                                        if ($color_name) {
                                            echo '<span class="badge" style="';
                                            if ($color_hex) echo 'background: ' . $color_hex . '20; border-left: 3px solid ' . $color_hex . ';';
                                            echo '">';
                                            if ($color_hex) echo '<span style="display: inline-block; width: 10px; height: 10px; background: ' . $color_hex . '; border-radius: 2px; margin-right: 4px;"></span>';
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
                                        $box_query = $conn->prepare("SELECT box_name FROM boxes WHERE box_id = ?");
                                        $box_query->bind_param("i", $box_id);
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
                                    $total_qty_display = (int)$row['qty'];
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
                                    <a href="/edit_product?id=<?= $row['product_id'] ?>" class="btn-edit">✏️ Edit</a>
                                    <button onclick="confirmDelete(<?= $row['product_id'] ?>, '<?= htmlspecialchars(addslashes($row['product_name'])) ?>')" class="btn-delete">🗑️ Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>📦 No products found</p>
                <a href="/add_product" style="display: inline-block; margin-top: 16px;" class="btn-primary">Add Your First Product</a>
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