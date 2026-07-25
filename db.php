<?php
// db.php — Database Connection powered by .env configuration
require_once __DIR__ . '/config.php';

$host     = env('DB_HOST', 'localhost');
$username = env('DB_USER', 'root');
$password = env('DB_PASS', '');
$database = env('DB_NAME', 'laguna');

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Database connection error. Please try again later.');
}

$conn->set_charset('utf8mb4');
