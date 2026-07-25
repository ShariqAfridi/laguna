<!-- Topbar Header -->
<div class="dash-topbar">
    <div class="breadcrumbs">
        <a href="<?php echo $base; ?>/">Storefront</a> <span>/</span>
        <a href="<?php echo $base; ?>/dashboard">Customer Account</a> <span>/</span>
        <span id="breadcrumb-current" style="color:#1E2F3A; font-weight:600; text-transform:capitalize;"><?php echo str_replace('-', ' ', $activeTab); ?></span>
    </div>

    <div class="topbar-user">
        <div class="topbar-avatar" style="overflow:hidden;">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?php echo $base . '/' . ltrim(htmlspecialchars($user['avatar']), '/'); ?>" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
                <?php 
                    $nameParts = explode(' ', $user['full_name'] ?? 'User');
                    echo strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                ?>
            <?php endif; ?>
        </div>
        <div>
            <div style="font-weight:600; font-size:14px; color:#1E2F3A;"><?php echo htmlspecialchars($user['full_name'] ?? 'Eleanor Vance'); ?></div>
            <div style="font-size:11px; color:#6D8491;"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
        </div>
    </div>
</div>
