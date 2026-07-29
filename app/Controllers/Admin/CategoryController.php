<?php
namespace App\Controllers\Admin;

use App\Middleware\AdminAuthMiddleware;

class CategoryController
{
    public static function index()
    {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/categories');
    }

    public static function add()
    {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/add_category');
    }

    public static function edit()
    {
        AdminAuthMiddleware::handle();
        view('admin/sidebar');
        view('admin/edit_category');
    }
}
?>
