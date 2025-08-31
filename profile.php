<?php require_once 'auth.php'; ?>

<?php
// user.php

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // back to login
    exit;
}

require_once 'dbconnect.php'; // expects $conn (mysqli)

// --- ensure users table has ProfileImage column (run-noop after first time) ---
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS ProfileImage VARCHAR(255) NULL");

// --- fetch current user ---
$userId = (int)$_SESSION['user_id'];
$user = ['Username' => 'User', 'Email' => '', 'ProfileImage' => null];

if ($res = $conn->query("SELECT Username, Email, ProfileImage FROM users WHERE UserID = $userId LIMIT 1")) {
    if ($res->num_rows) $user = $res->fetch_assoc();
    $res->free();
}

// --- handle profile image upload ---
$uploadMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    // Basic validation
    if (!is_dir(__DIR__ . '/uploads')) {
        @mkdir(__DIR__ . '/uploads', 0777, true);
    }
    if (is_uploaded_file($_FILES['avatar']['tmp_name'])) {
        $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/gif'=>'gif', 'image/webp'=>'webp'];
        $type = mime_content_type($_FILES['avatar']['tmp_name']);
        if (!isset($allowed[$type])) {
            $uploadMsg = 'Please upload a JPG, PNG, GIF, or WEBP image.';
        } else {
            // sanitize filename
            $ext = $allowed[$type];
            $filename = 'avatar_u' . $userId . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $filename;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                $pathForDb = 'uploads/' . $filename;
                $stmt = $conn->prepare("UPDATE users SET ProfileImage = ? WHERE UserID = ?");
                $stmt->bind_param('si', $pathForDb, $userId);
                if ($stmt->execute()) {
                    $user['ProfileImage'] = $pathForDb;
                    $uploadMsg = 'Profile photo updated.';
                } else {
                    $uploadMsg = 'Could not save image path in database.';
                }
                $stmt->close();
            } else {
                $uploadMsg = 'Upload failed. Check folder permissions.';
            }
        }
    } else {
        $uploadMsg = 'No file selected.';
    }
}

// --- check if recipes.CreatedBy exists ---
$hasCreatedBy = false;
if ($res = $conn->query("SHOW COLUMNS FROM recipes LIKE 'CreatedBy'")) {
    $hasCreatedBy = $res->num_rows > 0;
    $res->free();
}

// --- fetch user recipes (own or saved fallback) ---
$recipes = [];
if ($hasCreatedBy) {
    $sql = "SELECT RecipeID, Name, Description, Region, CuisineType
            FROM recipes
            WHERE CreatedBy = $userId
            ORDER BY RecipeID DESC";
} else {
    // fallback: show saved recipes
    $sql = "SELECT r.RecipeID, r.Name, r.Description, r.Region, r.CuisineType
            FROM savedrecipes s
            JOIN recipes r ON r.RecipeID = s.RecipeID
            WHERE s.UserID = $userId
            ORDER BY s.SaveDate DESC";
}
if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) $recipes[] = $row;
    $res->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>User Profile</title>
