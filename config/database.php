<?php
// config/database.php — Database Connection Factory
require_once __DIR__ . '/app.php';

function get_db_connection()
{
    static $conn = null;
    if ($conn === null) {
        $host = env('DB_HOST', 'localhost');
        $username = env('DB_USER', 'root');
        $password = env('DB_PASS', '');
        $database = env('DB_NAME', 'onlifwko_laguna');

        $conn = new mysqli($host, $username, $password, $database);
        if ($conn->connect_error) {
            error_log('Database connection failed: ' . $conn->connect_error);
            die('Database connection error. Please try again later.');
        }
        $conn->set_charset('utf8mb4');

        // Ensure colors table has single/double/triple wick image columns across environments
        static $schemaChecked = false;
        if (!$schemaChecked) {
            $schemaChecked = true;
            $res = $conn->query("SHOW COLUMNS FROM colors LIKE 'single_wick_image'");
            if ($res && $res->num_rows === 0) {
                @$conn->query("ALTER TABLE colors ADD COLUMN single_wick_image VARCHAR(255) DEFAULT NULL");
                @$conn->query("ALTER TABLE colors ADD COLUMN double_wick_image VARCHAR(255) DEFAULT NULL");
                @$conn->query("ALTER TABLE colors ADD COLUMN triple_wick_image VARCHAR(255) DEFAULT NULL");
            }
        }
    }
    return $conn;
}
?>
