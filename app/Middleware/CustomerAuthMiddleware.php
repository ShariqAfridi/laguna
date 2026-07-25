<?php
// app/Middleware/CustomerAuthMiddleware.php — Protection Middleware for Customer Dashboard

namespace App\Middleware {
    class CustomerAuthMiddleware {

        /**
         * Protect customer dashboard routes. Redirects unauthenticated guests to customer login.
         */
        public static function handle() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            self::preventBackHistoryCache();

            $userRole = strtolower($_SESSION['user_role'] ?? 'customer');
            if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || in_array($userRole, ['admin', 'superadmin'], true)) {
                // Store intended return URL for post-login redirect
                $requestUri = $_SERVER['REQUEST_URI'] ?? '/dashboard';
                $_SESSION['intended_url'] = $requestUri;

                header('Location: ' . base_url('login'));
                exit;
            }
        }

        /**
         * Prevent browser back button history cache exposure after logout.
         */
        public static function preventBackHistoryCache() {
            header("Cache-Control: no-cache, no-store, must-revalidate");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        /**
         * Redirect already logged-in customers away from login/register pages.
         */
        public static function redirectIfAuthenticated() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                header('Location: ' . base_url('dashboard'));
                exit;
            }
        }
    }
}

namespace {
    if (!function_exists('check_customer_auth')) {
        function check_customer_auth() {
            \App\Middleware\CustomerAuthMiddleware::handle();
        }
    }
}
?>
