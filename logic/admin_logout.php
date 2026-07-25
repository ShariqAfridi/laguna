<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset admin session
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_name']);

// Get base directory path
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if (substr($scriptDir, -6) === '/logic') {
    $scriptDir = substr($scriptDir, 0, -6);
}
$base = ($scriptDir === '/' || $scriptDir === '.') ? '' : $scriptDir;

header("Location: " . $base . "/admin");
exit();
