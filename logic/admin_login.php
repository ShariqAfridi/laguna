<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    $authenticated = false;
    $adminName = 'Admin';

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

        // Clear old session data
        session_unset();

        // Regenerate session ID (security)
        session_regenerate_id(true);

        // Set admin session
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id']        = $adminId;
        $_SESSION['admin_name']      = $adminName;
        $_SESSION['admin_email']     = $adminEmail;

        // Remove any agent session if exists
        unset($_SESSION['agent_user']);

        // Detect base directory (stripping /logic subfolder if present)
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if (substr($scriptDir, -6) === '/logic') {
            $scriptDir = substr($scriptDir, 0, -6);
        }
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;

        // ✅ Redirect to admin dashboard
        header("Location: " . $base . "/admin/dashboard");
        exit();

    } else {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if (substr($scriptDir, -6) === '/logic') {
            $scriptDir = substr($scriptDir, 0, -6);
        }
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;

        header("Location: " . $base . "/admin?msg=invalid");
        exit();
    }
}