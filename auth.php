<?php
// auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

$SESSION_USER_KEY = 'user_id';

// If not logged in, remember the intended page and send to login
if (!isset($_SESSION[$SESSION_USER_KEY])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'homepage.php';
    header('Location: index.php'); // your login page
    exit;
}
