<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class FragranceController {
    public static function index() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/fragrance');
    }
}
?>
