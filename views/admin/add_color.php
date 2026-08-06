<?php
require_once __DIR__ . '/../../db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku        = trim($_POST['sku'] ?? '');
    $color_name = trim($_POST['color_name'] ?? '');
    $raw_hex    = trim($_POST['color_hex'] ?? '#687382');
    $clean_hex  = preg_replace('/[^0-9A-Fa-f]/', '', $raw_hex);
    if (strlen($clean_hex) === 3) {
        $color_hex = '#' . $clean_hex[0].$clean_hex[0].$clean_hex[1].$clean_hex[1].$clean_hex[2].$clean_hex[2];
    } elseif (strlen($clean_hex) >= 6) {
        $color_hex = '#' . substr($clean_hex, 0, 6);
    } else {
        $color_hex = '#' . str_pad($clean_hex, 6, '0');
    }
    $color_hex  = strtoupper($color_hex);
    $status     = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $upload_dir = __DIR__ . '/../../public/uploads/colors/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    // Helper for file upload
    $upload_file = function($key, $prefix) use ($upload_dir, $allowed, &$error_message) {
        if (!empty($_FILES[$key]['tmp_name'])) {
            $file = $_FILES[$key];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $target_file = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    return 'public/uploads/colors/' . $filename;
                } else {
                    $error_message = "Failed to upload {$prefix} image.";
                }
            } else {
                $error_message = 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF.';
            }
        }
        return '';
    };

    $color_image        = '';
    $single_wick_image  = $upload_file('single_wick_image', 'color_single');
    $double_wick_image  = $upload_file('double_wick_image', 'color_double');
    $triple_wick_image  = $upload_file('triple_wick_image', 'color_triple');

    if (empty($error_message) && !empty($color_name)) {
        $stmt = $conn->prepare("INSERT INTO colors (sku, color_name, color_hex, color_image, single_wick_image, double_wick_image, triple_wick_image, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssii", $sku, $color_name, $color_hex, $color_image, $single_wick_image, $double_wick_image, $triple_wick_image, $status, $sort_order);

        if ($stmt->execute()) {
            echo "<script>window.location.href='" . base_url('/admin/colors') . "';</script>";
            exit;
        } else {
            $error_message = 'Database insert failed: ' . $conn->error;
        }
    }
}
?>

<div class="admin-wrapper">
    <div style="margin-bottom:20px;">
        <a href="<?= base_url('/admin/colors'); ?>" style="color:#6b7280; text-decoration:none; font-size:14px; font-weight:500;">&larr; Back to Color Variants</a>
    </div>

    <div class="admin-card">
        <h2 class="admin-title" style="margin-bottom:6px;">Add New Color Variant</h2>
        <p class="admin-subtitle" style="margin-bottom:24px;">Define color swatches and upload wick-specific images for Single Wick (Vessel C), Double Wick (Vessel D), and Triple Wick (Vessel E).</p>

        <?php if (!empty($error_message)): ?>
            <div style="background:#fde8e8; color:#9b1c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
                
                <div>
                    <label class="admin-label">Color SKU</label>
                    <input type="text" name="sku" class="admin-input" placeholder="e.g. 02, 03, 12">
                </div>

                <div>
                    <label class="admin-label">Color Name *</label>
                    <input type="text" name="color_name" class="admin-input" placeholder="e.g. Ocean Blue" required>
                </div>

                <div>
                    <label class="admin-label">Hex Code & Color Picker *</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="color" id="colorPicker" value="#687382" style="width:42px; height:42px; border:1px solid #d1d5db; border-radius:8px; cursor:pointer; padding:3px; background:#fff; flex-shrink:0;" title="Click to pick color">
                        <input type="text" name="color_hex" id="color_hex" class="admin-input" value="#687382" maxlength="7" placeholder="#687382" required style="font-family:monospace; font-weight:600; text-transform:uppercase;">
                    </div>
                </div>

                <div>
                    <label class="admin-label">Single Wick Image (Vessel C)</label>
                    <input type="file" name="single_wick_image" accept="image/*" class="admin-input" style="padding:8px;">
                </div>

                <div>
                    <label class="admin-label">Double Wick Image (Vessel D)</label>
                    <input type="file" name="double_wick_image" accept="image/*" class="admin-input" style="padding:8px;">
                </div>

                <div>
                    <label class="admin-label">Triple Wick Image (Vessel E)</label>
                    <input type="file" name="triple_wick_image" accept="image/*" class="admin-input" style="padding:8px;">
                </div>

                <div>
                    <label class="admin-label">Status</label>
                    <select name="status" class="admin-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

            </div>

            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" class="admin-btn-primary">Save Color Variant</button>
                <a href="<?= base_url('/admin/colors'); ?>" class="admin-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const picker = document.getElementById("colorPicker");
const hexInput = document.getElementById("color_hex");

function syncColor(val) {
    if (!val) return;
    let clean = val.replace(/[^0-9A-Fa-f]/g, '');
    if (clean.length === 0) return;
    
    let fullHex;
    if (clean.length === 3) {
        fullHex = '#' + clean[0] + clean[0] + clean[1] + clean[1] + clean[2] + clean[2];
    } else {
        fullHex = '#' + (clean + '000000').substring(0, 6);
    }
    
    try {
        picker.value = fullHex;
    } catch(e) {}
}

if (picker) {
    picker.addEventListener("input", (e) => {
        hexInput.value = e.target.value.toUpperCase();
    });
}

if (hexInput) {
    hexInput.addEventListener("input", (e) => {
        syncColor(e.target.value.trim());
    });
    hexInput.addEventListener("blur", (e) => {
        let val = e.target.value.trim();
        if (val) {
            if (!val.startsWith('#')) val = '#' + val;
            let clean = val.replace(/[^0-9A-Fa-f]/g, '');
            if (clean.length > 0) {
                e.target.value = '#' + clean.toUpperCase();
            }
        }
    });
}
</script>
