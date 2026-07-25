<!-- 1. DASHBOARD HOME -->
<div id="tab-home" class="dash-panel <?php echo $activeTab === 'home' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>Dashboard Overview</h2>
            <p>Welcome to your personal Laguna Vibe Atelier account.</p>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-box">
            <div class="kpi-box-title">Total Orders</div>
            <div class="kpi-box-num"><?php echo count($orders); ?></div>
        </div>
        <div class="kpi-box">
            <div class="kpi-box-title">Pending Orders</div>
            <div class="kpi-box-num"><?php echo intval($pendingCount ?? 0); ?></div>
        </div>
        <div class="kpi-box">
            <div class="kpi-box-title">Completed Orders</div>
            <div class="kpi-box-num"><?php echo intval($completedCount ?? 0); ?></div>
        </div>
        <div class="kpi-box">
            <div class="kpi-box-title">Saved Addresses</div>
            <div class="kpi-box-num"><?php echo count($addresses ?? []); ?></div>
        </div>
    </div>

    <h3 style="font-family:'Cinzel', serif; font-size:18px; margin-bottom:14px;">Recent Order Activity</h3>
    <?php if (!empty($orders)): ?>
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
            <thead>
                <tr style="border-bottom:1px solid #EEF3F6; color:#6D8491;">
                    <th style="padding:10px;">ORDER #</th>
                    <th style="padding:10px;">DATE</th>
                    <th style="padding:10px;">TOTAL</th>
                    <th style="padding:10px;">STATUS</th>
                    <th style="padding:10px; text-align:right;">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr style="border-bottom:1px solid #F4F8FA;">
                    <td style="padding:12px 10px; font-weight:600;"><?php echo htmlspecialchars($o['order_number']); ?></td>
                    <td style="padding:12px 10px;"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                    <td style="padding:12px 10px; font-weight:600;">$<?php echo number_format($o['total'], 2); ?></td>
                    <td style="padding:12px 10px;">
                        <span class="badge-st badge-<?php echo strtolower($o['status'] === 'delivered' ? 'del' : ($o['status'] === 'shipped' ? 'ship' : 'proc')); ?>">
                            <?php echo ucfirst($o['status']); ?>
                        </span>
                    </td>
                    <td style="padding:12px 10px; text-align:right;">
                        <button onclick="openTab('orders')" class="btn-lvb btn-lvb-outline" style="padding:5px 10px; font-size:10px;">View</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="text-align:center; padding:40px 20px; background:#F8FBFD; border-radius:12px; border:1px dashed #D6E8F0;">
        <div style="font-size:32px; margin-bottom:8px;">📦</div>
        <h4 style="font-family:'Cinzel', serif; margin-bottom:4px; font-size:16px;">No Orders Placed Yet</h4>
        <p style="font-size:12px; color:#6D8491; margin-bottom:16px;">Explore our luxury coastal collection and place your first candle order!</p>
        <a href="<?php echo $base; ?>/shop" class="btn-lvb" style="padding:8px 18px; font-size:10px;">Explore Collection &rarr;</a>
    </div>
    <?php endif; ?>
</div>
