<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class CandlePricingController
{
    public static function index()
    {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/candle_pricing');
    }

    public static function update()
    {
        AdminAuthMiddleware::handle();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . base_url('admin/candle_pricing'));
            exit;
        }

        require_once __DIR__ . '/../../../db.php';
        $dbConn = get_db_connection();

        // 1. Process General Builder Pricing Settings
        $settingKeys = [
            'vessel_c_price' => isset($_POST['vessel_c_price']) ? floatval($_POST['vessel_c_price']) : 30.00,
            'vessel_d_price' => isset($_POST['vessel_d_price']) ? floatval($_POST['vessel_d_price']) : 40.00,
            'vessel_e_price' => isset($_POST['vessel_e_price']) ? floatval($_POST['vessel_e_price']) : 55.00,
        ];

        foreach ($settingKeys as $key => $val) {
            $formattedVal = is_numeric($val) ? number_format((float)$val, 2, '.', '') : (string)$val;
            $stmt = $dbConn->prepare("INSERT INTO builder_pricing_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
            if ($stmt) {
                $stmt->bind_param("ss", $key, $formattedVal);
                $stmt->execute();
                $stmt->close();
            }
        }

        // 2. Process Keepsake Box Prices if provided
        if (isset($_POST['box_prices']) && is_array($_POST['box_prices'])) {
            foreach ($_POST['box_prices'] as $boxId => $priceVal) {
                $boxId = intval($boxId);
                $price = floatval($priceVal);
                if ($boxId > 0 && $price >= 0) {
                    $boxStmt = $dbConn->prepare("UPDATE boxes SET box_price = ?, updated_at = NOW() WHERE box_id = ?");
                    if ($boxStmt) {
                        $boxStmt->bind_param("di", $price, $boxId);
                        $boxStmt->execute();
                        $boxStmt->close();
                    }
                }
            }
        }

        $_SESSION['admin_flash_success'] = "Candle product pricing settings updated successfully!";
        header("Location: " . base_url('admin/candle_pricing'));
        exit;
    }
}
?>
