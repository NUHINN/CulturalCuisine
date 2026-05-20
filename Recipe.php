<?php
require_once __DIR__ . '/auth.php';

$supportsImages = db_has_column('recipes', 'ImagePath');
$supportsPrep = db_has_column('recipes', 'PrepTime');
$supportsCook = db_has_column('recipes', 'CookTime');
$supportsServings = db_has_column('recipes', 'Servings');
$supportsDifficulty = db_has_column('recipes', 'Difficulty');
$supportsCreatedBy = db_has_column('recipes', 'CreatedBy');

function recipe_payload_from_post(?string $imagePath = null): array
{
    $fields = [
        'Name' => clean_text($_POST['name'] ?? '', 100),
        'Description' => clean_long_text($_POST['description'] ?? '', 4000),
        'Region' => clean_text($_POST['region'] ?? '', 50),
        'CuisineType' => clean_text($_POST['cuisineType'] ?? '', 50),
    ];

    if (db_has_column('recipes', 'PrepTime')) {
        $fields['PrepTime'] = max(0, int_value($_POST['prepTime'] ?? 0));
    }
    if (db_has_column('recipes', 'CookTime')) {
        $fields['CookTime'] = max(0, int_value($_POST['cookTime'] ?? 0));
    }
    if (db_has_column('recipes', 'Servings')) {
        $fields['Servings'] = max(0, int_value($_POST['servings'] ?? 0));
    }
    if (db_has_column('recipes', 'Difficulty')) {
        $difficulty = clean_text($_POST['difficulty'] ?? 'Easy', 20);
        $fields['Difficulty'] = in_array($difficulty, ['Easy', 'Medium', 'Hard'], true) ? $difficulty : 'Easy';
    }
    if ($imagePath !== null && db_has_column('recipes', 'ImagePath')) {
        $fields['ImagePath'] = $imagePath;
    }

    return $fields;
}

function recipe_insert(array $fields): bool
{
    if (db_has_column('recipes', 'CreatedBy') && current_user_id()) {
        $fields['CreatedBy'] = (int)current_user_id();
    }

    $columns = array_keys($fields);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $types = '';
    $values = [];

    foreach ($fields as $value) {
        $types .= is_int($value) ? 'i' : 's';
        $values[] = $value;
    }

    return run_prepared(
        'INSERT INTO recipes (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
        $types,
        $values
    );
}

function recipe_update(int $recipeId, array $fields): bool
{
    $assignments = [];
    $types = '';
    $values = [];

    foreach ($fields as $column => $value) {
        $assignments[] = $column . ' = ?';
        $types .= is_int($value) ? 'i' : 's';
        $values[] = $value;
    }

    if (empty($assignments)) {
        return true;
    }

    $types .= 'i';
    $values[] = $recipeId;

    return run_prepared('UPDATE recipes SET ' . implode(', ', $assignments) . ' WHERE RecipeID = ?', $types, $values);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        [$imagePath, $uploadError] = $supportsImages ? upload_image('recipeImage', 'recipes') : [null, null];
        if ($uploadError) {
            flash('danger', $uploadError);
            redirect('Recipe.php');
        }

        $fields = recipe_payload_from_post($imagePath);
        if ($fields['Name'] === '' || $fields['Description'] === '') {
            flash('danger', 'Recipe name and description are required.');
            redirect('Recipe.php');
        }

        if ($action === 'add') {
            $ok = recipe_insert($fields);
            flash($ok ? 'success' : 'danger', $ok ? 'Recipe added successfully.' : 'Could not add the recipe.');
        } else {
            $recipeId = int_value($_POST['id'] ?? 0);
            $ok = $recipeId > 0 && recipe_update($recipeId, $fields);
            flash($ok ? 'success' : 'danger', $ok ? 'Recipe updated successfully.' : 'Could not update the recipe.');
        }
        redirect('Recipe.php');
    }

    if ($action === 'delete') {
        $recipeId = int_value($_POST['id'] ?? 0);
        $ok = $recipeId > 0 && delete_recipe_with_dependents($recipeId);
        flash($ok ? 'success' : 'danger', $ok ? 'Recipe and related records deleted.' : 'Could not delete the recipe.');
        redirect('Recipe.php');
    }
}

