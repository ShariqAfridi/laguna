<?php
require_once __DIR__ . '/../../Middleware/AdminAuthMiddleware.php';
require_once __DIR__ . '/../../Models/Order.php';
check_admin_auth();

class OrdersController {
    public static function index() {
        $stats = Order::getStats();
        view('admin/sidebar');
        view('admin/orders', ['stats' => $stats]);
    }
}
OrdersController::index();
?>
