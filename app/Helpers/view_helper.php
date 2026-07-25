<?php
// app/Helpers/view_helper.php — View Renderer & Data Binding Helper

if (!function_exists('view')) {
    function view($viewPath, $data = []) {
        // Extract variables into current scope
        if (!empty($data) && is_array($data)) {
            extract($data);
        }

        // Calculate base path for templates
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = preg_replace('#/logic$#', '', $scriptDir);
        $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;

        // Convert dot notation to directory slash (e.g., 'admin.pages.users' => 'admin/pages/users.php')
        $relativePath = str_replace('.', '/', $viewPath);
        if (substr($relativePath, -4) !== '.php') {
            $relativePath .= '.php';
        }

        $fullPath = dirname(__DIR__, 2) . '/views/' . $relativePath;

        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            // Fallback for legacy views path
            $legacyPath = dirname(__DIR__, 2) . '/views/' . ltrim($viewPath, '/');
            if (file_exists($legacyPath)) {
                require $legacyPath;
            } else {
                trigger_error("View template [{$viewPath}] not found at [{$fullPath}]", E_USER_WARNING);
            }
        }
    }
}
?>
