<?php
// app/Models/Order.php — Order Data Access Model
namespace App\Models;

class Order {
    public static function getStats(): array {
        $conn = \get_db_connection();
        
        // Total sales (sum of total from orders where status != cancelled)
        $salesRes = $conn->query("SELECT COALESCE(SUM(total), 0) as total_sales FROM orders WHERE status != 'cancelled'");
        $totalSales = $salesRes ? (float)($salesRes->fetch_assoc()['total_sales'] ?? 0) : 0;

        // Total revenue
        $revRes = $conn->query("SELECT COALESCE(SUM(total), 0) as revenue FROM orders WHERE status NOT IN ('cancelled')");
        $totalRevenue = $revRes ? (float)($revRes->fetch_assoc()['revenue'] ?? 0) : 0;

        // Total orders count
        $ordersCountRes = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
        $totalOrders = $ordersCountRes ? (int)($ordersCountRes->fetch_assoc()['total_orders'] ?? 0) : 0;

        // Unique customers
        $customersRes = $conn->query("SELECT COUNT(DISTINCT email) as unique_customers FROM orders");
        $totalCustomers = $customersRes ? (int)($customersRes->fetch_assoc()['unique_customers'] ?? 0) : 0;

        // 30-day Trend
        $trendRes = $conn->query("SELECT 
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN total ELSE 0 END) as current_period,
            SUM(CASE WHEN created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY) THEN total ELSE 0 END) as previous_period
            FROM orders WHERE status != 'cancelled'");
        $trend = $trendRes ? $trendRes->fetch_assoc() : [];
        $currentPeriod = (float)($trend['current_period'] ?? 0);
        $prevPeriod = (float)($trend['previous_period'] ?? 0);
        $percentageChange = ($prevPeriod > 0) ? (($currentPeriod - $prevPeriod) / $prevPeriod) * 100 : 0;
        $arrow = $percentageChange > 0 ? 'fa-arrow-up' : ($percentageChange < 0 ? 'fa-arrow-down' : '');
        $changeClass = $percentageChange > 0 ? 'change-up' : ($percentageChange < 0 ? 'change-down' : 'change-neutral');

        // Recent chart totals (last 6 orders)
        $chartTotals = [];
        $chartRes = $conn->query("SELECT total FROM orders ORDER BY id DESC LIMIT 6");
        if ($chartRes) {
            while ($row = $chartRes->fetch_assoc()) {
                $chartTotals[] = (float)$row['total'];
            }
        }

        // Candle Industry Business Metrics
        $productsRes = $conn->query("SELECT COUNT(*) as total_products FROM products");
        $totalProducts = $productsRes ? (int)($productsRes->fetch_assoc()['total_products'] ?? 0) : 0;

        $fragrancesRes = $conn->query("SELECT COUNT(*) as total_fragrances FROM fragrances");
        $totalFragrances = $fragrancesRes ? (int)($fragrancesRes->fetch_assoc()['total_fragrances'] ?? 0) : 0;

        $categoriesRes = $conn->query("SELECT COUNT(*) as total_categories FROM categories");
        $totalCategories = $categoriesRes ? (int)($categoriesRes->fetch_assoc()['total_categories'] ?? 0) : 0;

        $boxesRes = $conn->query("SELECT COUNT(*) as total_boxes FROM boxes");
        $totalBoxes = $boxesRes ? (int)($boxesRes->fetch_assoc()['total_boxes'] ?? 0) : 0;

