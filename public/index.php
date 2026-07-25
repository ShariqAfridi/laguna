<?php
// public/index.php — Single Entry Point for Laguna Vibe Web Application

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/router.php';
?>
