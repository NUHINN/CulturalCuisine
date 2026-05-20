<?php
// Database connection for Cultural Cuisine Explorer.
// Update these values if your local XAMPP/phpMyAdmin credentials differ.
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "culturalcuisineexplorer";

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die('Database connection failed. Please check your local configuration.');
}

if (!$conn->set_charset('utf8mb4')) {
    error_log('Could not set database charset: ' . $conn->error);
}
