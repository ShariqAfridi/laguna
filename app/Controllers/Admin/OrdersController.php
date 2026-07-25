<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;
use App\Models\Order;

class OrdersController {
    public static function index() {
        AdminAuthMiddleware::handle();

        $updateMsg = $_GET['updated'] ?? null;

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_order_id'], $_POST['update_status'])) {
            $orderId   = (int) $_POST['update_order_id'];
            $newStatus = trim($_POST['update_status']);
            $success   = Order::updateStatus($orderId, $newStatus);
            $updateStatusMsg = $success ? 'success' : 'error';

            $redirect = base_url('admin/orders?updated=' . $updateStatusMsg);
            if (!empty($_POST['current_page']))   $redirect .= '&page='   . (int)$_POST['current_page'];
            if (!empty($_POST['current_status'])) $redirect .= '&status=' . urlencode($_POST['current_status']);
            if (!empty($_POST['current_search'])) $redirect .= '&search=' . urlencode($_POST['current_search']);

            header("Location: " . $redirect);
            exit;
        }

        $stats = Order::getStats();
        view('admin/sidebar');
        view('admin/orders', ['stats' => $stats, 'updateMsg' => $updateMsg]);
    }
}
?>

