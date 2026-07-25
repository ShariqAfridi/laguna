<!-- 11. RECENTLY VIEWED -->
<div id="tab-recent-products" class="dash-panel <?php echo $activeTab === 'recent-products' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>Recently Viewed Products</h2>
            <p>Quickly return to products you recently browsed.</p>
        </div>
    </div>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:20px;">
        <div style="border:1px solid #EEF3F6; border-radius:12px; padding:16px; background:#FFFFFF;">
            <img src="<?php echo $base; ?>/img/newlogo.jpg" style="width:100%; height:140px; object-fit:cover; border-radius:8px; margin-bottom:10px;">
            <h4 style="font-family:'Cinzel', serif; font-size:14px; margin-bottom:4px;">Golden Sunset Candle</h4>
            <div style="font-weight:600; font-size:13px;">$54.00</div>
        </div>
    </div>
</div>
