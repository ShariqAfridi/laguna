<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../db.php';

$show_success  = false;
$error_message = '';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if editing existing product
$edit_mode = false;
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_data = null;

if ($product_id > 0) {
    $edit_mode = true;
    $query = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($edit_data) {
        // Decode JSON fields
        $edit_data['color_ids'] = json_decode($edit_data['color_id'], true) ?: [];
        $edit_data['size_ids'] = json_decode($edit_data['size_id'], true) ?: [];
        $edit_data['size_prices_array'] = json_decode($edit_data['size_prices'], true) ?: [];
        $edit_data['size_qtys_array'] = json_decode($edit_data['size_qtys'], true) ?: [];
        $edit_data['box_ids'] = json_decode($edit_data['box_id'], true) ?: [];
        // ADDED: Get wick type, default to 'single' if not set
        $edit_data['wick_type'] = $edit_data['wick_type'] ?? 'single';
    }
}

// ── FETCH LOOKUPS ─────────────────────────────────────────────────────
$fragrances = $conn->query("SELECT fragrance_id, fragrance_name FROM fragrances ORDER BY fragrance_name ASC");
$sizes      = $conn->query("SELECT size_id, size_name, size_details FROM sizes ORDER BY size_name ASC");
$boxes      = $conn->query("SELECT box_id, box_name FROM boxes ORDER BY box_name ASC");
$colors     = $conn->query("SELECT color_id, color_name, color_hex FROM colors ORDER BY color_name ASC");

$fragrances_arr = [];
$sizes_arr      = [];
$boxes_arr      = [];
$colors_arr     = [];

if ($fragrances) while ($r = $fragrances->fetch_assoc()) $fragrances_arr[] = $r;
if ($sizes)      while ($r = $sizes->fetch_assoc())      $sizes_arr[]      = $r;
if ($boxes)      while ($r = $boxes->fetch_assoc())      $boxes_arr[]      = $r;
if ($colors)     while ($r = $colors->fetch_assoc())     $colors_arr[]     = $r;

function getImagePathForDisplay($image_filename) {
    if (empty($image_filename)) {
        return null;
    }
    $clean_name = ltrim(preg_replace('#^/?(img/)?#i', '', $image_filename), '/');
    $img_path = dirname(__DIR__, 2) . "/public/assets/img/" . $clean_name;
    if (file_exists($img_path)) {
        return base_url('/public/assets/img/' . $clean_name);
    }

    if (preg_match('#^https?://#i', $image_filename)) {
        return $image_filename;
    }
    return null;
}

