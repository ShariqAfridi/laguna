<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class ProductsController {
    public static function list() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/list_product');
    }

    public static function add() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/add_product');
    }

    public static function edit() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/edit_product');
    }
}
?>
