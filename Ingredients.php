<?php
session_start();
require_once 'dbconnect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            // Add a new ingredient
            $recipeID = $_POST['recipeID'];
            $ingredientName = $_POST['ingredientName'];
            $measurement = $_POST['measurement'];
            $substitutes = $_POST['substitutes'];

            $stmt = $conn->prepare("INSERT INTO Ingredients (RecipeID, IngredientName, Measurement, Substitutes) 
                                    VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $recipeID, $ingredientName, $measurement, $substitutes);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit') {
            // Edit an existing ingredient
            $ingredientID = $_POST['ingredientID'];
            $recipeID = $_POST['recipeID'];
            $ingredientName = $_POST['ingredientName'];
            $measurement = $_POST['measurement'];
            $substitutes = $_POST['substitutes'];

            $stmt = $conn->prepare("UPDATE Ingredients 
                                    SET RecipeID = ?, IngredientName = ?, Measurement = ?, Substitutes = ? 
                                    WHERE IngredientID = ?");
            $stmt->bind_param("isssi", $recipeID, $ingredientName, $measurement, $substitutes, $ingredientID);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            // Delete an ingredient
            $ingredientID = $_POST['ingredientID'];

            $stmt = $conn->prepare("DELETE FROM Ingredients WHERE IngredientID = ?");
            $stmt->bind_param("i", $ingredientID);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch all ingredients
$result = $conn->query("SELECT * FROM Ingredients");
$ingredients = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all recipes for the dropdown
$recipesResult = $conn->query("SELECT RecipeID, Name FROM Recipes");
$recipes = $recipesResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingredients Management</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .container {
            margin-top: 30px;
        }
        .table th, .table td {
            text-align: center;
        }
        .table {
            background-color: #e9ecef; /* Ash color for the table */
        }
        .table th {
            background-color: #343a40; /* Black color for column headers */
            color: white;
        }
        .table td {
            background-color: #f8f9fa; /* Light background for data cells */
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .btn-sm {
            padding: 5px 10px;
        }
        .card {
            margin-top: 30px;
        }
        h1 {
            color: #007bff; /* Blue color for the main header */
        }
        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .form-label {
            font-weight: bold;
        }
        .form-control {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1 class="text-center my-4">Ingredients Management</h1>

        <!-- Display Ingredients -->
        <div class="card">
            <div class="card-header">
                <h3>Ingredients List</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>IngredientID</th>
                            <th>RecipeID</th>
                            <th>IngredientName</th>
                            <th>Measurement</th>
                            <th>Substitutes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredients as $ingredient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ingredient['IngredientID']); ?></td>
                                <td><?php echo htmlspecialchars($ingredient['RecipeID']); ?></td>
                                <td><?php echo htmlspecialchars($ingredient['IngredientName']); ?></td>
                                <td><?php echo htmlspecialchars($ingredient['Measurement']); ?></td>
                                <td><?php echo htmlspecialchars($ingredient['Substitutes']); ?></td>
                                <td>
                                    <!-- Edit form -->
                                    <form action="" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="ingredientID" value="<?php echo $ingredient['IngredientID']; ?>">
                                        <select name="recipeID" class="form-control" required>
                                            <option value="<?php echo htmlspecialchars($ingredient['RecipeID']); ?>" selected><?php echo htmlspecialchars($ingredient['RecipeID']); ?></option>
                                            <?php
                                            // Fetch RecipeIDs for the dropdown
                                            $recipes = $conn->query("SELECT RecipeID, Name FROM Recipes");
                                            while ($row = $recipes->fetch_assoc()) {
                                                echo '<option value="' . $row['RecipeID'] . '">' . $row['Name'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <input type="text" name="ingredientName" value="<?php echo htmlspecialchars($ingredient['IngredientName']); ?>" class="form-control" required>
                                        <input type="text" name="measurement" value="<?php echo htmlspecialchars($ingredient['Measurement']); ?>" class="form-control" required>
                                        <input type="text" name="substitutes" value="<?php echo htmlspecialchars($ingredient['Substitutes']); ?>" class="form-control" required>
                                        <button type="submit" class="btn btn-warning btn-sm">Update</button>
                                    </form>

                                    <!-- Delete form -->
                                    <form action="" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="ingredientID" value="<?php echo $ingredient['IngredientID']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Ingredient Form -->
        <div class="card">
            <div class="card-header">
                <h3>Add New Ingredient</h3>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <!-- RecipeID Dropdown -->
                    <div class="mb-3">
                        <label for="recipeID" class="form-label">RecipeID</label>
                        <select name="recipeID" class="form-control" required>
                            <option value="">Select Recipe</option>
                            <?php
                            // Fetch RecipeIDs for the dropdown
                            $recipes = $conn->query("SELECT RecipeID, Name FROM Recipes");
                            while ($row = $recipes->fetch_assoc()) {
                                echo '<option value="' . $row['RecipeID'] . '">' . $row['Name'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Ingredient Details -->
                    <div class="mb-3">
                        <label for="ingredientName" class="form-label">Ingredient Name</label>
                        <input type="text" class="form-control" id="ingredientName" name="ingredientName" required>
                    </div>
                    <div class="mb-3">
                        <label for="measurement" class="form-label">Measurement</label>
                        <input type="text" class="form-control" id="measurement" name="measurement" required>
                    </div>
                    <div class="mb-3">
                        <label for="substitutes" class="form-label">Substitutes</label>
                        <input type="text" class="form-control" id="substitutes" name="substitutes">
                    </div>
                    <button type="submit" class="btn btn-custom">Add Ingredient</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>


