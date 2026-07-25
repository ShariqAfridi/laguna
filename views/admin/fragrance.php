<?php
require_once __DIR__ . '/../../db.php';

/* ======================
    INSERT / UPDATE
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['fragrance_id'];
    $name = trim($_POST['fragrance_name']);

    if (!empty($id)) {
        $stmt = $conn->prepare("UPDATE fragrances SET fragrance_name=? WHERE fragrance_id=?");
        $stmt->bind_param("si", $name, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO fragrances (fragrance_name) VALUES (?)");
        $stmt->bind_param("s", $name);
    }

    $stmt->execute();
    echo "<script>window.location.href='<?php echo base_url('/admin/fragrance'); ?>';</script>";
    exit;
}

/* ======================
    FETCH
====================== */
$fragrances = $conn->query("SELECT * FROM fragrances ORDER BY fragrance_id DESC");

/* ======================
    DELETE
====================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM fragrances WHERE fragrance_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>window.location.href='<?php echo base_url('/admin/fragrance'); ?>';</script>";
    exit;
}
?>

<div class="page-content-wrapper">
    <div class="category-container">
        
        <div class="header-flex">
            <h2>Fragrances</h2>
            <p class="subtitle">Manage your candle scents and fragrance profiles.</p>
        </div>

        <!-- FORM -->
        <form method="post" id="fragranceForm" class="input-group">
            <input type="hidden" name="fragrance_id" id="edit_id">

            <div class="field">
                <label>Fragrance Name</label>
                <input type="text" name="fragrance_name" id="fragrance_name"
                    placeholder="e.g. Amber Musk" required>
            </div>

            <div class="form-actions">
                <button type="submit" id="submitBtn">Add Fragrance</button>
                <button type="button" onclick="resetForm()" id="cancelBtn"
                    style="display:none;" class="btn-cancel">Cancel</button>
            </div>
        </form>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="category-table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Fragrance Name</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php $i=1; while($row = $fragrances->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><strong><?= htmlspecialchars($row['fragrance_name']); ?></strong></td>
                        <td style="text-align: right;">
                            <button class="action-btn edit"
                               onclick="editRow(
                                   <?= $row['fragrance_id']; ?>,
                                   '<?= htmlspecialchars($row['fragrance_name'], ENT_QUOTES); ?>'
                               )">✏️</button>

                            <a href="javascript:void(0);" class="action-btn delete" 
                               onclick="confirmDelete(<?= $row['fragrance_id']; ?>)">🗑️</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
// CONFIRM DELETE
function confirmDelete(id) {
    if(confirm('Are you sure you want to delete this fragrance?')) {
        window.location.href = '/fragrance?action=delete&id=' + id;
    }
}

// EDIT
function editRow(id, name) {
    document.getElementById('edit_id').value = id;
    document.getElementById('fragrance_name').value = name;

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerText = "Update Fragrance";
    submitBtn.style.background = "#28a745"; // Success green for update
    document.getElementById('cancelBtn').style.display = "inline-block";
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// RESET
function resetForm() {
    document.getElementById('edit_id').value = "";
    document.getElementById('fragranceForm').reset();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerText = "Add Fragrance";
    submitBtn.style.background = "#007bff";
    document.getElementById('cancelBtn').style.display = "none";
}
</script>

<style>
/* REMOVED margin-left: 250px to prevent overlap */
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

/* FORM STYLING */
.input-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 35px;
    align-items: flex-end;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.field {
    flex: 1;
    min-width: 250px;
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
    transition: all 0.2s;
}

input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

button {
    padding: 12px 25px;
    background: #007bff;
    border: none;
    color: #fff;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.2s;
}

button:hover { background: #0056b3; }

.btn-cancel {
    background: #6c757d;
}
.btn-cancel:hover { background: #5a6268; }

/* TABLE STYLING */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.category-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.category-table th {
    text-align: left;
    padding: 15px;
    background: #f1f3f5;
    color: #495057;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.category-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    font-size: 15px;
}

.action-btn {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
    transition: transform 0.2s;
}

.action-btn:hover { transform: scale(1.2); }

/* MOBILE RESPONSIVENESS */
@media (max-width: 768px) {
    .page-content-wrapper {
        padding: 15px;
        
    }

    .category-container {
        padding: 20px;
    }

    .input-group {
        flex-direction: column;
        align-items: stretch;
    }

    .field {
        min-width: 100%;
    }

    .form-actions {
        display: flex;
        gap: 10px;
    }

    .form-actions button {
        flex: 1;
    }

    .category-table th, 
    .category-table td {
        padding: 12px 10px;
        font-size: 14px;
    }
}
</style>