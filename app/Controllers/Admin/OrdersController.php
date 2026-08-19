<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;
use App\Models\Order;

class OrdersController {
    public static function index() {
        AdminAuthMiddleware::handle();

        // ─── Handle CSV Export BEFORE rendering any views/HTML ────────
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            self::exportCsv();
            exit;
        }

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

    public static function exportCsv() {
        $conn = \get_db_connection();
        $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
        $searchTerm   = isset($_GET['search']) ? trim($_GET['search']) : '';

        $exportSql = "SELECT o.* FROM orders o WHERE 1=1";
        if (!empty($statusFilter) && $statusFilter != 'all') {
            $se = mysqli_real_escape_string($conn, $statusFilter);
            $exportSql .= " AND o.status = '$se'";
        }
        if (!empty($searchTerm)) {
            $st = mysqli_real_escape_string($conn, $searchTerm);
            $exportSql .= " AND (o.order_number LIKE '%$st%' OR o.name LIKE '%$st%' OR o.email LIKE '%$st%' OR o.address LIKE '%$st%')";
        }
        $exportSql .= " ORDER BY o.created_at DESC";

        $res = mysqli_query($conn, $exportSql);

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Laguna_Vibe_Orders_' . date('Y-m-d_H-i') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // CSV Column Headers
        fputcsv($output, [
            'Order ID',
            'Order Number',
            'Order Date',
            'Status',
            'Customer Name',
            'Email',
            'Phone',
            'Address',
            'City',
            'State',
            'Zip Code',
            'Country',
            'Shipping Method',
            'Delivery Estimate',
            'Payment Method',
            'Transaction ID',
            'Ordered Items (SKU, Name, Scent, Qty, Price)',
            'Subtotal ($)',
            'Shipping ($)',
            'Tax ($)',
            'Discount ($)',
            'Promo Code',
            'Grand Total ($)',
            'Customer Notes'
        ]);

        if ($res && mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
                $oid = (int)$row['id'];

                // Fetch order items with SKU details
                $itemsRes = mysqli_query($conn, "
                    SELECT oi.*, p.sku AS product_sku, acc.sku AS accessory_sku 
                    FROM order_items oi 
                    LEFT JOIN products p ON oi.product_id = p.product_id 
                    LEFT JOIN accessory acc ON oi.product_id = acc.accessory_id 
                    WHERE oi.order_id = $oid
                ");

                $itemParts = [];
                if ($itemsRes) {
                    while ($item = mysqli_fetch_assoc($itemsRes)) {
                        $itemSku = !empty($item['product_sku']) ? $item['product_sku'] : (!empty($item['accessory_sku']) ? $item['accessory_sku'] : '');
                        $skuStr = !empty($itemSku) ? " [SKU: {$itemSku}]" : '';
                        $scentStr = !empty($item['scent']) ? " ({$item['scent']})" : '';
                        $itemParts[] = $item['product_name'] . $skuStr . $scentStr . " x " . $item['quantity'] . " ($" . number_format((float)$item['price'], 2) . ")";
                    }
                }
                $itemsSummary = implode(" | ", $itemParts);

                fputcsv($output, [
                    $row['id'],
                    $row['order_number'],
                    $row['created_at'],
                    ucfirst($row['status']),
                    $row['name'],
                    $row['email'],
                    $row['phone'] ?? '',
                    $row['address'],
                    $row['city'] ?? '',
                    $row['state'] ?? '',
                    $row['zip'] ?? '',
                    $row['country'] ?? 'US',
                    $row['shipping_method'] ?? 'Standard',
                    $row['delivery_estimate'] ?? '',
                    $row['payment_method'] ?? '',
                    $row['boa_transaction_id'] ?? '',
                    $itemsSummary,
                    number_format((float)($row['subtotal'] ?? $row['total']), 2),
                    number_format((float)($row['shipping'] ?? 0), 2),
                    number_format((float)($row['tax'] ?? 0), 2),
                    number_format((float)($row['discount'] ?? 0), 2),
                    $row['promo_code'] ?? '',
                    number_format((float)$row['total'], 2),
                    $row['notes'] ?? ''
                ]);
            }
        }
        fclose($output);
        exit;
    }
}
?>

