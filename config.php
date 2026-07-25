<?php
/**
 * config.php - Environment & Global Helper Loader for Laguna Vibe
 */

if (!function_exists('load_dotenv')) {
    function load_dotenv($filePath) {
        if (!file_exists($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");

                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
        return true;
    }
}

// Load .env from root directory if exists
load_dotenv(__DIR__ . '/.env');

if (!function_exists('env')) {
    function env($key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        if ($val === 'true' || $val === '(true)') return true;
        if ($val === 'false' || $val === '(false)') return false;
        if ($val === 'null' || $val === '(null)') return null;
        return $val;
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        $appUrl = env('APP_URL');
        
        if (empty($appUrl)) {
            // Automatic fail-safe fallback if APP_URL is not set
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
            $appUrl = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
        }
        
        $appUrl = rtrim($appUrl, '/');
        $path   = ltrim($path, '/');
        
        return empty($path) ? ($appUrl ?: '/') : $appUrl . '/' . $path;
    }
}

// Global base variable for view compatibility
$base = env('APP_URL');
if (empty($base)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') { $scriptDir = substr($scriptDir, 0, -6); }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
}
$base = rtrim($base, '/');
