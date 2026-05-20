<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $recipeId = int_value($_POST['recipeID'] ?? 0);
        $tagName = clean_text($_POST['tagName'] ?? '', 50);

        if ($recipeId <= 0 || $tagName === '') {
            flash('danger', 'Recipe and tag name are required.');
            redirect('recipetags.php');
        }

        if ($action === 'add') {
            $ok = run_prepared('INSERT INTO recipetags (RecipeID, TagName) VALUES (?, ?)', 'is', [$recipeId, $tagName]);
            flash($ok ? 'success' : 'danger', $ok ? 'Tag added.' : 'Could not add tag.');
        } else {
            $tagId = int_value($_POST['tagID'] ?? 0);
            $ok = $tagId > 0 && run_prepared('UPDATE recipetags SET RecipeID = ?, TagName = ? WHERE TagID = ?', 'isi', [$recipeId, $tagName, $tagId]);
            flash($ok ? 'success' : 'danger', $ok ? 'Tag updated.' : 'Could not update tag.');
        }
        redirect('recipetags.php');
    }

    if ($action === 'delete') {
        $tagId = int_value($_POST['tagID'] ?? 0);
        $ok = $tagId > 0 && run_prepared('DELETE FROM recipetags WHERE TagID = ?', 'i', [$tagId]);
        flash($ok ? 'success' : 'danger', $ok ? 'Tag deleted.' : 'Could not delete tag.');
        redirect('recipetags.php');
    }
}

$recipeTags = fetch_all_prepared(
    'SELECT t.TagID, t.RecipeID, t.TagName, r.Name AS RecipeName
     FROM recipetags t
     LEFT JOIN recipes r ON r.RecipeID = t.RecipeID
     ORDER BY t.TagID DESC'
);
$recipes = get_recipe_options();

$pageTitle = 'Recipe Tags Management | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mb-4">
        <span class="eyebrow"><i class="fa-solid fa-tags"></i> Management</span>
        <h1 class="section-title mt-2">Recipe tags</h1>
        <p class="section-copy mb-0">Tags make search and filtering feel fast, clear, and useful.</p>
    </section>

    <section class="form-card mb-5">
        <h2 class="h4 fw-bold mb-3">Add tag</h2>
        <form method="post" class="row g-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="col-md-6">
                <label class="form-label" for="recipeID">Recipe</label>
                <select class="form-select" id="recipeID" name="recipeID" required>
                    <option value="">Select recipe</option>
                    <?php foreach ($recipes as $recipe): ?>
                        <option value="<?php echo (int)$recipe['RecipeID']; ?>"><?php echo e($recipe['Name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tagName">Tag</label>
                <input class="form-control" id="tagName" name="tagName" maxlength="50" placeholder="festival, spicy, local..." required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Add</button>
            </div>
        </form>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-list"></i> Records</span>
                <h2 class="section-title">Tag list</h2>
            </div>
        </div>

        <?php if (empty($recipeTags)): ?>
            <div class="empty-state">No recipe tags have been added yet.</div>
        <?php else: ?>
            <div class="table-responsive table-modern">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Recipe</th>
                            <th>Tag</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recipeTags as $tag): ?>
                            <tr>
                                <td>#<?php echo (int)$tag['TagID']; ?></td>
                                <td class="fw-bold"><?php echo e($tag['RecipeName'] ?? 'Unknown recipe'); ?></td>
                                <td><span class="tag-pill"><?php echo e($tag['TagName']); ?></span></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#editTag<?php echo (int)$tag['TagID']; ?>">Edit</button>
                                        <form method="post" class="js-confirm-delete">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="tagID" value="<?php echo (int)$tag['TagID']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="editTag<?php echo (int)$tag['TagID']; ?>">
                                <td colspan="4">
                                    <form method="post" class="row g-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="tagID" value="<?php echo (int)$tag['TagID']; ?>">
                                        <div class="col-md-6">
                                            <select class="form-select" name="recipeID" required>
                                                <?php foreach ($recipes as $recipe): ?>
                                                    <option value="<?php echo (int)$recipe['RecipeID']; ?>" <?php echo (int)$recipe['RecipeID'] === (int)$tag['RecipeID'] ? 'selected' : ''; ?>><?php echo e($recipe['Name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4"><input class="form-control" name="tagName" value="<?php echo e($tag['TagName']); ?>" required></div>
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