<link rel="stylesheet" href="style1.css"> <!-- keep your existing theme -->
<style>
  /* Page background: orange gradient like homepage/search */
  body {
    margin: 0;
    padding: 0;
    background: linear-gradient(135deg, #ff9966, #ff5e62);
    font-family: "Poppins", sans-serif;
    min-height: 100vh;
  }

  .container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 16px;
  }

  /* Unified glass card sections */
  .section {
    background: rgba(255,255,255,0.96);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 18px 40px rgba(0,0,0,0.18);
    padding: 24px;
    margin-bottom: 24px;
    animation: rise .45s ease;
  }
  @keyframes rise {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* 1) Big Profile Picture Block */
  .profile-header {
    text-align: center;
  }
  .avatar-wrap {
    display: inline-block;
    position: relative;
    margin-bottom: 16px;
  }
  .avatar {
    width: 200px;          /* bigger avatar */
    height: 200px;
    border-radius: 50%;
    object-fit: cover;
    background: #f7f7f7;
    border: 4px solid #ff9a7a;
    box-shadow: 0 12px 28px rgba(0,0,0,0.18);
  }
  .username {
    font-size: 28px;
    font-weight: 800;
    color: #07001f;
    margin: 10px 0 6px 0;
  }
  .email {
    color: #444;
    margin-bottom: 18px;
  }

  /* Upload form inline under avatar */
  .upload-inline {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    border-radius: 12px;
    padding: 10px 12px;
    border: 1px dashed #ffd3c6;
  }
  .upload-inline input[type=file]{
    max-width: 250px;
  }
  .btn {
    display: inline-block;
    background: linear-gradient(45deg, #ff9966, #ff5e62);
    color: #fff;
    padding: 10px 14px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
  }
  .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.22);
  }
  .status {
    margin-top: 10px;
    color: #2e7d32;
    font-weight: 600;
  }

  /* 2) Bio editor (UI only) */
  .bio h2, .info h2, .saved h2 {
    margin: 0 0 12px 0;
    color: #07001f;
    letter-spacing: .2px;
  }
  .bio textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    background: #fff;
    font-size: 14px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    min-height: 110px;
  }
  .bio textarea:focus {
    border-color: #ff5e62;
    box-shadow: 0 0 6px rgba(255, 94, 98, 0.45);
  }
  .muted {
    font-size: 12px;
    color: #666;
    margin-top: 6px;
  }

  /* 3) Personal Info grid */
  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
  }
  .info-item {
    background: #fff;
    border: 1px solid #f0f0f0;
    border-radius: 14px;
    padding: 14px;
  }
  .info-item b {
    display: block;
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 4px;
  }
  .info-item span {
    font-size: 15px;
    color: #111;
  }

  /* 4) Saved Recipes as cards */
  .cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
    gap: 16px;
  }
  .card {
    background: #fff;
    border: 1px solid #f0f0f0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(0,0,0,0.08);
    transition: transform .15s, box-shadow .15s;
  }
  .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 30px rgba(0,0,0,0.12);
  }
  .thumb {
    width: 100%;
    height: 140px;
    object-fit: cover;
    background: #ffe7df;
  }
  .card-body {
    padding: 12px 14px 16px;
  }
  .card-title {
    margin: 0 0 6px 0;
    font-weight: 800;
    color: #07001f;
  }
  .meta {
    font-size: 12px;
    color: #666;
  }
  .actions {
    margin-top: 10px;
    display: flex;
    gap: 10px;
  }
  .link {
    text-decoration: none;
    color: #ff5e62;
    font-weight: 700;
    font-size: 13px;
  }

  /* Responsive */
  @media (max-width: 680px) {
    .avatar { width: 160px; height: 160px; }
  }
</style>
</head>
<body>

<div class="container">

  <!-- 1) Big Profile Picture Block -->
  <section class="section profile-header">
    <div class="avatar-wrap">
      <img
        class="avatar"
        src="<?php echo $user['ProfileImage'] ? htmlspecialchars($user['ProfileImage']) : 'https://via.placeholder.com/300x300.png?text=Profile'; ?>"
        alt="Profile picture">
    </div>

    <div class="username"><?php echo htmlspecialchars($user['Username']); ?></div>
    <div class="email"><?php echo htmlspecialchars($user['Email']); ?></div>

    <form method="post" enctype="multipart/form-data" class="upload-inline">
      <input type="file" name="avatar" accept="image/*" required>
      <button class="btn" type="submit">Upload New Photo</button>
    </form>
    <?php if ($uploadMsg): ?>
      <div class="status"><?php echo htmlspecialchars($uploadMsg); ?></div>
    <?php endif; ?>
  </section>

  <!-- 2) Bio (UI only) -->
  <section class="section bio">
    <h2>Bio</h2>
    <textarea placeholder="Tell something about yourself"></textarea>
    
    <div style="margin-top:10px;">
      <button class="btn" type="button" onclick="alert('UI only right now. I can wire this to DB on request!')">Save Bio</button>
      <a href="homepage.php" class="link" style="margin-left:10px;">← Back to Home</a>
    </div>
  </section>

  <!-- 3) Personal Information -->
  <section class="section info">
    <h2>Personal Information</h2>
    <div class="info-grid">
      <div class="info-item">
        <b>Username</b>
        <span><?php echo htmlspecialchars($user['Username']); ?></span>
      </div>
      <div class="info-item">
        <b>Email</b>
        <span><?php echo htmlspecialchars($user['Email']); ?></span>
      </div>
      <div class="info-item">
        <b>User ID</b>
        <span>#<?php echo (int)$userId; ?></span>
      </div>
      <div class="info-item">
        <b>Account</b>
        <span>Member</span>
      </div>
    </div>
  </section>

  <!-- 4) Saved Recipes / My Recipes -->
  <section class="section saved">
    <h2><?php echo $hasCreatedBy ? 'Your Recipes' : 'Your Saved Recipes'; ?></h2>

    <?php if (empty($recipes)): ?>
      <div class="info-item" style="margin-top:6px;">
        <span>You don’t have any recipes here yet.</span>
      </div>
    <?php else: ?>
      <div class="cards">
        <?php foreach ($recipes as $r): ?>
          <?php $rid = (int)$r['RecipeID']; ?>
          <div class="card">
            <div class="card-body">
              <h3 class="card-title"><?php echo htmlspecialchars($r['Name']); ?></h3>
              <div class="meta">
                <?php echo htmlspecialchars($r['Region'] ?? ''); ?>
                <?php if (!empty($r['CuisineType'])): ?>
                  · <?php echo htmlspecialchars($r['CuisineType']); ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</div>

</body>
</html>
