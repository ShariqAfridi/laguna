<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class ColorsController {
    public static function index() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/colors');
    }
}
?>
