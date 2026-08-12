<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../app/Helpers/ImageOptimizer.php';
use App\Helpers\ImageOptimizer;

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = strtoupper(trim($_POST['sku'] ?? ''));
    $vessel_code = strtoupper(trim($_POST['vessel_code'] ?? ''));
    $box_name = trim($_POST['box_name'] ?? '');
    $box_dimensions = '';
    $box_price = floatval($_POST['box_price'] ?? 0);
    $box_description = trim($_POST['box_description'] ?? '');
    $status = isset($_POST['status']) ? (int) $_POST['status'] : 1;
    $sort_order = (int) ($_POST['sort_order'] ?? 0);

    $box_image = '';
    if (!empty($_FILES['box_image']['tmp_name']) && $_FILES['box_image']['error'] === UPLOAD_ERR_OK) {
        $opt = ImageOptimizer::optimize($_FILES['box_image'], 'uploads/boxes/', 'box_', 1400, 1048576, 85);
        if ($opt['success']) {
            $box_image = 'public/' . $opt['path'];
        } else {
            $error_message = $opt['error'];
        }
    } else {
        $error_message = 'Packaging box image is mandatory!';
    }

    if (empty($error_message) && !empty($box_name)) {
        $stmt = $conn->prepare('INSERT INTO boxes (sku, vessel_code, box_name, box_image, box_dimensions, box_price, box_description, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssdsii', $sku, $vessel_code, $box_name, $box_image, $box_dimensions, $box_price, $box_description, $status, $sort_order);

        if ($stmt->execute()) {
            echo "<script>window.location.href='" . base_url('/admin/boxes') . "';</script>";
            exit;
        } else {
            $error_message = 'Database insert failed: ' . $conn->error;
        }
    }
}
?>

<div class="admin-wrapper">
    <div style="margin-bottom:20px;">
        <a href="<?= base_url('/admin/boxes'); ?>" style="color:#6b7280; text-decoration:none; font-size:14px; font-weight:500;">&larr; Back to Packaging Boxes</a>
    </div>

    <div class="admin-card">
        <h2 class="admin-title" style="margin-bottom:6px;">Add New Box Packaging Design</h2>
        <p class="admin-subtitle" style="margin-bottom:24px;">Upload mandatory box image, set box code (SKU), assign vessel size, and set optional extra packaging cost.</p>

        <?php if (!empty($error_message)): ?>
            <div style="background:#fde8e8; color:#9b1c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
                
                <div>
                    <label class="admin-label">Box Code (SKU) *</label>
                    <input type="text" name="sku" class="admin-input" placeholder="e.g. B01B, B01W, B02B, B02W" required style="text-transform:uppercase;">
                </div>

                <div>
                    <label class="admin-label">Associated Vessel *</label>
                    <select name="vessel_code" class="admin-select" required>
                        <option value="C">Vessel C (10 oz)</option>
                        <option value="D">Vessel D (14 oz)</option>
                        <option value="E">Vessel E (16 oz - No boxes)</option>
                        <option value="">None / General</option>
                    </select>
                </div>

                <div>
                    <label class="admin-label">Box Name *</label>
                    <input type="text" name="box_name" class="admin-input" placeholder="e.g. Black Box (C Candle - 10 oz)" required>
                </div>

                <div>
                    <label class="admin-label">Price ($)</label>
                    <input type="number" step="0.01" name="box_price" class="admin-input" value="6.00" placeholder="0.00">
                </div>

                <div>
                    <label class="admin-label">Box Image * (Mandatory)</label>
                    <input type="file" name="box_image" accept="image/*" class="admin-input" required style="padding:8px;">
                </div>

                <div>
                    <label class="admin-label">Status</label>
                    <select name="status" class="admin-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Description</label>
                    <textarea name="box_description" class="admin-input" rows="3" placeholder="e.g. Double wick · Black cubic keepsake box."></textarea>
                </div>

            </div>

            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" class="admin-btn-primary">Save Box Design</button>
                <a href="<?= base_url('/admin/boxes'); ?>" class="admin-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
