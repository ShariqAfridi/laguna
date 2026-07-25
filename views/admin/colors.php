<?php
require_once __DIR__ . '/../../db.php';

/* ======================
    INSERT / UPDATE
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $color_name = trim($_POST['color_name']);
    $color_hex  = $_POST['color_hex'];
    $id         = $_POST['id'];

    if (!empty($id)) {
        $stmt = $conn->prepare("UPDATE colors SET color_name=?, color_hex=? WHERE color_id=?");
        $stmt->bind_param("ssi", $color_name, $color_hex, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO colors (color_name, color_hex) VALUES (?, ?)");
        $stmt->bind_param("ss", $color_name, $color_hex);
    }

    $stmt->execute();
    echo "<script>window.location.href='<?php echo base_url('/admin/colors'); ?>';</script>";
    exit;
}

/* ======================
    DELETE
====================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM colors WHERE color_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>window.location.href='<?php echo base_url('/admin/colors'); ?>';</script>";
    exit;
}

/* ======================
    FETCH
====================== */
$result = $conn->query("SELECT color_id, color_name, color_hex FROM colors ORDER BY color_id DESC");
?>

<div class="page-content-wrapper">
    <div class="color-container">
        
        <div class="header-flex">
            <h2>Color Chart</h2>
            <p class="subtitle">Define the visual palette for your product collection.</p>
        </div>

        <!-- FORM -->
        <form method="post" id="colorForm" class="input-group">
            <input type="hidden" name="id" id="edit_id">

            <div class="field">
                <label>Color Name</label>
                <input type="text" name="color_name" id="color_name"
                    placeholder="e.g. Ocean Blue" required>
            </div>

            <div class="field hex-field">
                <label>Hex Code</label>
                <div class="hex-input-wrapper">
                    <input type="text" name="color_hex" id="color_hex"
                        placeholder="#687382" maxlength="7" required>
                    
                    <div class="palette-trigger" id="openPalette" title="Open Color Picker">
                        <div class="color-preview" id="livePreview"></div>
                    </div>
                </div>
                <input type="color" id="colorPicker" value="#687382" style="display:none;">
            </div>

            <div class="form-actions">
                <button type="submit" id="submitBtn">Add Color</button>
                <button type="button" id="cancelBtn" style="display:none;" 
                    onclick="resetForm()" class="btn-cancel">Cancel</button>
            </div>
        </form>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="color-table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Preview</th>
                        <th>Color Name</th>
                        <th>HEX Code</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; while($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td>
                            <div class="table-swatch" style="background: <?= htmlspecialchars($row['color_hex']); ?>;"></div>
                        </td>
                        <td><strong><?= htmlspecialchars($row['color_name']); ?></strong></td>
                        <td><code><?= htmlspecialchars($row['color_hex']); ?></code></td>
                        <td style="text-align: right;">
                            <button class="action-btn edit" 
                                onclick="editRow(
                                    <?= $row['color_id']; ?>,
                                    '<?= htmlspecialchars($row['color_name'], ENT_QUOTES); ?>',
                                    '<?= $row['color_hex']; ?>'
                                )">✏️</button>

                            <a href="javascript:void(0);" class="action-btn delete" 
                               onclick="confirmDelete(<?= $row['color_id']; ?>)">🗑️</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
const picker = document.getElementById("colorPicker");
const hexInput = document.getElementById("color_hex");
const preview = document.getElementById("livePreview");
const openPalette = document.getElementById("openPalette");

// Trigger hidden native picker
openPalette.addEventListener("click", () => picker.click());

// Sync picker -> hex input
picker.addEventListener("input", () => {
    hexInput.value = picker.value.toUpperCase();
    preview.style.background = picker.value;
});

// Sync hex input -> picker
hexInput.addEventListener("input", () => {
    let val = hexInput.value;
    if (!val.startsWith('#')) val = '#' + val;
    if (/^#([0-9A-F]{3}){1,2}$/i.test(val)) {
        picker.value = val;
        preview.style.background = val;
    }
});

function confirmDelete(id) {
    if(confirm('Delete this color from the chart?')) {
        window.location.href = '/colors?action=delete&id=' + id;
    }
}

function editRow(id, name, hex) {
    document.getElementById("edit_id").value = id;
    document.getElementById("color_name").value = name;
    document.getElementById("color_hex").value = hex;

    picker.value = hex;
    preview.style.background = hex;

    const submitBtn = document.getElementById("submitBtn");
    submitBtn.innerText = "Save Changes";
    submitBtn.style.background = "#28a745";
    document.getElementById("cancelBtn").style.display = "inline-block";
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById("edit_id").value = "";
    document.getElementById("colorForm").reset();

    const submitBtn = document.getElementById("submitBtn");
    submitBtn.innerText = "Add Color";
    submitBtn.style.background = "#007bff";
    document.getElementById("cancelBtn").style.display = "none";

    preview.style.background = "#687382";
}
</script>

<style>
/* Remove margin-left: main layout handles it */
.page-content-wrapper {
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.header-flex { margin-bottom: 25px; }
.subtitle { color: #666; font-size: 14px; margin-top: 5px; }

.color-container {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

/* FORM DESIGN */
.input-group {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 35px;
    align-items: flex-end;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
}

.field {
    flex: 2;
    min-width: 200px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.hex-field { flex: 1; }

.field label { font-size: 13px; font-weight: 600; color: #444; }

.hex-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

input[type="text"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.palette-trigger {
    width: 45px;
    height: 43px;
    border: 1px solid #ddd;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: #fff;
    flex-shrink: 0;
}

.color-preview {
    width: 25px;
    height: 25px;
    border-radius: 4px;
    background: #687382;
    border: 1px solid rgba(0,0,0,0.1);
}

button {
    padding: 12px 20px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.btn-cancel { background: #6c757d; }

/* TABLE DESIGN */
.table-responsive { overflow-x: auto; }
.color-table { width: 100%; border-collapse: collapse; }
.color-table th { 
    text-align: left; padding: 15px; background: #f1f3f5; 
    font-size: 13px; text-transform: uppercase; color: #495057;
}
.color-table td { padding: 15px; border-bottom: 1px solid #eee; }

.table-swatch {
    width: 40px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid rgba(0,0,0,0.05);
}

code {
    background: #f1f3f5;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 14px;
}

.action-btn {
    background: none; border: none; padding: 5px; 
    cursor: pointer; font-size: 16px; text-decoration: none;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .page-content-wrapper { padding: 15px;  }
    .input-group { flex-direction: column; align-items: stretch; }
    .field { min-width: 100%; }
    .form-actions { display: flex; gap: 10px; }
    .form-actions button { flex: 1; }
}
</style>