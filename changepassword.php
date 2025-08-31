<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'dbconnect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$pw_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId   = (int)$_SESSION['user_id'];
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $pw_message = '❌ New password and confirmation do not match.';
    } elseif (strlen($new) < 6) {
        $pw_message = '❌ Password must be at least 6 characters.';
    } else {
        // Legacy MD5 check (your DB stores md5 hashes now)
        $hashCurrent = md5($current);
        $stmt = $conn->prepare("SELECT UserID FROM users WHERE UserID=? AND PasswordHash=? LIMIT 1");
        $stmt->bind_param('is', $userId, $hashCurrent);
        $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->close();
            $hashNew = md5($new); // ⚠ Legacy — recommend password_hash() later
            $up = $conn->prepare("UPDATE users SET PasswordHash=? WHERE UserID=?");
            $up->bind_param('si', $hashNew, $userId);
            if ($up->execute()) {
                $pw_message = '✅ Password updated successfully.';
            } else {
                $pw_message = '❌ Could not update password. Try again.';
            }
            $up->close();
        } else {
            $pw_message = '❌ Current password is incorrect.';
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Roboto', sans-serif;
        margin: 0;
        padding: 0;
        background: linear-gradient(120deg, #ffcc00, #ff9900);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
    .card {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.25);
        width: 100%;
        max-width: 400px;
    }
    h2 {
        margin-top: 0;
        text-align: center;
        color: #ff9900;
    }
    label {
        display:block;
        margin-top: 15px;
        font-weight: 500;
    }
    input {
        width:100%;
        padding:10px;
        margin-top:6px;
        border:1px solid #ccc;
        border-radius:6px;
    }
    button {
        width:100%;
        margin-top:20px;
        padding:12px;
        border:none;
        border-radius:6px;
        background:#ff9900;
        color:#fff;
        font-size:16px;
        cursor:pointer;
        transition:0.3s;
    }
    button:hover { background:#ffcc00; color:#333; }
    .msg { margin-top:15px; text-align:center; font-weight:bold; }
    .ok { color:#2e7d32; }
    .err { color:#b00020; }
    .back {
        display:block;
        text-align:center;
        margin-top:15px;
        text-decoration:none;
        color:#333;
    }
</style>
</head>
<body>

<div class="card">
    <h2>Change Password</h2>
    <form method="post">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required>

        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required>

        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">Update Password</button>
    </form>

    <?php if($pw_message): ?>
      <div class="msg <?php echo (strpos($pw_message,'✅')!==false)?'ok':'err'; ?>">
        <?php echo htmlspecialchars($pw_message); ?>
      </div>
    <?php endif; ?>

    <a href="homepage.php" class="back">← Back to Homepage</a>
</div>

</body>
</html>
