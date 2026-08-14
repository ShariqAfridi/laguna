<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../app/Helpers/ImageOptimizer.php';
use App\Helpers\ImageOptimizer;

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name                  = trim($_POST['fragrance_name'] ?? '');
    $sku                   = trim($_POST['sku'] ?? '');
    $fragrance_description = trim($_POST['fragrance_description'] ?? '');
    $status                = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $sort_order            = (int)($_POST['sort_order'] ?? 0);

    $fragrance_image = '';
    if (!empty($_FILES['fragrance_image']['tmp_name']) && $_FILES['fragrance_image']['error'] === UPLOAD_ERR_OK) {
        $opt = ImageOptimizer::optimize($_FILES['fragrance_image'], 'uploads/fragrances/', 'frag_', 1400, 1048576, 85);
        if ($opt['success']) {
            $fragrance_image = 'public/' . $opt['path'];
        } else {
            $error_message = $opt['error'];
        }
    }

    $scent_note_image = '';
    if (!empty($_FILES['scent_note_image']['tmp_name']) && $_FILES['scent_note_image']['error'] === UPLOAD_ERR_OK) {
        $opt = ImageOptimizer::optimize($_FILES['scent_note_image'], 'uploads/fragrances/', 'scent_note_', 1400, 1048576, 85);
        if ($opt['success']) {
            $scent_note_image = 'public/' . $opt['path'];
        } else {
            $error_message = $opt['error'];
        }
    }

    if (empty($error_message) && !empty($name)) {
        $stmt = $conn->prepare("INSERT INTO fragrances (fragrance_name, sku, fragrance_image, scent_note_image, fragrance_description, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssii", $name, $sku, $fragrance_image, $scent_note_image, $fragrance_description, $status, $sort_order);

        if ($stmt->execute()) {
            echo "<script>window.location.href='" . base_url('/admin/fragrance') . "';</script>";
            exit;
        } else {
            $error_message = 'Database insert failed: ' . $conn->error;
        }
    }
}
?>

<div class="admin-wrapper">
    <div style="margin-bottom:20px;">
        <a href="<?= base_url('/admin/fragrance'); ?>" style="color:#6b7280; text-decoration:none; font-size:14px; font-weight:500;">&larr; Back to Fragrance Profiles</a>
    </div>

    <div class="admin-card">
        <h2 class="admin-title" style="margin-bottom:6px;">Add New Fragrance Profile</h2>
        <p class="admin-subtitle" style="margin-bottom:24px;">Define scent notes, aromatic ingredients, and upload fragrance & scent note images.</p>

        <?php if (!empty($error_message)): ?>
            <div style="background:#fde8e8; color:#9b1c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
                
                <div>
                    <label class="admin-label">Fragrance Name *</label>
                    <input type="text" name="fragrance_name" class="admin-input" placeholder="e.g. Amber Musk & Warm Vanilla" required>
                </div>

                <div>
                    <label class="admin-label">SKU</label>
                    <input type="text" name="sku" class="admin-input" placeholder="e.g. 02">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Description / Scent Notes</label>
                    <textarea name="fragrance_description" class="admin-input" rows="4" style="resize:vertical; line-height:1.5; font-family:inherit; min-height:90px;" placeholder="Enter top, mid, and base scent notes or fragrance description..."></textarea>
                </div>

                <div>
                    <label class="admin-label">Fragrance Image</label>
                    <input type="file" name="fragrance_image" accept="image/*" class="admin-input" style="padding:8px;">
                </div>

                <div>
                    <label class="admin-label">Scent Notes Image</label>
                    <input type="file" name="scent_note_image" accept="image/*" class="admin-input" style="padding:8px;">
                </div>

                <div>
                    <label class="admin-label">Status</label>
                    <select name="status" class="admin-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <!--
                <div>
                    <label class="admin-label">Sort Order</label>
                    <input type="number" name="sort_order" class="admin-input" value="0">
                </div>
                -->

            </div>

            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" class="admin-btn-primary">Save Fragrance Profile</button>
                <a href="<?= base_url('/admin/fragrance'); ?>" class="admin-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
