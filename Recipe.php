<?php
session_start();

require_once 'dbconnect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            // Ensure RecipeID is properly passed
            $name = $_POST['name'];
            $description = $_POST['description'];
            $region = $_POST['region'];
            $cuisineType = $_POST['cuisineType'];

            // Insert Recipe only if RecipeID exists in Recipes
            $stmt = $conn->prepare("INSERT INTO Recipes (Name, Description, Region, CuisineType) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $description, $region, $cuisineType);
            if($stmt->execute()) {
                echo "Recipe added successfully!";
            } else {
                echo "Error: Could not add the recipe.";
            }
        } elseif ($action === 'edit') {
            // Ensure RecipeID is properly passed
            $id = $_POST['id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            $region = $_POST['region'];
            $cuisineType = $_POST['cuisineType'];

            $stmt = $conn->prepare("UPDATE Recipes SET Name = ?, Description = ?, Region = ?, CuisineType = ? WHERE RecipeID = ?");
            $stmt->bind_param("ssssi", $name, $description, $region, $cuisineType, $id);
            if($stmt->execute()) {
                echo "Recipe updated successfully!";
            } else {
                echo "Error: Could not update the recipe.";
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'];

            // Ensure RecipeID exists before deleting
            $stmt = $conn->prepare("DELETE FROM Recipes WHERE RecipeID = ?");
            $stmt->bind_param("i", $id);
            if($stmt->execute()) {
                echo "Recipe deleted successfully!";
            } else {
                echo "Error: Could not delete the recipe.";
            }
        }
    }
}

// Fetch all recipes
$stmt = $conn->query("SELECT * FROM Recipes");
$recipes = $stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipes Management</title>

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
        <h1 class="text-center my-4">Recipes Management</h1>

        <!-- Display Recipes -->
        <div class="card">
            <div class="card-header">
                <h3>Recipes List</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>RecipeID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Region</th>
                            <th>CuisineType</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recipes as $recipe): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($recipe['RecipeID']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['Name']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['Description']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['Region']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['CuisineType']); ?></td>
                                <td>
                                    <!-- Edit form -->
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?php echo $recipe['RecipeID']; ?>">
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($recipe['Name']); ?>" class="form-control" required>
                                        <input type="text" name="description" value="<?php echo htmlspecialchars($recipe['Description']); ?>" class="form-control" required>
                                        <input type="text" name="region" value="<?php echo htmlspecialchars($recipe['Region']); ?>" class="form-control" required>
                                        <input type="text" name="cuisineType" value="<?php echo htmlspecialchars($recipe['CuisineType']); ?>" class="form-control" required>
                                        <button type="submit" class="btn btn-warning btn-sm">Update</button>
                                    </form>

                                    <!-- Delete form -->
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $recipe['RecipeID']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Recipe Form -->
        <div class="card">
            <div class="card-header">
                <h3>Add New Recipe</h3>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="description" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label for="region" class="form-label">Region</label>
                        <input type="text" class="form-control" id="region" name="region" required>
                    </div>
                    <div class="mb-3">
                        <label for="cuisineType" class="form-label">Cuisine Type</label>
                        <input type="text" class="form-control" id="cuisineType" name="cuisineType" required>
                    </div>
                    <button type="submit" class="btn btn-custom">Add Recipe</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>

