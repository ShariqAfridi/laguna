<!-- 4. WISHLIST -->
<div id="tab-wishlist" class="dash-panel <?php echo $activeTab === 'wishlist' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>Wishlist</h2>
            <p>Your saved luxury items and fragrance candles.</p>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:20px;">
        <?php if (!empty($_SESSION['wishlist'])): ?>
            <div style="border:1px solid #EEF3F6; border-radius:12px; overflow:hidden; background:#FFFFFF; padding:16px;">
                <img src="<?php echo $base; ?>/img/newlogo.jpg" style="width:100%; height:160px; object-fit:cover; border-radius:8px; margin-bottom:12px;">
                <h4 style="font-family:'Cinzel', serif; font-size:15px; margin-bottom:4px;">Pacific Breeze Candle</h4>
                <div style="font-weight:600; font-size:14px; margin-bottom:12px;">$48.00</div>
                <button onclick="showToast('Added to Cart!')" class="btn-lvb" style="width:100%; font-size:10px; padding:8px;">Move to Cart</button>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:40px 20px; background:#F8FBFD; border-radius:12px; border:1px dashed #D6E8F0; grid-column: 1 / -1;">
                <div style="font-size:32px; margin-bottom:8px;">🤍</div>
                <h4 style="font-family:'Cinzel', serif; margin-bottom:4px; font-size:16px;">Your Wishlist is Empty</h4>
                <p style="font-size:12px; color:#6D8491; margin-bottom:16px;">Save your favorite coastal candles and accessories to view them here.</p>
                <a href="<?php echo $base; ?>/shop" class="btn-lvb" style="padding:8px 18px; font-size:10px;">Browse Shop &rarr;</a>
            </div>
        <?php endif; ?>
    </div>
</div>
