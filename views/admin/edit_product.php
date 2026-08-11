<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../app/Helpers/ImageOptimizer.php';
use App\Helpers\ImageOptimizer;

$show_success  = false;
$error_message = '';
$success_message = '';

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
        $c_dec = json_decode($edit_data['color_id'], true);
        $edit_data['color_ids'] = is_array($c_dec) ? $c_dec : [];

        $s_dec = json_decode($edit_data['size_id'], true);
        $edit_data['size_ids'] = is_array($s_dec) ? $s_dec : [];

        $p_dec = json_decode($edit_data['size_prices'], true);
        $edit_data['size_prices_array'] = is_array($p_dec) ? $p_dec : [];

        $q_dec = json_decode($edit_data['size_qtys'], true);
        $edit_data['size_qtys_array'] = is_array($q_dec) ? $q_dec : [];

        $b_dec = json_decode($edit_data['box_id'], true);
        $edit_data['box_ids'] = is_array($b_dec) ? $b_dec : [];

        $edit_data['wick_type'] = $edit_data['wick_type'] ?? 'single';
    }
}

// ── FETCH LOOKUPS ─────────────────────────────────────────────────────
$fragrances = $conn->query("SELECT fragrance_id, fragrance_name, fragrance_image, sku FROM fragrances ORDER BY fragrance_name ASC");
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

$fragrance_map = [];
foreach ($fragrances_arr as $f) { $fragrance_map[$f['fragrance_id']] = $f; }

function getImagePathForDisplay($image_filename) {
    if (empty($image_filename)) {
        return null;
    }
    if (preg_match('#^https?://#i', $image_filename)) {
        return $image_filename;
    }
    
    $clean_base = basename($image_filename);
    $upload_disk = dirname(__DIR__, 2) . "/public/uploads/products/" . $clean_base;
    if (file_exists($upload_disk)) {
        return base_url('/public/uploads/products/' . $clean_base);
    }

    $clean_name = ltrim(preg_replace('#^/?(public/|assets/img/)?#i', '', $image_filename), '/');
    $asset_disk = dirname(__DIR__, 2) . "/public/assets/img/" . $clean_name;
    if (file_exists($asset_disk)) {
        return base_url('/public/assets/img/' . $clean_name);
    }

    return base_url('/public/' . ltrim($image_filename, '/'));
}

