<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'dbconnect.php';

// ----- password change handler -----
$pw_message = '';
if (isset($_POST['sb_action']) && $_POST['sb_action'] === 'change_password') {
    if (!isset($_SESSION['user_id'])) {
        $pw_message = 'You need to log in first.';
    } else {
        $userId = (int)$_SESSION['user_id'];
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $pw_message = 'New password and confirmation do not match.';
        } elseif (strlen($new) < 6) {
            $pw_message = 'Password must be at least 6 characters.';
        } else {
            $hashCurrent = md5($current);
            $stmt = $conn->prepare("SELECT UserID FROM users WHERE UserID=? AND PasswordHash=? LIMIT 1");
            $stmt->bind_param('is', $userId, $hashCurrent);
            $stmt->execute(); $stmt->store_result();
            if ($stmt->num_rows === 1) {
                $stmt->close();
                $hashNew = md5($new);
                $up = $conn->prepare("UPDATE users SET PasswordHash=? WHERE UserID=?");
                $up->bind_param('si',$hashNew,$userId);
                if ($up->execute()) $pw_message = 'Password updated successfully.';
                else $pw_message = 'Could not update password.';
                $up->close();
            } else {
                $pw_message = 'Current password is incorrect.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(120deg, #ffcc00, #ff9900);
            background-size: cover;
            background-attachment: fixed;
        }

        header {
            background: linear-gradient(120deg, #ffcc00, #ff9900);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        header .logo {
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }

        header .nav-buttons {
            display: flex;
            gap: 15px;
        }

        header .nav-buttons a {
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            background-color: #333;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        header .nav-buttons a:hover {
            background-color: #444;
        }

        .container {
            max-width: 1200px;
            margin: 50px auto;
            text-align: center;
        }

        h1 {
            font-size: 48px;
            color: #333;
            margin-bottom: 20px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        }

        h1 .highlight {
            color: #ff9900;
        }

        .box-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 40px;
        }

        .box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 180px;
            height: 180px;
            margin: 15px;
            background-color: #fff;
            color: #333;
            text-align: center;
            font-size: 18px;
            text-transform: capitalize;
            text-decoration: none;
            border-radius: 10px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            padding: 20px;
        }

        .box i {
            font-size: 36px;
            margin-bottom: 10px;
            color: #ff9900;
        }

        .box:hover {
            background-color: #ffcc00;
            color: #fff;
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.4);
        }

        .about {
            background-color: #fff;
            padding: 40px 20px;
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            max-width: 800px;
        }

        .about h2 { font-size: 32px; color: #333; margin-bottom: 20px; }
        .about p { font-size: 16px; color: #555; line-height: 1.6; }

        footer {
            background-color: #333;
            color: #fff;
            padding: 20px 0;
            text-align: center;
            margin-top: 40px;
        }

        footer .social-icons { margin-top: 10px; }
        footer .social-icons a {
            color: #ffcc00;
            font-size: 24px;
            margin: 0 10px;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        footer .social-icons a:hover { color: #ff9900; }
        footer .footer-text { margin-top: 10px; font-size: 14px; }

        /* Sidebar */
        .sb-hamburger {
          position: fixed; left: 16px; top: 16px; z-index: 1001;
          width: 42px; height: 42px; border-radius: 10px;
          background: #4CAF50; color: #fff; border: none; cursor: pointer;
          display: grid; place-items: center; box-shadow: 0 6px 16px rgba(76,175,80,.35);
        }
        .sb-hamburger span, .sb-hamburger span::before, .sb-hamburger span::after {
          content:""; display:block; width:20px; height:2px; background:#fff; position:relative;
        }
        .sb-hamburger span::before { position:absolute; top:-6px; }
        .sb-hamburger span::after { position:absolute; top:6px; }

        .sb-overlay {
          position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1000;
          opacity:0; visibility:hidden; transition:.25s;
        }
        .sb-overlay.sb-open { opacity:1; visibility:visible; }

        .sb-sidebar {
          position:fixed; left:0; top:0; bottom:0; width:280px; z-index:1001;
          background:#fff; transform:translateX(-100%); transition:transform .28s ease;
          box-shadow:6px 0 24px rgba(0,0,0,.12); padding:18px;
        }
        .sb-sidebar.sb-open { transform:translateX(0); }

        .sb-menu { display:flex; flex-direction:column; gap:6px; }
        .sb-link { text-decoration:none; color:#333; padding:10px; border-radius:8px; }
        .sb-link:hover { background:#e8f5e9; }
        .sb-btn { margin-top:10px;width:100%;padding:10px;border:none;border-radius:8px;background:#4CAF50;color:#fff;cursor:pointer; }
        .sb-status-ok {color:#2e7d32;margin-top:8px;}
        .sb-status-err{color:#b00020;margin-top:8px;}
    </style>
</head>
<body>

<!-- hamburger -->
<button class="sb-hamburger" id="sbOpenBtn"><span></span></button>
<div class="sb-overlay" id="sbOverlay"></div>

<!-- sidebar -->
<aside class="sb-sidebar" id="sbSidebar">
  <h3 style="margin-top:0;color:#4CAF50;">Menu</h3>
  <nav class="sb-menu">
    <a class="sb-link" href="homepage.php">Home</a>
    <a class="sb-link" href="profile.php">Profile</a>
    <a class="sb-link" href="Recipe.php">Recipes</a>
    <a class="sb-link" href="savedrecipe.php">Saved Recipes</a>
    <a class="sb-link" href="reviews.php">Reviews</a>
    <a class="sb-link" href="changepassword.php">Change Password</a>
    <a class="sb-link" href="index.php">Logout</a>
  </nav>

  
  <?php if (!isset($_SESSION['user_id'])): ?>
    <p>You must <a href="index.php">log in</a> first.</p>
  <?php else: ?>
    
      <?php if($pw_message): ?>
        <div class="<?php echo stripos($pw_message,'success')!==false?'sb-status-ok':'sb-status-err'; ?>">
          <?php echo htmlspecialchars($pw_message); ?>
        </div>
      <?php endif; ?>
    </form>
  <?php endif; ?>
</aside>

<script>
(function(){
  const btn=document.getElementById('sbOpenBtn');
  const sb=document.getElementById('sbSidebar');
  const ov=document.getElementById('sbOverlay');
  function open(){sb.classList.add('sb-open');ov.classList.add('sb-open');}
  function close(){sb.classList.remove('sb-open');ov.classList.remove('sb-open');}
  btn.addEventListener('click',open);
  ov.addEventListener('click',close);
  document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});
})();
</script>

<!-- ==== ORIGINAL CONTENT ==== -->

<!-- Header Section -->
<header>
    <div class="logo">_....Cultural Cuisine Explorer</div>
    <div class="nav-buttons">
        <a href="contact.php">Contact Us</a>
    </div>
</header>

<div class="container">
    <h1>Explore <span class="highlight">Natural</span> and Spicy Cuisine</h1>

    <div class="box-container">
        <a href="profile.php" class="box">
            <i class="fas fa-user"></i>
            <div class="box-title">My Profile</div>
            

        <a href="recipe.php" class="box">
            <i class="fas fa-utensils"></i>
            <div class="box-title">Recipes</div>
        </a>

        <a href="Ingredients.php" class="box">
            <i class="fas fa-carrot"></i>
            <div class="box-title">Ingredients</div>
        </a>

        <a href="CulturalDetails.php" class="box">
            <i class="fas fa-globe"></i>
            <div class="box-title">Cultural Details</div>
        </a>

        <a href="savedrecipe.php" class="box">
            <i class="fas fa-bookmark"></i>
            <div class="box-title">Saved Recipes</div>
        </a>

        <a href="Reviews.php" class="box">
            <i class="fas fa-star"></i>
            <div class="box-title">Reviews</div>
        </a>

        <a href="RecipeTags.php" class="box">
            <i class="fas fa-tags"></i>
            <div class="box-title">Recipe Tags</div>
        </a>

        <a href="search.php" class="box">
            <i class="fas fa-search"></i>
            <div class="box-title">Search Recipies</div>
        </a>
    </div>

    <div class="about">
        <h2>About Us</h2>
        <p>Cultural Cuisine Explorer is your go-to platform for discovering delicious and authentic recipes from various cultures around the world. We bring you a rich collection of recipes, ingredients, and cultural details to inspire your culinary journey. Whether you are a seasoned chef or an aspiring cook, our website provides the tools and knowledge to help you explore and enjoy the diverse flavors of global cuisine.</p>
    </div>
</div>

<footer>
    <div class="social-icons">
        <a href="#" class="fab fa-facebook-f"></a>
        <a href="#" class="fab fa-twitter"></a>
        <a href="#" class="fab fa-instagram"></a>
        <a href="#" class="fab fa-linkedin-in"></a>
    </div>
    <div class="footer-text">
        &copy; 2025 Cultural Cuisine Explorer. All Rights Reserved.
    </div>
</footer>

</body>
</html>
