<?php
// routes/web.php — Storefront Route Definitions

return [
    '/'             => 'Frontend\HomeController@index',
    '/about'        => 'Frontend\AboutController@index',
    '/contact'      => 'Frontend\ContactController@index',
    '/journal'      => 'Frontend\JournalController@index',
    '/shop'         => 'Frontend\ShopController@index',
    '/accessories'  => 'Frontend\AccessoriesController@index',
    '/builder'      => 'Frontend\DesignController@index',
    '/checkout'     => 'Frontend\CheckoutController@index',
    '/thankyou'     => 'Frontend\ThankyouController@index',
    '/privacy'      => 'Frontend\PrivacyController@index',
    '/terms'        => 'Frontend\TermsController@index',
    '/returns'      => 'Frontend\ReturnsController@index',
    '/maintenance'  => 'Frontend\MaintenanceController@index',
    '/maintainance' => 'Frontend\MaintenanceController@index', // Typo fallback alias
    '/sync_cart'         => 'Frontend\CartController@sync',
    '/logic/sync_cart.php' => 'Frontend\CartController@sync',
    '/api/stripe/create-checkout-session' => 'Frontend\CheckoutController@createStripeSession',
    '/stripe/create-checkout-session.php' => 'Frontend\CheckoutController@createStripeSession',
    '/api/place-order'                     => 'Frontend\CheckoutController@placeOrder',
    '/logic/place_order.php'               => 'Frontend\CheckoutController@placeOrder',
];
?>