        return [
            'total_sales'      => $totalSales,
            'total_revenue'    => $totalRevenue,
            'total_orders'     => $totalOrders,
            'total_customers'  => $totalCustomers,
            'total_products'   => $totalProducts,
            'total_fragrances' => $totalFragrances,
            'total_categories' => $totalCategories,
            'total_boxes'      => $totalBoxes,
            'current_period'   => $currentPeriod,
            'prev_period'      => $prevPeriod,
            'percentage_change'=> $percentageChange,
            'arrow'            => $arrow,
            'change_class'     => $changeClass,
            'chart_totals'     => $chartTotals,
        ];
    }

    public static function updateStatus(int $orderId, string $status): bool {
        $allowed = ['processing', 'shipped', 'delivered', 'cancelled', 'pending', 'refunded'];
        if ($orderId <= 0 || !in_array($status, $allowed, true)) {
            return false;
        }

        $conn = \get_db_connection();
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $orderId);
            return $stmt->execute();
        }
        return false;
    }

    public static function calculateTotals(array $cart, string $promoCode = '', string $deliveryType = 'standard'): array {
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += floatval($item['price'] ?? 0) * intval($item['qty'] ?? 1);
        }

        if (strtolower($deliveryType) === 'express') {
            $shipping = 18.00;
        } else {
            $shipping = ($subtotal >= 75) ? 0.00 : 12.00;
        }

        $taxRate = 0.08;
        $tax = round($subtotal * $taxRate, 2);

        $discount = 0;
        $promoCodeUpper = strtoupper(trim($promoCode));
        if (!empty($promoCodeUpper)) {
            $couponCheck = \App\Models\Coupon::validate($promoCodeUpper, $subtotal);
            if ($couponCheck['valid']) {
                $discount = floatval($couponCheck['discount']);
            }
        }

        $total = max(0, $subtotal + $shipping + $tax - $discount);

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax'      => $tax,
            'discount' => $discount,
            'total'    => $total,
        ];
    }

    public static function getByUser(int $userId = 0, string $email = ''): array {
        $conn = \get_db_connection();
        $email = trim(strtolower($email));

        $where = [];
        if ($userId > 0) {
            $where[] = "user_id = " . intval($userId);
        }
        if (!empty($email)) {
            $where[] = "LOWER(email) = '" . $conn->real_escape_string($email) . "'";
        }

        if (empty($where)) {
            return [];
        }

        $sql = "SELECT * FROM orders WHERE (" . implode(" OR ", $where) . ") ORDER BY id DESC";
        $res = $conn->query($sql);

        $orders = [];
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $orderId = intval($row['id']);
                $itemsRes = $conn->query("
                    SELECT oi.*, p.image, p.fragrance_images, acc.image AS accessory_image
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.product_id
                    LEFT JOIN accessory acc ON oi.product_id = acc.accessory_id
                    WHERE oi.order_id = {$orderId}
                ");
                $items = [];
                if ($itemsRes && $itemsRes->num_rows > 0) {
                    while ($item = $itemsRes->fetch_assoc()) {
                        $rawImg = '';
                        if (!empty($item['fragrance_images']) && !empty($item['scent'])) {
                            $fMap = json_decode($item['fragrance_images'], true);
                            if (is_array($fMap) && !empty($fMap)) {
                                $scentEsc = $conn->real_escape_string(trim($item['scent']));
                                $fq = $conn->query("SELECT fragrance_id FROM fragrances WHERE fragrance_name = '$scentEsc' LIMIT 1");
                                if ($fq && $fr = $fq->fetch_assoc()) {
                                    $fid = (string)$fr['fragrance_id'];
                                    if (!empty($fMap[$fid])) {
                                        $rawImg = $fMap[$fid];
                                    }
                                }
                            }
                        }
                        if (empty($rawImg) && !empty($item['image'])) {
                            $rawImg = $item['image'];
                        }
                        if (empty($rawImg) && !empty($item['accessory_image'])) {
                            $rawImg = $item['accessory_image'];
                        }
                        if (empty($rawImg) && !empty($item['product_name'])) {
                            $pNameClean = trim(explode('+', $item['product_name'])[0]);
                            $pNameClean = trim(explode('—', $pNameClean)[0]);
                            $pNameEsc = $conn->real_escape_string($pNameClean);
                            if (!empty($pNameEsc)) {
                                $pq = $conn->query("SELECT image FROM products WHERE product_name LIKE '%$pNameEsc%' LIMIT 1");
                                if ($pq && $pr = $pq->fetch_assoc()) {
                                    $rawImg = $pr['image'] ?? '';
                                }
                            }
                        }

                        $imgUrl = '';
                        if (!empty($rawImg)) {
                            if (strpos($rawImg, 'http://') === 0 || strpos($rawImg, 'https://') === 0) {
                                $imgUrl = $rawImg;
                            } else {
                                $clean = ltrim(preg_replace('#^/?(public/)?#i', '', $rawImg), '/');
                                $imgUrl = function_exists('base_url') ? base_url('/public/' . $clean) : ('/public/' . $clean);
                            }
                        }
                        $item['image_url'] = $imgUrl;
                        $items[] = $item;
                    }
                }
                $row['items'] = $items;
                $orders[] = $row;
            }
        }
        return $orders;
    }
}
?>

