<?php
// app/Middleware/AdminAuthMiddleware.php — Protection Middleware for Admin Endpoints

if (!function_exists('check_admin_auth')) {
    function check_admin_auth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $scriptDir = preg_replace('#/logic$#', '', $scriptDir);
            $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
            
            header('Location: ' . $base . '/admin');
            exit;
        }
    }
}
?>
