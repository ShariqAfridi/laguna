<?php
require_once __DIR__ . '/../../db.php';

$error_message = '';
$success_message = '';

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
        // Check for duplicate code
        $stmt_check = $conn->prepare("SELECT id FROM coupons WHERE UPPER(code) = ?");
        $stmt_check->bind_param("s", $code);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $error_message = "A coupon with the code '{$code}' already exists.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO coupons 
                (code, description, type, value, min_order_amount, max_discount_amount, start_date, end_date, usage_limit, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssddsssii",
                $code,
                $description,
                $type,
                $value,
                $min_order_amount,
                $max_discount_amount,
                $start_date,
                $end_date,
                $usage_limit,
                $status
            );

            if ($stmt->execute()) {
                echo "<script>window.location.href='" . base_url('/admin/coupons') . "';</script>";
                exit;
            } else {
                $error_message = 'Failed to create coupon: ' . $conn->error;
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

    <!-- Full Width Form Card -->
    <div class="admin-card" style="background:#ffffff; padding:32px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.05); width:100%;">
        <h2 class="admin-title" style="margin-bottom:6px;">Add New Discount Coupon</h2>
        <p class="admin-subtitle" style="margin-bottom:24px;">Configure discount parameters, validity rules, and redemption limits.</p>

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
                    <div style="display:flex; gap:8px;">
                        <input type="text" name="code" id="couponCodeInput" class="admin-input" 
                               placeholder="e.g. WELCOME20, SUMMER15" 
                               value="<?= htmlspecialchars($_POST['code'] ?? ''); ?>" 
                               style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; text-transform:uppercase; font-family:'Courier New', monospace; font-weight:700; letter-spacing:1px;" 
                               required>
                        <button type="button" onclick="generateRandomCode()" style="padding:10px 14px; background:#f3f4f6; color:#374151; border:1px solid #d1d5db; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap;">
                            Generate
                        </button>
                    </div>
                </div>

                <!-- Discount Type -->
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                        Discount Type *
                    </label>
                    <select name="type" id="couponTypeSelect" style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; background:white;" required>
                        <option value="percentage" <?= (($_POST['type'] ?? 'percentage') === 'percentage') ? 'selected' : ''; ?>>Percentage Discount (%)</option>
                        <option value="fixed" <?= (($_POST['type'] ?? '') === 'fixed') ? 'selected' : ''; ?>>Fixed Cart Amount ($)</option>
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
                               value="<?= htmlspecialchars($_POST['value'] ?? ''); ?>" 
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
                           value="<?= htmlspecialchars($_POST['min_order_amount'] ?? ''); ?>" 
                           style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                </div>

                <!-- Description / Title -->
                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                        Description / Customer Message <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                    </label>
                    <input type="text" name="description" id="couponDescInput" 
                           placeholder="e.g. 20% discount on first order" 
                           value="<?= htmlspecialchars($_POST['description'] ?? ''); ?>" 
                           style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                </div>

                <!-- Max Discount Amount (Cap) -->
                <div id="maxDiscountWrapper">
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                        Max Discount Cap ($) <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                    </label>
                    <input type="number" step="0.01" min="0" name="max_discount_amount" id="maxDiscountInput" 
                           placeholder="e.g. 50.00" 
                           value="<?= htmlspecialchars($_POST['max_discount_amount'] ?? ''); ?>" 
                           style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                </div>

                <!-- Usage Limit -->
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                        Total Usage Limit <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                    </label>
                    <input type="number" min="1" step="1" name="usage_limit" id="usageLimitInput" 
                           placeholder="Unlimited" 
                           value="<?= htmlspecialchars($_POST['usage_limit'] ?? ''); ?>" 
                           style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                </div>

                <!-- Start Date -->
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                        Valid From (Start Date) <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                    </label>
                    <input type="date" name="start_date" id="startDateInput" 
                           value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>" 
                           style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                </div>

                <!-- End Date -->
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                        Valid Until (Expiry Date) <span style="font-weight:400; color:#9ca3af;">(Optional)</span>
                    </label>
                    <input type="date" name="end_date" id="endDateInput" 
                           value="<?= htmlspecialchars($_POST['end_date'] ?? ''); ?>" 
                           style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                </div>

                <!-- Status -->
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">
                        Status
                    </label>
                    <select name="status" id="statusSelect" style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; background:white;">
                        <option value="1" <?= ((isset($_POST['status']) ? $_POST['status'] : '1') == 1) ? 'selected' : ''; ?>>Active (Can be used at checkout)</option>
                        <option value="0" <?= ((isset($_POST['status']) ? $_POST['status'] : '1') == 0) ? 'selected' : ''; ?>>Inactive (Disabled)</option>
                    </select>
                </div>

            </div>

            <!-- Submit Button -->
            <div style="margin-top:30px; display:flex; gap:12px;">
                <button type="submit" style="padding:12px 28px; background:#0a2e3f; color:white; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; box-shadow:0 2px 8px rgba(10,46,63,0.15);">
                    Create Coupon
                </button>
                <a href="<?= base_url('/admin/coupons'); ?>" style="padding:12px 20px; background:#f3f4f6; color:#4b5563; border-radius:8px; font-size:14px; font-weight:500; text-decoration:none; display:inline-flex; align-items:center;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function generateRandomCode() {
    const prefixes = ['LVB', 'VIP', 'SAVE', 'SPECIAL', 'GIFT', 'SUMMER', 'WINTER', 'WELCOME'];
    const randomPrefix = prefixes[Math.floor(Math.random() * prefixes.length)];
    const randomNum = Math.floor(10 + Math.random() * 85);
    const code = randomPrefix + randomNum;
    document.getElementById('couponCodeInput').value = code;
}

function handleTypeChange() {
    const type = document.getElementById('couponTypeSelect').value;
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
    } else {
        valueSuffix.style.display = 'none';
        valuePrefix.style.display = 'block';
        couponValueInput.style.paddingLeft = '28px';
        couponValueInput.style.paddingRight = '14px';
        couponValueInput.placeholder = 'e.g. 15.00';
        maxDiscountWrapper.style.display = 'none';
    }
}

document.getElementById('couponTypeSelect').addEventListener('change', handleTypeChange);
handleTypeChange();
</script>
