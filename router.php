<?php
// Start session once, before any output (fixes 40k+ "headers already sent" warnings)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

$routes = [
    '/' => 'controllers/home.php',
    '/about' => 'controllers/about.php',
    '/contact' => 'controllers/contact.php',
    '/journal' => 'controllers/journal.php',
    '/shop' => 'controllers/shop.php',
    '/builder' => 'controllers/design.php',
    '/admin' => 'controllers/admin.php',
    '/logout' => 'logic/admin_logout.php',
    '/admin/logout' => 'logic/admin_logout.php',
    '/add_product' => 'controllers/add_product.php',
    '/edit_product' => 'controllers/edit_product.php',
    '/list_product' => 'controllers/list_product.php',
    '/fragrance' => 'controllers/fragrance.php',
    '/boxes' => 'controllers/box.php',
    '/colors' => 'controllers/colors.php',
    '/sizes' => 'controllers/sizes.php',
    '/checkout' => 'controllers/checkout.php',
    '/thankyou' => 'controllers/thankyou.php',
    '/orders' => 'controllers/orders.php',
    '/users' => 'controllers/users.php',
    '/admin_dashboard' => 'controllers/dashboard.php',
    '/accessories' => 'controllers/accessories.php',
    '/add_accessory' => 'controllers/add_accessory.php',
    '/edit_accessory' => 'controllers/edit_accessory.php',
    '/list_accessory' => 'controllers/list_accessory.php',
    '/privacy' => 'controllers/privacy.php',
    '/terms' => 'controllers/terms.php',
    '/returns' => 'controllers/returns.php',
    '/maintainance' => 'controllers/maintainance.php',
];

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Remove project folder name if exists (e.g., /laguna or /your-project-folder/)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
if (!empty($base) && strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}

$uri = rtrim($uri ?? '/', '/') ?: '/';  // null-safe (fixes PHP 8 deprecation)

// 404 handler
function handle404()
{
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 Not Found</h1>';
    echo '<p>The page you requested does not exist.</p>';
    exit;
}

// Route match
if (array_key_exists($uri, $routes)) {
    require $routes[$uri];
} else {
    handle404();
}
