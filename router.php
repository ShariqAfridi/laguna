<?php
// router.php — Master MVC Application Router

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/app.php';

// Load route tables
$webRoutes   = require __DIR__ . '/routes/web.php';
$adminConfig = require __DIR__ . '/routes/admin.php';

$adminRoutes   = $adminConfig['routes'] ?? [];
$legacyAdminRedirects = $adminConfig['redirects'] ?? [];

$routes = array_merge($webRoutes, $adminRoutes);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Remove project folder name if exists (e.g., /laguna)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
if (!empty($base) && strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}

// Ensure leading slash
if (empty($uri)) {
    $uri = '/';
}

// 1. Check strict 301 legacy redirects
if (array_key_exists($uri, $legacyAdminRedirects)) {
    $targetUrl = base_url($legacyAdminRedirects[$uri]);
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $targetUrl);
    exit;
}

// 2. Dispatch route
if (array_key_exists($uri, $routes)) {
    $target = $routes[$uri];

    if (is_callable($target)) {
        call_user_func($target);
    } elseif (is_array($target) && count($target) === 2) {
        list($class, $method) = $target;
        if (class_exists($class)) {
            $instance = new $class();
            if (method_exists($instance, $method)) {
                $instance->$method();
            } elseif (method_exists($class, $method)) {
                $class::$method();
            } else {
                http_response_code(500);
                echo "500 - Method {$method} not found in {$class}";
            }
        } else {
            http_response_code(500);
            echo "500 - Class {$class} not found";
        }
    } elseif (is_string($target) && strpos($target, '@') !== false) {
        list($class, $method) = explode('@', $target, 2);
        
        // Try exact class name first, then fallback to App\Controllers namespace prefix
        if (!class_exists($class)) {
            $class = 'App\\Controllers\\' . ltrim($class, '\\');
        }
        
        if (class_exists($class)) {
            if (method_exists($class, $method)) {
                // Static method call
                $class::$method();
            } else {
                $instance = new $class();
                if (method_exists($instance, $method)) {
                    $instance->$method();
                } else {
                    http_response_code(500);
                    echo "500 - Method {$method} not found in class {$class}";
                }
            }
        } else {
            http_response_code(500);
            echo "500 - Controller Class {$class} not found";
        }
    } elseif (is_string($target)) {
        $controllerFile = __DIR__ . '/' . ltrim($target, '/');
        if (file_exists($controllerFile)) {
            require $controllerFile;
        } else {
            http_response_code(404);
            echo "404 - Controller file not found: " . htmlspecialchars($target);
        }
    }
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>The requested URL {$uri} was not found on this server.</p>";
}
?>
