<?php
require_once __DIR__ . '/functions.php';

$recipeId = int_value($_GET['id'] ?? 0);
if ($recipeId <= 0) {
    flash('danger', 'Recipe not found.');
    redirect('search.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = 'recipe_detail.php?id=' . $recipeId;
        flash('warning', 'Please sign in to save or review recipes.');
        redirect('index.php');
    }

    $userId = (int)current_user_id();

    if ($action === 'toggle_save') {
        if (is_recipe_saved($userId, $recipeId)) {
            unsave_recipe_for_user($userId, $recipeId);
            flash('success', 'Recipe removed from your saved list.');
        } else {
            save_recipe_for_user($userId, $recipeId);
            flash('success', 'Recipe saved to your profile.');
        }
        redirect('recipe_detail.php?id=' . $recipeId);
    }

    if ($action === 'add_review') {
        $rating = int_value($_POST['rating'] ?? 0);
        $reviewText = clean_long_text($_POST['reviewText'] ?? '', 1200);

        if ($rating < 1 || $rating > 5) {
            flash('danger', 'Please choose a rating from 1 to 5 stars.');
        } elseif ($reviewText === '') {
            flash('danger', 'Please write a short review.');
        } else {
            $ok = run_prepared(
                'INSERT INTO reviews (UserID, RecipeID, Rating, ReviewText) VALUES (?, ?, ?, ?)',
                'iiis',
                [$userId, $recipeId, $rating, $reviewText]
            );
            flash($ok ? 'success' : 'danger', $ok ? 'Review added.' : 'Could not add your review.');
        }
        redirect('recipe_detail.php?id=' . $recipeId . '#reviews');
    }

    if ($action === 'delete_review') {
        $reviewId = int_value($_POST['reviewID'] ?? 0);
        $ok = run_prepared('DELETE FROM reviews WHERE ReviewID = ? AND UserID = ?', 'ii', [$reviewId, $userId]);
        flash($ok ? 'success' : 'danger', $ok ? 'Review deleted.' : 'Could not delete that review.');
        redirect('recipe_detail.php?id=' . $recipeId . '#reviews');
    }
}

$createdJoin = db_has_column('recipes', 'CreatedBy') ? 'LEFT JOIN users u ON u.UserID = r.CreatedBy' : '';
$createdSelect = db_has_column('recipes', 'CreatedBy') ? ', u.Username AS CreatedByName' : ', NULL AS CreatedByName';

$recipe = fetch_one_prepared(
    'SELECT ' . recipe_select_columns('r') . $createdSelect . ',
        COALESCE((SELECT AVG(rv.Rating) FROM reviews rv WHERE rv.RecipeID = r.RecipeID), 0) AS AvgRating,
        (SELECT COUNT(*) FROM reviews rv WHERE rv.RecipeID = r.RecipeID) AS ReviewCount
     FROM recipes r
     ' . $createdJoin . '
     WHERE r.RecipeID = ?
     LIMIT 1',
    'i',
    [$recipeId]
);

if (!$recipe) {
    flash('danger', 'Recipe not found.');
    redirect('search.php');
}

$ingredients = fetch_all_prepared(
    'SELECT IngredientID, IngredientName, Measurement, Substitutes FROM ingredients WHERE RecipeID = ? ORDER BY IngredientID ASC',
    'i',
    [$recipeId]
);
$culture = fetch_all_prepared(
    'SELECT DetailID, History, Festivals, Significance FROM culturaldetails WHERE RecipeID = ? ORDER BY DetailID ASC',
    'i',
    [$recipeId]
);
$tags = fetch_all_prepared(
    'SELECT TagName FROM recipetags WHERE RecipeID = ? ORDER BY TagName ASC',
    'i',
    [$recipeId]
);
$reviews = fetch_all_prepared(
    'SELECT rv.ReviewID, rv.UserID, rv.Rating, rv.ReviewText, rv.ReviewDate, u.Username
     FROM reviews rv
     LEFT JOIN users u ON u.UserID = rv.UserID
     WHERE rv.RecipeID = ?
     ORDER BY rv.ReviewDate DESC, rv.ReviewID DESC',
    'i',
    [$recipeId]
);

$isSaved = is_logged_in() ? is_recipe_saved((int)current_user_id(), $recipeId) : false;