// ── FORM SUBMISSION ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name    = trim($_POST['product_name']  ?? '');
    $user_sku        = trim($_POST['sku']           ?? '');
    $description     = trim($_POST['description']   ?? '');
    $selected_colors = array_map('intval', $_POST['colors'] ?? []);
    $selected_boxes  = array_map('intval', $_POST['boxes']  ?? []);
    $wick_type       = trim($_POST['wick_type'] ?? 'single');

    // Handle selected fragrances
    $selected_fragrance_ids = [];
    if (!empty($_POST['fragrances']) && is_array($_POST['fragrances'])) {
        $selected_fragrance_ids = array_map('intval', $_POST['fragrances']);
    } elseif (!empty($_POST['fragrance_id']) && is_numeric($_POST['fragrance_id'])) {
        $selected_fragrance_ids = [(int)$_POST['fragrance_id']];
    }

    $colors_json = !empty($selected_colors) ? json_encode($selected_colors) : '[]';
    $boxes_json  = !empty($selected_boxes)  ? json_encode($selected_boxes)  : '[]';

    $selected_size = isset($_POST['size_id']) ? (int)$_POST['size_id'] : 0;
    $single_price  = floatval($_POST['price'] ?? 0);
    $single_qty    = isset($_POST['qty']) ? max(0, (int)$_POST['qty']) : 0;

    // ── MAIN DEFAULT IMAGE UPLOAD ──────────────────────────────────────
    $main_image = null;
    if (!empty($_FILES['image']) && isset($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $opt = ImageOptimizer::optimize($_FILES['image'], 'uploads/products/', 'candle_', 1400, 1048576, 85);
        if ($opt['success']) {
            $main_image = $opt['path'];
        } else {
            $error_message = $opt['error'];
        }
    } elseif (isset($_POST['existing_image']) && !empty($_POST['existing_image'])) {
        $main_image = trim($_POST['existing_image']);
    }

    // ── PER-FRAGRANCE IMAGE UPLOADS ────────────────────────────────────
    $uploaded_fragrance_images = [];
    foreach ($selected_fragrance_ids as $fid) {
        $file_key = 'fragrance_image_' . $fid;
        if (!empty($_FILES[$file_key]) && isset($_FILES[$file_key]['tmp_name']) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $opt = ImageOptimizer::optimize($_FILES[$file_key], 'uploads/products/', 'fragrance_' . $fid . '_', 1400, 1048576, 85);
            if ($opt['success']) {
                $uploaded_fragrance_images[$fid] = $opt['path'];
            }
        } elseif (isset($_POST['existing_fragrance_image_' . $fid]) && !empty($_POST['existing_fragrance_image_' . $fid])) {
            $uploaded_fragrance_images[$fid] = trim($_POST['existing_fragrance_image_' . $fid]);
        }
    }

    // ── BASIC FIELD VALIDATION ────────────────────────────────────────
    if (empty($error_message)) {
        if (!$description)                      $error_message = "Description is required.";
        elseif ($selected_size <= 0)            $error_message = "Please select a vessel category (size).";
        elseif ($single_price <= 0)             $error_message = "Please enter a valid price.";
        elseif (empty($wick_type))              $error_message = "Please select a wick type.";
        elseif (empty($selected_fragrance_ids)) $error_message = "Please select at least one fragrance.";
    }

    // ── SAVE / UPDATE VARIATIONS ──────────────────────────────────────
    if (empty($error_message)) {
        $size_ids_json   = json_encode([$selected_size]);
        $size_prices_str = number_format((float)$single_price, 2, '.', '');
        $size_qtys_str   = (string)$single_qty;
        $total_qty       = $single_qty;

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

        $fragrance_images_map = [];
        foreach ($selected_fragrance_ids as $fid) {
            if (isset($uploaded_fragrance_images[$fid])) {
                $fragrance_images_map[(string)$fid] = $uploaded_fragrance_images[$fid];
            } elseif ($main_image) {
                $fragrance_images_map[(string)$fid] = $main_image;
            }
        }
        $fragrance_images_json = json_encode($fragrance_images_map);

        $conn->begin_transaction();
        try {
            if ($edit_mode && count($selected_fragrance_ids) === 1 && $selected_fragrance_ids[0] == $edit_data['fragrance_id']) {
                // Updating single product record directly
                $fid = $selected_fragrance_ids[0];
                $frag_info = $fragrance_map[$fid] ?? null;
                $frag_name = $frag_info['fragrance_name'] ?? 'Scent';
                $frag_sku  = !empty($frag_info['sku']) ? $frag_info['sku'] : sprintf('%02d', $fid);

                $variation_image = $uploaded_fragrance_images[$fid] ?? ($main_image ?: ($edit_data['image'] ?? ''));

                if (!empty($product_name)) {
                    $p_name = $product_name;
                } else {
                    $p_name = $frag_name . ' Candle';
                }

                $final_sku = !empty($user_sku) ? strtoupper($user_sku) : strtoupper($vessel_sku . $color_sku . $frag_sku);

                $u_stmt = $conn->prepare("
                    UPDATE products SET 
                        product_name = ?,
                        sku = ?,
                        description = ?,
                        image = ?,
                        fragrance_images = ?,
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
                $u_stmt->bind_param(
                    "sssssiissssssi",
                    $p_name,
                    $final_sku,
                    $description,
                    $variation_image,
                    $fragrance_images_json,
                    $total_qty,
                    $fid,
                    $colors_json,
                    $size_ids_json,
                    $size_prices_str,
                    $size_qtys_str,
                    $boxes_json,
                    $wick_type,
                    $product_id
                );
                $u_stmt->execute();
                $u_stmt->close();
            } else {
                // Multi-fragrance batch save
                foreach ($selected_fragrance_ids as $fid) {
                    $frag_info = $fragrance_map[$fid] ?? null;
                    $frag_name = $frag_info['fragrance_name'] ?? 'Scent';
                    $frag_sku  = !empty($frag_info['sku']) ? $frag_info['sku'] : sprintf('%02d', $fid);

                    $variation_image = $uploaded_fragrance_images[$fid] ?? ($main_image ?: ($frag_info['fragrance_image'] ?? ''));

                    $p_name = !empty($product_name) ? $product_name . ' (' . $frag_name . ')' : $frag_name . ' Candle';
                    $final_sku = strtoupper($vessel_sku . $color_sku . $frag_sku);

                    $check = $conn->prepare("SELECT product_id FROM products WHERE sku = ?");
                    $check->bind_param("s", $final_sku);
                    $check->execute();
                    $cres = $check->get_result();

                    if ($cres && $row = $cres->fetch_assoc()) {
                        $existing_id = (int)$row['product_id'];
                        $check->close();

                        $u_stmt = $conn->prepare("
                            UPDATE products SET 
                                product_name = ?,
                                description = ?,
                                image = ?,
                                fragrance_images = ?,
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
                        $u_stmt->bind_param(
                            "ssssiissssssi",
                            $p_name,
                            $description,
                            $variation_image,
                            $fragrance_images_json,
                            $total_qty,
                            $fid,
                            $colors_json,
                            $size_ids_json,
                            $size_prices_str,
                            $size_qtys_str,
                            $boxes_json,
                            $wick_type,
                            $existing_id
                        );
                        $u_stmt->execute();
                        $u_stmt->close();
                    } else {
                        $check->close();
                        $i_stmt = $conn->prepare("
                            INSERT INTO products
                                (product_name, sku, description, image, fragrance_images, qty,
                                 fragrance_id, color_id, size_id, size_prices, size_qtys, box_id, wick_type,
                                 created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        $i_stmt->bind_param(
                            "sssssiissssss",
                            $p_name,
                            $final_sku,
                            $description,
                            $variation_image,
                            $fragrance_images_json,
                            $total_qty,
                            $fid,
                            $colors_json,
                            $size_ids_json,
                            $size_prices_str,
                            $size_qtys_str,
                            $boxes_json,
                            $wick_type
                        );
                        $i_stmt->execute();
                        $i_stmt->close();
                    }
                }
            }

            $conn->commit();
            $show_success = true;
            $success_message = "Product variation(s) updated successfully! Redirecting...";
            echo '<script>setTimeout(function(){ window.location.href = "' . base_url('/admin/list_product') . '"; }, 1200);</script>';

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
<title><?= $edit_mode ? 'Edit Product' : 'Add Product' ?> — Candle Shop</title>
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
    --danger:      #ef4444;
    --danger-txt:  #991b1b;
    --danger-bg:   #fef2f2;
    --danger-bdr:  #fca5a5;
    --success-txt: #166534;
    --success-bg:  #f0fdf4;
    --success-bdr: #86efac;
    --radius:      10px;
    --radius-lg:   14px;
    --input-h:     42px;
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

.page-header h2 { font-size: 1.5rem; font-weight: 700; color: var(--text); }
.header-actions { display: flex; gap: 10px; align-items: center; }

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
    font-size: 0.88rem;
}

.btn-primary:hover { background: var(--blue-h); }

.btn-back {
    background: #fff;
    color: var(--muted);
    border: 1px solid var(--border);
    padding: 9px 16px;
    border-radius: var(--radius);
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.88rem;
    transition: all 0.15s;
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
    height: 200px;
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

.fragrance-upload-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
    margin-top: 14px;
}

.fragrance-upload-box {
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 10px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8px;
    position: relative;
    transition: border-color 0.2s;
}

.fragrance-upload-box:hover {
    border-color: var(--blue);
    background: #eff6ff;
}

.fragrance-upload-title {
    font-size: 12px;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 4px;
}

.fragrance-img-preview {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: white;
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

.wick-option input[type="radio"] { display: none; }

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
// Pre-fill values
$val_product_name = $_POST['product_name'] ?? ($edit_data['product_name'] ?? '');
$val_sku          = $_POST['sku']          ?? ($edit_data['sku'] ?? '');
$val_description  = $_POST['description']  ?? ($edit_data['description'] ?? '');
$val_fragrance_id = $_POST['fragrance_id'] ?? ($edit_data['fragrance_id'] ?? '');

$val_colors       = $_POST['colors'] ?? ($edit_data['color_ids'] ?? []);
$val_boxes        = $_POST['boxes']  ?? ($edit_data['box_ids'] ?? []);
$val_wick_type    = $_POST['wick_type'] ?? ($edit_data['wick_type'] ?? 'single');

$val_size_id = '';
if (!empty($edit_data['size_ids'])) {
    $val_size_id = $edit_data['size_ids'][0];
}

$val_price = '';
if (!empty($edit_data['size_prices_array']) && is_array($edit_data['size_prices_array'])) {
    $val_price = reset($edit_data['size_prices_array']);
} elseif (isset($edit_data['size_prices']) && is_numeric($edit_data['size_prices'])) {
    $val_price = $edit_data['size_prices'];
}

$val_qty = '';
if (isset($edit_data['qty']) && $edit_data['qty'] !== null && $edit_data['qty'] !== '') {
    $val_qty = $edit_data['qty'];
} elseif (!empty($edit_data['size_qtys_array']) && is_array($edit_data['size_qtys_array'])) {
    $val_qty = reset($edit_data['size_qtys_array']);
}

$val_image = $_POST['existing_image'] ?? ($edit_data['image'] ?? '');
?>

<div class="page-main-content">

    <div class="page-header">
        <div>
            <h2>✏️ Edit Product Variation</h2>
            <p style="color: var(--muted); margin-top: 4px;">Update product variation details and per-fragrance picture uploads.</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo $base; ?>/admin/list_product" class="btn-back">← Back</a>
            <button type="button" onclick="submitForm()" class="btn-primary">✓ Update Product Variation</button>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($show_success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success_message ?: 'Product updated successfully!') ?></div>
    <?php endif; ?>

    <form id="productForm" action="" method="POST" enctype="multipart/form-data" class="product-layout">

        <!-- LEFT COLUMN -->
        <div class="left-column">

            <!-- General Info -->
            <div class="card">
                <h3><span class="icon">📋</span> General Information</h3>

                <div class="form-group">
                    <label>Product Title</label>
                    <input type="text" name="product_name" id="productName"
                           placeholder="e.g. White Frost (Fragrance Free)"
                           value="<?= htmlspecialchars($val_product_name) ?>">
                </div>

                <div class="form-group">
                    <label>SKU Code</label>
                    <input type="text" name="sku" id="productSku"
                           placeholder="Auto-generated if empty"
                           value="<?= htmlspecialchars($val_sku) ?>">
                </div>

                <div class="form-group">
                    <label>Description <span class="req">*</span></label>
                    <textarea name="description" required><?= htmlspecialchars($val_description) ?></textarea>
                </div>
            </div>

            <!-- Fragrance Selector -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--border); margin-bottom: 16px;">
                    <h3 style="padding-bottom:0; border-bottom:none; margin-bottom:0;"><span class="icon">🏷️</span> Fragrance Variations <span class="req">*</span></h3>
                    <button type="button" class="btn-back" style="padding: 4px 10px; font-size: 11px;" onclick="toggleSelectAllFragrances()">
                        ✨ Select / Deselect All
                    </button>
                </div>

                <div class="form-group">
                    <label>Fragrances <small style="text-transform:none;letter-spacing:0;font-weight:400;color:#94a3b8">(Check fragrances to assign pictures)</small></label>
                    <div class="chip-group" id="fragranceChips">
                        <?php foreach ($fragrances_arr as $f): ?>
                            <input type="checkbox" name="fragrances[]"
                                   id="frag-<?= $f['fragrance_id'] ?>"
                                   value="<?= $f['fragrance_id'] ?>"
                                   data-name="<?= htmlspecialchars($f['fragrance_name']) ?>"
                                   data-image="<?= htmlspecialchars($f['fragrance_image'] ?? '') ?>"
                                   onchange="onFragranceSelectionChange(); updateAllSummaries();"
                                   <?= ($val_fragrance_id == $f['fragrance_id']) ? 'checked' : '' ?>>
                            <label for="frag-<?= $f['fragrance_id'] ?>">
                                🏷️ <?= htmlspecialchars($f['fragrance_name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-row" id="fragranceSummary">
                        <span class="summary-empty">No fragrances selected</span>
                    </div>
                </div>

                <!-- INDIVIDUAL FRAGRANCE PICTURE UPLOADS -->
                <div style="margin-top: 20px;" id="fragranceImagesSection">
                    <label style="color: #004b66; font-size: 12px;">📷 Upload Picture for Each Fragrance</label>
                    <div class="fragrance-upload-grid" id="fragranceUploadContainer"></div>
                </div>
            </div>

            <!-- Colors & Boxes -->
            <div class="card">
                <h3><span class="icon">🎨</span> Vessel Color &amp; Packaging</h3>

                <div class="form-group">
                    <label>Vessel Color</label>
                    <div class="chip-group" id="colorChips">
                        <?php foreach ($colors_arr as $c): ?>
                            <input type="radio" name="colors[]"
                                   id="color-<?= $c['color_id'] ?>"
                                   value="<?= $c['color_id'] ?>"
                                   data-sku="<?= htmlspecialchars($c['sku'] ?? '00') ?>"
                                   onchange="updateAllSummaries();"
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
                </div>

                <hr class="section-divider">

                <div class="form-group">
                    <label>Box / Packaging</label>
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
                </div>
            </div>

        </div><!-- /left-column -->

        <!-- RIGHT COLUMN -->
        <div class="right-column">

            <!-- Main Product Image -->
            <div class="card">
                <h3><span class="icon">🖼️</span> Variation Product Image</h3>

                <?php
                $img_preview_url = getImagePathForDisplay($val_image);
                ?>

                <?php if (!empty($val_image)): ?>
                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($val_image) ?>">
                <?php endif; ?>

                <div id="front-preview"
                     class="image-main"
                     onclick="document.getElementById('imageInput').click()"
                     style="<?= $img_preview_url ? 'background-image:url(' . htmlspecialchars($img_preview_url) . '); background-size:cover; background-position:center;' : '' ?>">
                    <?php if (!empty($val_image)): ?>
                        <div style="background: rgba(0,0,0,0.5); color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;">Current Image (Click to change)</div>
                    <?php else: ?>
                        <div class="upload-icon">📷</div>
                        <div class="upload-text">Upload Variation Image</div>
                        <div class="upload-sub">Primary candle render</div>
                    <?php endif; ?>
                </div>
                <input type="file" id="imageInput" name="image"
                       accept="image/*" onchange="previewImage(this)" hidden>

                <p style="font-size:0.75rem;color:#94a3b8;text-align:center;margin-top:10px;">
                    Click image to upload new variation photo
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

                <div class="form-group">
                    <label>Vessel Category (Size) <span class="req">*</span></label>
                    <select name="size_id" id="vesselSelect" class="admin-select" required
                            style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: #fff;">
                        <option value="">Select Vessel Category...</option>
                        <?php foreach ($sizes_arr as $s): ?>
                            <option value="<?= $s['size_id'] ?>"
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
                           value="<?= htmlspecialchars($val_price) ?>"
                           required>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label>Stock Qty</label>
                    <input type="number"
                           name="qty"
                           id="qtyInput"
                           placeholder="100"
                           min="0"
                           value="<?= htmlspecialchars($val_qty) ?>">
                </div>
            </div>

        </div><!-- /right-column -->
    </form>
</div>

<script>
const existingImgUrl = <?= json_encode($img_preview_url ?: '') ?>;

document.addEventListener('DOMContentLoaded', function () {
    updateAllSummaries();
    onFragranceSelectionChange();
});

function toggleSelectAllFragrances() {
    const checkboxes = document.querySelectorAll('#fragranceChips input[type="checkbox"]');
    if (checkboxes.length === 0) return;
    const allChecked = [...checkboxes].every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
    updateAllSummaries();
    onFragranceSelectionChange();
}

function onFragranceSelectionChange() {
    const container = document.getElementById('fragranceUploadContainer');
    const checked = [...document.querySelectorAll('#fragranceChips input[type="checkbox"]:checked')];
    if (!container) return;

    if (checked.length === 0) {
        container.innerHTML = '<div style="font-size:12px; color:#94a3b8; font-style:italic;">Check fragrances above to upload picture for each.</div>';
        return;
    }

    const existingHTMLs = {};
    container.querySelectorAll('.fragrance-upload-box').forEach(box => {
        const fid = box.getAttribute('data-fid');
        if (fid) existingHTMLs[fid] = box;
    });

    container.innerHTML = '';
    checked.forEach(cb => {
        const fid = cb.value;
        const fname = cb.getAttribute('data-name');
        const defaultImg = cb.getAttribute('data-image');

        if (existingHTMLs[fid]) {
            container.appendChild(existingHTMLs[fid]);
        } else {
            const box = document.createElement('div');
            box.className = 'fragrance-upload-box';
            box.setAttribute('data-fid', fid);
            
            let previewSrc = existingImgUrl || 'https://placehold.co/100x100?text=' + encodeURIComponent(fname);
            if (defaultImg) {
                previewSrc = defaultImg.startsWith('http') ? defaultImg : ('<?php echo base_url('/'); ?>' + defaultImg.replace(/^\/+/, ''));
            }

            box.innerHTML = `
                <div class="fragrance-upload-title">🏷️ ${escapeHtml(fname)}</div>
                <img id="frag_prev_${fid}" src="${previewSrc}" class="fragrance-img-preview" alt="${escapeHtml(fname)}">
                <?php if (!empty($val_image)): ?>
                    <input type="hidden" name="existing_fragrance_image_${fid}" value="${escapeHtml(existingImgUrl)}">
                <?php endif; ?>
                <input type="file" name="fragrance_image_${fid}" accept="image/*" onchange="previewIndividualFragranceImage(this, ${fid})" style="font-size: 11px; width: 100%;">
            `;
            container.appendChild(box);
        }
    });
}

function previewIndividualFragranceImage(input, fid) {
    const imgEl = document.getElementById('frag_prev_' + fid);
    if (input.files && input.files[0] && imgEl) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imgEl.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

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

function updateAllSummaries() {
    updateChipSummary('fragranceChips', 'fragranceSummary', 'No fragrances selected');
    updateChipSummary('colorChips',     'colorSummary',     'No colors selected');
    updateChipSummary('boxChips',       'boxSummary',       'No packaging selected');
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

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function submitForm() {
    const vesselSel = document.getElementById('vesselSelect');
    if (!vesselSel || !vesselSel.value) {
        alert('Please select a vessel category (size).');
        return;
    }

    const fragChecked = document.querySelectorAll('#fragranceChips input[type="checkbox"]:checked');
    if (fragChecked.length === 0) {
        alert('Please select at least one fragrance variation.');
        return;
    }

    const priceInput = document.getElementById('priceInput');
    const price = priceInput ? parseFloat(priceInput.value) : 0;
    if (!price || price <= 0) {
        alert('Please enter a valid price.');
        return;
    }

    document.getElementById('productForm').submit();
}
</script>
<?php ob_end_flush(); ?>
</body>
</html>