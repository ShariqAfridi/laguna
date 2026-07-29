<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class FragranceController {
    public static function index() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/fragrance');
    }

    public static function add() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/add_fragrance');
    }

    public static function edit() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/edit_fragrance');
    }
}
?>
