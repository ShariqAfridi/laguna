<!-- 5. SHOPPING CART -->
<div id="tab-cart" class="dash-panel <?php echo $activeTab === 'cart' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>Shopping Cart Summary</h2>
            <p>Review items in your active session shopping cart.</p>
        </div>
        <a href="<?php echo $base; ?>/checkout" class="btn-lvb" style="padding:8px 16px; font-size:10px;">Proceed to Checkout &rarr;</a>
    </div>

    <div style="border:1px solid #EEF3F6; border-radius:12px; padding:20px; background:#F8FBFD;">
        <?php 
        $subtotal = 0;
        if (!empty($cart)): 
            foreach ($cart as $item):
                $itemTotal = floatval($item['price'] ?? 0) * intval($item['qty'] ?? 1);
                $subtotal += $itemTotal;
        ?>
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #E4EFF4; padding-bottom:12px; margin-bottom:12px;">
            <div>
                <?php 
                    $scent = !empty($item['scent']) ? $item['scent'] : (!empty($item['fragrance_name']) ? $item['fragrance_name'] : '');
                    if (!empty($scent)): 
                ?>
                    <div style="font-size:12px; color:#0F4C5C; font-weight:600;">Scent: <?php echo htmlspecialchars($scent); ?></div>
                <?php endif; ?>
            </div>
            <div style="font-weight:600; font-size:14px;">$<?php echo number_format($itemTotal, 2); ?></div>
        </div>
        <?php 
            endforeach; 
        else:
        ?>
            <p style="font-size:13px; color:#6D8491;">Your shopping cart is currently empty.</p>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; font-weight:600; font-size:15px; margin-top:8px;">
            <span>Estimated Total:</span>
            <span>$<?php echo number_format($subtotal, 2); ?></span>
        </div>
    </div>
</div>
