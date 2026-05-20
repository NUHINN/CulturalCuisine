<?php
require_once __DIR__ . '/auth.php';

$supportsProfileImage = db_has_column('users', 'ProfileImage');
$supportsBio = db_has_column('users', 'Bio');
$userId = (int)current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = clean_text($_POST['username'] ?? '', 50);
        $bio = clean_long_text($_POST['bio'] ?? '', 1200);

        if (text_length($username) < 2) {
            flash('danger', 'Username must be at least 2 characters.');
            redirect('profile.php');
        }

        if ($supportsBio) {
            $ok = run_prepared('UPDATE users SET Username = ?, Bio = ? WHERE UserID = ?', 'ssi', [$username, $bio, $userId]);
        } else {
            $ok = run_prepared('UPDATE users SET Username = ? WHERE UserID = ?', 'si', [$username, $userId]);
        }

        flash($ok ? 'success' : 'danger', $ok ? 'Profile updated.' : 'Could not update profile.');
        redirect('profile.php');
    }

    if ($action === 'upload_avatar') {
        if (!$supportsProfileImage) {
            flash('warning', 'Run migration_upgrade.sql to enable profile image uploads.');
            redirect('profile.php');
        }

        [$imagePath, $uploadError] = upload_image('avatar', 'profiles');
        if ($uploadError) {
            flash('danger', $uploadError);
            redirect('profile.php');
        }

        if ($imagePath === null) {
            flash('warning', 'Choose an image before uploading.');
            redirect('profile.php');
        }

        $ok = run_prepared('UPDATE users SET ProfileImage = ? WHERE UserID = ?', 'si', [$imagePath, $userId]);
        flash($ok ? 'success' : 'danger', $ok ? 'Profile image updated.' : 'Could not update profile image.');
        redirect('profile.php');
    }
}

$user = current_user();

$savedRecipes = fetch_all_prepared(
    'SELECT s.SavedID, s.SaveDate, ' . recipe_select_columns('r') . ',
        COALESCE((SELECT AVG(rv.Rating) FROM reviews rv WHERE rv.RecipeID = r.RecipeID), 0) AS AvgRating
     FROM savedrecipes s
     JOIN recipes r ON r.RecipeID = s.RecipeID
     WHERE s.UserID = ?
     ORDER BY s.SaveDate DESC',
    'i',
    [$userId]
);

$myReviews = fetch_all_prepared(
    'SELECT rv.ReviewID, rv.RecipeID, rv.Rating, rv.ReviewText, rv.ReviewDate, r.Name AS RecipeName
     FROM reviews rv
     LEFT JOIN recipes r ON r.RecipeID = rv.RecipeID
     WHERE rv.UserID = ?
     ORDER BY rv.ReviewDate DESC, rv.ReviewID DESC',
    'i',
    [$userId]
);

$createdRecipes = [];
$createdRecipeCount = 0;
if (db_has_column('recipes', 'CreatedBy')) {
    $createdRecipeCount = (int)(fetch_one_prepared('SELECT COUNT(*) AS total FROM recipes WHERE CreatedBy = ?', 'i', [$userId])['total'] ?? 0);
    $createdRecipes = fetch_all_prepared(
        'SELECT ' . recipe_select_columns('r') . ' FROM recipes r WHERE r.CreatedBy = ? ORDER BY r.RecipeID DESC LIMIT 6',
        'i',
        [$userId]
    );
}

