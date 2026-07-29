<?php
require_once __DIR__ . '/../../db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: " . base_url('/admin/boxes'));
    exit;
}

$stmt = $conn->prepare("SELECT * FROM boxes WHERE box_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$box = $stmt->get_result()->fetch_assoc();

if (!$box) {
    header("Location: " . base_url('/admin/boxes'));
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $box_name        = trim($_POST['box_name'] ?? '');
    $box_dimensions  = $box['box_dimensions'] ?? '';
    $box_price       = floatval($_POST['box_price'] ?? 0);
    $box_description = trim($_POST['box_description'] ?? '');
    $status          = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $sort_order      = (int)($_POST['sort_order'] ?? 0);

    $box_image = $box['box_image'] ?? '';
    if (!empty($_FILES['box_image']['tmp_name'])) {
        $file = $_FILES['box_image'];
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../../public/uploads/boxes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = 'box_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $box_image = 'public/uploads/boxes/' . $filename;
            } else {
                $error_message = 'Failed to upload box image.';
            }
        } else {
            $error_message = 'Invalid image format.';
        }
    }

    if (empty($error_message) && !empty($box_name)) {
        $update_stmt = $conn->prepare("UPDATE boxes SET box_name=?, box_image=?, box_dimensions=?, box_price=?, box_description=?, status=?, sort_order=? WHERE box_id=?");
        $update_stmt->bind_param("sssdsiii", $box_name, $box_image, $box_dimensions, $box_price, $box_description, $status, $sort_order, $id);

        if ($update_stmt->execute()) {
            echo "<script>window.location.href='" . base_url('/admin/boxes') . "';</script>";
            exit;
        } else {
            $error_message = 'Database update failed: ' . $conn->error;
        }
    }
}
?>

<div class="admin-wrapper">
    <div style="margin-bottom:20px;">
        <a href="<?= base_url('/admin/boxes'); ?>" style="color:#6b7280; text-decoration:none; font-size:14px; font-weight:500;">&larr; Back to Packaging Boxes</a>
    </div>

    <div class="admin-card">
        <h2 class="admin-title" style="margin-bottom:6px;">Edit Box Packaging Design</h2>
        <p class="admin-subtitle" style="margin-bottom:24px;">Update details for <?= htmlspecialchars($box['box_name']); ?>.</p>

        <?php if (!empty($error_message)): ?>
            <div style="background:#fde8e8; color:#9b1c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
                
                <div>
                    <label class="admin-label">Box Name *</label>
                    <input type="text" name="box_name" class="admin-input" value="<?= htmlspecialchars($box['box_name']); ?>" required>
                </div>

                <div>
                    <label class="admin-label">Price ($)</label>
                    <input type="number" step="0.01" name="box_price" class="admin-input" value="<?= number_format((float)($box['box_price'] ?? 0), 2, '.', ''); ?>">
                </div>

                <div>
                    <label class="admin-label">Box Image</label>
                    <input type="file" name="box_image" accept="image/*" class="admin-input" style="padding:8px;">
                    <?php if (!empty($box['box_image'])): ?>
                        <div style="margin-top:8px;">
                            <img src="<?= htmlspecialchars(base_url('/' . ltrim($box['box_image'], '/'))); ?>" alt="Current Image" class="admin-thumb">
                        </div>
                    <?php else: ?>
                        <div style="margin-top:8px;">
                            <div class="admin-no-thumb">No<br>Image</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="admin-label">Status</label>
                    <select name="status" class="admin-select">
                        <option value="1" <?= ($box['status'] ?? 1) == 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?= ($box['status'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Description</label>
                    <textarea name="box_description" class="admin-input" rows="3" placeholder="e.g. Double wick · Black cubic keepsake box."><?= htmlspecialchars($box['box_description'] ?? ''); ?></textarea>
                </div>

            </div>

            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" class="admin-btn-primary">Update Box Design</button>
                <a href="<?= base_url('/admin/boxes'); ?>" class="admin-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
