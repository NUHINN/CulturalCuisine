<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $userId = int_value($_POST['userID'] ?? 0);
        $recipeId = int_value($_POST['recipeID'] ?? 0);

        if ($userId <= 0 || $recipeId <= 0) {
            flash('danger', 'User and recipe are required.');
            redirect('savedrecipe.php');
        }

        if ($action === 'add') {
            $ok = save_recipe_for_user($userId, $recipeId);
            flash($ok ? 'success' : 'danger', $ok ? 'Saved recipe added.' : 'Could not save recipe.');
        } else {
            $savedId = int_value($_POST['savedID'] ?? 0);
            $ok = $savedId > 0 && run_prepared(
                'UPDATE savedrecipes SET UserID = ?, RecipeID = ? WHERE SavedID = ?',
                'iii',
                [$userId, $recipeId, $savedId]
            );
            flash($ok ? 'success' : 'danger', $ok ? 'Saved recipe updated.' : 'Could not update saved recipe.');
        }
        redirect('savedrecipe.php');
    }

    if ($action === 'delete') {
        $savedId = int_value($_POST['savedID'] ?? 0);
        $ok = $savedId > 0 && run_prepared('DELETE FROM savedrecipes WHERE SavedID = ?', 'i', [$savedId]);
        flash($ok ? 'success' : 'danger', $ok ? 'Saved recipe removed.' : 'Could not remove saved recipe.');
        redirect('savedrecipe.php');
    }
}

$currentUserId = (int)current_user_id();
$mySavedRecipes = fetch_all_prepared(
    'SELECT s.SavedID, s.SaveDate, ' . recipe_select_columns('r') . ',
        COALESCE((SELECT AVG(rv.Rating) FROM reviews rv WHERE rv.RecipeID = r.RecipeID), 0) AS AvgRating
     FROM savedrecipes s
     JOIN recipes r ON r.RecipeID = s.RecipeID
     WHERE s.UserID = ?
     ORDER BY s.SaveDate DESC',
    'i',
    [$currentUserId]
);

$savedRecords = fetch_all_prepared(
    'SELECT s.SavedID, s.UserID, s.RecipeID, s.SaveDate, u.Username, r.Name AS RecipeName
     FROM savedrecipes s
     LEFT JOIN users u ON u.UserID = s.UserID
     LEFT JOIN recipes r ON r.RecipeID = s.RecipeID
     ORDER BY s.SaveDate DESC, s.SavedID DESC'
);
$users = get_user_options();
$recipes = get_recipe_options();

$pageTitle = 'Saved Recipes | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mb-4">
        <span class="eyebrow"><i class="fa-solid fa-bookmark"></i> Collection</span>
        <h1 class="section-title mt-2">Saved recipes</h1>
        <p class="section-copy mb-0">A personal saved list for presentation browsing, plus an admin-style table for maintaining saved recipe records.</p>
    </section>

    <section class="mb-5">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-heart"></i> Mine</span>
                <h2 class="section-title">Your saved recipes</h2>
            </div>
        </div>
        <?php if (empty($mySavedRecipes)): ?>
            <div class="empty-state">You have not saved any recipes yet. Browse recipes and tap save on the detail page.</div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($mySavedRecipes as $recipe): ?>
                    <article class="recipe-card">
                        <img class="recipe-thumb" src="<?php echo e(recipe_image($recipe['ImagePath'] ?? null)); ?>" alt="<?php echo e($recipe['Name']); ?>">
                        <div class="recipe-card-body">
                            <div class="recipe-meta">
                                <?php if (!empty($recipe['Region'])): ?><span class="meta-pill"><?php echo e($recipe['Region']); ?></span><?php endif; ?>
                                <?php if (!empty($recipe['CuisineType'])): ?><span class="meta-pill"><?php echo e($recipe['CuisineType']); ?></span><?php endif; ?>
                            </div>
                            <h3 class="recipe-title"><?php echo e($recipe['Name']); ?></h3>
                            <p class="muted-text flex-grow-1"><?php echo e(truncate_text($recipe['Description'] ?? '', 120)); ?></p>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span><?php echo render_stars((float)$recipe['AvgRating']); ?></span>
                                <span class="small muted-text">Saved <?php echo e($recipe['SaveDate']); ?></span>
                            </div>
                            <div class="d-flex gap-2 mt-auto">
                                <a class="btn btn-primary" href="recipe_detail.php?id=<?php echo (int)$recipe['RecipeID']; ?>">View</a>
                                <form method="post" class="js-confirm-delete">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="savedID" value="<?php echo (int)$recipe['SavedID']; ?>">
                                    <button class="btn btn-outline-danger" type="submit">Unsave</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="form-card mb-5">
        <h2 class="h4 fw-bold mb-3">Add saved recipe record</h2>
        <form method="post" class="row g-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="col-md-5">
                <label class="form-label" for="userID">User</label>
                <select class="form-select" id="userID" name="userID" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo (int)$user['UserID']; ?>" <?php echo (int)$user['UserID'] === $currentUserId ? 'selected' : ''; ?>><?php echo e($user['Username']); ?> (<?php echo e($user['Email']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="recipeID">Recipe</label>
                <select class="form-select" id="recipeID" name="recipeID" required>
                    <?php foreach ($recipes as $recipe): ?>
                        <option value="<?php echo (int)$recipe['RecipeID']; ?>"><?php echo e($recipe['Name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Save</button>
            </div>
        </form>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-list"></i> Records</span>
                <h2 class="section-title">All saved recipe records</h2>
            </div>
        </div>

        <?php if (empty($savedRecords)): ?>
            <div class="empty-state">No saved recipe records exist yet.</div>
        <?php else: ?>
            <div class="table-responsive table-modern">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Recipe</th>
                            <th>Saved date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($savedRecords as $record): ?>
                            <tr>
                                <td>#<?php echo (int)$record['SavedID']; ?></td>
                                <td><?php echo e($record['Username'] ?? 'Unknown user'); ?></td>
                                <td class="fw-bold"><?php echo e($record['RecipeName'] ?? 'Unknown recipe'); ?></td>
                                <td><?php echo e($record['SaveDate']); ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#editSaved<?php echo (int)$record['SavedID']; ?>">Edit</button>
                                        <form method="post" class="js-confirm-delete">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="savedID" value="<?php echo (int)$record['SavedID']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="editSaved<?php echo (int)$record['SavedID']; ?>">
                                <td colspan="5">
                                    <form method="post" class="row g-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="savedID" value="<?php echo (int)$record['SavedID']; ?>">
                                        <div class="col-md-5">
                                            <select class="form-select" name="userID" required>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo (int)$user['UserID']; ?>" <?php echo (int)$user['UserID'] === (int)$record['UserID'] ? 'selected' : ''; ?>><?php echo e($user['Username']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-select" name="recipeID" required>
                                                <?php foreach ($recipes as $recipe): ?>
                                                    <option value="<?php echo (int)$recipe['RecipeID']; ?>" <?php echo (int)$recipe['RecipeID'] === (int)$record['RecipeID'] ? 'selected' : ''; ?>><?php echo e($recipe['Name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2"><button class="btn btn-primary btn-sm w-100" type="submit">Save</button></div>
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
