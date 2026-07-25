<?php
// db.php — Forwarding to config/database.php
require_once __DIR__ . '/config/database.php';
$conn = get_db_connection();
?>
