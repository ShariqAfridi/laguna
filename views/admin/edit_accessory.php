<?php
// Remove any whitespace before <?php and fix session start
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once("db.php");

$show_success  = false;
$error_message = '';
$accessory_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect if no ID provided
if ($accessory_id <= 0) {
     echo "<script>window.location.href='/list_accessory';</script>";
exit;
}

// Fetch existing accessory data
$query = "SELECT * FROM accessory WHERE accessory_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $accessory_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
      echo "<script>window.location.href='/list_accessory';</script>";
exit;
}

$accessory = $result->fetch_assoc();
$stmt->close();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── FORM SUBMISSION ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $sku       = trim($_POST['sku'] ?? '');
    $price     = floatval(str_replace(',', '', $_POST['price'] ?? 0));
    $quantity  = (int)($_POST['quantity'] ?? 0);
    
    // Keep existing image by default
    $image = $accessory['image'];
    
    // ── IMAGE UPLOAD ──────────────────────────────────────────────────
    if (!empty($_FILES['image']) && isset($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed_types)) {
            $error_message = "Invalid image type. Supported: JPG, PNG, GIF, WEBP.";
        } else {
            // Delete old image if exists
            if (!empty($accessory['image'])) {
                $old_image_paths = [
                    $_SERVER['DOCUMENT_ROOT'] . '/img/' . $accessory['image'],
                    dirname(__DIR__, 2) . '/img/' . $accessory['image'],
                    __DIR__ . '/../img/' . $accessory['image']
                ];
                foreach ($old_image_paths as $old_path) {
                    if (file_exists($old_path)) {
                        unlink($old_path);
                        break;
                    }
                }
            }
            
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('accessory_', true) . '.' . strtolower($ext);
            
            $img_dir = dirname(__DIR__, 2) . "/img/";
            $image_path = $img_dir . $image_name;
            
            if (!file_exists($img_dir)) {
                mkdir($img_dir, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $image = $image_name;
            } else {
                $error_message = "Failed to move uploaded image.";
            }
        }
    }
    
    // ── VALIDATION ────────────────────────────────────────────────────
    if (empty($error_message)) {
        if (empty($name)) {
            $error_message = "Accessory name is required.";
        } elseif (empty($sku)) {
            $error_message = "SKU is required.";
        } elseif ($price <= 0) {
            $error_message = "Please enter a valid price greater than 0.";
        }
    }
    
    // Check if SKU already exists (excluding current accessory)
    if (empty($error_message)) {
        $check_sku = $conn->prepare("SELECT accessory_id FROM accessory WHERE sku = ? AND accessory_id != ?");
        $check_sku->bind_param("si", $sku, $accessory_id);
        $check_sku->execute();
        $check_sku->store_result();
        
        if ($check_sku->num_rows > 0) {
            $error_message = "SKU already exists. Please use a unique SKU.";
        }
        $check_sku->close();
    }
    
    // ── UPDATE DATABASE ───────────────────────────────────────────────
    if (empty($error_message)) {
        $update_stmt = $conn->prepare("
            UPDATE accessory 
            SET name = ?, sku = ?, price = ?, quantity = ?, image = ?, updated_at = NOW()
            WHERE accessory_id = ?
        ");
        
        if (!$update_stmt) {
            $error_message = "Prepare failed: " . $conn->error;
        } else {
            $update_stmt->bind_param("ssdisi", $name, $sku, $price, $quantity, $image, $accessory_id);
            
            if ($update_stmt->execute()) {
                $show_success = true;
                // Refresh accessory data
                $accessory['name'] = $name;
                $accessory['sku'] = $sku;
                $accessory['price'] = $price;
                $accessory['quantity'] = $quantity;
                $accessory['image'] = $image;
                
                echo '<script>setTimeout(function(){ window.location.href = "/list_accessory?updated=1"; }, 1000);</script>';
            } else {
                $error_message = "Update failed: " . $update_stmt->error;
            }
            $update_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Accessory — Candle Shop</title>
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
textarea {
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
}

input:focus, textarea:focus, select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
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

/* ── INFO BOX ── */
.info-box {
    background: var(--blue-l);
    border: 1px solid var(--blue-b);
    border-radius: var(--radius);
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 0.85rem;
    color: var(--blue);
}

.info-box strong {
    font-weight: 700;
}

/* ── CURRENT IMAGE PREVIEW ── */
.current-image {
    margin-bottom: 16px;
    text-align: center;
}

.current-image-label {
    font-size: 0.75rem;
    color: var(--muted);
    margin-bottom: 8px;
    display: block;
}

.current-image-preview {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border);
    margin: 0 auto;
}

</style>
</head>
<body>

<div class="page-main-content">

    <!-- Page Header -->
    <div class="page-header">
        <h2>✏️ Edit Accessory</h2>
        <div class="header-actions">
            <a href="/list_accessory" class="btn-back">← Back to List</a>
            <button type="button" onclick="submitForm()" class="btn-primary">💾 Save Changes</button>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($show_success): ?>
        <div class="alert alert-success">✅ Accessory updated successfully! Redirecting...</div>
    <?php endif; ?>

    <div class="info-box">
        <strong>ℹ️ Note:</strong> Fields marked with <span class="req" style="color:#ef4444;">*</span> are required. 
        Leave image empty to keep the current image.
    </div>

    <form id="accessoryForm" action="" method="POST" enctype="multipart/form-data" class="product-layout">

        <!-- ══════════════════════════════
             LEFT COLUMN
        ══════════════════════════════ -->
        <div class="left-column">

            <!-- Basic Information -->
            <div class="card">
                <h3>📦 Accessory Information</h3>

                <div class="form-group">
                    <label>Accessory Name <span class="req">*</span></label>
                    <input type="text" name="name"
                           placeholder="e.g. Candle Snuffer, Wick Trimmer, Gift Box"
                           required
                           value="<?= htmlspecialchars($accessory['name']) ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>SKU <span class="req">*</span></label>
                        <input type="text" name="sku"
                               placeholder="Unique product code"
                               required
                               value="<?= htmlspecialchars($accessory['sku']) ?>">
                        <small style="font-size: 0.7rem; color: var(--muted); margin-top: 4px; display: block;">
                            Stock Keeping Unit - must be unique
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Price ($) <span class="req">*</span></label>
                        <input type="text" name="price"
                               placeholder="0.00"
                               required
                               value="<?= number_format($accessory['price'], 2) ?>"
                               oninput="formatPrice(this)">
                    </div>
                </div>

                <div class="form-group">
                    <label>Quantity in Stock</label>
                    <input type="number" name="quantity"
                           placeholder="0"
                           min="0"
                           value="<?= (int)$accessory['quantity'] ?>">
                    <small style="font-size: 0.7rem; color: var(--muted); margin-top: 4px; display: block;">
                        Number of units available for sale
                    </small>
                </div>
            </div>

        </div><!-- /left-column -->

        <!-- ══════════════════════════════
             RIGHT COLUMN
        ══════════════════════════════ -->
        <div class="right-column">

            <!-- Image Upload -->
            <div class="card">
                <h3>🖼️ Accessory Image</h3>

                <?php if (!empty($accessory['image'])): ?>
                    <div class="current-image">
                        <div class="current-image-label">Current Image:</div>
                        <?php 
                        $current_image_path = '';
                        $paths_to_check = [
                            $_SERVER['DOCUMENT_ROOT'] . '/img/' . $accessory['image'],
                            dirname(__DIR__, 2) . '/img/' . $accessory['image'],
                            __DIR__ . '/../img/' . $accessory['image']
                        ];
                        foreach ($paths_to_check as $path) {
                            if (file_exists($path)) {
                                $current_image_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                                break;
                            }
                        }
                        ?>
                        <img src="<?= htmlspecialchars($current_image_path ?: '/img/' . $accessory['image']) ?>" 
                             class="current-image-preview" 
                             alt="<?= htmlspecialchars($accessory['name']) ?>">
                    </div>
                <?php endif; ?>

                <div id="image-preview-area"
                     class="image-main"
                     onclick="document.getElementById('imageInput').click()">
                    <div class="upload-icon">📷</div>
                    <div class="upload-text">
                        <?= !empty($accessory['image']) ? 'Click to change image' : 'Click to upload image' ?>
                    </div>
                    <div class="upload-sub">JPG, PNG, GIF, WEBP (leave empty to keep current)</div>
                </div>
                <input type="file" id="imageInput" name="image"
                       accept="image/*" onchange="previewImage(this)" hidden>

                <p style="font-size:0.75rem;color:#94a3b8;text-align:center;margin-top:10px;">
                    <?= !empty($accessory['image']) ? 'Upload a new image to replace the current one' : 'Click the area above to choose a photo' ?>
                </p>
            </div>

        

        </div><!-- /right-column -->
    </form>
</div>

<script>
/* ── PRICE FORMATTING ── */
function formatPrice(input) {
    let v = input.value.replace(/,/g, '').replace(/[^\d.]/g, '');
    const parts = v.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    if (parts.length > 1) {
        input.value = parts[0] + '.' + parts[1].slice(0, 2);
    } else {
        input.value = parts[0];
    }
}

/* ── IMAGE PREVIEW ── */
function previewImage(input) {
    const previewArea = document.getElementById('image-preview-area');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            // Clear existing content
            previewArea.innerHTML = '';
            // Create and add new image
            const img = document.createElement('img');
            img.src = e.target.result;
            previewArea.appendChild(img);
            // Update text for better UX
            const uploadText = document.createElement('div');
            uploadText.style.position = 'absolute';
            uploadText.style.bottom = '10px';
            uploadText.style.left = '0';
            uploadText.style.right = '0';
            uploadText.style.textAlign = 'center';
            uploadText.style.backgroundColor = 'rgba(0,0,0,0.6)';
            uploadText.style.color = 'white';
            uploadText.style.padding = '4px';
            uploadText.style.fontSize = '11px';
            uploadText.style.borderRadius = '4px';
            uploadText.style.margin = '0 auto';
            uploadText.style.width = 'fit-content';
            uploadText.textContent = 'New image (click to change)';
            previewArea.appendChild(uploadText);
        };
        reader.readAsDataURL(input.files[0]);
    } else if (<?= json_encode(!empty($accessory['image'])) ?>) {
        // Reload the current image if no new file selected
        const currentSrc = <?= json_encode($current_image_path ?: '/img/' . $accessory['image']) ?>;
        previewArea.innerHTML = '';
        const img = document.createElement('img');
        img.src = currentSrc;
        previewArea.appendChild(img);
        const uploadText = document.createElement('div');
        uploadText.style.position = 'absolute';
        uploadText.style.bottom = '10px';
        uploadText.style.left = '0';
        uploadText.style.right = '0';
        uploadText.style.textAlign = 'center';
        uploadText.style.backgroundColor = 'rgba(0,0,0,0.6)';
        uploadText.style.color = 'white';
        uploadText.style.padding = '4px';
        uploadText.style.fontSize = '11px';
        uploadText.style.borderRadius = '4px';
        uploadText.style.margin = '0 auto';
        uploadText.style.width = 'fit-content';
        uploadText.textContent = 'Current image (click to change)';
        previewArea.appendChild(uploadText);
    }
}

