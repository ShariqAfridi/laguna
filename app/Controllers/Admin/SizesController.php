<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class SizesController {
    public static function index() {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/sizes');
    }
}
?>
