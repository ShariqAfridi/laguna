<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../app/Helpers/ImageOptimizer.php';
use App\Helpers\ImageOptimizer;

$error_message = '';
$accessory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($accessory_id <= 0) {
    header("Location: " . base_url('/admin/list_accessory'));
    exit;
}

$stmt = $conn->prepare("SELECT * FROM accessory WHERE accessory_id = ?");
$stmt->bind_param("i", $accessory_id);
$stmt->execute();
$accessory = $stmt->get_result()->fetch_assoc();

if (!$accessory) {
    header("Location: " . base_url('/admin/list_accessory'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    $image = $accessory['image'];

    if (!empty($_FILES['image']) && isset($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $opt = ImageOptimizer::optimize($_FILES['image'], 'uploads/accessories/', 'accessory_', 1400, 1048576, 85);
        if ($opt['success']) {
            $image = basename($opt['path']);
        } else {
            $error_message = $opt['error'];
        }
    }

    if (empty($error_message) && !empty($name)) {
        $update_stmt = $conn->prepare("UPDATE accessory SET name=?, description=?, price=?, quantity=?, image=? WHERE accessory_id=?");
        $update_stmt->bind_param("ssdisi", $name, $description, $price, $quantity, $image, $accessory_id);

        if ($update_stmt->execute()) {
            echo "<script>window.location.href='" . base_url('/admin/list_accessory?updated=1') . "';</script>";
            exit;
        } else {
            $error_message = 'Database update failed: ' . $conn->error;
        }
    }
}
?>

<div class="admin-wrapper">
    <div style="margin-bottom:20px;">
        <a href="<?= base_url('/admin/list_accessory'); ?>" style="color:#6b7280; text-decoration:none; font-size:14px; font-weight:500;">&larr; Back to Accessories List</a>
    </div>

    <div class="admin-card">
        <h2 class="admin-title" style="margin-bottom:6px;">Edit Candle Accessory</h2>
        <p class="admin-subtitle" style="margin-bottom:24px;">Update accessory details for <?= htmlspecialchars($accessory['name'] ?? $accessory['accessory_name'] ?? ''); ?>.</p>

        <?php if (!empty($error_message)): ?>
            <div style="background:#fde8e8; color:#9b1c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
                
                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Accessory Name *</label>
                    <input type="text" name="name" class="admin-input" value="<?= htmlspecialchars($accessory['name'] ?? $accessory['accessory_name'] ?? ''); ?>" required>
                </div>

                <div>
                    <label class="admin-label">Price ($) *</label>
                    <input type="number" step="0.01" name="price" class="admin-input" value="<?= number_format((float)($accessory['price'] ?? 0), 2, '.', ''); ?>" required>
                </div>

                <div>
                    <label class="admin-label">Stock Quantity *</label>
                    <input type="number" name="quantity" class="admin-input" value="<?= (int)($accessory['quantity'] ?? 0); ?>" required>
                </div>

                <div>
                    <label class="admin-label">Accessory Image</label>
                    <input type="file" name="image" accept="image/*" class="admin-input" style="padding:8px;">
                    <?php if (!empty($accessory['image'])): ?>
                        <div style="margin-top:8px;">
                            <img src="<?= htmlspecialchars(base_url('/public/assets/img/' . ltrim($accessory['image'], '/'))); ?>" alt="Current Image" class="admin-thumb">
                        </div>
                    <?php else: ?>
                        <div style="margin-top:8px;">
                            <div class="admin-no-thumb">No<br>Image</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Description</label>
                    <textarea name="description" class="admin-textarea" rows="4"><?= htmlspecialchars($accessory['description'] ?? ''); ?></textarea>
                </div>

            </div>

            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" class="admin-btn-primary">Update Accessory</button>
                <a href="<?= base_url('/admin/list_accessory'); ?>" class="admin-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>