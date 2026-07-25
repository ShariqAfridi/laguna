<?php
require_once __DIR__ . '/../../Middleware/AdminAuthMiddleware.php';
require_once __DIR__ . '/../../Models/User.php';
check_admin_auth();

class UsersController {
    public static function index() {
        $currentUserId = $_SESSION['user_id'] ?? 0;
        $currentAdminName = $_SESSION['admin_name'] ?? '';
        $currentAdminEmail = $_SESSION['admin_email'] ?? '';
        
        $usersResult = User::getAllExcept($currentUserId, $currentAdminName, $currentAdminEmail);

        view('admin/sidebar');
        view('admin/users', ['usersResult' => $usersResult]);
    }
}
UsersController::index();
?>
