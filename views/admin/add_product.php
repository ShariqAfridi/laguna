<?php
// Remove any whitespace before <?php and fix session start
ob_start(); // Add output buffering
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../app/Helpers/ImageOptimizer.php';
use App\Helpers\ImageOptimizer;

$show_success  = false;
$error_message = '';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── FETCH LOOKUPS ─────────────────────────────────────────────────────
$fragrances = $conn->query("SELECT fragrance_id, fragrance_name, sku FROM fragrances ORDER BY fragrance_name ASC");
$sizes      = $conn->query("SELECT id AS size_id, category_name AS size_name, dimensions_subtitle AS size_details, sku, wick_type FROM categories WHERE status = 1 ORDER BY sort_order ASC, id ASC");
$boxes      = $conn->query("SELECT box_id, box_name FROM boxes ORDER BY box_name ASC");
$colors     = $conn->query("SELECT color_id, color_name, color_hex, sku FROM colors ORDER BY color_name ASC");

$fragrances_arr = [];
$sizes_arr      = [];
$boxes_arr      = [];
$colors_arr     = [];

if ($fragrances) while ($r = $fragrances->fetch_assoc()) $fragrances_arr[] = $r;
if ($sizes)      while ($r = $sizes->fetch_assoc())      $sizes_arr[]      = $r;
if ($boxes)      while ($r = $boxes->fetch_assoc())      $boxes_arr[]      = $r;
if ($colors)     while ($r = $colors->fetch_assoc())     $colors_arr[]     = $r;

// ── DUPLICATE PRODUCT DATA FETCH ─────────────────────────────────────
$duplicate_info = null;
if (isset($_GET['duplicate_id']) && is_numeric($_GET['duplicate_id'])) {
    $dup_id = (int)$_GET['duplicate_id'];
    $dup_stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $dup_stmt->bind_param("i", $dup_id);
    $dup_stmt->execute();
    $dup_res = $dup_stmt->get_result();
    if ($drow = $dup_res->fetch_assoc()) {
        $duplicate_info = $drow;
    }
    $dup_stmt->close();
}

