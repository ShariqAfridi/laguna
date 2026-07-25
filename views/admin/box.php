<?php
include("db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ======================
    INSERT / UPDATE
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $box_name = trim($_POST['box_name']);
    $id = $_POST['box_id'];

    if (!empty($id)) {
        $stmt = $conn->prepare("UPDATE boxes SET box_name=? WHERE box_id=?");
        $stmt->bind_param("si", $box_name, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO boxes (box_name) VALUES (?)");
        $stmt->bind_param("s", $box_name);
    }

    $stmt->execute();
    echo "<script>window.location.href='/boxes';</script>";
    exit;
}

/* ======================
    DELETE
====================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM boxes WHERE box_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>window.location.href='/boxes';</script>";
    exit;
}

/* ======================
    FETCH
====================== */
$result = $conn->query("SELECT box_id, box_name FROM boxes ORDER BY box_id DESC");
?>

<div class="page-content-wrapper">
    <div class="container-box">

        <div class="header-flex">
            <h2>Box Types</h2>
            <p class="subtitle">Manage packaging styles and container types.</p>
        </div>

        <!-- FORM -->
        <form method="post" id="boxForm" class="input-group">
            <input type="hidden" name="box_id" id="edit_id">

            <div class="field">
                <label>Box Name</label>
                <input type="text" name="box_name" id="box_name"
                    placeholder="e.g. Cubic Box" required>
            </div>

            <div class="form-actions">
                <button type="submit" id="submitBtn">Add Box</button>
                <button type="button" id="cancelBtn"
                    style="display:none;" class="btn-cancel"
                    onclick="resetForm()">Cancel</button>
            </div>
        </form>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Box Name</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php $i=1; while($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><strong><?= htmlspecialchars($row['box_name']); ?></strong></td>
                        <td class="actions" style="text-align: right;">
                            <button class="action-btn edit"
                               onclick="editRow(
                                   <?= $row['box_id']; ?>,
                                   '<?= htmlspecialchars($row['box_name'], ENT_QUOTES); ?>'
                               )">✏️</button>

                            <a href="<?php echo $base; ?>/admin/boxes?action=delete&id=<?= $row['box_id']; ?>"
                               class="action-btn delete"
                               onclick="return confirm('Delete this box?')">🗑️</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
function editRow(id, name) {
    document.getElementById("edit_id").value = id;
    document.getElementById("box_name").value = name;

    const submitBtn = document.getElementById("submitBtn");
    submitBtn.innerText = "Update Box";
    submitBtn.style.background = "#28a745";
    document.getElementById("cancelBtn").style.display = "inline-block";
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById("edit_id").value = "";
    document.getElementById("boxForm").reset();

    const submitBtn = document.getElementById("submitBtn");
    submitBtn.innerText = "Add Box";
    submitBtn.style.background = "#007bff";
    document.getElementById("cancelBtn").style.display = "none";
}
</script>

<style>
/* Synchronized with your sidebar layout */
.page-content-wrapper {
    padding: 30px;
    max-width: 1100px;
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

.container-box {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

/* FORM STYLING */
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

input[type="text"] {
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
    transition: background 0.2s;
}

.btn-cancel {
    background: #6c757d;
    margin-left: 5px;
}

/* TABLE STYLING */
.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 15px;
    background: #f1f3f5;
    color: #495057;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    font-size: 15px;
}

.action-btn {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    font-size: 18px;
    text-decoration: none;
    display: inline-block;
    transition: transform 0.2s;
}

.action-btn:hover {
    transform: scale(1.15);
}

.action-btn.edit { color: #007bff; }
.action-btn.delete { color: #dc3545; }

/* MOBILE RESPONSIVENESS */
@media (max-width: 768px) {
    .page-content-wrapper {
        padding: 15px;
    }
        
  

    .container-box {
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
}
</style>