<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = mime_content_type($_FILES['image']['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $error_message = "Invalid image type. Allowed: JPG, PNG, WEBP, GIF.";
        } else {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $image = uniqid('accessory_', true) . '.' . $ext;
            $imgDir = dirname(__DIR__, 2) . "/public/assets/img/";

            if (!file_exists($imgDir)) {
                mkdir($imgDir, 0755, true);
            }

            move_uploaded_file($_FILES['image']['tmp_name'], $imgDir . $image);
        }
    }

    if (empty($error_message) && !empty($name)) {
        $stmt = $conn->prepare("INSERT INTO accessory (name, description, price, quantity, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $name, $description, $price, $quantity, $image);

        if ($stmt->execute()) {
            echo "<script>window.location.href='" . base_url('/admin/list_accessory?added=1') . "';</script>";
            exit;
        } else {
            $error_message = 'Database insert failed: ' . $conn->error;
        }
    }
}
?>

<div class="admin-wrapper">
    <div style="margin-bottom:20px;">
        <a href="<?= base_url('/admin/list_accessory'); ?>" style="color:#6b7280; text-decoration:none; font-size:14px; font-weight:500;">&larr; Back to Accessories List</a>
    </div>

    <div class="admin-card">
        <h2 class="admin-title" style="margin-bottom:6px;">Add New Candle Accessory</h2>
        <p class="admin-subtitle" style="margin-bottom:24px;">Create a new candle tool, wick trimmer, snuffer, or accessory item.</p>

        <?php if (!empty($error_message)): ?>
            <div style="background:#fde8e8; color:#9b1c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
                
                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Accessory Name *</label>
                    <input type="text" name="name" class="admin-input" placeholder="e.g. Gold Wick Trimmer" required>
                </div>

                <div>
                    <label class="admin-label">Price ($) *</label>
                    <input type="number" step="0.01" name="price" class="admin-input" placeholder="0.00" required>
                </div>

                <div>
                    <label class="admin-label">Stock Quantity *</label>
                    <input type="number" name="quantity" class="admin-input" placeholder="0" required>
                </div>

                <div>
                    <label class="admin-label">Accessory Image</label>
                    <input type="file" name="image" accept="image/*" class="admin-input" style="padding:8px;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Description</label>
                    <textarea name="description" class="admin-textarea" rows="4" placeholder="e.g. Premium matte gold stainless steel candle wick trimmer."></textarea>
                </div>

            </div>

            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" class="admin-btn-primary">Save Accessory</button>
                <a href="<?= base_url('/admin/list_accessory'); ?>" class="admin-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>