<?php
// config/app.php — Application Global Configuration & Helpers

if (!function_exists('load_dotenv')) {
    function load_dotenv($path) {
        if (!file_exists($path)) { return; }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) { continue; }
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim(trim($value), '"\'');
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env from project root
load_dotenv(dirname(__DIR__) . '/.env');

if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = getenv($key);
        if ($val === false || $val === null) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return $val;
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        $envAppUrl = env('APP_URL', null);
        if (!empty($envAppUrl)) {
            return rtrim($envAppUrl, '/') . '/' . ltrim($path, '/');
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = preg_replace('#/logic$#', '', $scriptDir);
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
        return $protocol . $host . $base . '/' . ltrim($path, '/');
    }
}

// Load Composer autoloader if available
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// Register Autoloader for App\ namespace and Models fallback
spl_autoload_register(function ($class) {
    $baseDir = dirname(__DIR__) . '/app/';

    // 1. PSR-4 Autoloading for App\ namespace
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // 2. Fallback for Models accessed without namespace prefix
    $modelFile = $baseDir . 'Models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
        return;
    }
});

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../app/Helpers/view_helper.php';
require_once __DIR__ . '/../app/Middleware/AdminAuthMiddleware.php';
?>
