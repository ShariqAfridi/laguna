<?php
namespace App\Controllers\Frontend;

use App\Models\User;
use App\Middleware\CustomerAuthMiddleware;

class AuthController {
    public static function index() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Prevent back-button caching of auth page
        CustomerAuthMiddleware::preventBackHistoryCache();

        // If customer is already logged in, redirect to dashboard
        CustomerAuthMiddleware::redirectIfAuthenticated();

        // Generate CSRF token if not set
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        view('frontend/auth', [
            'csrf_token' => $_SESSION['csrf_token']
        ]);
    }

    public static function login() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $identifier = trim($input['email'] ?? ($input['username'] ?? ''));
        $password   = trim($input['password'] ?? '');

        if (empty($identifier) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Please provide your email/username and password.']);
            return;
        }

        $user = User::authenticate($identifier, $password);
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address or password.']);
            return;
        }

        if (in_array(strtolower($user['role'] ?? 'customer'), ['admin', 'superadmin'], true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address or password.']);
            return;
        }

        if (($user['status'] ?? 'active') === 'banned') {
            echo json_encode(['success' => false, 'error' => 'Your account has been suspended. Please contact customer support.']);
            return;
        }

        // Prevent session fixation attack: Regenerate session ID upon successful login
        session_regenerate_id(true);

        // Set customer session variables
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['full_name'] ?? $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'] ?? 'customer';
        $_SESSION['logged_in_at'] = time();

        // Handle return redirect URL
        $redirectUrl = $_SESSION['intended_url'] ?? base_url('dashboard');
        unset($_SESSION['intended_url']);

        echo json_encode([
            'success'  => true,
            'message'  => 'Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!',
            'redirect' => $redirectUrl
        ]);
    }

    public static function register() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $fullName = trim(strip_tags($input['full_name'] ?? ''));
        $email    = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = trim($input['password'] ?? '');
        $phone    = trim(strip_tags($input['phone'] ?? ''));

        if (empty($fullName) || !$email || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Full Name, a valid Email, and Password are required.']);
            return;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long.']);
            return;
        }

        $res = User::register($fullName, $email, $password, $phone);
        if (!$res['success']) {
            echo json_encode($res);
            return;
        }

        // Regenerate session ID upon account creation
        session_regenerate_id(true);

        $user = $res['user'];
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['full_name'] ?? $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'] ?? 'customer';
        $_SESSION['logged_in_at'] = time();

        echo json_encode([
            'success'  => true,
            'message'  => 'Account created successfully! Welcome to LVB.',
            'redirect' => base_url('dashboard')
        ]);
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        // Clear all session variables
        $_SESSION = [];

        // Delete session cookie if set
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy session completely and regenerate ID
        session_destroy();
        session_start();
        session_regenerate_id(true);

        // Prevent browser back history cache
        CustomerAuthMiddleware::preventBackHistoryCache();

        header("Location: " . base_url('login'));
        exit;
    }
}
?>
