<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class ColorsController {
    public static function index() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/colors');
    }

    public static function add() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/add_color');
    }

    public static function edit() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/edit_color');
    }
}
?>
