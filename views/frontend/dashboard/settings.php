<!-- 14. ACCOUNT SETTINGS -->
<div id="tab-settings" class="dash-panel <?php echo $activeTab === 'settings' ? 'active' : ''; ?>">
    <div class="panel-title">
        <div>
            <h2>Account Security & Preferences</h2>
            <p>Manage passwords, security credentials, and notification settings.</p>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:24px; max-width:540px;">
        <!-- Change Password Form -->
        <div style="border:1px solid #EEF3F6; border-radius:12px; padding:24px; background:#FFFFFF;">
            <h4 style="font-family:'Cinzel', serif; font-size:16px; margin-bottom:16px;">Update Account Password</h4>
            <form onsubmit="handleChangePassword(event)">
                <div class="form-grp">
                    <label class="form-lbl">New Password</label>
                    <input type="password" name="new_password" class="form-inp" minlength="6" placeholder="Enter new password" required>
                </div>
                <div class="form-grp">
                    <label class="form-lbl">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-inp" minlength="6" placeholder="Confirm new password" required>
                </div>
                <button type="submit" class="btn-lvb" style="padding:10px 20px;">Update Password</button>
            </form>
        </div>


        <div style="padding:16px; border:1px solid #EEF3F6; border-radius:10px; background:#FFFFFF;">
            <div style="font-weight:600; font-size:14px; color:#1E2F3A;">Two-Factor Authentication (2FA)</div>
            <div style="font-size:12px; color:#6D8491; margin-top:2px; margin-bottom:10px;">Protect your customer account with 2-step verification.</div>
            <button onclick="showToast('2FA setup link sent to your registered email address.')" class="btn-lvb btn-lvb-outline" style="padding:6px 12px; font-size:10px;">Enable 2FA Protection</button>
        </div>
    </div>
</div>
