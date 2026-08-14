<?php
require_once __DIR__ . '/../../db.php';

$error_message = '';
$success_message = '';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: " . base_url('/admin/coupons'));
    exit;
}

$stmt_fetch = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
$stmt_fetch->bind_param("i", $id);
$stmt_fetch->execute();
$coupon = $stmt_fetch->get_result()->fetch_assoc();

if (!$coupon) {
    header("Location: " . base_url('/admin/coupons'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code                = strtoupper(trim($_POST['code'] ?? ''));
    $description         = trim($_POST['description'] ?? '');
    $type                = in_array($_POST['type'] ?? '', ['percentage', 'fixed']) ? $_POST['type'] : 'percentage';
    $value               = floatval($_POST['value'] ?? 0);
    $min_order_amount    = floatval($_POST['min_order_amount'] ?? 0);
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? floatval($_POST['max_discount_amount']) : null;
    $start_date          = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date            = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $usage_limit         = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
    $status              = isset($_POST['status']) ? intval($_POST['status']) : 1;

    // Validate
    if (empty($code)) {
        $error_message = 'Coupon code is required.';
    } elseif ($value <= 0) {
        $error_message = 'Discount value must be greater than 0.';
    } elseif ($type === 'percentage' && $value > 100) {
        $error_message = 'Percentage discount cannot exceed 100%.';
    } elseif (!empty($start_date) && !empty($end_date) && $start_date > $end_date) {
        $error_message = 'Start date cannot be after the end date.';
    } else {
        // Check for duplicate code (excluding current coupon)
        $stmt_check = $conn->prepare("SELECT id FROM coupons WHERE UPPER(code) = ? AND id != ?");
        $stmt_check->bind_param("si", $code, $id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $error_message = "Another coupon with the code '{$code}' already exists.";
        } else {
            $stmt = $conn->prepare("
                UPDATE coupons SET 
                code = ?, description = ?, type = ?, value = ?, min_order_amount = ?, max_discount_amount = ?, start_date = ?, end_date = ?, usage_limit = ?, status = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssddsssiii",
                $code,
                $description,
                $type,
                $value,
                $min_order_amount,
                $max_discount_amount,
                $start_date,
                $end_date,
                $usage_limit,
                $status,
                $id
            );

            if ($stmt->execute()) {
                echo "<script>window.location.href='" . base_url('/admin/coupons') . "';</script>";
                exit;
            } else {
                $error_message = 'Failed to update coupon: ' . $conn->error;
            }
        }
    }
}
?>

<style>
.coupon-form-layout {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 28px;
    align-items: start;
}
@media (max-width: 960px) {
    .coupon-form-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="admin-wrapper">
    <div style="margin-bottom:20px;">
        <a href="<?= base_url('/admin/coupons'); ?>" style="color:#6b7280; text-decoration:none; font-size:14px; font-weight:500; display:inline-flex; align-items:center; gap:6px;">
            &larr; Back to Coupons
        </a>
    </div>

    <div class="coupon-form-layout">
        
        <!-- Left: Form -->
        <div class="admin-card" style="background:#ffffff; padding:30px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <h2 class="admin-title">Edit Discount Coupon</h2>
                <a href="<?= base_url('/admin/coupons?action=delete&id=' . $coupon['id']); ?>" 
                   onclick="return confirm('Are you sure you want to delete this coupon?');" 
                   style="color:#e11d48; font-size:13px; font-weight:600; text-decoration:none; padding:4px 8px; border-radius:6px; background:#fff1f2; border:1px solid #fecdd3;">
                    Delete Coupon
                </a>
            </div>
            <p class="admin-subtitle" style="margin-bottom:24px;">Editing coupon <strong style="color:#0a2e3f;">#<?= $coupon['id']; ?> (<?= htmlspecialchars($coupon['code']); ?>)</strong> &bull; Total used: <?= (int)$coupon['used_count']; ?> times</p>

            <?php if (!empty($error_message)): ?>
                <div style="background:#fde8e8; color:#9b1c1c; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px; border:1px solid #f8b4b4;">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="post" id="couponForm">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:20px;">
                    
                    <!-- Coupon Code -->
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Coupon Code *
                        </label>
                        <input type="text" name="code" id="couponCodeInput" class="admin-input" 
                               placeholder="e.g. WELCOME20" 
                               value="<?= htmlspecialchars($_POST['code'] ?? $coupon['code']); ?>" 
                               style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; text-transform:uppercase; font-family:'Courier New', monospace; font-weight:700; letter-spacing:1px;" 
                               required>
                    </div>

                    <!-- Discount Type -->
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Discount Type *
                        </label>
                        <select name="type" id="couponTypeSelect" style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; background:white;" required>
                            <option value="percentage" <?= (($_POST['type'] ?? $coupon['type']) === 'percentage') ? 'selected' : ''; ?>>Percentage Discount (%)</option>
                            <option value="fixed" <?= (($_POST['type'] ?? $coupon['type']) === 'fixed') ? 'selected' : ''; ?>>Fixed Cart Amount ($)</option>
                        </select>
                    </div>

                    <!-- Discount Value -->
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Discount Value *
                        </label>
                        <div style="position:relative;">
                            <span id="valuePrefix" style="position:absolute; left:12px; top:10px; font-size:14px; color:#6b7280; font-weight:600; display:none;">$</span>
                            <input type="number" step="0.01" min="0.01" name="value" id="couponValueInput" 
                                   placeholder="e.g. 20" 
                                   value="<?= htmlspecialchars($_POST['value'] ?? $coupon['value']); ?>" 
                                   style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;" 
                                   required>
                            <span id="valueSuffix" style="position:absolute; right:14px; top:10px; font-size:14px; color:#6b7280; font-weight:600;">%</span>
                        </div>
                    </div>

                    <!-- Minimum Order Amount -->
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Minimum Order Amount ($) <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="min_order_amount" id="minOrderInput" 
                               placeholder="0.00" 
                               value="<?= htmlspecialchars($_POST['min_order_amount'] ?? $coupon['min_order_amount']); ?>" 
                               style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                    </div>

                    <!-- Description / Title -->
                    <div style="grid-column: 1 / -1;">
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Description / Customer Message <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                        </label>
                        <input type="text" name="description" id="couponDescInput" 
                               placeholder="e.g. 20% discount on first order" 
                               value="<?= htmlspecialchars($_POST['description'] ?? $coupon['description']); ?>" 
                               style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                    </div>

                    <!-- Max Discount Amount (Cap) -->
                    <div id="maxDiscountWrapper">
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Max Discount Cap ($) <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                        </label>
                        <input type="number" step="0.01" min="0" name="max_discount_amount" id="maxDiscountInput" 
                               placeholder="e.g. 50.00" 
                               value="<?= htmlspecialchars($_POST['max_discount_amount'] ?? $coupon['max_discount_amount']); ?>" 
                               style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                    </div>

                    <!-- Usage Limit -->
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Total Usage Limit <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                        </label>
                        <input type="number" min="1" step="1" name="usage_limit" id="usageLimitInput" 
                               placeholder="Unlimited" 
                               value="<?= htmlspecialchars($_POST['usage_limit'] ?? $coupon['usage_limit']); ?>" 
                               style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Valid From (Start Date) <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                        </label>
                        <input type="date" name="start_date" id="startDateInput" 
                               value="<?= htmlspecialchars($_POST['start_date'] ?? $coupon['start_date']); ?>" 
                               style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Valid Until (Expiry Date) <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                        </label>
                        <input type="date" name="end_date" id="endDateInput" 
                               value="<?= htmlspecialchars($_POST['end_date'] ?? $coupon['end_date']); ?>" 
                               style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                    </div>

                    <!-- Status -->
                    <div>
                        <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                            Status
                        </label>
                        <select name="status" id="statusSelect" style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; background:white;">
                            <option value="1" <?= ((isset($_POST['status']) ? $_POST['status'] : $coupon['status']) == 1) ? 'selected' : ''; ?>>Active (Can be used at checkout)</option>
                            <option value="0" <?= ((isset($_POST['status']) ? $_POST['status'] : $coupon['status']) == 0) ? 'selected' : ''; ?>>Inactive (Disabled)</option>
                        </select>
                    </div>

                </div>

                <!-- Submit Button -->
                <div style="margin-top:30px; display:flex; gap:12px;">
                    <button type="submit" style="padding:12px 24px; background:#0a2e3f; color:white; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; box-shadow:0 2px 8px rgba(10,46,63,0.15);">
                        Save Changes
                    </button>
                    <a href="<?= base_url('/admin/coupons'); ?>" style="padding:12px 20px; background:#f3f4f6; color:#4b5563; border-radius:8px; font-size:14px; font-weight:500; text-decoration:none; display:inline-flex; align-items:center;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Right: Live Preview Card -->
        <div>
            <div style="background:white; border-radius:12px; padding:24px; border:1px solid #eef2f6; box-shadow:0 4px 12px rgba(0,0,0,0.04); position:sticky; top:20px;">
                <h3 style="font-size:14px; font-weight:700; color:#1e293b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:16px;">
                    Live Coupon Preview
                </h3>

                <!-- Voucher Mockup -->
                <div style="background:linear-gradient(135deg, #0a2e3f 0%, #154c64 100%); border-radius:16px; padding:22px; color:white; position:relative; overflow:hidden; box-shadow:0 10px 25px -5px rgba(10,46,63,0.3);">
                    <!-- Decorative pattern circles -->
                    <div style="position:absolute; right:-20px; top:-20px; width:90px; height:90px; border-radius:50%; background:rgba(255,255,255,0.06);"></div>
                    <div style="position:absolute; right:30px; bottom:-30px; width:70px; height:70px; border-radius:50%; background:rgba(255,255,255,0.04);"></div>

                    <div style="font-family:'Cinzel', serif; font-size:13px; letter-spacing:2px; opacity:0.8; margin-bottom:12px;">
                        LVB ATELIER
                    </div>

                    <div id="previewValueDisplay" style="font-size:28px; font-weight:800; line-height:1.2; margin-bottom:6px;">
                        <?= ($coupon['type'] === 'percentage') ? rtrim(rtrim(number_format($coupon['value'], 2), '0'), '.') . '% OFF' : '$' . number_format($coupon['value'], 2) . ' OFF'; ?>
                    </div>

                    <div id="previewDescDisplay" style="font-size:12px; opacity:0.85; margin-bottom:18px; min-height:18px;">
                        <?= htmlspecialchars($coupon['description'] ?: ($coupon['type'] === 'percentage' ? $coupon['value'] . '% off candle orders' : '$' . $coupon['value'] . ' off candle orders')); ?>
                    </div>

                    <div style="background:rgba(255,255,255,0.15); border:1px dashed rgba(255,255,255,0.4); border-radius:8px; padding:8px 12px; display:flex; justify-content:space-between; align-items:center;">
                        <span id="previewCodeDisplay" style="font-family:'Courier New', monospace; font-size:15px; font-weight:700; letter-spacing:1.5px;">
                            <?= htmlspecialchars($coupon['code']); ?>
                        </span>
                        <span style="font-size:10px; text-transform:uppercase; background:rgba(255,255,255,0.25); padding:2px 6px; border-radius:4px;">
                            CODE
                        </span>
                    </div>

                    <div id="previewDetailsDisplay" style="margin-top:14px; font-size:11px; opacity:0.7; line-height:1.5;">
                        • Applies at checkout
                    </div>
                </div>

                <!-- Guidance Box -->
                <div style="margin-top:20px; padding:14px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; font-size:12px; color:#64748b; line-height:1.6;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                        <strong style="color:#334155;">Redemptions:</strong>
                        <span style="font-weight:700; color:#0a2e3f;"><?= (int)$coupon['used_count']; ?> times</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <strong style="color:#334155;">Created On:</strong>
                        <span><?= date('M j, Y', strtotime($coupon['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function updatePreview() {
    const code = document.getElementById('couponCodeInput').value.trim().toUpperCase() || 'CODE';
    const type = document.getElementById('couponTypeSelect').value;
    const val = parseFloat(document.getElementById('couponValueInput').value) || 0;
    const desc = document.getElementById('couponDescInput').value.trim();
    const minOrder = parseFloat(document.getElementById('minOrderInput').value) || 0;
    const endDate = document.getElementById('endDateInput').value;

    const valueSuffix = document.getElementById('valueSuffix');
    const valuePrefix = document.getElementById('valuePrefix');
    const couponValueInput = document.getElementById('couponValueInput');
    const maxDiscountWrapper = document.getElementById('maxDiscountWrapper');

    if (type === 'percentage') {
        valueSuffix.style.display = 'block';
        valuePrefix.style.display = 'none';
        couponValueInput.style.paddingLeft = '14px';
        couponValueInput.style.paddingRight = '30px';
        couponValueInput.placeholder = 'e.g. 20 (for 20%)';
        maxDiscountWrapper.style.display = 'block';
        document.getElementById('previewValueDisplay').textContent = (val > 0 ? val : '20') + '% OFF';
    } else {
        valueSuffix.style.display = 'none';
        valuePrefix.style.display = 'block';
        couponValueInput.style.paddingLeft = '28px';
        couponValueInput.style.paddingRight = '14px';
        couponValueInput.placeholder = 'e.g. 15.00';
        maxDiscountWrapper.style.display = 'none';
        document.getElementById('previewValueDisplay').textContent = '$' + (val > 0 ? val.toFixed(2) : '10.00') + ' OFF';
    }

    document.getElementById('previewCodeDisplay').textContent = code;
    document.getElementById('previewDescDisplay').textContent = desc || (type === 'percentage' ? val + '% off your candle order' : '$' + val + ' off your candle order');

    let details = [];
    if (minOrder > 0) details.push(`• Min. order: $${minOrder.toFixed(2)}`);
    if (endDate) details.push(`• Expires: ${endDate}`);
    if (details.length === 0) details.push('• Applies to all checkout items');
    document.getElementById('previewDetailsDisplay').innerHTML = details.join('<br>');
}

['input', 'change'].forEach(evt => {
    document.getElementById('couponCodeInput').addEventListener(evt, updatePreview);
    document.getElementById('couponTypeSelect').addEventListener(evt, updatePreview);
    document.getElementById('couponValueInput').addEventListener(evt, updatePreview);
    document.getElementById('couponDescInput').addEventListener(evt, updatePreview);
    document.getElementById('minOrderInput').addEventListener(evt, updatePreview);
    document.getElementById('endDateInput').addEventListener(evt, updatePreview);
});

// Run once on load
updatePreview();
</script>
