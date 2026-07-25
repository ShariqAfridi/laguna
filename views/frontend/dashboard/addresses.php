<!-- 6. SAVED ADDRESSES -->
<div id="tab-addresses" class="dash-panel <?php echo $activeTab === 'addresses' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>Saved Addresses</h2>
            <p>Manage your primary shipping and billing addresses.</p>
        </div>
        <button onclick="openModal('modalAddress')" class="btn-lvb" style="padding:8px 16px; font-size:10px;">+ Add Address</button>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:20px;">
        <?php if (!empty($addresses)): ?>
            <?php foreach ($addresses as $addr): ?>
            <div style="border:1px solid <?php echo !empty($addr['is_default']) ? '#D6E8F0' : '#EEF3F6'; ?>; background:<?php echo !empty($addr['is_default']) ? '#F8FBFD' : '#FFFFFF'; ?>; border-radius:12px; padding:20px; position:relative;">
                <?php if (!empty($addr['is_default'])): ?>
                    <span class="badge-st badge-del" style="margin-bottom:8px;">Default Shipping</span>
                <?php else: ?>
                    <span class="badge-st badge-proc" style="margin-bottom:8px; background:#F4F8FA; color:#6D8491;"><?php echo htmlspecialchars($addr['title'] ?? 'Address'); ?></span>
                <?php endif; ?>
                <h4 style="font-family:'Cinzel', serif; margin-bottom:4px;"><?php echo htmlspecialchars($addr['full_name'] ?: ($user['full_name'] ?? 'Eleanor Vance')); ?></h4>
                <p style="font-size:13px; color:#5A6D7A; line-height:1.5;">
                    <?php echo htmlspecialchars($addr['address']); ?><br>
                    <?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['state'] ?? 'CA'); ?> <?php echo htmlspecialchars($addr['zip']); ?>
                </p>
                <div style="margin-top:12px; text-align:right;">
                    <button onclick="deleteAddress(<?php echo $addr['id']; ?>)" style="background:none; border:none; color:#C5221F; font-size:11px; cursor:pointer; font-weight:600;">Remove</button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; padding:40px 20px; background:#F8FBFD; border-radius:12px; border:1px dashed #D6E8F0; grid-column: 1 / -1;">
                <div style="font-size:32px; margin-bottom:8px;">📍</div>
                <h4 style="font-family:'Cinzel', serif; margin-bottom:4px; font-size:16px;">No Saved Addresses</h4>
                <p style="font-size:12px; color:#6D8491; margin-bottom:16px;">Add a primary shipping and billing address for fast 1-click checkout.</p>
                <button onclick="openModal('modalAddress')" class="btn-lvb" style="padding:8px 16px; font-size:10px;">+ Add New Address</button>
            </div>
        <?php endif; ?>
    </div>
</div>
