<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// If already logged in, redirect
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: /admin_dashboard");
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

        <form method="POST" action="/logic/admin_login.php">
            <input type="text" name="name" placeholder="Username" class="input-field" required>
            <input type="password" name="password" placeholder="Password" class="input-field" required>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>

</body>
</html>