// ── FORM SUBMISSION ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name    = trim($_POST['product_name']  ?? '');
    $description     = trim($_POST['description']   ?? '');
    $fragrance_id    = !empty($_POST['fragrance_id']) ? (int)$_POST['fragrance_id'] : null;
    $selected_colors = array_map('intval', $_POST['colors'] ?? []);
    $selected_boxes  = array_map('intval', $_POST['boxes']  ?? []);
    $wick_type       = $_POST['wick_type'] ?? 'single'; // ADDED: Get wick type

    $colors_json = !empty($selected_colors) ? json_encode($selected_colors) : '[]';
    $boxes_json  = !empty($selected_boxes)  ? json_encode($selected_boxes)  : '[]';

    $selected_sizes = $_POST['sizes']       ?? [];
    $size_prices    = $_POST['size_prices'] ?? [];

    // ── IMAGE UPLOAD (SAME LOGIC AS ADD.PHP) ──────────────────────────
    $image = null;
    
    if (!empty($_FILES['image']) && isset($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed_types)) {
            $error_message = "Invalid image type. Supported: JPG, PNG, GIF, WEBP.";
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('candle_', true) . '.' . strtolower($ext);
            
            // Use the SAME path as add.php
            $img_dir = dirname(__DIR__, 2) . "/public/assets/img/";

            $image_path = $img_dir . $image_name;
            
            if (!file_exists($img_dir)) {
                mkdir($img_dir, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $image = $image_name;
                // If updating with new image, delete old image
                if ($edit_mode && $edit_data && !empty($edit_data['image'])) {
                    $old_image_path = $img_dir . $edit_data['image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
            } else {
                $error_message = "Failed to move uploaded image.";
            }
        }
    } elseif ($edit_mode && isset($_POST['existing_image']) && !empty($_POST['existing_image'])) {
        // Keep existing image
        $image = $_POST['existing_image'];
    } elseif (!$edit_mode) {
        $error_message = "Product image is required.";
    }

    // ── BASIC FIELD VALIDATION ────────────────────────────────────────
    if (empty($error_message)) {
        if (!$product_name)          $error_message = "Product name is required.";
        elseif (!$description)       $error_message = "Description is required.";
        elseif (empty($selected_sizes)) $error_message = "Please select at least one size.";
        elseif (empty($wick_type))   $error_message = "Please select a wick type."; // ADDED: Validate wick
    }

    // ── SIZE + PRICE VALIDATION ───────────────────────────────────────
    $sizes_to_insert = [];
    if (empty($error_message)) {
        foreach ($selected_sizes as $size_id) {
            $raw_price = isset($size_prices[$size_id]) ? str_replace(',', '', $size_prices[$size_id]) : '';
            $price     = floatval($raw_price);
            $qty       = isset($_POST['qty_' . $size_id]) ? max(0, (int)$_POST['qty_' . $size_id]) : 0;

            if ($price <= 0) {
                $error_message = "Please enter a valid price for all selected sizes.";
                break;
            }
            $sizes_to_insert[] = [
                'size_id' => (int)$size_id,
                'price'   => $price,
                'qty'     => $qty
            ];
        }
    }

    // ── INSERT OR UPDATE ──────────────────────────────────────────────
    if (empty($error_message)) {
        // Build JSON arrays
        $size_ids_arr   = array_column($sizes_to_insert, 'size_id');
        $size_ids_json  = json_encode($size_ids_arr);

        // Map: { "size_id": price, ... } and { "size_id": qty, ... }
        $price_map = [];
        $qty_map = [];
        
        foreach ($sizes_to_insert as $s) {
            $price_map[(string)$s['size_id']] = $s['price'];
            $qty_map[(string)$s['size_id']] = $s['qty'];
        }
        
        $size_prices_json = json_encode($price_map);
        $size_qtys_json = json_encode($qty_map);
        
        // Total qty = sum across all selected sizes
        $total_qty = array_sum(array_column($sizes_to_insert, 'qty'));
        
        // Handle null values for foreign keys
        $fragrance_id_db = $fragrance_id !== null ? $fragrance_id : 0;
        
        $conn->begin_transaction();
        try {
            if ($edit_mode && $product_id > 0) {
                // UPDATE existing product with wick_type column
                $stmt = $conn->prepare("
                    UPDATE products SET
                        product_name = ?,
                        description = ?,
                        image = ?,
                        qty = ?,
                        fragrance_id = ?,
                        color_id = ?,
                        size_id = ?,
                        size_prices = ?,
                        size_qtys = ?,
                        box_id = ?,
                        wick_type = ?,
                        updated_at = NOW()
                    WHERE product_id = ?
                ");
                
                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
                
                $stmt->bind_param(
                    "sssiissssssi", // 12 parameters for UPDATE (added wick_type)
                    $product_name,
                    $description,
                    $image,
                    $total_qty,
                    $fragrance_id_db,
                    $colors_json,
                    $size_ids_json,
                    $size_prices_json,
                    $size_qtys_json,
                    $boxes_json,
                    $wick_type, // ADDED: Bind wick_type
                    $product_id
                );
            } else {
                // INSERT new product with wick_type column
                $stmt = $conn->prepare("
                    INSERT INTO products
                        (product_name, description, image, qty,
                         fragrance_id, color_id, size_id, size_prices, size_qtys, box_id, wick_type,
                         created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
                
                $stmt->bind_param(
                    "sssiissssss", // 11 parameters for INSERT (added wick_type)
                    $product_name,
                    $description,
                    $image,
                    $total_qty,
                    $fragrance_id_db,
                    $colors_json,
                    $size_ids_json,
                    $size_prices_json,
                    $size_qtys_json,
                    $boxes_json,
                    $wick_type // ADDED: Bind wick_type
                );
            }

            if (!$stmt->execute()) throw new Exception(($edit_mode ? "Update" : "Insert") . " failed: " . $stmt->error);
            $stmt->close();
            $conn->commit();

            $show_success = true;
            echo '<script>setTimeout(function(){ window.location.href = "<?php echo base_url('/admin/list_product'); ?>"; }, 1000);</script>';

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
<title><?= $edit_mode ? 'Edit' : 'Add' ?> Product — Candle Shop</title>
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
    .page-main-content { margin-left: 0; padding: 16px; padding-top:60px; }
}

/* ── PAGE HEADER ── */
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

/* ── BUTTONS ── */
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

/* ── TWO-COLUMN LAYOUT ── */
.product-layout {
    display: grid;
    grid-template-columns: 1.55fr 1fr;
    gap: 22px;
    align-items: start;
}

.left-column,
.right-column {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

@media (max-width: 900px) {
    .product-layout { grid-template-columns: 1fr; }
}

/* ── CARDS ── */
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

/* ── FORM ELEMENTS ── */
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

/* ── TOGGLE CHIPS ── */
.chip-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.chip-group input[type="checkbox"] { display: none; }

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

.chip-group input[type="checkbox"]:checked + label {
    background: var(--blue);
    color: #fff;
    border-color: var(--blue);
}

.chip-group input[type="checkbox"]:checked + label .color-dot {
    border-color: rgba(255,255,255,0.4);
}

.color-dot {
    width: 13px;
    height: 13px;
    border-radius: 3px;
    border: 1px solid rgba(0,0,0,0.12);
    flex-shrink: 0;
}

/* ── SIZE ACCORDION ROWS ── */
.size-row {
    border: 1px solid var(--border);
    border-radius: 10px;
    margin-bottom: 8px;
    overflow: hidden;
    transition: border-color 0.15s;
}

.size-row.active { border-color: var(--blue-b); }

.size-row-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 14px;
    cursor: pointer;
    background: #fafbfc;
    transition: background 0.15s;
}

.size-row.active .size-row-header { background: var(--blue-l); }
.size-row-header:hover { background: #f0f4ff; }

.size-checkbox {
    width: 18px !important;
    height: 18px !important;
    accent-color: var(--blue);
    cursor: pointer;
    flex-shrink: 0;
}

.size-label-text {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text);
    flex: 1;
}

.size-label-detail {
    font-size: 0.78rem;
    color: var(--muted);
    font-weight: 400;
    margin-left: 6px;
}

.size-row-body {
    padding: 14px;
    background: #fff;
    border-top: 1px solid var(--border);
    display: none;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.size-row.active .size-row-body { display: grid; }
.size-row-body .form-group { margin-bottom: 0; }

.size-badge {
    font-size: 0.75rem;
    padding: 3px 9px;
    border-radius: 20px;
    background: #e0e7ff;
    color: #3730a3;
    font-weight: 600;
    margin-left: auto;
}

.size-row.active .size-badge {
    background: var(--blue);
    color: #fff;
}

/* ── IMAGE UPLOAD ── */
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
    object-fit: cover;
    position: absolute;
    inset: 0;
    border-radius: 12px;
}

/* ── ALERTS ── */
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

/* ── EMPTY STATE ── */
.empty-state {
    padding: 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    color: var(--muted);
    background: #f8fafc;
    border: 1px dashed var(--border);
    text-align: center;
}

/* ── SUMMARY CHIP ── */
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

/* ── WICK SELECTOR STYLES ── */
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

<div class="page-main-content">

    <!-- Page Header -->
    <div class="page-header">
        <h2><?= $edit_mode ? '✏️ Edit' : '🕯️ Add New' ?> Candle Product</h2>
        <div class="header-actions">
            <a href="<?php echo $base; ?>/admin/list_product" class="btn-back">← Back</a>
            <button type="button" onclick="submitForm()" class="btn-primary"><?= $edit_mode ? '✏️ Update' : '+ Add' ?> Product</button>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($show_success): ?>
        <div class="alert alert-success">✅ Product <?= $edit_mode ? 'updated' : 'added' ?> successfully! Redirecting...</div>
    <?php endif; ?>

    <form id="productForm" action="" method="POST" enctype="multipart/form-data" class="product-layout">
        
        <?php if ($edit_mode && $product_id > 0): ?>
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <?php if ($edit_data && $edit_data['image']): ?>
                <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_data['image']) ?>">
            <?php endif; ?>
        <?php endif; ?>

        <!-- ══════════════════════════════
             LEFT COLUMN
        ══════════════════════════════ -->
        <div class="left-column">

            <!-- General Info -->
            <div class="card">
                <h3><span class="icon">📋</span> General Information</h3>

                <div class="form-group">
                    <label>Product Name <span class="req">*</span></label>
                    <input type="text" name="product_name"
                           placeholder="e.g. Amber Musk Candle" required
                           value="<?= htmlspecialchars($edit_mode && $edit_data ? $edit_data['product_name'] : ($_POST['product_name'] ?? '')) ?>">
                </div>

                <div class="form-group">
                    <label>Description <span class="req">*</span></label>
                    <textarea name="description" placeholder="Describe your candle…" required><?= htmlspecialchars($edit_mode && $edit_data ? $edit_data['description'] : ($_POST['description'] ?? '')) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom:0">
                        <label>Fragrance</label>
                        <select name="fragrance_id">
                            <option value="">— None —</option>
                            <?php foreach ($fragrances_arr as $f): ?>
                                <option value="<?= $f['fragrance_id'] ?>"
                                    <?php 
                                    $selected_fragrance = $edit_mode && $edit_data ? $edit_data['fragrance_id'] : ($_POST['fragrance_id'] ?? '');
                                    echo ($selected_fragrance == $f['fragrance_id']) ? 'selected' : '';
                                    ?>>
                                    <?= htmlspecialchars($f['fragrance_name']) ?>
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
                    <label>Colors <small style="text-transform:none;letter-spacing:0;font-weight:400;color:#94a3b8">(select multiple)</small></label>
                    <?php if (empty($colors_arr)): ?>
                        <div class="empty-state">No colors found. Please add colors first.</div>
                    <?php else: ?>
                        <div class="chip-group" id="colorChips">
                            <?php foreach ($colors_arr as $c): ?>
                                <?php
                                $selected_colors = [];
                                if ($edit_mode && $edit_data) {
                                    $selected_colors = $edit_data['color_ids'];
                                } elseif (isset($_POST['colors'])) {
                                    $selected_colors = $_POST['colors'];
                                }
                                ?>
                                <input type="checkbox" name="colors[]"
                                       id="color-<?= $c['color_id'] ?>"
                                       value="<?= $c['color_id'] ?>"
                                       <?= in_array($c['color_id'], $selected_colors) ? 'checked' : '' ?>>
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
                                <?php
                                $selected_boxes = [];
                                if ($edit_mode && $edit_data) {
                                    $selected_boxes = $edit_data['box_ids'];
                                } elseif (isset($_POST['boxes'])) {
                                    $selected_boxes = $_POST['boxes'];
                                }
                                ?>
                                <input type="checkbox" name="boxes[]"
                                       id="box-<?= $b['box_id'] ?>"
                                       value="<?= $b['box_id'] ?>"
                                       <?= in_array($b['box_id'], $selected_boxes) ? 'checked' : '' ?>>
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

        <!-- ══════════════════════════════
             RIGHT COLUMN
        ══════════════════════════════ -->
        <div class="right-column">

            <!-- Image Upload -->
            <div class="card">
                <h3><span class="icon">🖼️</span> Product Image</h3>

                <div id="front-preview"
                     class="image-main"
                     onclick="document.getElementById('imageInput').click()">
                    <?php 
                    $display_image_path = null;
                    if ($edit_mode && $edit_data && $edit_data['image']) {
                        $display_image_path = getImagePathForDisplay($edit_data['image']);
                    }
                    
                    if ($display_image_path): ?>
                        <img src="<?= htmlspecialchars($display_image_path) ?>" alt="Product Image" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">Click to upload image</div>
                        <div class="upload-sub">JPG, PNG, GIF, WEBP</div>
                    <?php endif; ?>
                </div>
                <input type="file" id="imageInput" name="image"
                       accept="image/*" onchange="previewImage(this)" <?= $edit_mode ? '' : 'required' ?> hidden>

                <p style="font-size:0.75rem;color:#94a3b8;text-align:center;margin-top:10px;">
                    Click the area above to choose a photo
                </p>
            </div>

            <!-- Wick Selection -->
            <div class="card">
                <h3><span class="icon">🕯️</span> Wick Type <span class="req">*</span></h3>
                <div class="form-group" style="margin-bottom:0">
                    <div class="wick-options">
                        <?php
                        $selected_wick = '';
                        if ($edit_mode && $edit_data) {
                            $selected_wick = $edit_data['wick_type'];
                        } elseif (isset($_POST['wick_type'])) {
                            $selected_wick = $_POST['wick_type'];
                        }
                        ?>
                        <div class="wick-option">
                            <input type="radio" name="wick_type" id="wick_single" value="single"
                                   <?= ($selected_wick === 'single') ? 'checked' : '' ?>>
                            <label for="wick_single">
                                <span class="wick-icon">🕯️</span>
                                Single Wick
                            </label>
                        </div>
                        <div class="wick-option">
                            <input type="radio" name="wick_type" id="wick_double" value="double"
                                   <?= ($selected_wick === 'double') ? 'checked' : '' ?>>
                            <label for="wick_double">
                                <span class="wick-icon">🕯️🕯️</span>
                                Double Wick
                            </label>
                        </div>
                    <div class="wick-option">
    <input type="radio" name="wick_type" id="wick_none" value="none"
           <?= ($selected_wick === 'none') ? 'checked' : '' ?>>
    <label for="wick_none">
        <span class="wick-icon">🚫</span>
        No Wick
    </label>
</div>
                    </div>
                </div>
            </div>

            <!-- Sizes & Pricing -->
            <div class="card">
                <h3><span class="icon">📏</span> Sizes &amp; Pricing
                    <span style="font-size:0.8rem;font-weight:400;color:var(--muted);margin-left:6px">— select all that apply</span>
                </h3>

                <?php if (empty($sizes_arr)): ?>
                    <div class="empty-state">No sizes found. Please add sizes first.</div>
                <?php else: ?>
                    <?php foreach ($sizes_arr as $s): ?>
                        <?php
                        $is_checked = false;
                        $price_value = '';
                        $qty_value = 0;
                        
                        if ($edit_mode && $edit_data) {
                            if (in_array($s['size_id'], $edit_data['size_ids'])) {
                                $is_checked = true;
                                $price_value = $edit_data['size_prices_array'][$s['size_id']] ?? '';
                                $qty_value = $edit_data['size_qtys_array'][$s['size_id']] ?? 0;
                            }
                        } elseif (isset($_POST['sizes'])) {
                            $is_checked = in_array($s['size_id'], $_POST['sizes']);
                            $price_value = $_POST['size_prices'][$s['size_id']] ?? '';
                            $qty_value = (int)($_POST['qty_' . $s['size_id']] ?? 0);
                        }
                        ?>
                        <div class="size-row <?= $is_checked ? 'active' : '' ?>" id="sizeRow_<?= $s['size_id'] ?>">
                            <div class="size-row-header" onclick="toggleSize(<?= $s['size_id'] ?>)">
                                <input type="checkbox"
                                       class="size-checkbox"
                                       name="sizes[]"
                                       id="size_<?= $s['size_id'] ?>"
                                       value="<?= $s['size_id'] ?>"
                                       onclick="event.stopPropagation(); toggleSize(<?= $s['size_id'] ?>)"
                                       <?= $is_checked ? 'checked' : '' ?>>
                                <span class="size-label-text">
                                    <?= htmlspecialchars($s['size_name']) ?>
                                    <?php if ($s['size_details']): ?>
                                        <span class="size-label-detail">(<?= htmlspecialchars($s['size_details']) ?>)</span>
                                    <?php endif; ?>
                                </span>
                                <span class="size-badge" id="sizeBadge_<?= $s['size_id'] ?>">
                                    <?= ($is_checked && $price_value > 0) ? '$ ' . number_format($price_value) . ' · ' . $qty_value . ' pcs' : 'Not selected' ?>
                                </span>
                            </div>
                            <div class="size-row-body" id="sizeBody_<?= $s['size_id'] ?>">
                                <div class="form-group">
                                    <label>Price ($) <span class="req">*</span></label>
                                    <input type="text"
                                           name="size_prices[<?= $s['size_id'] ?>]"
                                           id="price_<?= $s['size_id'] ?>"
                                           placeholder="0"
                                           class="price-input"
                                           value="<?= htmlspecialchars($price_value) ?>"
                                           oninput="formatPrice(this); updateSizeBadge(<?= $s['size_id'] ?>)">
                                </div>
                                <div class="form-group">
                                    <label>Quantity</label>
                                    <input type="number"
                                           name="qty_<?= $s['size_id'] ?>"
                                           id="qty_<?= $s['size_id'] ?>"
                                           placeholder="0"
                                           min="0"
                                           value="<?= $qty_value ?>"
                                           onchange="updateSizeBadge(<?= $s['size_id'] ?>)">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div><!-- /right-column -->
    </form>
</div>

<script>
/* ── RESTORE STATE ON PAGE RELOAD ── */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.size-row').forEach(function (row) {
        const id = row.id.replace('sizeRow_', '');
        const cb = document.getElementById('size_' + id);
        if (cb && cb.checked) {
            activateSize(parseInt(id), false);
        }
    });
    updateAllSummaries();
});

/* ── SIZE TOGGLE ── */
function toggleSize(id) {
    const cb  = document.getElementById('size_' + id);
    const row = document.getElementById('sizeRow_' + id);

    cb.checked = !cb.checked;

    if (cb.checked) {
        activateSize(id, true);
    } else {
        row.classList.remove('active');
        document.getElementById('sizeBadge_' + id).textContent = 'Not selected';
    }
    updateAllSummaries();
}

function activateSize(id, focusPrice) {
    const row = document.getElementById('sizeRow_' + id);
    row.classList.add('active');
    updateSizeBadge(id);
    if (focusPrice) {
        setTimeout(function () {
            const p = document.getElementById('price_' + id);
            if (p) p.focus();
        }, 50);
    }
}

function updateSizeBadge(id) {
    const badge = document.getElementById('sizeBadge_' + id);
    const price = document.getElementById('price_' + id);
    const qty   = document.getElementById('qty_' + id);
    const cb    = document.getElementById('size_' + id);
    if (!cb || !cb.checked) return;

    const p = parseFloat((price ? price.value : '').replace(/,/g, '')) || 0;
    const q = parseInt(qty ? qty.value : 0) || 0;

    badge.textContent = p > 0
        ? '$ ' + formatNum(p) + ' · ' + q + ' pcs'
        : 'Set price ↓';
}

/* ── PRICE FORMATTING ── */
function formatPrice(input) {
    let v = input.value.replace(/,/g, '').replace(/[^\d.]/g, '');
    const parts = v.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    input.value = parts.join('.');
}

function formatNum(n) {
    return n.toLocaleString('en-US', { maximumFractionDigits: 0 });
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
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
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
    const checked = [...document.querySelectorAll('#' + chipsId + ' input[type="checkbox"]:checked')];
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

/* ── LIVE LISTENERS ── */
document.addEventListener('change', function (e) {
    if (e.target.matches('input[name="colors[]"]') ||
        e.target.matches('input[name="boxes[]"]')) {
        updateAllSummaries();
    }
});

/* ── FORM SUBMIT VALIDATION ── */
function submitForm() {
    const name = document.querySelector('input[name="product_name"]');
    if (!name || !name.value.trim()) {
        alert('Please enter a product name.');
        name && name.focus();
        return;
    }

    const selectedSizes = [...document.querySelectorAll('input[name="sizes[]"]:checked')];
    if (selectedSizes.length === 0) {
        alert('Please select at least one size.');
        return;
    }

    let priceError = false;
    selectedSizes.forEach(function (cb) {
        const priceEl = document.getElementById('price_' + cb.value);
        const price   = parseFloat((priceEl ? priceEl.value : '').replace(/,/g, ''));
        if (!price || price <= 0) priceError = true;
    });

    if (priceError) {
        alert('Please enter a valid price for all selected sizes.');
        return;
    }

// ADDED: Check if wick type is selected
const wickSelected = document.querySelector('input[name="wick_type"]:checked');
if (!wickSelected) {
    alert('Please select a wick type (Single, Double, or No Wick).');
    return;
}

    <?php if (!$edit_mode): ?>
    const image = document.getElementById('imageInput');
    if (!image || !image.files || image.files.length === 0) {
        alert('Please upload a product image.');
        return;
    }
    <?php endif; ?>

    document.getElementById('productForm').submit();
}
</script>
</body>
</html>