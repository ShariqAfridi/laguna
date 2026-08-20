<?php
check_admin_auth();

require_once __DIR__ . '/../../db.php';
$dbConn = get_db_connection();

// Fetch current builder pricing settings
$settingsRes = $dbConn->query("SELECT setting_key, setting_value FROM builder_pricing_settings");
$settings = [
    'vessel_c_price'        => '30.00',
    'vessel_d_price'        => '40.00',
    'vessel_e_price'        => '55.00',
    'builder_shipping_fee'  => '9.00',
    'customization_fee'     => '0.00',
    'enable_custom_pricing' => '1',
];

if ($settingsRes && $settingsRes->num_rows > 0) {
    while ($row = $settingsRes->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Fetch all keepsake boxes
$boxesRes = $dbConn->query("SELECT box_id, box_name, vessel_code, box_price, box_image, sku FROM boxes ORDER BY vessel_code ASC, box_id ASC");
$boxes = [];
if ($boxesRes && $boxesRes->num_rows > 0) {
    while ($row = $boxesRes->fetch_assoc()) {
        $boxes[] = $row;
    }
}

$flashSuccess = $_SESSION['admin_flash_success'] ?? null;
unset($_SESSION['admin_flash_success']);
?>

<div class="admin-wrapper">
    <!-- ADMIN HEADER -->
    <div class="admin-header">
        <div>
            <h1 class="admin-title">Create a Candle Pricing</h1>
            <p class="admin-subtitle">Configure base vessel prices, keepsake box add-ons, shipping fees, and customization surcharges for the interactive candle builder.</p>
        </div>
        <a href="<?php echo base_url('/builder'); ?>" target="_blank" class="admin-btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
            </svg>
            Preview Builder
        </a>
    </div>

    <?php if ($flashSuccess): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span><?php echo htmlspecialchars($flashSuccess); ?></span>
        </div>
    <?php endif; ?>

    <form action="<?php echo base_url('/admin/candle_pricing/update'); ?>" method="POST" id="pricingForm">
        
        <!-- SECTION 1: BASE VESSEL PRICING -->
        <div class="admin-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #f3f4f6; padding-bottom: 14px;">
                <div>
                    <h3 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0;">1. Base Vessel Pricing</h3>
                    <p style="font-size: 13px; color: #6b7280; margin-top: 2px;">Set base prices for each candle vessel tier in the custom builder.</p>
                </div>
                <span class="admin-badge-active">Active Builder Tier</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                
                <!-- Vessel C Card -->
                <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; background: #fafafa; transition: border-color 0.2s;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                        <div style="width: 42px; height: 42px; border-radius: 8px; background: #1f2c35; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;">
                            C
                        </div>
                        <div>
                            <h4 style="font-size: 15px; font-weight: 700; color: #1f2c35; margin: 0;">Vessel C (10 oz)</h4>
                            <span style="font-size: 12px; color: #6b7280;">Single Wick · 45 Hours</span>
                        </div>
                    </div>
                    <label class="admin-label" for="vessel_c_price">Base Price ($)</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6b7280; font-weight: 600;">$</span>
                        <input type="number" step="0.01" min="0" class="admin-input" id="vessel_c_price" name="vessel_c_price" value="<?php echo htmlspecialchars($settings['vessel_c_price']); ?>" style="padding-left: 28px;" oninput="updateSim()">
                    </div>
                </div>

                <!-- Vessel D Card -->
                <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; background: #fafafa; transition: border-color 0.2s;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                        <div style="width: 42px; height: 42px; border-radius: 8px; background: #1f2c35; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;">
                            D
                        </div>
                        <div>
                            <h4 style="font-size: 15px; font-weight: 700; color: #1f2c35; margin: 0;">Vessel D (14 oz)</h4>
                            <span style="font-size: 12px; color: #6b7280;">Double Wick · 60 Hours</span>
                        </div>
                    </div>
                    <label class="admin-label" for="vessel_d_price">Base Price ($)</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6b7280; font-weight: 600;">$</span>
                        <input type="number" step="0.01" min="0" class="admin-input" id="vessel_d_price" name="vessel_d_price" value="<?php echo htmlspecialchars($settings['vessel_d_price']); ?>" style="padding-left: 28px;" oninput="updateSim()">
                    </div>
                </div>

                <!-- Vessel E Card -->
                <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; background: #fafafa; transition: border-color 0.2s;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                        <div style="width: 42px; height: 42px; border-radius: 8px; background: #1f2c35; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px;">
                            E
                        </div>
                        <div>
                            <h4 style="font-size: 15px; font-weight: 700; color: #1f2c35; margin: 0;">Vessel E (18 oz)</h4>
                            <span style="font-size: 12px; color: #6b7280;">Triple Wick · 80 Hours</span>
                        </div>
                    </div>
                    <label class="admin-label" for="vessel_e_price">Base Price ($)</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6b7280; font-weight: 600;">$</span>
                        <input type="number" step="0.01" min="0" class="admin-input" id="vessel_e_price" name="vessel_e_price" value="<?php echo htmlspecialchars($settings['vessel_e_price']); ?>" style="padding-left: 28px;" oninput="updateSim()">
                    </div>
                </div>

            </div>
        </div>

        <!-- SECTION 2: KEEPSAKE BOX PACKAGING PRICES -->
        <div class="admin-card">
            <div style="margin-bottom: 20px; border-bottom: 1px solid #f3f4f6; padding-bottom: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0;">2. Keepsake Box Addon Prices</h3>
                    <p style="font-size: 13px; color: #6b7280; margin-top: 2px;">Manage addon prices for packaging boxes available in Step 4 of the builder.</p>
                </div>
                <a href="<?php echo base_url('/admin/boxes'); ?>" class="admin-btn-edit" style="margin: 0;">Manage All Boxes &rarr;</a>
            </div>

            <?php if (!empty($boxes)): ?>
                <div class="admin-table-container" style="padding: 0; box-shadow: none; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Box Image</th>
                                <th>Box Name</th>
                                <th>SKU</th>
                                <th>Compatible Vessel</th>
                                <th>Current Addon Price ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($boxes as $b): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($b['box_image'])): ?>
                                            <img src="<?php echo base_url('/' . ltrim($b['box_image'], '/')); ?>" alt="<?php echo htmlspecialchars($b['box_name']); ?>" class="admin-thumb">
                                        <?php else: ?>
                                            <div class="admin-no-thumb">NO<br>IMG</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="color: #111827;"><?php echo htmlspecialchars($b['box_name']); ?></strong>
                                    </td>
                                    <td>
                                        <code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 12px; color: #4b5563;"><?php echo htmlspecialchars($b['sku'] ?: '—'); ?></code>
                                    </td>
                                    <td>
                                        <span class="admin-badge-active" style="background: #e0f2fe; color: #0369a1;">Vessel <?php echo htmlspecialchars($b['vessel_code'] ?: 'C'); ?></span>
                                    </td>
                                    <td style="width: 180px;">
                                        <div style="position: relative;">
                                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; font-weight: 600; font-size: 13px;">$</span>
                                            <input type="number" step="0.01" min="0" class="admin-input box-price-input" data-boxid="<?php echo $b['box_id']; ?>" name="box_prices[<?php echo $b['box_id']; ?>]" value="<?php echo number_format((float)$b['box_price'], 2, '.', ''); ?>" style="padding-left: 24px; padding-top: 7px; padding-bottom: 7px; font-size: 13px;" oninput="updateSim()">
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #6b7280; font-size: 14px; text-align: center; padding: 20px;">No boxes found. <a href="<?php echo base_url('/admin/boxes/add'); ?>">Click here to add boxes</a>.</p>
            <?php endif; ?>
        </div>

        <!-- SAVE BUTTON BAR -->
        <div style="display: flex; gap: 16px; margin-top: 10px;">
            <button type="submit" class="admin-btn-primary" style="padding: 14px 36px; font-size: 15px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Save Pricing Settings
            </button>
            <a href="<?php echo base_url('/admin/dashboard'); ?>" class="admin-btn-secondary" style="padding: 14px 24px; font-size: 15px;">Cancel</a>
        </div>
    </form>
</div>