// ── FORM SUBMISSION ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name    = trim($_POST['product_name']  ?? '');
    $user_sku        = trim($_POST['sku']           ?? '');
    $description     = trim($_POST['description']   ?? '');
    $fragrance_id    = !empty($_POST['fragrance_id']) ? (int)$_POST['fragrance_id'] : null;
    $selected_colors = array_map('intval', $_POST['colors'] ?? []);
    $selected_boxes  = array_map('intval', $_POST['boxes']  ?? []);
    $wick_type       = trim($_POST['wick_type'] ?? 'single');

    $colors_json = !empty($selected_colors) ? json_encode($selected_colors) : '[]';
    $boxes_json  = !empty($selected_boxes)  ? json_encode($selected_boxes)  : '[]';

    $selected_size = isset($_POST['size_id']) ? (int)$_POST['size_id'] : 0;
    $selected_sizes = $selected_size > 0 ? [$selected_size] : [];

    $single_price = floatval($_POST['price'] ?? 0);
    $single_qty   = isset($_POST['qty']) ? max(0, (int)$_POST['qty']) : 0;

    // ── IMAGE UPLOAD & COMPRESSION ────────────────────────────────────
    $image = null;

    if (!empty($_FILES['image']) && isset($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $opt = ImageOptimizer::optimize($_FILES['image'], 'uploads/products/', 'candle_', 1400, 1048576, 85);
        if ($opt['success']) {
            $image = $opt['path'];
        } else {
            $error_message = $opt['error'];
        }
    } elseif (!empty($_POST['existing_image'])) {
        $image = trim($_POST['existing_image']);
    } else {
        $error_message = "Product image is required.";
    }

    // ── BASIC FIELD VALIDATION ────────────────────────────────────────
    if (empty($error_message)) {
        if (!$product_name)             $error_message = "Product name is required.";
        elseif (!$description)          $error_message = "Description is required.";
        elseif ($selected_size <= 0)    $error_message = "Please select a vessel category (size).";
        elseif ($single_price <= 0)     $error_message = "Please enter a valid price.";
        elseif (empty($wick_type))      $error_message = "Please select a wick type.";
    }

    // ── INSERT ────────────────────────────────────────────────────────
    if (empty($error_message)) {
        // Simple non-array price and quantity values
        $size_ids_json   = json_encode([$selected_size]);
        $size_prices_str = number_format((float)$single_price, 2, '.', '');
        $size_qtys_str   = (string)$single_qty;
        $total_qty       = $single_qty;

        // ── DYNAMIC SKU GENERATION ────────────────────────────────────────
        if (!empty($user_sku)) {
            $final_sku = strtoupper($user_sku);
        } else {
            $vessel_sku = 'C';
            if ($selected_size > 0) {
                foreach ($sizes_arr as $s) {
                    if ((int)$s['size_id'] === $selected_size && !empty($s['sku'])) {
                        $vessel_sku = $s['sku'];
                        break;
                    }
                }
            }

            $color_sku = '00';
            if (!empty($selected_colors)) {
                $first_color_id = $selected_colors[0];
                foreach ($colors_arr as $c) {
                    if ((int)$c['color_id'] === $first_color_id && !empty($c['sku'])) {
                        $color_sku = $c['sku'];
                        break;
                    }
                }
            }

            $fragrance_sku = '00';
            if ($fragrance_id !== null && $fragrance_id > 0) {
                foreach ($fragrances_arr as $f) {
                    if ((int)$f['fragrance_id'] === $fragrance_id && !empty($f['sku'])) {
                        $fragrance_sku = $f['sku'];
                        break;
                    }
                }
            }

            $final_sku = strtoupper($vessel_sku . $color_sku . $fragrance_sku);
        }

        // Check SKU uniqueness
        $sku_check_stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
        $sku_check_stmt->bind_param("s", $final_sku);
        $sku_check_stmt->execute();
        $sku_count = 0;
        $sku_check_stmt->bind_result($sku_count);
        $sku_check_stmt->fetch();
        $sku_check_stmt->close();

        if ($sku_count > 0) {
            $error_message = "A product with SKU \"{$final_sku}\" already exists. Please choose a different vessel, color, or fragrance.";
        }
    }

    // ── INSERT ────────────────────────────────────────────────────────
    if (empty($error_message)) {
        $fragrance_id_db = $fragrance_id !== null ? $fragrance_id : 0;
        $color_id_str    = $colors_json;
        $size_id_str     = $size_ids_json;
        $size_prices_str = $size_prices_json;
        $box_id_str      = $boxes_json;

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("
                INSERT INTO products
                    (product_name, sku, description, image, qty,
                     fragrance_id, color_id, size_id, size_prices, size_qtys, box_id, wick_type,
                     created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param(
                "ssssiissssss",
                $product_name,
                $final_sku,
                $description,
                $image,
                $total_qty,
                $fragrance_id_db,
                $color_id_str,
                $size_id_str,
                $size_prices_str,
                $size_qtys_str,
                $box_id_str,
                $wick_type
            );

            if (!$stmt->execute()) {
                throw new Exception("Insert failed: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->commit();

            $show_success = true;
            echo '<script>setTimeout(function(){ window.location.href = "' . base_url('/admin/list_product') . '"; }, 1000);</script>';

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product — Candle Shop</title>
<style>
:root {
    --blue:        #2563eb;
    --blue-h:      #1d4ed8;
    --blue-l:      #eff6ff;
    --blue-b:      #bfdbfe;
    --bg:          #f1f5f9;
    --card:        #ffffff;
    --border:      #e2e8f0;
    --text:        #1e293b;
    --muted:       #64748b;
    --success-bg:  #f0fdf4;
    --success-bdr: #86efac;
    --success-txt: #166534;
    --danger-bg:   #fef2f2;
    --danger-bdr:  #fca5a5;
    --danger-txt:  #991b1b;
    --input-h:     40px;
    --radius:      10px;
    --radius-lg:   14px;
}

*, *::before, *::after { box-sizing: border-box; }

body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: var(--bg);
    margin: 0;
    padding: 0;
    color: var(--text);
    font-size: 14px;
    line-height: 1.5;
}

.page-main-content {
    padding: 28px 32px;
    margin-left: 250px;
}

@media (max-width: 960px) {
    .page-main-content { margin-left: 0; padding: 16px; padding-top: 70px; }
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
    flex-wrap: wrap;
    gap: 12px;
}

.page-header h2 {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text);
}

.header-actions { display: flex; gap: 10px; align-items: center; }

.btn-primary {
    background: var(--blue);
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: background 0.18s, transform 0.1s;
}
.btn-primary:hover  { background: var(--blue-h); }
.btn-primary:active { transform: scale(0.98); }

.btn-back {
    background: transparent;
    color: var(--muted);
    border: 1px solid var(--border);
    padding: 10px 18px;
    border-radius: var(--radius);
    font-weight: 500;
    font-size: 0.88rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.18s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-back:hover { color: var(--text); border-color: #94a3b8; background: #f8fafc; }

.product-layout {
    display: grid;
    grid-template-columns: 1.55fr 1fr;
    gap: 22px;
    align-items: start;
}

.left-column, .right-column {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

@media (max-width: 900px) {
    .product-layout { grid-template-columns: 1fr; }
}

.card {
    background: var(--card);
    border-radius: var(--radius-lg);
    padding: 22px;
    border: 1px solid var(--border);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.card h3 {
    margin: 0 0 18px 0;
    font-size: 0.93rem;
    font-weight: 700;
    color: var(--text);
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 8px;
}

.card h3 span.icon { font-size: 15px; }

.form-group { margin-bottom: 16px; }
.form-group:last-child { margin-bottom: 0; }

label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--muted);
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

label .req { color: #ef4444; margin-left: 2px; }

input[type="text"],
input[type="number"],
textarea,
select {
    width: 100%;
    height: var(--input-h);
    padding: 0 12px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    font-size: 0.88rem;
    font-family: inherit;
    color: var(--text);
    background: #fff;
    transition: border-color 0.18s, box-shadow 0.18s;
    outline: none;
    appearance: none;
}

input:focus, textarea:focus, select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}

textarea {
    height: auto;
    min-height: 100px;
    padding: 10px 12px;
    resize: vertical;
}

select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 32px;
    cursor: pointer;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.section-divider {
    border: none;
    border-top: 1px solid var(--border);
    margin: 16px 0;
}

.chip-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.chip-group input[type="checkbox"],
.chip-group input[type="radio"] { display: none; }

.chip-group label {
    padding: 6px 13px;
    border-radius: 8px;
    border: 1px solid var(--border);
    cursor: pointer;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--muted);
    background: #f8fafc;
    margin-bottom: 0;
    text-transform: none;
    letter-spacing: 0;
    transition: all 0.15s;
    height: auto;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    user-select: none;
}

.chip-group label:hover {
    background: var(--blue-l);
    border-color: var(--blue-b);
    color: var(--blue);
}

.chip-group input[type="checkbox"]:checked + label,
.chip-group input[type="radio"]:checked + label {
    background: var(--blue);
    color: #fff;
    border-color: var(--blue);
}

.color-dot {
    width: 13px;
    height: 13px;
    border-radius: 3px;
    border: 1px solid rgba(0,0,0,0.12);
    flex-shrink: 0;
}

.image-main {
    height: 220px;
    border: 2px dashed var(--border);
    border-radius: var(--radius-lg);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: #fafbfc;
    transition: border-color 0.18s, background 0.18s;
    overflow: hidden;
    position: relative;
}

.image-main:hover { border-color: var(--blue); background: var(--blue-l); }
.image-main .upload-icon { font-size: 28px; margin-bottom: 6px; line-height: 1; }
.image-main .upload-text { font-size: 0.8rem; color: var(--muted); font-weight: 500; }
.image-main .upload-sub  { font-size: 0.73rem; color: #94a3b8; margin-top: 3px; }

.image-main img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    position: absolute;
    inset: 0;
    border-radius: 12px;
    background: #f8fafc;
}

.alert {
    padding: 12px 16px;
    border-radius: var(--radius);
    margin-bottom: 20px;
    font-size: 0.88rem;
    font-weight: 500;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.alert-error   { background: var(--danger-bg);  color: var(--danger-txt);  border: 1px solid var(--danger-bdr); }
.alert-success { background: var(--success-bg); color: var(--success-txt); border: 1px solid var(--success-bdr); }

.empty-state {
    padding: 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    color: var(--muted);
    background: #f8fafc;
    border: 1px dashed var(--border);
    text-align: center;
}

.summary-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-height: 28px;
    margin-top: 8px;
    padding: 6px 0 0;
}

.summary-chip {
    font-size: 0.76rem;
    padding: 3px 10px;
    border-radius: 20px;
    background: #e0e7ff;
    color: #3730a3;
    font-weight: 500;
}

.summary-empty {
    font-size: 0.78rem;
    color: #94a3b8;
    font-style: italic;
}

.wick-options {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.wick-option {
    flex: 1;
    min-width: 80px;
}

.wick-option input[type="radio"] {
    display: none;
}

.wick-option label {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 8px;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.15s;
    background: #fafbfc;
    margin-bottom: 0;
    text-transform: none;
    letter-spacing: 0;
    font-weight: 600;
    font-size: 0.82rem;
    color: var(--muted);
    text-align: center;
}

.wick-option label:hover {
    border-color: var(--blue-b);
    background: var(--blue-l);
}

.wick-option input[type="radio"]:checked + label {
    border-color: var(--blue);
    background: var(--blue);
    color: #fff;
}

.wick-option .wick-icon {
    font-size: 24px;
    margin-bottom: 4px;
    display: block;
}
</style>
</head>
<body>

<?php
// Compute Form Pre-fill Values (handles duplicate_id and POST errors)
$val_product_name = $_POST['product_name'] ?? ($duplicate_info ? $duplicate_info['product_name'] . ' (Copy)' : '');
$val_sku          = $_POST['sku']          ?? ($duplicate_info && !empty($duplicate_info['sku']) ? $duplicate_info['sku'] . '-COPY' : '');
$val_description  = $_POST['description']  ?? ($duplicate_info['description'] ?? '');
$val_fragrance_id = $_POST['fragrance_id'] ?? ($duplicate_info['fragrance_id'] ?? '');

$val_colors       = $_POST['colors'] ?? ($duplicate_info ? (json_decode($duplicate_info['color_id'] ?? '[]', true) ?: []) : []);
$val_boxes        = $_POST['boxes']  ?? ($duplicate_info ? (json_decode($duplicate_info['box_id'] ?? '[]', true) ?: []) : []);
$val_wick_type    = $_POST['wick_type'] ?? ($duplicate_info ? ($duplicate_info['wick_type'] ?? 'single') : 'single');

$dup_sizes        = $duplicate_info ? (json_decode($duplicate_info['size_id'] ?? '[]', true) ?: []) : [];
$val_size_id      = $_POST['size_id'] ?? (!empty($dup_sizes) ? $dup_sizes[0] : '');

$dup_price = '';
if ($duplicate_info && !empty($duplicate_info['size_prices'])) {
    if (is_numeric($duplicate_info['size_prices'])) {
        $dup_price = $duplicate_info['size_prices'];
    } else {
        $sp_arr = json_decode($duplicate_info['size_prices'], true) ?: [];
        if (!empty($sp_arr)) {
            $dup_price = reset($sp_arr);
        }
    }
}

$dup_qty = '';
if ($duplicate_info && isset($duplicate_info['qty'])) {
    $dup_qty = $duplicate_info['qty'];
} elseif ($duplicate_info && !empty($duplicate_info['size_qtys'])) {
    if (is_numeric($duplicate_info['size_qtys'])) {
        $dup_qty = $duplicate_info['size_qtys'];
    } else {
        $sq_arr = json_decode($duplicate_info['size_qtys'], true) ?: [];
        if (!empty($sq_arr)) {
            $dup_qty = reset($sq_arr);
        }
    }
}

$val_price        = $_POST['price'] ?? ($dup_price !== '' ? $dup_price : '');
$val_qty          = $_POST['qty']   ?? ($dup_qty !== '' ? $dup_qty : '100');
$val_image        = $_POST['existing_image'] ?? ($duplicate_info['image'] ?? '');
?>

<div class="page-main-content">

    <div class="page-header">
        <h2>🕯️ Add New Product</h2>
        <div class="header-actions">
            <a href="<?php echo $base; ?>/admin/list_product" class="btn-back">← Back</a>
            <button type="button" onclick="submitForm()" class="btn-primary">+ Add Product</button>
        </div>
    </div>

    <?php if ($duplicate_info): ?>
        <div style="background: #f0fdf4; color: #166534; border: 1px solid #86efac; padding: 14px 18px; border-radius: var(--radius); margin-bottom: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <span>📋</span> Duplicating Product: <strong><?= htmlspecialchars($duplicate_info['product_name']) ?></strong> &nbsp;— All details copied. Review and click "+ Add Product" to save.
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($show_success): ?>
        <div class="alert alert-success">✅ Product added successfully! Redirecting...</div>
    <?php endif; ?>

    <form id="productForm" action="" method="POST" enctype="multipart/form-data" class="product-layout">

        <!-- LEFT COLUMN -->
        <div class="left-column">

            <!-- General Info -->
            <div class="card">
                <h3><span class="icon">📋</span> General Information</h3>

                <div class="form-group">
                    <label>Product Name <span class="req">*</span></label>
                    <input type="text" name="product_name" id="productName"
                           placeholder="e.g. Amber Musk Candle" required
                           value="<?= htmlspecialchars($val_product_name) ?>">
                </div>

                <div class="form-group">
                    <label>Product SKU <small style="text-transform:none;letter-spacing:0;font-weight:400;color:#94a3b8">(Auto-generated)</small></label>
                    <div style="display:flex;gap:8px;">
                        <input type="text" name="sku" id="productSku" placeholder="e.g. C0702"
                               value="<?= htmlspecialchars($val_sku) ?>"
                               readonly
                               style="text-transform:uppercase;font-family:monospace;font-weight:600;background:#f8fafc;cursor:not-allowed;">
                        <button type="button" onclick="autoGenerateSKU()" class="btn-back" style="flex-shrink:0;padding:0 12px;" title="Generate SKU automatically">⚡ Refresh SKU</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description <span class="req">*</span></label>
                    <textarea name="description" placeholder="Describe your candle…" required><?= htmlspecialchars($val_description) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom:0">
                        <label>Fragrance</label>
                        <select name="fragrance_id" id="fragranceSelect" onchange="autoGenerateSKU()">
                            <option value="" data-sku="00">— None —</option>
                            <?php foreach ($fragrances_arr as $f): ?>
                                <option value="<?= $f['fragrance_id'] ?>"
                                        data-sku="<?= htmlspecialchars($f['sku'] ?? '00') ?>"
                                    <?= ($val_fragrance_id == $f['fragrance_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['fragrance_name']) ?> (SKU: <?= htmlspecialchars($f['sku'] ?? '00') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Colors & Boxes -->
            <div class="card">
                <h3><span class="icon">🎨</span> Colors &amp; Packaging</h3>

                <div class="form-group">
                    <label>Colors <small style="text-transform:none;letter-spacing:0;font-weight:400;color:#94a3b8">(select single color)</small></label>
                    <?php if (empty($colors_arr)): ?>
                        <div class="empty-state">No colors found. Please add colors first.</div>
                    <?php else: ?>
                        <div class="chip-group" id="colorChips">
                            <?php foreach ($colors_arr as $c): ?>
                                <input type="radio" name="colors[]"
                                       id="color-<?= $c['color_id'] ?>"
                                       value="<?= $c['color_id'] ?>"
                                       data-sku="<?= htmlspecialchars($c['sku'] ?? '00') ?>"
                                       onchange="autoGenerateSKU(); updateAllSummaries();"
                                       <?= in_array($c['color_id'], (array)$val_colors) ? 'checked' : '' ?>>
                                <label for="color-<?= $c['color_id'] ?>">
                                    <span class="color-dot" style="background:<?= htmlspecialchars($c['color_hex']) ?>"></span>
                                    <?= htmlspecialchars($c['color_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="summary-row" id="colorSummary">
                            <span class="summary-empty">No colors selected</span>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="section-divider">

                <div class="form-group">
                    <label>Box / Packaging <small style="text-transform:none;letter-spacing:0;font-weight:400;color:#94a3b8">(select multiple)</small></label>
                    <?php if (empty($boxes_arr)): ?>
                        <div class="empty-state">No box types found. Please add boxes first.</div>
                    <?php else: ?>
                        <div class="chip-group" id="boxChips">
                            <?php foreach ($boxes_arr as $b): ?>
                                <input type="checkbox" name="boxes[]"
                                       id="box-<?= $b['box_id'] ?>"
                                       value="<?= $b['box_id'] ?>"
                                       onchange="updateAllSummaries()"
                                       <?= in_array($b['box_id'], (array)$val_boxes) ? 'checked' : '' ?>>
                                <label for="box-<?= $b['box_id'] ?>">
                                    📦 <?= htmlspecialchars($b['box_name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="summary-row" id="boxSummary">
                            <span class="summary-empty">No packaging selected</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /left-column -->

        <!-- RIGHT COLUMN -->
        <div class="right-column">

            <!-- Image Upload -->
            <div class="card">
                <h3><span class="icon">🖼️</span> Product Image</h3>

                <?php
                $img_preview_url = '';
                if (!empty($val_image)) {
                    $img_preview_url = base_url('/' . ltrim($val_image, '/'));
                }
                ?>

                <?php if (!empty($val_image)): ?>
                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($val_image) ?>">
                <?php endif; ?>

                <div id="front-preview"
                     class="image-main"
                     onclick="document.getElementById('imageInput').click()"
                     style="<?= $img_preview_url ? 'background-image:url(' . htmlspecialchars($img_preview_url) . '); background-size:cover; background-position:center;' : '' ?>">
                    <?php if (!empty($val_image)): ?>
                        <div style="background: rgba(0,0,0,0.5); color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;">Copied Image (Click to change)</div>
                    <?php else: ?>
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">Click to upload image</div>
                        <div class="upload-sub">JPG, PNG, GIF, WEBP</div>
                    <?php endif; ?>
                </div>
                <input type="file" id="imageInput" name="image"
                       accept="image/*" onchange="previewImage(this)" hidden <?= empty($val_image) ? 'required' : '' ?>>

                <p style="font-size:0.75rem;color:#94a3b8;text-align:center;margin-top:10px;">
                    Click the area above to choose a photo
                </p>
            </div>

            <!-- Wick Selection -->
            <div class="card">
                <h3><span class="icon">🕯️</span> Wick Type <span class="req">*</span></h3>
                <div class="form-group" style="margin-bottom:0">
                    <div class="wick-options">
                        <div class="wick-option">
                            <input type="radio" name="wick_type" id="wick_single" value="single"
                                   <?= ($val_wick_type === 'single') ? 'checked' : '' ?>>
                            <label for="wick_single">
                                <span class="wick-icon">🕯️</span>
                                Single Wick
                            </label>
                        </div>
                        <div class="wick-option">
                            <input type="radio" name="wick_type" id="wick_double" value="double"
                                   <?= ($val_wick_type === 'double') ? 'checked' : '' ?>>
                            <label for="wick_double">
                                <span class="wick-icon">🕯️🕯️</span>
                                Double Wick
                            </label>
                        </div>
                        <div class="wick-option">
                            <input type="radio" name="wick_type" id="wick_triple" value="triple"
                                   <?= ($val_wick_type === 'triple') ? 'checked' : '' ?>>
                            <label for="wick_triple">
                                <span class="wick-icon">🕯️🕯️🕯️</span>
                                Triple Wick
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vessel Category / Size & Pricing -->
            <div class="card">
                <h3><span class="icon">📏</span> Size &amp; Pricing</h3>

                <?php if (empty($sizes_arr)): ?>
                    <div class="empty-state">No vessel categories found. Please add categories first.</div>
                <?php else: ?>
                    <div class="form-group">
                        <label>Vessel Category (Size) <span class="req">*</span></label>
                        <select name="size_id" id="vesselSelect" class="admin-select" required
                                onchange="onVesselChange();"
                                style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: #fff;">
                            <option value="" data-sku="C" data-wick="single">Select Vessel Category (Size)...</option>
                            <?php foreach ($sizes_arr as $s): ?>
                                <?php
                                $wType = 'single';
                                $wStr = strtolower($s['wick_type'] ?? '');
                                if (strpos($wStr, 'double') !== false) $wType = 'double';
                                elseif (strpos($wStr, 'triple') !== false) $wType = 'triple';
                                ?>
                                <option value="<?= $s['size_id'] ?>"
                                        data-sku="<?= htmlspecialchars($s['sku'] ?? 'C') ?>"
                                        data-wick="<?= $wType ?>"
                                        <?= ((int)$val_size_id === (int)$s['size_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['size_name']) ?> <?= $s['size_details'] ? '(' . htmlspecialchars($s['size_details']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label>Price ($) <span class="req">*</span></label>
                        <input type="number"
                               step="0.01"
                               name="price"
                               id="priceInput"
                               placeholder="0.00"
                               class="admin-input"
                               value="<?= htmlspecialchars($val_price) ?>"
                               required>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label>Quantity</label>
                        <input type="number"
                               name="qty"
                               id="qtyInput"
                               placeholder="0"
                               min="0"
                               class="admin-input"
                               value="<?= htmlspecialchars($val_qty) ?>">
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /right-column -->
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    updateAllSummaries();
    if (!document.getElementById('productSku').value) {
        autoGenerateSKU();
    }
});

/* ── SKU AUTO-GENERATION ── */
function autoGenerateSKU() {
    const vesselSel = document.getElementById('vesselSelect');
    const vesselOpt = vesselSel ? vesselSel.options[vesselSel.selectedIndex] : null;
    const vesselSku = (vesselOpt && vesselOpt.dataset.sku) ? vesselOpt.dataset.sku : 'C';

    let colorSku = '00';
    const checkedColor = document.querySelector('#colorChips input:checked');
    if (checkedColor && checkedColor.dataset.sku) {
        colorSku = checkedColor.dataset.sku;
    }

    const fragSel = document.getElementById('fragranceSelect');
    const fragOpt = fragSel ? fragSel.options[fragSel.selectedIndex] : null;
    const fragSku = (fragOpt && fragOpt.dataset.sku) ? fragOpt.dataset.sku : '00';

    const generated = (vesselSku + colorSku + fragSku).toUpperCase();
    const skuInput = document.getElementById('productSku');
    if (skuInput) {
        skuInput.value = generated;
    }
}

/* ── VESSEL CHANGE HANDLER ── */
function onVesselChange() {
    autoGenerateSKU();
    const vesselSel = document.getElementById('vesselSelect');
    if (!vesselSel) return;
    const vesselOpt = vesselSel.options[vesselSel.selectedIndex];
    if (vesselOpt && vesselOpt.dataset.wick) {
        const targetWick = vesselOpt.dataset.wick;
        const wickRadio = document.getElementById('wick_' + targetWick);
        if (wickRadio) {
            wickRadio.checked = true;
        }
    }
}

/* ── IMAGE PREVIEW ── */
function previewImage(input) {
    const preview = document.getElementById('front-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = '';
            const img = document.createElement('img');
            img.src = e.target.result;
            preview.appendChild(img);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/* ── CHIP SUMMARIES ── */
function updateAllSummaries() {
    updateChipSummary('colorChips', 'colorSummary', 'No colors selected');
    updateChipSummary('boxChips',   'boxSummary',   'No packaging selected');
}

function updateChipSummary(chipsId, summaryId, emptyText) {
    const checked = [...document.querySelectorAll('#' + chipsId + ' input:checked')];
    const el = document.getElementById(summaryId);
    if (!el) return;
    if (checked.length === 0) {
        el.innerHTML = '<span class="summary-empty">' + emptyText + '</span>';
    } else {
        el.innerHTML = checked.map(function (cb) {
            const lbl = cb.nextElementSibling;
            return '<span class="summary-chip">' + (lbl ? lbl.textContent.trim() : cb.value) + '</span>';
        }).join('');
    }
}

/* ── FORM SUBMIT VALIDATION ── */
function submitForm() {
    const name = document.getElementById('productName');
    if (!name || !name.value.trim()) {
        alert('Please enter a product name.');
        name && name.focus();
        return;
    }

    const vesselSel = document.getElementById('vesselSelect');
    if (!vesselSel || !vesselSel.value) {
        alert('Please select a vessel category (size).');
        vesselSel && vesselSel.focus();
        return;
    }

    const priceInput = document.getElementById('priceInput');
    const price = priceInput ? parseFloat(priceInput.value) : 0;
    if (!price || price <= 0) {
        alert('Please enter a valid price.');
        priceInput && priceInput.focus();
        return;
    }

    const wickSelected = document.querySelector('input[name="wick_type"]:checked');
    if (!wickSelected) {
        alert('Please select a wick type (Single, Double, or Triple Wick).');
        return;
    }

    const image = document.getElementById('imageInput');
    if (!image || !image.files || image.files.length === 0) {
        alert('Please upload a product image.');
        return;
    }

    document.getElementById('productForm').submit();
}
</script>
<script src="<?= base_url('/public/assets/js/image-compressor.js') ?>"></script>
<?php ob_end_flush(); ?>
</body>
</html>