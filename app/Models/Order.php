<?php
// app/Models/Order.php — Order Data Access Model
require_once __DIR__ . '/../../config/database.php';

class Order {
    public static function getStats() {
        $conn = get_db_connection();
        $salesRes = $conn->query("SELECT COALESCE(SUM(total), 0) as total_sales FROM orders WHERE status != 'cancelled'");
        $totalSales = $salesRes->fetch_assoc()['total_sales'] ?? 0;

        $ordersCountRes = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
        $totalOrders = $ordersCountRes->fetch_assoc()['total_orders'] ?? 0;

        $customersRes = $conn->query("SELECT COUNT(DISTINCT email) as unique_customers FROM orders");
        $totalCustomers = $customersRes->fetch_assoc()['unique_customers'] ?? 0;

        return [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'total_customers' => $totalCustomers
        ];
    }
}
?>
