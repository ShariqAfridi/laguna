<?php
// routes/admin.php — Admin Dashboard Route Definitions & Legacy Redirects

return [
    'routes' => [
        '/admin'                => 'Admin\AdminLoginController@index',
        '/admin/dashboard'      => 'Admin\DashboardController@index',
        '/admin/users'          => 'Admin\UsersController@index',
        '/admin/orders'         => 'Admin\OrdersController@index',
        '/admin/add_product'    => 'Admin\ProductsController@add',
        '/admin/edit_product'   => 'Admin\ProductsController@edit',
        '/admin/list_product'   => 'Admin\ProductsController@list',
        '/admin/fragrance'      => 'Admin\FragranceController@index',
        '/admin/boxes'          => 'Admin\BoxController@index',
        '/admin/colors'         => 'Admin\ColorsController@index',
        '/admin/sizes'          => 'Admin\SizesController@index',
        '/admin/add_accessory'  => 'Admin\AccessoriesController@add',
        '/admin/edit_accessory' => 'Admin\AccessoriesController@edit',
        '/admin/list_accessory' => 'Admin\AccessoriesController@list',
        '/admin/logout'         => 'Admin\AdminLoginController@logout',
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
