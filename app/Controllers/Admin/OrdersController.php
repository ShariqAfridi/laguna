<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;
use App\Models\Order;

class OrdersController {
    public static function index() {
        AdminAuthMiddleware::handle();
        $stats = Order::getStats();
        view('admin/sidebar');
        view('admin/orders', ['stats' => $stats]);
    }
}
?>
