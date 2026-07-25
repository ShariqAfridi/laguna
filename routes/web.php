<?php
// routes/web.php — Frontend Public Storefront Route Table

return [
    '/'             => 'app/Controllers/Frontend/HomeController.php',
    '/about'        => 'app/Controllers/Frontend/AboutController.php',
    '/contact'      => 'app/Controllers/Frontend/ContactController.php',
    '/journal'      => 'app/Controllers/Frontend/JournalController.php',
    '/shop'         => 'app/Controllers/Frontend/ShopController.php',
    '/accessories'  => 'app/Controllers/Frontend/AccessoriesController.php',
    '/builder'      => 'app/Controllers/Frontend/DesignController.php',
    '/checkout'     => 'app/Controllers/Frontend/CheckoutController.php',
    '/thankyou'     => 'app/Controllers/Frontend/ThankyouController.php',
    '/privacy'      => 'app/Controllers/Frontend/PrivacyController.php',
    '/terms'        => 'app/Controllers/Frontend/TermsController.php',
    '/returns'      => 'app/Controllers/Frontend/ReturnsController.php',
    '/maintainance' => 'app/Controllers/Frontend/MaintainanceController.php',
];
?>
