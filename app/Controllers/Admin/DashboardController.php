<?php
require_once __DIR__ . '/../../Middleware/AdminAuthMiddleware.php';
require_once __DIR__ . '/../../Models/Order.php';
check_admin_auth();

class DashboardController {
    public static function index() {
        $stats = Order::getStats();
        view('admin/sidebar');
        view('admin/dashboard', ['stats' => $stats]);
    }
}
DashboardController::index();
?>
