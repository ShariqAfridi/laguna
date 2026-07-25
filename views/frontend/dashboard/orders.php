<!-- 3. ORDERS -->
<div id="tab-orders" class="dash-panel <?php echo $activeTab === 'orders' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>Orders & Status Timeline</h2>
            <p>Track shipments, review items, download invoices, and manage orders.</p>
        </div>
    </div>

    <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
        <button class="btn-lvb btn-lvb-outline" style="padding:6px 12px; font-size:10px;">All Orders</button>
        <button class="btn-lvb btn-lvb-outline" style="padding:6px 12px; font-size:10px;">Shipped</button>
        <button class="btn-lvb btn-lvb-outline" style="padding:6px 12px; font-size:10px;">Delivered</button>
    </div>

    <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $o): 
            $items = $o['items'] ?? [];
            $itemsJson = htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8');
            $firstItemName = !empty($items) ? ($items[0]['name'] ?? 'Luxury Candle') : 'Pacific Breeze Candle';
        ?>
        <div style="border:1px solid #EEF3F6; border-radius:12px; padding:20px; background:#FFFFFF; margin-bottom:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; border-bottom:1px solid #F4F8FA; padding-bottom:12px;">
                <div>
                    <span style="font-size:10px; font-weight:600; color:#6D8491; letter-spacing:1px;">ORDER #</span>
                    <h4 style="margin:0; font-family:'Cinzel', serif; font-size:16px;"><?php echo htmlspecialchars($o['order_number']); ?></h4>
                </div>
                <div>
                    <span class="badge-st badge-<?php echo strtolower($o['status'] === 'delivered' ? 'del' : ($o['status'] === 'shipped' ? 'ship' : 'proc')); ?>"><?php echo ucfirst($o['status']); ?></span>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="order-timeline">
                <div class="timeline-step active"><div class="timeline-dot"></div>Confirmed</div>
                <div class="timeline-step active"><div class="timeline-dot"></div>Packed</div>
                <div class="timeline-step <?php echo in_array($o['status'], ['shipped', 'delivered']) ? 'active' : ''; ?>"><div class="timeline-dot"></div>Shipped</div>
                <div class="timeline-step <?php echo $o['status'] === 'delivered' ? 'active' : ''; ?>"><div class="timeline-dot"></div>Delivered</div>
            </div>

            <!-- Product Preview Item Line -->
            <div style="font-size:13px; color:#1E2F3A; margin:12px 0; background:#F8FBFD; padding:10px 14px; border-radius:8px;">
                <span>📦 <strong><?php echo htmlspecialchars($firstItemName); ?></strong> <?php if (count($items) > 1): ?><small style="color:#6D8491;">(+<?php echo count($items) - 1; ?> more item)</small><?php endif; ?></span>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; border-top:1px solid #F4F8FA; padding-top:12px;">
                <div style="font-size:13px;">Placed on <?php echo date('M d, Y', strtotime($o['created_at'])); ?> &bull; Total: <strong>$<?php echo number_format($o['total'], 2); ?></strong></div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button onclick="openOrderModal('<?php echo $o['order_number']; ?>', '<?php echo date('M d, Y', strtotime($o['created_at'])); ?>', '<?php echo number_format($o['total'], 2); ?>', '<?php echo $o['status']; ?>', '<?php echo $itemsJson; ?>')" class="btn-lvb btn-lvb-outline" style="padding:6px 12px; font-size:10px;">View Details & Tracking</button>
                    <button onclick="downloadInvoice('<?php echo $o['order_number']; ?>')" class="btn-lvb btn-lvb-outline" style="padding:6px 12px; font-size:10px;">Download Invoice</button>
                    <button onclick="reorder('<?php echo $o['order_number']; ?>')" class="btn-lvb" style="padding:6px 12px; font-size:10px;">Reorder</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align:center; padding:40px 20px; background:#F8FBFD; border-radius:12px; border:1px dashed #D6E8F0;">
            <div style="font-size:32px; margin-bottom:8px;">🛍️</div>
            <h4 style="font-family:'Cinzel', serif; margin-bottom:4px; font-size:16px;">No Order History</h4>
            <p style="font-size:12px; color:#6D8491; margin-bottom:16px;">You haven't placed any orders with Laguna Vibe Atelier yet.</p>
            <a href="<?php echo $base; ?>/shop" class="btn-lvb" style="padding:8px 18px; font-size:10px;">Start Shopping &rarr;</a>
        </div>
    <?php endif; ?>
</div>
