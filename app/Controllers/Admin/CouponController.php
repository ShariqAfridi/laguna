<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class CouponController
{
    public static function index()
    {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/coupons');
    }

    public static function add()
    {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/add_coupon');
    }

    public static function edit()
    {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/edit_coupon');
    }

    public static function delete()
    {
        AdminAuthMiddleware::handle();
        require_once dirname(__DIR__, 3) . '/config/database.php';
        $conn = \get_db_connection();

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        }
        header("Location: " . base_url('/admin/coupons'));
        exit;
    }

    public static function toggle()
    {
        AdminAuthMiddleware::handle();
        require_once dirname(__DIR__, 3) . '/config/database.php';
        $conn = \get_db_connection();

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE coupons SET status = IF(status = 1, 0, 1) WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        }
        header("Location: " . base_url('/admin/coupons'));
        exit;
    }
}
