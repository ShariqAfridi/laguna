<?php
require_once __DIR__ . '/../../db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name                  = trim($_POST['fragrance_name'] ?? '');
    $fragrance_description = trim($_POST['fragrance_description'] ?? '');
    $status                = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $sort_order            = (int)($_POST['sort_order'] ?? 0);

    $fragrance_image = '';
    if (!empty($_FILES['fragrance_image']['tmp_name'])) {
        $file = $_FILES['fragrance_image'];
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../../public/uploads/fragrances/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = 'frag_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $fragrance_image = 'public/uploads/fragrances/' . $filename;
            } else {
                $error_message = 'Failed to upload fragrance image.';
            }
        } else {
            $error_message = 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF.';
        }
    }

    $scent_note_image = '';
    if (!empty($_FILES['scent_note_image']['tmp_name'])) {
        $file = $_FILES['scent_note_image'];
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../../public/uploads/fragrances/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = 'scent_note_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $scent_note_image = 'public/uploads/fragrances/' . $filename;
            } else {
                $error_message = 'Failed to upload scent note image.';
            }
        } else {
            $error_message = 'Invalid scent note image format.';
        }
    }

    if (empty($error_message) && !empty($name)) {
        $stmt = $conn->prepare("INSERT INTO fragrances (fragrance_name, fragrance_image, scent_note_image, fragrance_description, status, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $name, $fragrance_image, $scent_note_image, $fragrance_description, $status, $sort_order);

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
                
                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Fragrance Name *</label>
                    <input type="text" name="fragrance_name" class="admin-input" placeholder="e.g. Amber Musk & Warm Vanilla" required>
                </div>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Description / Scent Notes</label>
                    <textarea name="fragrance_description" id="fragrance_description_editor" class="admin-input" rows="5" placeholder="Enter top, mid, and base scent notes or fragrance description..."></textarea>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof ClassicEditor !== 'undefined') {
        ClassicEditor
            .create(document.querySelector('#fragrance_description_editor'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo']
            })
            .catch(error => {
                console.error(error);
            });
    }
});
</script>
