<?php
namespace App\Controllers\Admin;

class AdminLoginController {
    public static function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::login();
            return;
        }
        view('admin/login');
    }

    public static function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $conn = \get_db_connection();
        $username = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';

        $authenticated = false;
        $adminName  = 'Admin';
        $adminEmail = '';
        $adminId    = null;

        if (!empty($username) && !empty($password)) {
            $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE (username = ? OR email = ?) AND role = 'admin' LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (password_verify($password, $row['password']) || $password === $row['password']) {
                        $authenticated = true;
                        $adminId    = $row['id'];
                        $adminName  = $row['username'];
                        $adminEmail = $row['email'];
                    }
                }
                $stmt->close();
            }
        }

        if ($authenticated) {
            session_unset();
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['user_id']         = $adminId;
            $_SESSION['admin_name']       = $adminName;
            $_SESSION['admin_email']      = $adminEmail;

            header("Location: " . base_url('/admin/dashboard'));
            exit();
        } else {
            header("Location: " . base_url('/admin?msg=invalid'));
            exit();
        }
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['user_id']);

        header("Location: " . base_url('/admin'));
        exit();
    }
}
?>
