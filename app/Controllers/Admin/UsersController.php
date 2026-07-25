<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;
use App\Models\User;

class UsersController {
    public static function index() {
        AdminAuthMiddleware::handle();

        $currentUserId    = $_SESSION['user_id'] ?? 0;
        $currentAdminName  = $_SESSION['admin_name'] ?? '';
        $currentAdminEmail = $_SESSION['admin_email'] ?? '';

        $usersResult = User::getAllExcept($currentUserId, $currentAdminName, $currentAdminEmail);

        view('admin/sidebar');
        view('admin/users', ['usersResult' => $usersResult]);
    }
}
?>
