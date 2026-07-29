<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;

// If already logged in, redirect
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: " . $base . "/admin_dashboard");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Added viewport meta tag for mobile responsiveness -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candle Admin Login</title>

 <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background: url('https://m.media-amazon.com/images/I/71umCKnKw3L.jpg') no-repeat center center/cover;
        min-height: 100vh; /* Changed to min-height for better mobile coverage */
        width: 100%;
        display: flex; /* Using Flexbox for more reliable centering */
        justify-content: center;
        align-items: center;
    }

    .login-container {
        background: rgba(0,0,0,0.65); /* Slightly darker for better contrast */
        padding: 35px;
        border-radius: 12px;
        /* Responsive Width logic */
        width: 90%; 
        max-width: 360px; 
        color: #fff;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        backdrop-filter: blur(5px); /* Adds a subtle glassmorphism effect */
    }

    .login-container h2 {
        margin-bottom: 20px;
        font-size: 24px;
    }

    .input-field {
        width: 100%;
        padding: 14px; /* Slightly larger tap target for mobile */
        margin: 10px 0;
        border-radius: 6px;
        border: none;
        outline: none;
        font-size: 16px; /* Prevents iOS auto-zoom on focus */
    }

    .btn {
        width: 100%;
        padding: 14px;
        background: #0b506e;
        border: none;
        border-radius: 6px;
        color: white;
        font-weight: bold;
        cursor: pointer;
        font-size: 16px;
        margin-top: 10px;
    }

    .btn:hover {
        background: #0d638a;
    }

    .error {
        color: #ff6b6b;
        margin-bottom: 10px;
        font-size: 14px;
    }
</style>
</head>
<body>

    <div class="login-container">
        <h2>Admin</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'invalid'): ?>
            <div class="error">Invalid username or password</div>
        <?php endif; ?>

        <form method="POST" action="<?php echo base_url('/admin'); ?>">
            <input type="text" name="name" placeholder="Username" class="input-field" required>
            <div style="position: relative; margin: 10px 0;">
                <input type="password" id="adminPassword" name="password" placeholder="Password" class="input-field" required style="margin: 0; padding-right: 42px;">
                <button type="button" onclick="toggleAdminPassword(this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #333;" aria-label="Toggle password visibility">
                    <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

    <script>
    function toggleAdminPassword(btn) {
        const input = document.getElementById('adminPassword');
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
    </script>
</body>
</html>