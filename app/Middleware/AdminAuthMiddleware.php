<?php
// app/Middleware/AdminAuthMiddleware.php — Protection Middleware for Admin Endpoints

namespace App\Middleware {
    class AdminAuthMiddleware {
        public static function handle() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
                header('Location: ' . base_url('/admin'));
                exit;
            }
        }
    }
}

namespace {
    // Global helper function for backward compatibility
    if (!function_exists('check_admin_auth')) {
        function check_admin_auth() {
            \App\Middleware\AdminAuthMiddleware::handle();
        }
    }
}
?>
