<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../app/Helpers/ImageOptimizer.php';
use App\Helpers\ImageOptimizer;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: " . base_url('/admin/fragrance'));
    exit;
}

$stmt = $conn->prepare("SELECT * FROM fragrances WHERE fragrance_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$fragrance = $stmt->get_result()->fetch_assoc();

if (!$fragrance) {
    header("Location: " . base_url('/admin/fragrance'));
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name                  = trim($_POST['fragrance_name'] ?? '');
    $sku                   = trim($_POST['sku'] ?? '');
    $fragrance_description = trim($_POST['fragrance_description'] ?? '');
    $status                = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $sort_order            = (int)($_POST['sort_order'] ?? 0);

    $fragrance_image = $fragrance['fragrance_image'] ?? '';
    if (!empty($_FILES['fragrance_image']['tmp_name']) && $_FILES['fragrance_image']['error'] === UPLOAD_ERR_OK) {
        $opt = ImageOptimizer::optimize($_FILES['fragrance_image'], 'uploads/fragrances/', 'frag_', 1400, 1048576, 85);
        if ($opt['success']) {
            $fragrance_image = 'public/' . $opt['path'];
        } else {
            $error_message = $opt['error'];
        }
    }

    $scent_note_image = $fragrance['scent_note_image'] ?? '';
    if (!empty($_FILES['scent_note_image']['tmp_name']) && $_FILES['scent_note_image']['error'] === UPLOAD_ERR_OK) {
        $opt = ImageOptimizer::optimize($_FILES['scent_note_image'], 'uploads/fragrances/', 'scent_note_', 1400, 1048576, 85);
        if ($opt['success']) {
            $scent_note_image = 'public/' . $opt['path'];
        } else {
            $error_message = $opt['error'];
        }
    }

    if (empty($error_message) && !empty($name)) {
        $update_stmt = $conn->prepare("UPDATE fragrances SET fragrance_name=?, sku=?, fragrance_image=?, scent_note_image=?, fragrance_description=?, status=?, sort_order=? WHERE fragrance_id=?");
        $update_stmt->bind_param("sssssiii", $name, $sku, $fragrance_image, $scent_note_image, $fragrance_description, $status, $sort_order, $id);

        if ($update_stmt->execute()) {
            echo "<script>window.location.href='" . base_url('/admin/fragrance') . "';</script>";
            exit;
        } else {
            $error_message = 'Database update failed: ' . $conn->error;
        }
    }
}
?>

<div class="admin-wrapper">
    <div style="margin-bottom:20px;">
        <a href="<?= base_url('/admin/fragrance'); ?>" style="color:#6b7280; text-decoration:none; font-size:14px; font-weight:500;">&larr; Back to Fragrance Profiles</a>
    </div>

    <div class="admin-card">
        <h2 class="admin-title" style="margin-bottom:6px;">Edit Fragrance Profile</h2>
        <p class="admin-subtitle" style="margin-bottom:24px;">Update details for <?= htmlspecialchars($fragrance['fragrance_name']); ?>.</p>

        <?php if (!empty($error_message)): ?>
            <div style="background:#fde8e8; color:#9b1c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
                
                <div>
                    <label class="admin-label">Fragrance Name *</label>
                    <input type="text" name="fragrance_name" class="admin-input" value="<?= htmlspecialchars($fragrance['fragrance_name']); ?>" required>
                </div>

                <div>
                    <label class="admin-label">SKU</label>
                    <input type="text" name="sku" class="admin-input" value="<?= htmlspecialchars($fragrance['sku'] ?? ''); ?>" placeholder="e.g. 02">
                </div>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

                <div style="grid-column: 1 / -1;">
                    <label class="admin-label">Description / Scent Notes</label>
                    <textarea name="fragrance_description" id="fragrance_description_editor" class="admin-input" rows="5" placeholder="Enter top, mid, and base scent notes or fragrance description..."><?= htmlspecialchars($fragrance['fragrance_description'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label class="admin-label">Fragrance Image</label>
                    <input type="file" name="fragrance_image" accept="image/*" class="admin-input" style="padding:8px;">
                    <?php if (!empty($fragrance['fragrance_image'])): ?>
                        <div style="margin-top:12px; padding:12px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; display:inline-block;">
                            <div style="font-size:12px; font-weight:600; color:#4b5563; margin-bottom:8px;">Current Fragrance Image:</div>
                            <img src="<?= htmlspecialchars(base_url('/' . ltrim($fragrance['fragrance_image'], '/'))); ?>" alt="Current Fragrance Image" style="width:160px; height:160px; object-fit:contain; border-radius:8px; background:#fff; border:1px solid #d1d5db; padding:4px; box-shadow:0 2px 6px rgba(0,0,0,0.06); display:block;">
                        </div>
                    <?php else: ?>
                        <div style="margin-top:12px;">
                            <div class="admin-no-thumb" style="width:120px; height:120px; font-size:13px;">No Fragrance<br>Image</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="admin-label">Scent Notes Image</label>
                    <input type="file" name="scent_note_image" accept="image/*" class="admin-input" style="padding:8px;">
                    <?php if (!empty($fragrance['scent_note_image'])): ?>
                        <div style="margin-top:12px; padding:12px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; display:inline-block;">
                            <div style="font-size:12px; font-weight:600; color:#4b5563; margin-bottom:8px;">Current Scent Notes Image:</div>
                            <img src="<?= htmlspecialchars(base_url('/' . ltrim($fragrance['scent_note_image'], '/'))); ?>" alt="Current Scent Notes Image" style="width:160px; height:160px; object-fit:contain; border-radius:8px; background:#fff; border:1px solid #d1d5db; padding:4px; box-shadow:0 2px 6px rgba(0,0,0,0.06); display:block;">
                        </div>
                    <?php else: ?>
                        <div style="margin-top:12px;">
                            <div class="admin-no-thumb" style="width:120px; height:120px; font-size:13px;">No Scent Notes<br>Image</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="admin-label">Status</label>
                    <select name="status" class="admin-select">
                        <option value="1" <?= ($fragrance['status'] ?? 1) == 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?= ($fragrance['status'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <!--
                <div>
                    <label class="admin-label">Sort Order</label>
                    <input type="number" name="sort_order" class="admin-input" value="<?= (int)($fragrance['sort_order'] ?? 0); ?>">
                </div>
                -->

            </div>

            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" class="admin-btn-primary">Update Fragrance Profile</button>
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
</div>
