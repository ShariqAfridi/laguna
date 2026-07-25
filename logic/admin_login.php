<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';

    // Admin credentials
    $admin_username = "laguna";
    $admin_password = "laguna90@!";

    if ($username === $admin_username && $password === $admin_password) {

        // Clear old session data
        session_unset();

        // Regenerate session ID (security)
        session_regenerate_id(true);

        // Set admin session
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = "Admin";

        // Remove any agent session if exists
        unset($_SESSION['agent_user']);

        // ✅ Redirect to ADD PRODUCT page
        header("Location: /admin_dashboard");
        exit();

    } else {
        header("Location: /admin/login.php?msg=invalid");
        exit();
    }
}