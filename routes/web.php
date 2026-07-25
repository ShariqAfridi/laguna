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
];
?>
