<?php
session_start();
require_once 'dbconnect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            // Add a new tag
            $recipeID = $_POST['recipeID'];
            $tagName = $_POST['tagName'];

            $stmt = $conn->prepare("INSERT INTO RecipeTags (RecipeID, TagName) VALUES (?, ?)");
            $stmt->bind_param("is", $recipeID, $tagName);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit') {
            // Edit an existing tag
            $tagID = $_POST['tagID'];
            $recipeID = $_POST['recipeID'];
            $tagName = $_POST['tagName'];

            $stmt = $conn->prepare("UPDATE RecipeTags 
                                    SET RecipeID = ?, TagName = ? 
                                    WHERE TagID = ?");
            $stmt->bind_param("isi", $recipeID, $tagName, $tagID);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            // Delete a tag
            $tagID = $_POST['tagID'];

            $stmt = $conn->prepare("DELETE FROM RecipeTags WHERE TagID = ?");
            $stmt->bind_param("i", $tagID);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch all recipe tags
$result = $conn->query("SELECT * FROM RecipeTags");
$recipeTags = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all recipes for the dropdown
$recipesResult = $conn->query("SELECT RecipeID, Name FROM Recipes");
$recipes = $recipesResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Tags Management</title>
    <!-- Link to Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center text-primary">Recipe Tags</h1>

        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>TagID</th>
                    <th>RecipeID</th>
                    <th>Recipe Name</th>
                    <th>TagName</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipeTags as $tag): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tag['TagID']); ?></td>
                        <td><?php echo htmlspecialchars($tag['RecipeID']); ?></td>
                        <td><?php
                            // Find the recipe name based on RecipeID
                            $recipe = array_filter($recipes, function ($r) use ($tag) {
                                return $r['RecipeID'] == $tag['RecipeID'];
                            });
                            echo htmlspecialchars(array_shift($recipe)['Name']);
                        ?></td>
                        <td><?php echo htmlspecialchars($tag['TagName']); ?></td>
                        <td>
                            <!-- Edit form -->
                            <form action="" method="POST" style="display:inline-block;">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="tagID" value="<?php echo $tag['TagID']; ?>">
                                <select name="recipeID" class="form-select" required>
                                    <?php foreach ($recipes as $recipe): ?>
                                        <option value="<?php echo $recipe['RecipeID']; ?>" 
                                            <?php echo ($recipe['RecipeID'] == $tag['RecipeID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($recipe['Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="tagName" class="form-control" value="<?php echo htmlspecialchars($tag['TagName']); ?>" required>
                                <button type="submit" class="btn btn-success mt-2">Update</button>
                            </form>

                            <!-- Delete form -->
                            <form action="" method="POST" style="display:inline-block;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="tagID" value="<?php echo $tag['TagID']; ?>">
                                <button type="submit" class="btn btn-danger mt-2">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2 class="text-center text-success">Add Recipe Tag</h2>
        <form action="" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label for="recipeID" class="form-label">Recipe:</label>
                <select name="recipeID" class="form-select" required>
                    <?php foreach ($recipes as $recipe): ?>
                        <option value="<?php echo $recipe['RecipeID']; ?>"><?php echo htmlspecialchars($recipe['Name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="tagName" class="form-label">Tag Name:</label>
                <input type="text" name="tagName" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Tag</button>
        </form>
    </div>

    <!-- Bootstrap JS (optional for interactive components like dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
