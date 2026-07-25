<?php
require_once __DIR__ . '/../../Middleware/AdminAuthMiddleware.php';
check_admin_auth();

class AccessoriesController {
    public static function list() {
        require_once __DIR__ . '/../../../views/admin/sidebar.php';
        require_once __DIR__ . '/../../../views/admin/list_accessory.php';
    }

    public static function add() {
        require_once __DIR__ . '/../../../views/admin/sidebar.php';
        require_once __DIR__ . '/../../../views/admin/add_accessory.php';
    }

    public static function edit() {
        require_once __DIR__ . '/../../../views/admin/sidebar.php';
        require_once __DIR__ . '/../../../views/admin/edit_accessory.php';
    }
}
?>
