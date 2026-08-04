<?php
require_once __DIR__ . '/../../db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    header('Location: ' . base_url('/admin/categories'));
    exit;
}

$stmt = $conn->prepare('SELECT * FROM categories WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
    header('Location: ' . base_url('/admin/categories'));
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $category_name), '-'));
    $slug = preg_replace('/-+/', '-', $slug);
    if (empty($slug)) { $slug = $category['slug'] ?? ('category-' . $id); }

    $sku                 = trim($_POST['sku'] ?? '');
    $dimensions_subtitle = trim($_POST['dimensions_subtitle'] ?? '');
    $size_badge          = $category['size_badge'] ?? '';
    $burn_time_badge     = trim($_POST['burn_time_badge'] ?? '');
    $wick_type           = trim($_POST['wick_type'] ?? '');
    $status              = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $sort_order          = (int)($_POST['sort_order'] ?? 0);

    $image_path = $category['image'];
    if (!empty($_FILES['category_image']['tmp_name'])) {
        $file = $_FILES['category_image'];
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../../public/uploads/categories/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = 'cat_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $image_path = 'public/uploads/categories/' . $filename;
            } else {
                $error_message = 'Failed to upload image.';
            }
        } else {
            $error_message = 'Invalid image format.';
        }
    }

    if (empty($error_message) && !empty($category_name)) {
        $update_stmt = $conn->prepare('UPDATE categories SET category_name=?, description=?, slug=?, sku=?, dimensions_subtitle=?, image=?, size_badge=?, burn_time_badge=?, wick_type=?, status=?, sort_order=? WHERE id=?');
        $update_stmt->bind_param('sssssssssiii', $category_name, $description, $slug, $sku, $dimensions_subtitle, $image_path, $size_badge, $burn_time_badge, $wick_type, $status, $sort_order, $id);

        if ($update_stmt->execute()) {
            echo "<script>window.location.href='" . base_url('/admin/categories') . "';</script>";
            exit;
        } else {
            $error_message = 'Database update failed: ' . $conn->error;
        }
    }
}
?>

<div class="admin-wrapper">
    <div style="margin-bottom:20px;">
        <a href="<?= base_url('/admin/categories'); ?>" style="color:#6b7280; text-decoration:none; font-size:14px; font-weight:500;">&larr; Back to Vessel Categories</a>
    </div>

    <div class="admin-card">
        <h2 class="admin-title" style="margin-bottom:6px;">Edit Vessel Category</h2>
        <p class="admin-subtitle" style="margin-bottom:24px;">Update category details, images, or specifications for <?= htmlspecialchars($category['category_name']); ?>.</p>

        <?php if (!empty($error_message)): ?>
            <div style="background:#fde8e8; color:#9b1c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:20px;">
                
                <div>
                    <label class="admin-label">Category Name *</label>
                    <input type="text" name="category_name" class="admin-input" value="<?= htmlspecialchars($category['category_name']); ?>" required>
                </div>

                <div>
                    <label class="admin-label">SKU</label>
                    <input type="text" name="sku" class="admin-input" value="<?= htmlspecialchars($category['sku'] ?? ''); ?>" placeholder="e.g. C">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Description</label>
                    <textarea name="description" class="admin-input" rows="3" placeholder="Enter vessel category description..."><?= htmlspecialchars($category['description'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label class="admin-label">Dimensions</label>
                    <input type="text" name="dimensions_subtitle" class="admin-input" value="<?= htmlspecialchars($category['dimensions_subtitle'] ?? ''); ?>">
                </div>

                <div>
                    <label class="admin-label">Burn Time Badge</label>
                    <input type="text" name="burn_time_badge" class="admin-input" value="<?= htmlspecialchars($category['burn_time_badge'] ?? ''); ?>">
                </div>

                <div>
                    <label class="admin-label">Wick Type</label>
                    <input type="text" name="wick_type" class="admin-input" value="<?= htmlspecialchars($category['wick_type'] ?? ''); ?>">
                </div>

                <div>
                    <label class="admin-label">Category Image</label>
                    <input type="file" name="category_image" accept="image/*" class="admin-input" style="padding:8px;">
                    <?php if (!empty($category['image'])): ?>
                        <div style="margin-top:8px;">
                            <img src="<?= htmlspecialchars(base_url('/' . ltrim($category['image'], '/'))); ?>" alt="Current Image" class="admin-thumb">
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
                        <option value="1" <?= $category['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?= $category['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <!--
                <div>
                    <label class="admin-label">Sort Order</label>
                    <input type="number" name="sort_order" class="admin-input" value="<?= (int) ($category['sort_order'] ?? 0); ?>">
                </div>
                -->

            </div>

            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" class="admin-btn-primary">Update Category</button>
                <a href="<?= base_url('/admin/categories'); ?>" class="admin-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
