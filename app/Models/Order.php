<?php
// app/Models/Order.php — Order Data Access Model
namespace App\Models;

class Order {
    public static function getStats() {
        $conn = \get_db_connection();
        $salesRes = $conn->query("SELECT COALESCE(SUM(total), 0) as total_sales FROM orders WHERE status != 'cancelled'");
        $totalSales = $salesRes ? ($salesRes->fetch_assoc()['total_sales'] ?? 0) : 0;

        $ordersCountRes = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
        $totalOrders = $ordersCountRes ? ($ordersCountRes->fetch_assoc()['total_orders'] ?? 0) : 0;

        $customersRes = $conn->query("SELECT COUNT(DISTINCT email) as unique_customers FROM orders");
        $totalCustomers = $customersRes ? ($customersRes->fetch_assoc()['unique_customers'] ?? 0) : 0;

        return [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'total_customers' => $totalCustomers
        ];
    }
}

if (!class_exists('Order', false)) {
    class_alias('App\Models\Order', 'Order');
}
?>
