<?php
namespace App\Models;

class Coupon {
    /**
     * Fetch a coupon record by code (case-insensitive)
     */
    public static function findByCode(string $code): ?array {
        $code = strtoupper(trim($code));
        if (empty($code)) return null;

        $conn = \get_db_connection();
        $stmt = $conn->prepare("SELECT * FROM coupons WHERE UPPER(code) = ? LIMIT 1");
        if (!$stmt) return null;

        $stmt->bind_param("s", $code);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0 ? $res->fetch_assoc() : null;
    }

    /**
     * Validate a coupon against current cart subtotal and date/usage constraints
     */
    public static function validate(string $code, float $subtotal = 0.00): array {
        $code = strtoupper(trim($code));
        if (empty($code)) {
            return [
                'valid' => false,
                'discount' => 0.00,
                'message' => 'Please enter a coupon code.',
                'coupon' => null
            ];
        }

        $coupon = self::findByCode($code);
        if (!$coupon) {
            return [
                'valid' => false,
                'discount' => 0.00,
                'message' => 'Invalid coupon code.',
                'coupon' => null
            ];
        }

        // 1. Status Check
        if (intval($coupon['status']) !== 1) {
            return [
                'valid' => false,
                'discount' => 0.00,
                'message' => 'This coupon is currently inactive.',
                'coupon' => null
            ];
        }

        $today = date('Y-m-d');

        // 2. Start Date Check
        if (!empty($coupon['start_date']) && $coupon['start_date'] > $today) {
            return [
                'valid' => false,
                'discount' => 0.00,
                'message' => 'This coupon is not active yet (starts on ' . date('M j, Y', strtotime($coupon['start_date'])) . ').',
                'coupon' => null
            ];
        }

        // 3. End Date / Expiry Check
        if (!empty($coupon['end_date']) && $coupon['end_date'] < $today) {
            return [
                'valid' => false,
                'discount' => 0.00,
                'message' => 'This coupon expired on ' . date('M j, Y', strtotime($coupon['end_date'])) . '.',
                'coupon' => null
            ];
        }

        // 4. Usage Limit Check
        if (!empty($coupon['usage_limit']) && intval($coupon['usage_limit']) > 0) {
            if (intval($coupon['used_count']) >= intval($coupon['usage_limit'])) {
                return [
                    'valid' => false,
                    'discount' => 0.00,
                    'message' => 'This coupon has reached its maximum usage limit.',
                    'coupon' => null
                ];
            }
        }

        // 5. Minimum Order Amount Check
        $minSpend = floatval($coupon['min_order_amount'] ?? 0);
        if ($minSpend > 0 && $subtotal < $minSpend) {
            $diff = $minSpend - $subtotal;
            return [
                'valid' => false,
                'discount' => 0.00,
                'message' => 'Minimum order amount of $' . number_format($minSpend, 2) . ' required (add $' . number_format($diff, 2) . ' more).',
                'coupon' => null
            ];
        }

        // 6. Calculate Discount Amount
        $type = strtolower($coupon['type']);
        $val = floatval($coupon['value']);
        $discount = 0.00;

        if ($type === 'percentage') {
            $discount = round(($subtotal * $val) / 100, 2);
            $maxDiscount = !empty($coupon['max_discount_amount']) ? floatval($coupon['max_discount_amount']) : null;
            if ($maxDiscount !== null && $maxDiscount > 0) {
                $discount = min($discount, $maxDiscount);
            }
        } else { // fixed
            $discount = min($val, $subtotal);
        }

        $discount = max(0.00, $discount);

        $discountText = ($type === 'percentage') ? rtrim(rtrim(number_format($val, 2), '0'), '.') . '% off' : '$' . number_format($val, 2) . ' off';

        return [
            'valid' => true,
            'code' => $coupon['code'],
            'type' => $type,
            'value' => $val,
            'discount' => $discount,
            'description' => $coupon['description'] ?? '',
            'message' => 'Coupon "' . htmlspecialchars($coupon['code']) . '" applied (' . $discountText . ')!',
            'coupon' => $coupon
        ];
    }

    /**
     * Increment used_count when an order is finalized
     */
    public static function incrementUsage(string $code): bool {
        $code = strtoupper(trim($code));
        if (empty($code)) return false;

        $conn = \get_db_connection();
        $stmt = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE UPPER(code) = ?");
        if ($stmt) {
            $stmt->bind_param("s", $code);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Get summary stats for admin dashboard/coupons
     */
    public static function getStats(): array {
        $conn = \get_db_connection();
        $allRes = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active, SUM(used_count) as total_used FROM coupons");
        $stats = $allRes ? $allRes->fetch_assoc() : [];
        return [
            'total' => intval($stats['total'] ?? 0),
            'active' => intval($stats['active'] ?? 0),
            'inactive' => intval($stats['total'] ?? 0) - intval($stats['active'] ?? 0),
            'total_used' => intval($stats['total_used'] ?? 0)
        ];
    }
}
