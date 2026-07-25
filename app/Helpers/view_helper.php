<?php
// app/Helpers/view_helper.php — View Renderer & Data Binding Helper

if (!function_exists('view')) {
    function view($viewPath, $data = [], $withLayout = true) {
        // Extract variables into current scope
        if (!empty($data) && is_array($data)) {
            extract($data);
        }

        // Calculate base path for templates
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = preg_replace('#/logic$#', '', $scriptDir);
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;

        // Convert dot notation to directory slash
        $relativePath = str_replace('.', '/', $viewPath);
        if (substr($relativePath, -4) !== '.php') {
            $relativePath .= '.php';
        }

        $viewsBase = dirname(__DIR__, 2) . '/views/';
        $fullPath = $viewsBase . $relativePath;

        if (!file_exists($fullPath)) {
            $legacyPath = $viewsBase . ltrim($viewPath, '/');
            if (file_exists($legacyPath)) {
                $fullPath = $legacyPath;
            } else {
                trigger_error("View template [{$viewPath}] not found at [{$fullPath}]", E_USER_WARNING);
                return;
            }
        }

        // Check if rendering a frontend view requiring master layout
        $isFrontend = (strpos($relativePath, 'frontend/') === 0 && strpos($relativePath, 'frontend/layouts/') !== 0);

        if ($isFrontend && $withLayout) {
            $marque  = $viewsBase . 'frontend/layouts/marque.php';
            $header  = $viewsBase . 'frontend/layouts/header.php';
            $header1 = $viewsBase . 'frontend/layouts/header1.php';
            $footer  = $viewsBase . 'frontend/layouts/footer.php';


            if (file_exists($marque))  { require $marque; }
            if (file_exists($header))  { require $header; }
            if (file_exists($header1)) { require $header1; }

            require $fullPath;

            if (file_exists($footer))  { require $footer; }
        } else {
            require $fullPath;
        }
    }
}
?>

