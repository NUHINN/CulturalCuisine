<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $userId = int_value($_POST['userID'] ?? 0);
        $recipeId = int_value($_POST['recipeID'] ?? 0);
        $rating = int_value($_POST['rating'] ?? 0);
        $reviewText = clean_long_text($_POST['reviewText'] ?? '', 1200);

        if ($userId <= 0 || $recipeId <= 0 || $rating < 1 || $rating > 5 || $reviewText === '') {
            flash('danger', 'User, recipe, rating, and review text are required.');
            redirect('reviews.php');
        }

        if ($action === 'add') {
            $ok = run_prepared(
                'INSERT INTO reviews (UserID, RecipeID, Rating, ReviewText) VALUES (?, ?, ?, ?)',
                'iiis',
                [$userId, $recipeId, $rating, $reviewText]
            );
            flash($ok ? 'success' : 'danger', $ok ? 'Review added.' : 'Could not add review.');
        } else {
            $reviewId = int_value($_POST['reviewID'] ?? 0);
            $ok = $reviewId > 0 && run_prepared(
                'UPDATE reviews SET UserID = ?, RecipeID = ?, Rating = ?, ReviewText = ? WHERE ReviewID = ?',
                'iiisi',
                [$userId, $recipeId, $rating, $reviewText, $reviewId]
            );
            flash($ok ? 'success' : 'danger', $ok ? 'Review updated.' : 'Could not update review.');
        }
        redirect('reviews.php');
    }

    if ($action === 'delete') {
        $reviewId = int_value($_POST['reviewID'] ?? 0);
        $ok = $reviewId > 0 && run_prepared('DELETE FROM reviews WHERE ReviewID = ?', 'i', [$reviewId]);
        flash($ok ? 'success' : 'danger', $ok ? 'Review deleted.' : 'Could not delete review.');
        redirect('reviews.php');
    }
}

$reviews = fetch_all_prepared(
    'SELECT rv.ReviewID, rv.UserID, rv.RecipeID, rv.Rating, rv.ReviewText, rv.ReviewDate,
            u.Username, r.Name AS RecipeName
     FROM reviews rv
     LEFT JOIN users u ON u.UserID = rv.UserID
     LEFT JOIN recipes r ON r.RecipeID = rv.RecipeID
     ORDER BY rv.ReviewDate DESC, rv.ReviewID DESC'
);
$users = get_user_options();
$recipes = get_recipe_options();

$pageTitle = 'Reviews Management | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mb-4">
        <span class="eyebrow"><i class="fa-solid fa-star"></i> Management</span>
        <h1 class="section-title mt-2">Reviews</h1>
        <p class="section-copy mb-0">Moderate and add recipe feedback with a clean star-rating workflow.</p>
    </section>

    <section class="form-card mb-5">
        <h2 class="h4 fw-bold mb-3">Add review</h2>
        <form method="post" class="row g-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="col-md-4">
                <label class="form-label" for="userID">User</label>
                <select class="form-select" id="userID" name="userID" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int)$user['UserID']; ?>" <?php echo (int)$user['UserID'] === (int)current_user_id() ? 'selected' : ''; ?>><?php echo e($user['Username']); ?> (<?php echo e($user['Email']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="recipeID">Recipe</label>
                <select class="form-select" id="recipeID" name="recipeID" required>
                    <?php foreach ($recipes as $recipe): ?>
                        <option value="<?php echo (int)$recipe['RecipeID']; ?>"><?php echo e($recipe['Name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label d-block">Rating</label>
                <div class="rating-input">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="adminRating<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $i === 5 ? 'checked' : ''; ?>>
                        <label for="adminRating<?php echo $i; ?>"><i class="fa-solid fa-star"></i></label>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="reviewText">Review text</label>
                <textarea class="form-control" id="reviewText" name="reviewText" required></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-2"></i>Add review</button>
            </div>
        </form>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-comments"></i> Records</span>
                <h2 class="section-title">Review list</h2>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">No reviews have been added yet.</div>
        <?php else: ?>
            <div class="table-responsive table-modern">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Recipe</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td>#<?php echo (int)$review['ReviewID']; ?></td>
                                <td><?php echo e($review['Username'] ?? 'Unknown user'); ?></td>
                                <td class="fw-bold"><?php echo e($review['RecipeName'] ?? 'Unknown recipe'); ?></td>
                                <td><?php echo render_stars((float)$review['Rating']); ?></td>
                                <td><?php echo e(truncate_text($review['ReviewText'], 90)); ?></td>
                                <td><?php echo e($review['ReviewDate']); ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#editReview<?php echo (int)$review['ReviewID']; ?>">Edit</button>
                                        <form method="post" class="js-confirm-delete">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="reviewID" value="<?php echo (int)$review['ReviewID']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="editReview<?php echo (int)$review['ReviewID']; ?>">
                                <td colspan="7">
                                    <form method="post" class="row g-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="reviewID" value="<?php echo (int)$review['ReviewID']; ?>">
                                        <div class="col-md-3">
                                            <select class="form-select" name="userID" required>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo (int)$user['UserID']; ?>" <?php echo (int)$user['UserID'] === (int)$review['UserID'] ? 'selected' : ''; ?>><?php echo e($user['Username']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" name="recipeID" required>
                                                <?php foreach ($recipes as $recipe): ?>
                                                    <option value="<?php echo (int)$recipe['RecipeID']; ?>" <?php echo (int)$recipe['RecipeID'] === (int)$review['RecipeID'] ? 'selected' : ''; ?>><?php echo e($recipe['Name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2"><input class="form-control" type="number" min="1" max="5" name="rating" value="<?php echo (int)$review['Rating']; ?>" required></div>
                                        <div class="col-md-3"><textarea class="form-control" name="reviewText" required><?php echo e($review['ReviewText']); ?></textarea></div>
                                        <div class="col-md-1"><button class="btn btn-primary btn-sm w-100" type="submit">Save</button></div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