$pageTitle = 'Profile | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mb-4">
        <?php if (!$supportsProfileImage || !$supportsBio): ?>
            <div class="alert alert-warning">Run <strong>migration_upgrade.sql</strong> to enable profile images and saved bio text.</div>
        <?php endif; ?>
        <div class="profile-hero">
            <img class="profile-avatar" src="<?php echo e(profile_image($user['ProfileImage'] ?? null)); ?>" alt="<?php echo e($user['Username'] ?? 'Profile'); ?>">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-id-card"></i> Profile</span>
                <h1 class="section-title mt-2"><?php echo e($user['Username'] ?? 'User'); ?></h1>
                <p class="muted-text mb-3"><?php echo e($user['Email'] ?? ''); ?></p>
                <?php if (!empty($user['Bio'])): ?>
                    <p class="section-copy mb-0"><?php echo nl2br(e($user['Bio'])); ?></p>
                <?php else: ?>
                    <p class="section-copy mb-0">Add a short bio to personalize your cultural food profile.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <section class="form-card h-100">
                <h2 class="h4 fw-bold mb-3">Edit profile</h2>
                <form method="post" class="row g-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" id="username" name="username" value="<?php echo e($user['Username'] ?? ''); ?>" maxlength="50" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input class="form-control" value="<?php echo e($user['Email'] ?? ''); ?>" disabled>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="bio">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" <?php echo $supportsBio ? '' : 'disabled'; ?>><?php echo e($user['Bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Save profile</button>
                    </div>
                </form>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="form-card h-100">
                <h2 class="h4 fw-bold mb-3">Profile image</h2>
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="upload_avatar">
                    <div class="col-12 text-center">
                        <img id="profilePreview" class="profile-avatar" src="<?php echo e(profile_image($user['ProfileImage'] ?? null)); ?>" alt="Profile preview">
                    </div>
                    <div class="col-12">
                        <input class="form-control" type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" data-image-preview="#profilePreview" <?php echo $supportsProfileImage ? '' : 'disabled'; ?>>
                        <div class="form-text">JPG, PNG, WEBP, or GIF. Max 3 MB.</div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100" type="submit" <?php echo $supportsProfileImage ? '' : 'disabled'; ?>>Upload image</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <section class="mb-5">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($savedRecipes); ?></div>
                <div class="stat-label">Saved recipes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($myReviews); ?></div>
                <div class="stat-label">Reviews written</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo (int)$createdRecipeCount; ?></div>
                <div class="stat-label">Recipes created</div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-bookmark"></i> Saved</span>
                <h2 class="section-title">Saved recipes</h2>
            </div>
            <a class="btn btn-outline-primary" href="savedrecipe.php">Manage saved</a>
        </div>
        <?php if (empty($savedRecipes)): ?>
            <div class="empty-state">No saved recipes yet. Browse and save a recipe to see it here.</div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($savedRecipes as $recipe): ?>
                    <article class="recipe-card">
                        <img class="recipe-thumb" src="<?php echo e(recipe_image($recipe['ImagePath'] ?? null)); ?>" alt="<?php echo e($recipe['Name']); ?>">
                        <div class="recipe-card-body">
                            <h3 class="recipe-title"><?php echo e($recipe['Name']); ?></h3>
                            <p class="muted-text flex-grow-1"><?php echo e(truncate_text($recipe['Description'] ?? '', 120)); ?></p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span><?php echo render_stars((float)$recipe['AvgRating']); ?></span>
                                <span class="small muted-text"><?php echo e($recipe['SaveDate']); ?></span>
                            </div>
                            <a class="btn btn-primary mt-auto" href="recipe_detail.php?id=<?php echo (int)$recipe['RecipeID']; ?>">Open</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="content-card">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-star"></i> Reviews</span>
                <h2 class="section-title">Your reviews</h2>
            </div>
        </div>
        <?php if (empty($myReviews)): ?>
            <div class="empty-state">You have not reviewed any recipes yet.</div>
        <?php else: ?>
            <div class="info-list">
                <?php foreach ($myReviews as $review): ?>
                    <article class="info-item">
                        <div class="d-flex justify-content-between gap-3">
                            <strong><?php echo e($review['RecipeName'] ?? 'Unknown recipe'); ?></strong>
                            <span><?php echo render_stars((float)$review['Rating']); ?></span>
                        </div>
                        <p class="mb-1 mt-2"><?php echo nl2br(e($review['ReviewText'])); ?></p>
                        <a class="small fw-bold" href="recipe_detail.php?id=<?php echo (int)$review['RecipeID']; ?>">View recipe</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
