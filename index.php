<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'dbconnect.php';

$login_error = '';
// Handle login when the Sign In form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && (isset($_POST['email']) || isset($_POST['username']) || isset($_POST['identifier']))) {
    // Accept username OR email without changing your form fields
    $identifier = '';
    if (isset($_POST['identifier']))      $identifier = trim($_POST['identifier']);
    elseif (isset($_POST['username']))    $identifier = trim($_POST['username']);
    elseif (isset($_POST['email']))       $identifier = trim($_POST['email']);

    $password = $_POST['password'];
    // Use MD5 to match your current DB (you can migrate later)
    $hash = md5($password);

    // Login via username OR email
    $stmt = $conn->prepare("SELECT UserID FROM users WHERE (Username = ? OR Email = ?) AND PasswordHash = ? LIMIT 1");
    $stmt->bind_param('sss', $identifier, $identifier, $hash);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $_SESSION['user_id'] = (int)$row['UserID'];
        $dest = $_SESSION['redirect_after_login'] ?? 'homepage.php';
        unset($_SESSION['redirect_after_login']);
        header("Location: $dest");
        exit;
    } else {
        $login_error = 'Invalid username/email or password.';
        // (Optional) you can echo $login_error inside your Sign In container if you want to show it.
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register & Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" id="signup" style="display:none;">
      <h1 class="form-title">Register</h1>
      <form method="post" action="register.php">
        <div class="input-group">
           <i class="fas fa-user"></i>
           <input type="text" name="fName" id="fName" placeholder="First Name" required>
           <label for="fname">First Name</label>
        </div>

        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" id="email" placeholder="Email" required>
            <label for="email">Email</label>
        </div>
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <label for="password">Password</label>
        </div>
       <input type="submit" class="btn" value="Sign Up" name="signUp">
      </form>
      <p class="or">
        ----------or--------
      </p>
      <div class="icons">
        <i class="fab fa-google"></i>
        <i class="fab fa-facebook"></i>
      </div>
      <div class="links">
        <p>Already Have Account ?</p>
        <button id="signInButton">Sign In</button>
      </div>
    </div>

    <div class="container" id="signIn">
        <h1 class="form-title">Sign In</h1>
        <!-- CHANGED: post back to THIS page so the PHP above can log in -->
        <form method="post" action="">
          <div class="input-group">
              <i class="fas fa-envelope"></i>
              <!-- Keep your 'email' field name: PHP handles email OR username -->
              <input type="email" name="email" id="email" placeholder="Email" required>
              <label for="email">Email</label>
          </div>
          <div class="input-group">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" id="password" placeholder="Password" required>
              <label for="password">Password</label>
          </div>
          <p class="recover">
            <a href="#">Recover Password</a>
          </p>
         <input type="submit" class="btn" value="Sign In" name="signIn">
        </form>
        <p class="or">
          ----------or--------
        </p>
        <div class="icons">
          <i class="fab fa-google"></i>
          <i class="fab fa-facebook"></i>
        </div>
        <div class="links">
          <p>Don't have account yet?</p>
          <button id="signUpButton">Sign Up</button>
        </div>
      </div>
      <script src="script.js"></script>
</body>
</html>
