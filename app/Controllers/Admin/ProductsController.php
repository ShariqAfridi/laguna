<?php
require_once __DIR__ . '/../../Middleware/AdminAuthMiddleware.php';
check_admin_auth();

class ProductsController {
    public static function list() {
        require_once __DIR__ . '/../../../views/admin/sidebar.php';
        require_once __DIR__ . '/../../../views/admin/list_product.php';
    }

    public static function add() {
        require_once __DIR__ . '/../../../views/admin/sidebar.php';
        require_once __DIR__ . '/../../../views/admin/add_product.php';
    }

    public static function edit() {
        require_once __DIR__ . '/../../../views/admin/sidebar.php';
        require_once __DIR__ . '/../../../views/admin/edit_product.php';
    }
}
?>
