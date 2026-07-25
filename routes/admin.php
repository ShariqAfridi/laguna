<?php
// routes/admin.php — Admin Dashboard Route Table & Legacy Redirects

return [
    'routes' => [
        '/admin'                => 'app/Controllers/Admin/AdminLoginController.php',
        '/admin/dashboard'      => 'app/Controllers/Admin/DashboardController.php',
        '/admin/users'          => 'app/Controllers/Admin/UsersController.php',
        '/admin/orders'         => 'app/Controllers/Admin/OrdersController.php',
        '/admin/add_product'    => 'controllers/admin/add_product.php',
        '/admin/edit_product'   => 'controllers/admin/edit_product.php',
        '/admin/list_product'   => 'controllers/admin/list_product.php',
        '/admin/fragrance'      => 'app/Controllers/Admin/FragranceController.php',
        '/admin/boxes'          => 'app/Controllers/Admin/BoxController.php',
        '/admin/colors'         => 'app/Controllers/Admin/ColorsController.php',
        '/admin/sizes'          => 'app/Controllers/Admin/SizesController.php',
        '/admin/add_accessory'  => 'controllers/admin/add_accessory.php',
        '/admin/edit_accessory' => 'controllers/admin/edit_accessory.php',
        '/admin/list_accessory' => 'controllers/admin/list_accessory.php',
        '/admin/logout'         => 'logic/admin_logout.php',
    ],
    'redirects' => [
        '/admin_dashboard' => '/admin/dashboard',
        '/users'           => '/admin/users',
        '/orders'          => '/admin/orders',
        '/add_product'     => '/admin/add_product',
        '/edit_product'    => '/admin/edit_product',
        '/list_product'    => '/admin/list_product',
        '/fragrance'       => '/admin/fragrance',
        '/boxes'           => '/admin/boxes',
        '/colors'          => '/admin/colors',
        '/sizes'           => '/admin/sizes',
        '/add_accessory'   => '/admin/add_accessory',
        '/edit_accessory'  => '/admin/edit_accessory',
        '/list_accessory'  => '/admin/list_accessory',
        '/logout'          => '/admin/logout',
    ]
];
?>
