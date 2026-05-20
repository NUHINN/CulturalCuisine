<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $recipeId = int_value($_POST['recipeID'] ?? 0);
        $ingredientName = clean_text($_POST['ingredientName'] ?? '', 100);
        $measurement = clean_text($_POST['measurement'] ?? '', 50);
        $substitutes = clean_long_text($_POST['substitutes'] ?? '', 1200);

        if ($recipeId <= 0 || $ingredientName === '') {
            flash('danger', 'Recipe and ingredient name are required.');
            redirect('Ingredients.php');
        }

        if ($action === 'add') {
            $ok = run_prepared(
                'INSERT INTO ingredients (RecipeID, IngredientName, Measurement, Substitutes) VALUES (?, ?, ?, ?)',
                'isss',
                [$recipeId, $ingredientName, $measurement, $substitutes]
            );
            flash($ok ? 'success' : 'danger', $ok ? 'Ingredient added.' : 'Could not add ingredient.');
        } else {
            $ingredientId = int_value($_POST['ingredientID'] ?? 0);
            $ok = $ingredientId > 0 && run_prepared(
                'UPDATE ingredients SET RecipeID = ?, IngredientName = ?, Measurement = ?, Substitutes = ? WHERE IngredientID = ?',
                'isssi',
                [$recipeId, $ingredientName, $measurement, $substitutes, $ingredientId]
            );
            flash($ok ? 'success' : 'danger', $ok ? 'Ingredient updated.' : 'Could not update ingredient.');
        }
        redirect('Ingredients.php');
    }

    if ($action === 'delete') {
        $ingredientId = int_value($_POST['ingredientID'] ?? 0);
        $ok = $ingredientId > 0 && run_prepared('DELETE FROM ingredients WHERE IngredientID = ?', 'i', [$ingredientId]);
        flash($ok ? 'success' : 'danger', $ok ? 'Ingredient deleted.' : 'Could not delete ingredient.');
        redirect('Ingredients.php');
    }
}

$ingredients = fetch_all_prepared(
    'SELECT i.IngredientID, i.RecipeID, i.IngredientName, i.Measurement, i.Substitutes, r.Name AS RecipeName
     FROM ingredients i
     LEFT JOIN recipes r ON r.RecipeID = i.RecipeID
     ORDER BY i.IngredientID DESC'
);
$recipes = get_recipe_options();

$pageTitle = 'Ingredients Management | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mb-4">
        <span class="eyebrow"><i class="fa-solid fa-carrot"></i> Management</span>
        <h1 class="section-title mt-2">Ingredients</h1>
        <p class="section-copy mb-0">Keep measurements and substitutes linked to each recipe for richer detail pages.</p>
    </section>

    <section class="form-card mb-5">
        <h2 class="h4 fw-bold mb-3">Add ingredient</h2>
        <form method="post" class="row g-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="col-md-4">
                <label class="form-label" for="recipeID">Recipe</label>
                <select class="form-select" id="recipeID" name="recipeID" required>
                    <option value="">Select recipe</option>
                    <?php foreach ($recipes as $recipe): ?>
                        <option value="<?php echo (int)$recipe['RecipeID']; ?>"><?php echo e($recipe['Name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="ingredientName">Ingredient</label>
                <input class="form-control" id="ingredientName" name="ingredientName" maxlength="100" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="measurement">Measurement</label>
                <input class="form-control" id="measurement" name="measurement" maxlength="50">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="substitutes">Substitutes</label>
                <input class="form-control" id="substitutes" name="substitutes">
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-2"></i>Add ingredient</button>
            </div>
        </form>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-list"></i> Records</span>
                <h2 class="section-title">Ingredient list</h2>
            </div>
        </div>

        <?php if (empty($ingredients)): ?>
            <div class="empty-state">No ingredients have been added yet.</div>
        <?php else: ?>
            <div class="table-responsive table-modern">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Recipe</th>
                            <th>Ingredient</th>
                            <th>Measurement</th>
                            <th>Substitutes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredients as $ingredient): ?>
                            <tr>
                                <td>#<?php echo (int)$ingredient['IngredientID']; ?></td>
                                <td><?php echo e($ingredient['RecipeName'] ?? 'Unknown recipe'); ?></td>
                                <td class="fw-bold"><?php echo e($ingredient['IngredientName']); ?></td>
                                <td><?php echo e($ingredient['Measurement']); ?></td>
                                <td><?php echo e($ingredient['Substitutes']); ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#editIngredient<?php echo (int)$ingredient['IngredientID']; ?>">Edit</button>
                                        <form method="post" class="js-confirm-delete">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="ingredientID" value="<?php echo (int)$ingredient['IngredientID']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="editIngredient<?php echo (int)$ingredient['IngredientID']; ?>">
                                <td colspan="6">
                                    <form method="post" class="row g-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="ingredientID" value="<?php echo (int)$ingredient['IngredientID']; ?>">
                                        <div class="col-md-3">
                                            <select class="form-select" name="recipeID" required>
                                                <?php foreach ($recipes as $recipe): ?>
                                                    <option value="<?php echo (int)$recipe['RecipeID']; ?>" <?php echo (int)$recipe['RecipeID'] === (int)$ingredient['RecipeID'] ? 'selected' : ''; ?>><?php echo e($recipe['Name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3"><input class="form-control" name="ingredientName" value="<?php echo e($ingredient['IngredientName']); ?>" required></div>
                                        <div class="col-md-2"><input class="form-control" name="measurement" value="<?php echo e($ingredient['Measurement']); ?>"></div>
                                        <div class="col-md-3"><input class="form-control" name="substitutes" value="<?php echo e($ingredient['Substitutes']); ?>"></div>
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
