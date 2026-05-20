<?php
$servername = trim(getenv("DB_HOST"));
$username = trim(getenv("DB_USER"));
$password = trim(getenv("DB_PASS"));
$dbname = trim(getenv("DB_NAME"));
$port = (int) trim(getenv("DB_PORT"));

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed. Please check your database configuration.");
}
?>