$pageTitle = ($recipe['Name'] ?? 'Recipe') . ' | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mb-4">
        <div class="detail-hero">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-bowl-rice"></i> Recipe detail</span>
                <h1 class="display-title mt-3"><?php echo e($recipe['Name']); ?></h1>
                <p class="section-copy mt-3"><?php echo nl2br(e($recipe['Description'])); ?></p>
                <div class="d-flex flex-wrap gap-2 my-4">
                    <?php if (!empty($recipe['Region'])): ?><span class="meta-pill"><i class="fa-solid fa-location-dot"></i><?php echo e($recipe['Region']); ?></span><?php endif; ?>
                    <?php if (!empty($recipe['CuisineType'])): ?><span class="meta-pill"><i class="fa-solid fa-bowl-food"></i><?php echo e($recipe['CuisineType']); ?></span><?php endif; ?>
                    <?php if (!empty($recipe['Difficulty'])): ?><span class="meta-pill"><i class="fa-solid fa-gauge-high"></i><?php echo e($recipe['Difficulty']); ?></span><?php endif; ?>
                    <?php if (!empty($recipe['PrepTime'])): ?><span class="meta-pill"><i class="fa-solid fa-clock"></i><?php echo (int)$recipe['PrepTime']; ?> min prep</span><?php endif; ?>
                    <?php if (!empty($recipe['CookTime'])): ?><span class="meta-pill"><i class="fa-solid fa-fire-burner"></i><?php echo (int)$recipe['CookTime']; ?> min cook</span><?php endif; ?>
                    <?php if (!empty($recipe['Servings'])): ?><span class="meta-pill"><i class="fa-solid fa-users"></i><?php echo (int)$recipe['Servings']; ?> servings</span><?php endif; ?>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div><?php echo render_stars((float)$recipe['AvgRating']); ?> <strong><?php echo number_format((float)$recipe['AvgRating'], 1); ?></strong> <span class="muted-text">(<?php echo (int)$recipe['ReviewCount']; ?> reviews)</span></div>
                    <form method="post" action="recipe_detail.php?id=<?php echo $recipeId; ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="toggle_save">
                        <button class="btn <?php echo $isSaved ? 'btn-success' : 'btn-outline-primary'; ?>" type="submit">
                            <i class="fa-<?php echo $isSaved ? 'solid' : 'regular'; ?> fa-bookmark me-2"></i><?php echo $isSaved ? 'Saved' : 'Save recipe'; ?>
                        </button>
                    </form>
                </div>
            </div>
            <img class="detail-image" src="<?php echo e(recipe_image($recipe['ImagePath'] ?? null)); ?>" alt="<?php echo e($recipe['Name']); ?>">
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-7">
            <section class="content-card mb-4">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow"><i class="fa-solid fa-carrot"></i> Ingredients</span>
                        <h2 class="section-title">What goes in</h2>
                    </div>
                </div>
                <?php if (empty($ingredients)): ?>
                    <div class="empty-state">No ingredients have been added for this recipe yet.</div>
                <?php else: ?>
                    <div class="info-list">
                        <?php foreach ($ingredients as $ingredient): ?>
                            <div class="info-item">
                                <strong><?php echo e($ingredient['Measurement']); ?></strong>
                                <?php echo e($ingredient['IngredientName']); ?>
                                <?php if (!empty($ingredient['Substitutes'])): ?>
                                    <div class="small muted-text mt-1">Substitute: <?php echo e($ingredient['Substitutes']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="content-card" id="reviews">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow"><i class="fa-solid fa-star"></i> Reviews</span>
                        <h2 class="section-title">Community notes</h2>
                    </div>
                </div>

                <?php if (is_logged_in()): ?>
                    <form method="post" action="recipe_detail.php?id=<?php echo $recipeId; ?>#reviews" class="mb-4">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="add_review">
                        <div class="mb-3">
                            <label class="form-label d-block">Your rating</label>
                            <div class="rating-input">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="rating<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                                    <label for="rating<?php echo $i; ?>" aria-label="<?php echo $i; ?> stars"><i class="fa-solid fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reviewText">Review</label>
                            <textarea class="form-control" id="reviewText" name="reviewText" required></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Post review</button>
                    </form>
                <?php else: ?>
                    <div class="empty-state mb-4">Sign in to save this recipe or leave a review.</div>
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                    <div class="empty-state">No reviews yet. Be the first to rate this recipe.</div>
                <?php else: ?>
                    <div class="info-list">
                        <?php foreach ($reviews as $review): ?>
                            <article class="info-item">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <strong><?php echo e($review['Username'] ?? 'Guest'); ?></strong>
                                        <?php echo render_stars((float)$review['Rating']); ?>
                                    </div>
                                    <span class="small muted-text"><?php echo e($review['ReviewDate']); ?></span>
                                </div>
                                <p class="mb-2 mt-2"><?php echo nl2br(e($review['ReviewText'])); ?></p>
                                <?php if (is_logged_in() && (int)$review['UserID'] === (int)current_user_id()): ?>
                                    <form method="post" action="recipe_detail.php?id=<?php echo $recipeId; ?>#reviews" class="js-confirm-delete d-inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_review">
                                        <input type="hidden" name="reviewID" value="<?php echo (int)$review['ReviewID']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete review</button>
                                    </form>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="content-card mb-4">
                <span class="eyebrow"><i class="fa-solid fa-earth-asia"></i> Cultural details</span>
                <h2 class="section-title mt-2">Story behind the dish</h2>
                <?php if (empty($culture)): ?>
                    <div class="empty-state mt-3">No cultural details have been added yet.</div>
                <?php else: ?>
                    <div class="info-list mt-3">
                        <?php foreach ($culture as $detail): ?>
                            <div class="info-item"><strong>History</strong><?php echo nl2br(e($detail['History'])); ?></div>
                            <div class="info-item"><strong>Festivals</strong><?php echo nl2br(e($detail['Festivals'])); ?></div>
                            <div class="info-item"><strong>Significance</strong><?php echo nl2br(e($detail['Significance'])); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="content-card">
                <span class="eyebrow"><i class="fa-solid fa-tags"></i> Tags</span>
                <h2 class="section-title mt-2">Recipe labels</h2>
                <?php if (empty($tags)): ?>
                    <div class="empty-state mt-3">No tags yet.</div>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php foreach ($tags as $tag): ?>
                            <a class="tag-pill" href="search.php?tag=<?php echo urlencode((string)$tag['TagName']); ?>"><?php echo e($tag['TagName']); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
