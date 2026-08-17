<!-- 3. ORDERS -->
<div id="tab-orders" class="dash-panel <?php echo $activeTab === 'orders' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>My Orders & Live Tracking</h2>
            <p>Track your handcrafted shipments, review ordered items, and manage past orders.</p>
        </div>
        <div>
            <span style="font-size:12px; color:#6D8491; background:#F0F6F8; padding:6px 14px; border-radius:20px; font-weight:600;">
                <?php echo count($orders); ?> Total <?php echo count($orders) === 1 ? 'Order' : 'Orders'; ?>
            </span>
        </div>
    </div>

    <!-- Status Filters -->
    <div class="order-filter-bar" style="display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap;">
        <button type="button" onclick="filterDashboardOrders('all', this)" class="btn-lvb btn-lvb-filter active" style="padding:7px 16px; font-size:11px; border-radius:20px; cursor:pointer;">
            All (<?php echo count($orders); ?>)
        </button>
        <button type="button" onclick="filterDashboardOrders('processing', this)" class="btn-lvb btn-lvb-outline btn-lvb-filter" style="padding:7px 16px; font-size:11px; border-radius:20px; cursor:pointer;">
            Processing (<?php echo count(array_filter($orders, fn($x) => in_array(strtolower($x['status'] ?? ''), ['processing', 'pending', 'pending_payment']))); ?>)
        </button>
        <button type="button" onclick="filterDashboardOrders('shipped', this)" class="btn-lvb btn-lvb-outline btn-lvb-filter" style="padding:7px 16px; font-size:11px; border-radius:20px; cursor:pointer;">
            Shipped (<?php echo count(array_filter($orders, fn($x) => strtolower($x['status'] ?? '') === 'shipped')); ?>)
        </button>
        <button type="button" onclick="filterDashboardOrders('delivered', this)" class="btn-lvb btn-lvb-outline btn-lvb-filter" style="padding:7px 16px; font-size:11px; border-radius:20px; cursor:pointer;">
            Delivered (<?php echo count(array_filter($orders, fn($x) => strtolower($x['status'] ?? '') === 'delivered')); ?>)
        </button>
    </div>

    <?php if (!empty($orders)): ?>
        <div id="ordersContainer">
        <?php foreach ($orders as $o): 
            $items = $o['items'] ?? [];
            $st = strtolower($o['status'] ?? 'processing');
            $stGroup = in_array($st, ['pending', 'pending_payment', 'processing']) ? 'processing' : $st;
            
            $orderFullData = [
                'id' => $o['id'],
                'order_number' => $o['order_number'],
                'date' => date('F j, Y \a\t g:i A', strtotime($o['created_at'])),
                'status' => $o['status'],
                'total' => (float)$o['total'],
                'subtotal' => (float)($o['subtotal'] ?? $o['total']),
                'shipping' => (float)($o['shipping'] ?? 0),
                'shipping_method' => $o['shipping_method'] ?? 'FedEx Home Delivery®',
                'delivery_estimate' => $o['delivery_estimate'] ?? '3–5 business days',
                'discount' => (float)($o['discount'] ?? 0),
                'tax' => (float)($o['tax'] ?? 0),
                'promo_code' => $o['promo_code'] ?? '',
                'name' => $o['name'] ?? '',
                'email' => $o['email'] ?? '',
                'phone' => $o['phone'] ?? '',
                'address' => $o['address'] ?? '',
                'city' => $o['city'] ?? '',
                'state' => $o['state'] ?? '',
                'zip' => $o['zip'] ?? '',
                'country' => $o['country'] ?? '',
                'payment_method' => $o['payment_method'] ?? 'Credit Card (Stripe)',
                'notes' => $o['notes'] ?? '',
                'items' => $items
            ];
            $orderDataAttr = htmlspecialchars(json_encode($orderFullData), ENT_QUOTES, 'UTF-8');
            $badgeClass = ($st === 'delivered') ? 'del' : (($st === 'shipped') ? 'ship' : (($st === 'cancelled') ? 'can' : 'proc'));
        ?>
        <div class="user-order-card" data-status="<?php echo $stGroup; ?>" style="border:1px solid #EEF3F6; border-radius:14px; padding:22px; background:#FFFFFF; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.02);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; border-bottom:1px solid #F4F8FA; padding-bottom:14px;">
                <div>
                    <span style="font-size:10px; font-weight:700; color:#6D8491; letter-spacing:1px; text-transform:uppercase;">ORDER REFERENCE</span>
                    <h4 style="margin:2px 0 0; font-family:'Cinzel', serif; font-size:17px; color:#1E2F3A; font-weight:700;"><?php echo htmlspecialchars($o['order_number']); ?></h4>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="badge-st badge-<?php echo $badgeClass; ?>"><?php echo ucfirst($o['status']); ?></span>
                </div>
            </div>

            <!-- Status Timeline Tracker -->
            <div class="order-timeline" style="margin:22px 0;">
                <div class="timeline-step active">
                    <div class="timeline-dot"></div>
                    <span>Confirmed</span>
                </div>
                <div class="timeline-step <?php echo in_array($st, ['processing', 'shipped', 'delivered']) ? 'active' : ''; ?>">
                    <div class="timeline-dot"></div>
                    <span>Processing</span>
                </div>
                <div class="timeline-step <?php echo in_array($st, ['shipped', 'delivered']) ? 'active' : ''; ?>">
                    <div class="timeline-dot"></div>
                    <span>In Transit</span>
                </div>
                <div class="timeline-step <?php echo ($st === 'delivered') ? 'active' : ''; ?>">
                    <div class="timeline-dot"></div>
                    <span>Delivered</span>
                </div>
            </div>

            <!-- Products List Preview -->
            <div style="margin:16px 0; background:#F8FBFD; border:1px solid #EAF2F6; border-radius:10px; padding:12px 16px;">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $idx => $it): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:6px 0; <?php echo ($idx < count($items)-1) ? 'border-bottom:1px solid #EBF1F5;' : ''; ?>">
                            <div style="font-size:13px; color:#1E2F3A;">
                                📦 <strong><?php echo htmlspecialchars($it['product_name'] ?? $it['name'] ?? 'Handcrafted Candle'); ?></strong>
                                <?php if (!empty($it['scent'])): ?>
                                    <span style="color:#6D8491; font-size:12px;"> · <?php echo htmlspecialchars($it['scent']); ?></span>
                                <?php endif; ?>
                                <span style="color:#6D8491; font-size:12px;"> (×<?php echo intval($it['quantity'] ?? $it['qty'] ?? 1); ?>)</span>
                            </div>
                            <div style="font-size:13px; font-weight:600; color:#1E2F3A;">
                                $<?php echo number_format(floatval($it['price'] ?? 0) * intval($it['quantity'] ?? $it['qty'] ?? 1), 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="font-size:13px; color:#1E2F3A;">
                        📦 <strong>Order Items</strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer Details & Actions -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; border-top:1px solid #F4F8FA; padding-top:14px;">
                <div style="font-size:13px; color:#475569;">
                    Placed on <strong><?php echo date('M d, Y', strtotime($o['created_at'])); ?></strong> &bull; Total: <strong style="color:#1E2F3A; font-size:15px;">$<?php echo number_format($o['total'], 2); ?></strong>
                    <?php if (!empty($o['shipping_method'])): ?>
                        <span style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; margin-left:6px;">
                            <i class="fas fa-truck"></i> <?php echo htmlspecialchars($o['shipping_method']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($o['promo_code'])): ?>
                        <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; margin-left:6px;">
                            Promo: <?php echo htmlspecialchars($o['promo_code']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="button" data-order="<?php echo $orderDataAttr; ?>" onclick="openOrderModalFromBtn(this)" class="btn-lvb btn-lvb-outline" style="padding:7px 14px; font-size:11px; border-radius:6px; cursor:pointer;">
                        <i class="fas fa-eye"></i> View Details & Tracking
                    </button>
                    <a href="<?php echo $base; ?>/thankyou?order_id=<?php echo $o['id']; ?>&order_number=<?php echo urlencode($o['order_number']); ?>" target="_blank" class="btn-lvb btn-lvb-outline" style="padding:7px 14px; font-size:11px; border-radius:6px; text-decoration:none;">
                        <i class="fas fa-receipt"></i> Full Receipt
                    </a>
                    <button type="button" onclick="reorder('<?php echo $o['order_number']; ?>')" class="btn-lvb" style="padding:7px 14px; font-size:11px; border-radius:6px; cursor:pointer;">
                        <i class="fas fa-redo"></i> Reorder
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding:50px 20px; background:#F8FBFD; border-radius:14px; border:1px dashed #D6E8F0;">
            <div style="font-size:38px; margin-bottom:12px;">🛍️</div>
            <h4 style="font-family:'Cinzel', serif; margin-bottom:6px; font-size:18px; color:#1E2F3A;">No Order History Found</h4>
            <p style="font-size:13px; color:#6D8491; margin-bottom:20px; max-width:400px; margin-left:auto; margin-right:auto;">
                You haven't placed any orders with Laguna Vibe Atelier yet. Any orders placed online will appear here with live tracking.
            </p>
            <a href="<?php echo $base; ?>/shop" class="btn-lvb" style="padding:10px 24px; font-size:11px; border-radius:8px;">Explore Our Collections &rarr;</a>
        </div>
    <?php endif; ?>
</div>
