<?php
require_once __DIR__ . '/../../db.php';

// ===== HANDLE INSERT / UPDATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['size_id'];
    $name = trim($_POST['size_name']);
    $details = trim($_POST['size_details']);

    if (!empty($id)) {
        $stmt = $conn->prepare("UPDATE sizes SET size_name=?, size_details=? WHERE size_id=?");
        $stmt->bind_param("ssi", $name, $details, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO sizes (size_name, size_details) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $details);
    }

    $stmt->execute();
    echo "<script>window.location.href='<?php echo base_url('/admin/sizes'); ?>';</script>";
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM sizes WHERE size_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>window.location.href='<?php echo base_url('/admin/sizes'); ?>';</script>";
    exit;
}

// ===== FETCH =====
$sizes = $conn->query("SELECT * FROM sizes ORDER BY size_id DESC");
?>

<div class="page-content-wrapper">
    <div class="category-container">
        <div class="header-flex">
            <h2>Manage Sizes</h2>
            <p class="subtitle">Add or edit product dimensions and burn times.</p>
        </div>

        <!-- FORM -->
        <form method="post" id="sizeForm" class="input-group">
            <input type="hidden" name="size_id" id="edit_id">

            <div class="field">
                <label>Size Name</label>
                <input type="text" name="size_name" id="size_name" placeholder="e.g. Vessel C" required>
            </div>

            <div class="field">
                <label>Details</label>
                <input type="text" name="size_details" id="size_details" placeholder='e.g. 3" · 45 hrs' required>
            </div>

            <div class="actions">
                <button type="submit" id="submitBtn">Add Size</button>
                <button type="button" onclick="resetForm()" id="cancelBtn" style="display:none;" class="btn-cancel">Cancel</button>
            </div>
        </form>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="category-table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Size Name</th>
                        <th>Details</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($row = $sizes->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><strong><?= htmlspecialchars($row['size_name']); ?></strong></td>
                        <td><span class="badge-detail"><?= htmlspecialchars($row['size_details']); ?></span></td>
                        <td style="text-align: right;">
                            <button class="action-btn edit" onclick="editRow(
                                <?= $row['size_id']; ?>,
                                '<?= htmlspecialchars($row['size_name'], ENT_QUOTES); ?>',
                                '<?= htmlspecialchars($row['size_details'], ENT_QUOTES); ?>'
                            )">✏️</button>

                            <a href="javascript:void(0);" class="action-btn delete" 
                               onclick="confirmDelete(<?= $row['size_id']; ?>)">🗑️</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// ===== DELETE CONFIRMATION =====
function confirmDelete(id) {
    if(confirm('Are you sure you want to delete this size?')) {
        window.location.href = '/sizes?action=delete&id=' + id;
    }
}

// ===== EDIT =====
function editRow(id, name, details) {
    document.getElementById('edit_id').value = id;
    document.getElementById('size_name').value = name;
    document.getElementById('size_details').value = details;

    document.getElementById('submitBtn').innerText = "Update Size";
    document.getElementById('submitBtn').style.background = "#28a745";
    document.getElementById('cancelBtn').style.display = "inline-block";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ===== RESET =====
function resetForm() {
    document.getElementById('edit_id').value = "";
    document.getElementById('sizeForm').reset();
    document.getElementById('submitBtn').innerText = "Add Size";
    document.getElementById('submitBtn').style.background = "#007bff";
    document.getElementById('cancelBtn').style.display = "none";
}
</script>

<style>
/* 
   IMPORTANT: margin-left is removed. 
   Body margin is handled by your main sidebar layout.
*/
.page-content-wrapper {
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.header-flex {
    margin-bottom: 25px;
}

.subtitle {
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}

.category-container {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.input-group {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #f0f0f0;
    align-items: flex-end;
}

.field {
    flex: 1;
    min-width: 200px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field label {
    font-size: 13px;
    font-weight: 600;
    color: #444;
}

input {
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

input:focus {
    outline: none;
    border-color: #007bff;
}

button {
    padding: 12px 25px;
    background: #007bff;
    border: none;
    color: #fff;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: opacity 0.2s;
}

.btn-cancel {
    background: #6c757d;
    margin-left: 10px;
}

.table-responsive {
    overflow-x: auto;
}

.category-table {
    width: 100%;
    border-collapse: collapse;
}

.category-table th {
    text-align: left;
    padding: 15px;
    background: #f8f9fa;
    color: #555;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.category-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    color: #333;
    font-size: 15px;
}

.badge-detail {
    background: #e9ecef;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 13px;
    color: #495057;
}

.action-btn {
    background: none;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
}

.action-btn:hover {
    transform: scale(1.2);
}

@media (max-width: 768px) {
    .page-content-wrapper { padding: 15px;   }
    .input-group { flex-direction: column; align-items: stretch; }
    .field { min-width: 100%; }
    .category-container { padding: 20px; }
}
</style>