<?php
// Start session once, before any output (fixes 40k+ "headers already sent" warnings)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

$routes = [
    // ── Website / Public Routes (controllers/frontend/) ──
    '/'            => 'controllers/frontend/home.php',
    '/about'       => 'controllers/frontend/about.php',
    '/contact'     => 'controllers/frontend/contact.php',
    '/journal'     => 'controllers/frontend/journal.php',
    '/shop'        => 'controllers/frontend/shop.php',
    '/builder'     => 'controllers/frontend/design.php',
    '/checkout'    => 'controllers/frontend/checkout.php',
    '/thankyou'    => 'controllers/frontend/thankyou.php',
    '/privacy'     => 'controllers/frontend/privacy.php',
    '/terms'       => 'controllers/frontend/terms.php',
    '/returns'     => 'controllers/frontend/returns.php',
    '/maintainance' => 'controllers/frontend/maintainance.php',

    // ── Admin Dashboard Routes (controllers/admin/) ──
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
    '/admin/accessories'    => 'controllers/admin/accessories.php',
    '/admin/add_accessory'  => 'controllers/admin/add_accessory.php',
    '/admin/edit_accessory' => 'controllers/admin/edit_accessory.php',
    '/admin/list_accessory' => 'controllers/admin/list_accessory.php',
    '/admin/logout'         => 'logic/admin_logout.php',
];

// ── Legacy Admin URL Redirect Map (Strict 301 Redirect to /admin/...) ──
$legacyAdminRedirects = [
    '/admin_dashboard' => '/admin/dashboard',
    '/users' => '/admin/users',
    '/orders' => '/admin/orders',
    '/add_product' => '/admin/add_product',
    '/edit_product' => '/admin/edit_product',
    '/list_product' => '/admin/list_product',
    '/fragrance' => '/admin/fragrance',
    '/boxes' => '/admin/boxes',
    '/colors' => '/admin/colors',
    '/sizes' => '/admin/sizes',
    '/accessories' => '/admin/accessories',
    '/add_accessory' => '/admin/add_accessory',
    '/edit_accessory' => '/admin/edit_accessory',
    '/list_accessory' => '/admin/list_accessory',
    '/logout' => '/admin/logout',
];

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Remove project folder name if exists (e.g., /laguna or /your-project-folder/)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
if (!empty($base) && strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}

$uri = rtrim($uri ?? '/', '/') ?: '/';  // null-safe (fixes PHP 8 deprecation)

// Handle 301 Redirect for legacy un-prefixed admin URLs
if (isset($legacyAdminRedirects[$uri])) {
    $queryString = !empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '?') !== false
        ? '?' . explode('?', $_SERVER['REQUEST_URI'], 2)[1]
        : '';
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . base_url($legacyAdminRedirects[$uri]) . $queryString);
    exit;
}

// 404 handler
if (!function_exists('handle404')) {
    function handle404()
    {
        header('HTTP/1.0 404 Not Found');
        echo '<h1>404 Not Found</h1>';
        echo '<p>The page you requested does not exist.</p>';
        exit;
    }
}

// Route match
if (array_key_exists($uri, $routes)) {
    require $routes[$uri];
} else {
    handle404();
}
