<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['signUp'])) {
    redirect('index.php');
}

require_csrf();

$username = clean_text($_POST['fName'] ?? '', 50);
$email = strtolower(clean_text($_POST['email'] ?? '', 100));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');

$_SESSION['auth_tab'] = 'signup';

if (text_length($username) < 2) {
    flash('danger', 'Username must be at least 2 characters.');
    redirect('index.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('danger', 'Please enter a valid email address.');
    redirect('index.php');
}

if (strlen($password) < 8) {
    flash('danger', 'Password must be at least 8 characters.');
    redirect('index.php');
}

if ($password !== $confirmPassword) {
    flash('danger', 'Password confirmation does not match.');
    redirect('index.php');
}

$existing = fetch_one_prepared('SELECT UserID FROM users WHERE Email = ? LIMIT 1', 's', [$email]);
if ($existing) {
    flash('warning', 'An account already exists with that email address.');
    redirect('index.php');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

if (run_prepared('INSERT INTO users (Username, Email, PasswordHash) VALUES (?, ?, ?)', 'sss', [$username, $email, $passwordHash])) {
    unset($_SESSION['auth_tab']);
    flash('success', 'Account created. You can sign in now.');
    redirect('index.php');
}

flash('danger', 'Could not create your account right now. Please try again.');
redirect('index.php');
