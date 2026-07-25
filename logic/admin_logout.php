<?php
// Forwarding shim to AdminLoginController
require_once __DIR__ . '/../config/app.php';
App\Controllers\Admin\AdminLoginController::logout();
?>