$createdJoin = $supportsCreatedBy ? 'LEFT JOIN users u ON u.UserID = r.CreatedBy' : '';
$createdSelect = $supportsCreatedBy ? ', u.Username AS CreatedByName' : ', NULL AS CreatedByName';

$recipes = fetch_all_prepared(
    'SELECT ' . recipe_select_columns('r') . $createdSelect . ',
        COALESCE((SELECT AVG(rv.Rating) FROM reviews rv WHERE rv.RecipeID = r.RecipeID), 0) AS AvgRating,
        (SELECT COUNT(*) FROM reviews rv WHERE rv.RecipeID = r.RecipeID) AS ReviewCount,
        (SELECT GROUP_CONCAT(DISTINCT t.TagName ORDER BY t.TagName SEPARATOR \',\') FROM recipetags t WHERE t.RecipeID = r.RecipeID) AS Tags
     FROM recipes r
     ' . $createdJoin . '
     ORDER BY r.RecipeID DESC'
);

$pageTitle = 'Recipes Management | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mb-4">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-utensils"></i> Management</span>
                <h1 class="section-title">Recipes</h1>
                <p class="section-copy mb-0">Add recipe images, preparation metadata, and the core fields used across browsing, profiles, saved recipes, and details.</p>
            </div>
        </div>
        <?php if (!$supportsImages): ?>
            <div class="alert alert-warning mb-0">Run <strong>migration_upgrade.sql</strong> to enable recipe image uploads and extra recipe metadata.</div>
        <?php endif; ?>
    </section>

    <section class="form-card mb-5">
        <h2 class="h4 fw-bold mb-3">Add new recipe</h2>
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="col-md-6">
                <label class="form-label" for="name">Recipe name</label>
                <input class="form-control" id="name" name="name" maxlength="100" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="region">Region</label>
                <input class="form-control" id="region" name="region" maxlength="50">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="cuisineType">Cuisine type</label>
                <input class="form-control" id="cuisineType" name="cuisineType" maxlength="50">
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" required></textarea>
            </div>
            <?php if ($supportsPrep): ?>
                <div class="col-md-3">
                    <label class="form-label" for="prepTime">Prep time</label>
                    <input class="form-control" type="number" min="0" id="prepTime" name="prepTime" placeholder="minutes">
                </div>
            <?php endif; ?>
            <?php if ($supportsCook): ?>
                <div class="col-md-3">
                    <label class="form-label" for="cookTime">Cook time</label>
                    <input class="form-control" type="number" min="0" id="cookTime" name="cookTime" placeholder="minutes">
                </div>
            <?php endif; ?>
            <?php if ($supportsServings): ?>
                <div class="col-md-3">
                    <label class="form-label" for="servings">Servings</label>
                    <input class="form-control" type="number" min="0" id="servings" name="servings">
                </div>
            <?php endif; ?>
            <?php if ($supportsDifficulty): ?>
                <div class="col-md-3">
                    <label class="form-label" for="difficulty">Difficulty</label>
                    <select class="form-select" id="difficulty" name="difficulty">
                        <option>Easy</option>
                        <option>Medium</option>
                        <option>Hard</option>
                    </select>
                </div>
            <?php endif; ?>
            <?php if ($supportsImages): ?>
                <div class="col-md-6">
                    <label class="form-label" for="recipeImage">Recipe image</label>
                    <input class="form-control" type="file" id="recipeImage" name="recipeImage" accept="image/jpeg,image/png,image/webp,image/gif" data-image-preview="#newRecipePreview">
                    <div class="form-text">JPG, PNG, WEBP, or GIF. Max 3 MB.</div>
                </div>
                <div class="col-md-6">
                    <img id="newRecipePreview" class="recipe-thumb rounded-4" src="<?php echo e(DEFAULT_RECIPE_IMAGE); ?>" alt="Recipe preview">
                </div>
            <?php endif; ?>
            <div class="col-12">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-2"></i>Add recipe</button>
            </div>
        </form>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-rectangle-list"></i> Catalog</span>
                <h2 class="section-title">Current recipes</h2>
            </div>
        </div>

        <?php if (empty($recipes)): ?>
            <div class="empty-state">No recipes yet. Add one with the form above.</div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <?php $tagList = array_filter(array_map('trim', explode(',', (string)($recipe['Tags'] ?? '')))); ?>
                    <article class="recipe-card">
                        <img class="recipe-thumb" src="<?php echo e(recipe_image($recipe['ImagePath'] ?? null)); ?>" alt="<?php echo e($recipe['Name']); ?>">
                        <div class="recipe-card-body">
                            <div class="recipe-meta">
                                <span class="meta-pill">#<?php echo (int)$recipe['RecipeID']; ?></span>
                                <?php if (!empty($recipe['Region'])): ?><span class="meta-pill"><?php echo e($recipe['Region']); ?></span><?php endif; ?>
                                <?php if (!empty($recipe['CuisineType'])): ?><span class="meta-pill"><?php echo e($recipe['CuisineType']); ?></span><?php endif; ?>
                            </div>
                            <h3 class="recipe-title"><?php echo e($recipe['Name']); ?></h3>
                            <p class="muted-text"><?php echo e(truncate_text($recipe['Description'] ?? '', 120)); ?></p>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span><?php echo render_stars((float)$recipe['AvgRating']); ?></span>
                                <span class="small muted-text"><?php echo (int)$recipe['ReviewCount']; ?> reviews</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php foreach (array_slice($tagList, 0, 3) as $tagName): ?>
                                    <span class="tag-pill"><?php echo e($tagName); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-auto">
                                <a class="btn btn-sm btn-outline-primary" href="recipe_detail.php?id=<?php echo (int)$recipe['RecipeID']; ?>">Details</a>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#editRecipe<?php echo (int)$recipe['RecipeID']; ?>">Edit</button>
                                <form method="post" class="js-confirm-delete">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$recipe['RecipeID']; ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="collapse border-top" id="editRecipe<?php echo (int)$recipe['RecipeID']; ?>">
                            <form method="post" enctype="multipart/form-data" class="p-3 row g-2">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo (int)$recipe['RecipeID']; ?>">
                                <div class="col-12"><input class="form-control" name="name" value="<?php echo e($recipe['Name']); ?>" required></div>
                                <div class="col-md-6"><input class="form-control" name="region" value="<?php echo e($recipe['Region']); ?>" placeholder="Region"></div>
                                <div class="col-md-6"><input class="form-control" name="cuisineType" value="<?php echo e($recipe['CuisineType']); ?>" placeholder="Cuisine type"></div>
                                <div class="col-12"><textarea class="form-control" name="description" required><?php echo e($recipe['Description']); ?></textarea></div>
                                <?php if ($supportsPrep): ?><div class="col-md-3"><input class="form-control" type="number" min="0" name="prepTime" value="<?php echo e($recipe['PrepTime']); ?>" placeholder="Prep"></div><?php endif; ?>
                                <?php if ($supportsCook): ?><div class="col-md-3"><input class="form-control" type="number" min="0" name="cookTime" value="<?php echo e($recipe['CookTime']); ?>" placeholder="Cook"></div><?php endif; ?>
                                <?php if ($supportsServings): ?><div class="col-md-3"><input class="form-control" type="number" min="0" name="servings" value="<?php echo e($recipe['Servings']); ?>" placeholder="Servings"></div><?php endif; ?>
                                <?php if ($supportsDifficulty): ?>
                                    <div class="col-md-3">
                                        <select class="form-select" name="difficulty">
                                            <?php foreach (['Easy', 'Medium', 'Hard'] as $difficulty): ?>
                                                <option <?php echo ($recipe['Difficulty'] ?? '') === $difficulty ? 'selected' : ''; ?>><?php echo e($difficulty); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <?php if ($supportsImages): ?>
                                    <div class="col-12"><input class="form-control" type="file" name="recipeImage" accept="image/jpeg,image/png,image/webp,image/gif"></div>
                                <?php endif; ?>
                                <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Save changes</button></div>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
