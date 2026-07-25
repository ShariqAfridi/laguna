<?php
// db.php  --  STAGING: update these 3 values after creating the DB in cPanel
// (MySQL Databases -> create DB + user -> add user to DB with ALL PRIVILEGES)

/*
 * $host     = 'localhost';
 * $username = 'onlifwko_laguna';   // e.g. lagunavi_be  (cPanel prefixes apply)
 * $password = 'PTm(zZYZTtvCqhV]';
 * $database = 'onlifwko_laguna';   // the DB you import lagunavi_be.sql into
 */

$host = 'localhost';
$username = 'root';  // e.g. lagunavi_be  (cPanel prefixes apply)
$password = '';
$database = 'laguna';  // the DB you import lagunavi_be.sql into

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Database connection error. Please try again later.');
}

$conn->set_charset('utf8mb4');
