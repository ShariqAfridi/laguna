<?php
require_once __DIR__ . '/../../Middleware/AdminAuthMiddleware.php';
check_admin_auth();

class ColorsController {
    public static function index() {
        require_once __DIR__ . '/../../../views/admin/sidebar.php';
        require_once __DIR__ . '/../../../views/admin/colors.php';
    }
}
ColorsController::index();
?>
