<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once("db.php");

$error_message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $sku      = trim($_POST['sku'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    $image = '';

    // Upload Image
    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] === UPLOAD_ERR_OK
    ) {

        $allowed = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        $mime = mime_content_type($_FILES['image']['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $error_message = "Invalid image type.";
        } else {

            $ext = strtolower(
                pathinfo(
                    $_FILES['image']['name'],
                    PATHINFO_EXTENSION
                )
            );

            $image = uniqid('accessory_', true) . '.' . $ext;

            $imgDir = dirname(__DIR__, 2) . "/img/";

            if (!file_exists($imgDir)) {
                mkdir($imgDir, 0755, true);
            }

            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                $imgDir . $image
            );
        }
    } else {
        $error_message = "Please upload an image.";
    }

    if (
        empty($error_message) &&
        $name &&
        $sku &&
        $price > 0
    ) {

        $stmt = $conn->prepare("
            INSERT INTO accessory
            (
                name,
                sku,
                price,
                quantity,
                image,
                created_at,
                updated_at
            )
            VALUES
            (
                ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ");

        $stmt->bind_param(
            "ssdis",
            $name,
            $sku,
            $price,
            $quantity,
            $image
        );

        if ($stmt->execute()) {

          echo "<script>window.location.href='/list_accessory';</script>";
exit;

        } else {
            $error_message = $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Add Accessory</title>

<style>

:root{
    --blue:#2563eb;
    --blue-h:#1d4ed8;
    --bg:#f1f5f9;
    --card:#fff;
    --border:#e2e8f0;
    --text:#1e293b;
    --muted:#64748b;
    --radius:10px;
    --radius-lg:14px;
}

*{
    box-sizing:border-box;
}

body{
    margin:0;
    background:var(--bg);
    font-family:'Segoe UI',sans-serif;
    color:var(--text);
}

.page-main-content{
    padding:30px;
    }

.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.page-header h2{
    margin:0;
}

.header-actions{
    display:flex;
    gap:10px;
}

.btn-primary{
    border:none;
    background:var(--blue);
    color:#fff;
    padding:10px 20px;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
}

.btn-primary:hover{
    background:var(--blue-h);
}

.btn-back{
    border:1px solid var(--border);
    background:#fff;
    color:#555;
    text-decoration:none;
    padding:10px 20px;
    border-radius:10px;
}

.product-layout{
    display:grid;
    grid-template-columns:1.6fr 1fr;
    gap:22px;
}

.left-column,
.right-column{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.card{
    background:#fff;
    border-radius:14px;
    border:1px solid var(--border);
    padding:22px;
}

.card h3{
    margin-top:0;
    margin-bottom:20px;
    border-bottom:1px solid var(--border);
    padding-bottom:12px;
}

.form-group{
    margin-bottom:18px;
}

.form-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}

label{
    display:block;
    margin-bottom:6px;
    font-size:13px;
    font-weight:600;
    color:var(--muted);
}

.req{
    color:red;
}

input[type=text],
input[type=number]{
    width:100%;
    height:42px;
    border:1px solid var(--border);
    border-radius:10px;
    padding:0 12px;
}

.image-main{
    height:260px;
    border:2px dashed var(--border);
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    cursor:pointer;
    overflow:hidden;
    position:relative;
}

.image-main:hover{
    border-color:var(--blue);
}

.image-main img{
    width:100%;
    height:100%;
    object-fit:cover;
    position:absolute;
    inset:0;
}

.upload-icon{
    font-size:32px;
}

.upload-text{
    font-weight:600;
    margin-top:8px;
}

.upload-sub{
    color:#999;
    font-size:12px;
}

.alert{
    padding:12px 15px;
    border-radius:10px;
    margin-bottom:15px;
    background:#fee2e2;
    color:#991b1b;
}

@media(max-width:900px){

    .page-main-content{
        margin-left:0;
    }

    .product-layout{
        grid-template-columns:1fr;
    }
}
</style>
</head>

<body>

<div class="page-main-content">

    <div class="page-header">
        <h2>📦 Add Accessory</h2>

        <div class="header-actions">
            <a href="<?php echo $base; ?>/admin/list_accessory" class="btn-back">
                ← Back
            </a>

            <button
                type="button"
                onclick="submitForm()"
                class="btn-primary">
                + Add Accessory
            </button>
        </div>
    </div>

    <?php if($error_message): ?>
        <div class="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <form
        id="accessoryForm"
        method="POST"
        enctype="multipart/form-data"
        class="product-layout">

        <div class="left-column">

            <div class="card">

                <h3>📦 Accessory Information</h3>

                <div class="form-group">
                    <label>Name <span class="req">*</span></label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>SKU <span class="req">*</span></label>
                    <input type="text" name="sku" required>
                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label>Price ($)</label>
                        <input
                            type="number"
                            step="0.01"
                            name="price"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Quantity</label>
                        <input
                            type="number"
                            min="0"
                            name="quantity"
                            required>
                    </div>

                </div>

            </div>

        </div>

        <div class="right-column">

            <div class="card">

                <h3>🖼️ Accessory Image</h3>

                <div
                    id="imagePreview"
                    class="image-main"
                    onclick="document.getElementById('imageInput').click()">

                    <div class="upload-icon">📷</div>
                    <div class="upload-text">
                        Click to upload image
                    </div>
                    <div class="upload-sub">
                        JPG, PNG, GIF, WEBP
                    </div>

                </div>

                <input
                    type="file"
                    id="imageInput"
                    name="image"
                    accept="image/*"
                    hidden
                    required
                    onchange="previewAccessoryImage(this)">

            </div>

        </div>

    </form>

</div>

<script>

function submitForm()
{
    document.getElementById('accessoryForm').submit();
}

function previewAccessoryImage(input)
{
    const preview =
        document.getElementById('imagePreview');

    if(input.files && input.files[0])
    {
        const reader = new FileReader();

        reader.onload = function(e)
        {
            preview.innerHTML =
                '<img src="' +
                e.target.result +
                '">';
        };

        reader.readAsDataURL(input.files[0]);
    }
}

</script>

</body>
</html>