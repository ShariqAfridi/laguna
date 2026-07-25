<?php
// Forwarding shim to CartController
require_once __DIR__ . '/../config/app.php';
App\Controllers\Frontend\CartController::sync();
?>