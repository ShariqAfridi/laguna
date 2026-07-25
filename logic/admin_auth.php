<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Ensure admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (substr($scriptDir, -6) === '/logic') {
        $scriptDir = substr($scriptDir, 0, -6);
    }
    $base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;
    
    header("Location: " . $base . "/admin");
    exit();
}