/* Preserve current image preview on page load if there's an existing image */
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($accessory['image'])): ?>
    // Set up the preview area with current image
    const previewArea = document.getElementById('image-preview-area');
    const currentSrc = <?= json_encode($current_image_path ?: '/img/' . $accessory['image']) ?>;
    previewArea.innerHTML = '';
    const img = document.createElement('img');
    img.src = currentSrc;
    previewArea.appendChild(img);
    const uploadText = document.createElement('div');
    uploadText.style.position = 'absolute';
    uploadText.style.bottom = '10px';
    uploadText.style.left = '0';
    uploadText.style.right = '0';
    uploadText.style.textAlign = 'center';
    uploadText.style.backgroundColor = 'rgba(0,0,0,0.6)';
    uploadText.style.color = 'white';
    uploadText.style.padding = '4px';
    uploadText.style.fontSize = '11px';
    uploadText.style.borderRadius = '4px';
    uploadText.style.margin = '0 auto';
    uploadText.style.width = 'fit-content';
    uploadText.textContent = 'Current image (click to change)';
    previewArea.appendChild(uploadText);
    <?php endif; ?>
    
    // Format price on load
    const priceInput = document.querySelector('input[name="price"]');
    if (priceInput && priceInput.value) {
        formatPrice(priceInput);
    }
});

/* ── FORM SUBMIT VALIDATION ── */
function submitForm() {
    const name = document.querySelector('input[name="name"]');
    if (!name || !name.value.trim()) {
        alert('Please enter an accessory name.');
        name && name.focus();
        return;
    }
    
    const sku = document.querySelector('input[name="sku"]');
    if (!sku || !sku.value.trim()) {
        alert('Please enter a SKU.');
        sku && sku.focus();
        return;
    }
    
    const price = document.querySelector('input[name="price"]');
    let priceValue = price ? parseFloat(price.value.replace(/,/g, '')) : 0;
    if (!price || priceValue <= 0) {
        alert('Please enter a valid price greater than 0.');
        price && price.focus();
        return;
    }
    
    document.getElementById('accessoryForm').submit();
}
</script>
<?php ob_end_flush(); ?>
</body>
</html>