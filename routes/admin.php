<?php
// routes/admin.php — Admin Dashboard Route Table & Legacy Redirects

return [
    'routes' => [
        '/admin'                => 'controllers/admin/admin.php',
        '/admin/dashboard'      => 'controllers/admin/dashboard.php',
        '/admin/users'          => 'controllers/admin/users.php',
        '/admin/orders'         => 'controllers/admin/orders.php',
        '/admin/add_product'    => 'controllers/admin/add_product.php',
        '/admin/edit_product'   => 'controllers/admin/edit_product.php',
        '/admin/list_product'   => 'controllers/admin/list_product.php',
        '/admin/fragrance'      => 'controllers/admin/fragrance.php',
        '/admin/boxes'          => 'controllers/admin/box.php',
        '/admin/colors'         => 'controllers/admin/colors.php',
        '/admin/sizes'          => 'controllers/admin/sizes.php',
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
