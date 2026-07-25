<!-- 10. REVIEWS & RATINGS -->
<div id="tab-reviews" class="dash-panel <?php echo $activeTab === 'reviews' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>My Product Reviews</h2>
            <p>Review products you've purchased from Laguna Vibe.</p>
        </div>
        <button onclick="openReviewModal('')" class="btn-lvb" style="padding:8px 16px; font-size:10px;">+ Write Review</button>
    </div>

    <div style="display:flex; flex-direction:column; gap:16px;">
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $rev): ?>
            <div style="border:1px solid #EEF3F6; border-radius:12px; padding:20px; background:#FFFFFF;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="color:#F59E0B; font-size:16px;"><?php echo str_repeat('★', intval($rev['rating'])) . str_repeat('☆', 5 - intval($rev['rating'])); ?></div>
                    <span style="font-size:11px; color:#6D8491;"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                </div>
                <h4 style="font-family:'Cinzel', serif; font-size:15px; margin:6px 0;"><?php echo htmlspecialchars($rev['product_name']); ?></h4>
                <p style="font-size:13px; color:#5A6D7A; margin:0;">"<?php echo htmlspecialchars($rev['review_text']); ?>"</p>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; padding:40px 20px; background:#F8FBFD; border-radius:12px; border:1px dashed #D6E8F0; width:100%;">
                <div style="font-size:32px; margin-bottom:8px;">⭐</div>
                <h4 style="font-family:'Cinzel', serif; margin-bottom:4px; font-size:16px;">No Product Reviews Submitted</h4>
                <p style="font-size:12px; color:#6D8491; margin-bottom:16px;">Once your order is delivered, share your experience by reviewing your products.</p>
                <button onclick="openReviewModal('')" class="btn-lvb btn-lvb-outline" style="padding:8px 16px; font-size:10px;">Write a Review</button>
            </div>
        <?php endif; ?>
    </div>
</div>
