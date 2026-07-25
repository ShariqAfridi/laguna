<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class AccessoriesController {
    public static function list() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/list_accessory');
    }

    public static function add() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/add_accessory');
    }

    public static function edit() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/edit_accessory');
    }
}
?>
