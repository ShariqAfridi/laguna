<?php
/**
 * views/frontend/auth.php — Luxury Customer Login & Registration
 */
if (!isset($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
?>

<style>
.auth-container {
    background: linear-gradient(180deg, #F7FCFD 0%, #FFFFFF 100%);
    min-height: calc(100vh - 65px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    font-family: 'Inter', sans-serif;
    color: #1E2F3A;
}

.auth-card {
    background: #FFFFFF;
    border: 1px solid #EEF3F6;
    border-radius: 16px;
    max-width: 460px;
    width: 100%;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
}

.auth-tabs {
    display: flex;
    border-bottom: 1px solid #EEF3F6;
    margin-bottom: 28px;
}
.auth-tab-btn {
    flex: 1;
    text-align: center;
    padding: 12px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #6D8491;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}
.auth-tab-btn.active {
    color: #1E2F3A;
    border-bottom-color: #1E2F3A;
}

.auth-form { display: none; }
.auth-form.active { display: block; }

.form-group { margin-bottom: 20px; }
.form-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #1E2F3A;
    margin-bottom: 8px;
}
.form-control {
    width: 100%;
    padding: 12px 16px;
    font-size: 14px;
    border: 1px solid #DCE6ED;
    border-radius: 8px;
    outline: none;
    transition: border-color 0.2s ease;
}
.form-control:focus {
    border-color: #6D8491;
}

.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.password-wrapper .form-control {
    padding-right: 42px;
}
.toggle-password-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #6D8491;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s ease;
}
.toggle-password-btn:hover {
    color: #1E2F3A;
}

.btn-auth {
    width: 100%;
    padding: 14px;
    background: #1E2F3A;
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: background-color 0.2s ease;
}
.btn-auth:hover {
    background: #14222B;
}

.auth-error {
    background: #FCE8E6;
    color: #C5221F;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 12px;
    margin-bottom: 20px;
    display: none;
}
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-tabs">
            <button class="auth-tab-btn active" onclick="switchAuthTab('login')">Sign In</button>
            <button class="auth-tab-btn" onclick="switchAuthTab('register')">Create Account</button>
        </div>

        <div id="authError" class="auth-error"></div>

        <!-- Login Form -->
        <form id="loginForm" class="auth-form active" onsubmit="submitLogin(event)">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('loginPassword', this)" aria-label="Toggle password visibility">
                        <svg class="eye-icon eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="eye-icon eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-auth">Sign In</button>
        </form>

        <!-- Register Form -->
        <form id="registerForm" class="auth-form" onsubmit="submitRegister(event)">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" placeholder="Eleanor Vance" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number (Optional)</label>
                <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="registerPassword" name="password" class="form-control" placeholder="At least 6 characters" required minlength="6">
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('registerPassword', this)" aria-label="Toggle password visibility">
                        <svg class="eye-icon eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="eye-icon eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-auth">Create Account</button>
        </form>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const openEye = btn.querySelector('.eye-open');
    const closedEye = btn.querySelector('.eye-closed');

    if (input.type === 'password') {
        input.type = 'text';
        if (openEye) openEye.style.display = 'none';
        if (closedEye) closedEye.style.display = 'block';
    } else {
        input.type = 'password';
        if (openEye) openEye.style.display = 'block';
        if (closedEye) closedEye.style.display = 'none';
    }
}

function switchAuthTab(tab) {
    document.querySelectorAll('.auth-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
    document.getElementById('authError').style.display = 'none';

    if (tab === 'login') {
        document.querySelectorAll('.auth-tab-btn')[0].classList.add('active');
        document.getElementById('loginForm').classList.add('active');
    } else {
        document.querySelectorAll('.auth-tab-btn')[1].classList.add('active');
        document.getElementById('registerForm').classList.add('active');
    }
}

function showError(msg) {
    const el = document.getElementById('authError');
    el.textContent = msg;
    el.style.display = 'block';
}

function submitLogin(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());

    fetch('<?php echo $base; ?>/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.href = res.redirect || '<?php echo $base; ?>/dashboard';
        } else {
            showError(res.error || 'Login failed.');
        }
    })
    .catch(() => showError('An error occurred during login.'));
}

function submitRegister(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());

    fetch('<?php echo $base; ?>/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.href = res.redirect || '<?php echo $base; ?>/dashboard';
        } else {
            showError(res.error || 'Registration failed.');
        }
    })
    .catch(() => showError('An error occurred during registration.'));
}
</script>
