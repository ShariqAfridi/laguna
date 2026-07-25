<!-- 2. MY PROFILE -->
<div id="tab-profile" class="dash-panel <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>My Profile</h2>
            <p>Manage your personal profile details and security credentials.</p>
        </div>
    </div>

    <form id="profileForm" onsubmit="handleProfileSubmit(event)" enctype="multipart/form-data">
        <!-- Avatar Photo Upload Box -->
        <div style="display:flex; align-items:center; gap:20px; padding:20px; background:#F8FBFD; border:1px solid #D6E8F0; border-radius:12px; margin-bottom:24px;">
            <div id="avatarPreviewContainer" style="width:70px; height:70px; border-radius:50%; background:#1E2F3A; color:#FFFFFF; font-family:'Cinzel', serif; font-size:22px; font-weight:600; display:flex; align-items:center; justify-content:center; text-transform:uppercase; border:3px solid #D6E8F0; overflow:hidden; position:relative;">
                <?php if (!empty($user['avatar'])): ?>
                    <img id="avatarImage" src="<?php echo $base . '/' . ltrim(htmlspecialchars($user['avatar']), '/'); ?>" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <span id="avatarInitials">
                        <?php 
                            $nameParts = explode(' ', $user['full_name'] ?? 'User');
                            echo strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                        ?>
                    </span>
                    <img id="avatarImage" src="" style="width:100%; height:100%; object-fit:cover; display:none;">
                <?php endif; ?>
            </div>
            <div>
                <h4 style="margin:0 0 4px 0; font-family:'Cinzel', serif; font-size:16px;">Profile Photo</h4>
                <p style="margin:0 0 10px 0; font-size:12px; color:#6D8491;">Upload a square JPG, PNG or WEBP image (max 5MB).</p>
                <label class="btn-lvb btn-lvb-outline" style="padding:6px 14px; font-size:10px; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    📷 Upload New Photo
                    <input type="file" name="avatar" id="avatarFileInput" accept="image/*" style="display:none;" onchange="previewAvatar(event)">
                </label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-grp">
                <label class="form-lbl">Full Name</label>
                <input type="text" name="full_name" class="form-inp" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-grp">
                <label class="form-lbl">Email Address</label>
                <input type="email" name="email" class="form-inp" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-grp">
                <label class="form-lbl">Phone Number</label>
                <input type="text" name="phone" class="form-inp" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>
            <div class="form-grp">
                <label class="form-lbl">Date of Birth</label>
                <input type="date" name="dob" class="form-inp" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-grp">
                <label class="form-lbl">Gender</label>
                <select name="gender" class="form-inp">
                    <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Other">Prefer not to say</option>
                </select>
            </div>
            <div class="form-grp">
                <label class="form-lbl">City</label>
                <input type="text" name="city" class="form-inp" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
            </div>
        </div>
        <button type="submit" class="btn-lvb">Save Profile Updates</button>
    </form>
</div>
