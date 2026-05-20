<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $recipeId = int_value($_POST['recipeID'] ?? 0);
        $history = clean_long_text($_POST['history'] ?? '', 5000);
        $festivals = clean_long_text($_POST['festivals'] ?? '', 3000);
        $significance = clean_long_text($_POST['significance'] ?? '', 3000);

        if ($recipeId <= 0 || $history === '') {
            flash('danger', 'Recipe and history are required.');
            redirect('CulturalDetails.php');
        }

        if ($action === 'add') {
            $ok = run_prepared(
                'INSERT INTO culturaldetails (RecipeID, History, Festivals, Significance) VALUES (?, ?, ?, ?)',
                'isss',
                [$recipeId, $history, $festivals, $significance]
            );
            flash($ok ? 'success' : 'danger', $ok ? 'Cultural details added.' : 'Could not add cultural details.');
        } else {
            $detailId = int_value($_POST['detailID'] ?? 0);
            $ok = $detailId > 0 && run_prepared(
                'UPDATE culturaldetails SET RecipeID = ?, History = ?, Festivals = ?, Significance = ? WHERE DetailID = ?',
                'isssi',
                [$recipeId, $history, $festivals, $significance, $detailId]
            );
            flash($ok ? 'success' : 'danger', $ok ? 'Cultural details updated.' : 'Could not update cultural details.');
        }
        redirect('CulturalDetails.php');
    }

    if ($action === 'delete') {
        $detailId = int_value($_POST['detailID'] ?? 0);
        $ok = $detailId > 0 && run_prepared('DELETE FROM culturaldetails WHERE DetailID = ?', 'i', [$detailId]);
        flash($ok ? 'success' : 'danger', $ok ? 'Cultural detail deleted.' : 'Could not delete cultural detail.');
        redirect('CulturalDetails.php');
    }
}

$details = fetch_all_prepared(
    'SELECT c.DetailID, c.RecipeID, c.History, c.Festivals, c.Significance, r.Name AS RecipeName
     FROM culturaldetails c
     LEFT JOIN recipes r ON r.RecipeID = c.RecipeID
     ORDER BY c.DetailID DESC'
);
$recipes = get_recipe_options();

$pageTitle = 'Cultural Details Management | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mb-4">
        <span class="eyebrow"><i class="fa-solid fa-earth-asia"></i> Management</span>
        <h1 class="section-title mt-2">Cultural details</h1>
        <p class="section-copy mb-0">Document the history, festivals, and cultural significance that make a recipe more than a list of ingredients.</p>
    </section>

    <section class="form-card mb-5">
        <h2 class="h4 fw-bold mb-3">Add cultural detail</h2>
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
            <div class="col-12">
                <label class="form-label" for="history">History</label>
                <textarea class="form-control" id="history" name="history" required></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="festivals">Festivals</label>
                <textarea class="form-control" id="festivals" name="festivals"></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="significance">Significance</label>
                <textarea class="form-control" id="significance" name="significance"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-2"></i>Add detail</button>
            </div>
        </form>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-list"></i> Records</span>
                <h2 class="section-title">Culture notes</h2>
            </div>
        </div>

        <?php if (empty($details)): ?>
            <div class="empty-state">No cultural details have been added yet.</div>
        <?php else: ?>
            <div class="table-responsive table-modern">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Recipe</th>
                            <th>History</th>
                            <th>Festivals</th>
                            <th>Significance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $detail): ?>
                            <tr>
                                <td>#<?php echo (int)$detail['DetailID']; ?></td>
                                <td class="fw-bold"><?php echo e($detail['RecipeName'] ?? 'Unknown recipe'); ?></td>
                                <td><?php echo e(truncate_text($detail['History'], 90)); ?></td>
                                <td><?php echo e(truncate_text($detail['Festivals'], 70)); ?></td>
                                <td><?php echo e(truncate_text($detail['Significance'], 70)); ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#editDetail<?php echo (int)$detail['DetailID']; ?>">Edit</button>
                                        <form method="post" class="js-confirm-delete">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="detailID" value="<?php echo (int)$detail['DetailID']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="editDetail<?php echo (int)$detail['DetailID']; ?>">
                                <td colspan="6">
                                    <form method="post" class="row g-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="detailID" value="<?php echo (int)$detail['DetailID']; ?>">
                                        <div class="col-md-4">
                                            <select class="form-select" name="recipeID" required>
                                                <?php foreach ($recipes as $recipe): ?>
                                                    <option value="<?php echo (int)$recipe['RecipeID']; ?>" <?php echo (int)$recipe['RecipeID'] === (int)$detail['RecipeID'] ? 'selected' : ''; ?>><?php echo e($recipe['Name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12"><textarea class="form-control" name="history" required><?php echo e($detail['History']); ?></textarea></div>
                                        <div class="col-md-6"><textarea class="form-control" name="festivals"><?php echo e($detail['Festivals']); ?></textarea></div>
                                        <div class="col-md-6"><textarea class="form-control" name="significance"><?php echo e($detail['Significance']); ?></textarea></div>
                                        <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Save changes</button></div>
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
