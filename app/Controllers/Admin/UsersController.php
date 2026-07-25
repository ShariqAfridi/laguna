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

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            $userId = (int)($_POST['user_id'] ?? 0);
            $msg = '';

            if ($userId > 0) {
                if ($action === 'update_status') {
                    $newStatus = trim($_POST['status'] ?? 'active');
                    if (User::updateStatus($userId, $newStatus)) {
                        $msg = 'status_updated';
                    }
                } elseif ($action === 'update_role') {
                    $newRole = trim($_POST['role'] ?? 'customer');
                    if (User::updateRole($userId, $newRole)) {
                        $msg = 'role_updated';
                    }
                } elseif ($action === 'delete_user') {
                    $msg = User::deleteUser($userId, $currentAdminName);
                }
            }

            $redirectUrl = base_url('admin/users?msg=' . urlencode($msg));
            header("Location: " . $redirectUrl);
            exit;
        }

        $usersResult = User::getAllExcept($currentUserId, $currentAdminName, $currentAdminEmail);

        view('admin/sidebar');
        view('admin/users', ['usersResult' => $usersResult, 'updateMsg' => $_GET['msg'] ?? '']);
    }
}
?>

