<?php
session_start();
require_once 'dbconnect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            // Add a new saved recipe
            $userID = $_POST['userID'];
            $recipeID = $_POST['recipeID'];
            $saveDate = date('Y-m-d H:i:s');  // Current date and time

            $stmt = $conn->prepare("INSERT INTO SavedRecipes (UserID, RecipeID, SaveDate) 
                                    VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $userID, $recipeID, $saveDate);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit') {
            // Edit an existing saved recipe
            $savedID = $_POST['savedID'];
            $userID = $_POST['userID'];
            $recipeID = $_POST['recipeID'];

            $stmt = $conn->prepare("UPDATE SavedRecipes 
                                    SET UserID = ?, RecipeID = ? 
                                    WHERE SavedID = ?");
            $stmt->bind_param("iii", $userID, $recipeID, $savedID);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            // Delete a saved recipe
            $savedID = $_POST['savedID'];

            $stmt = $conn->prepare("DELETE FROM SavedRecipes WHERE SavedID = ?");
            $stmt->bind_param("i", $savedID);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch all saved recipes
$result = $conn->query("SELECT * FROM SavedRecipes");
$savedRecipes = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all users for the dropdown
$usersResult = $conn->query("SELECT UserID, Username FROM users");
$users = $usersResult->fetch_all(MYSQLI_ASSOC);

// Fetch all recipes for the dropdown
$recipesResult = $conn->query("SELECT RecipeID, Name FROM Recipes");
$recipes = $recipesResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Recipes Management</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 1200px; /* Limit the width of the content */
            margin: 0 auto; /* Center the content */
            padding: 20px;
        }
        h1 {
            color: #007bff; /* Blue color for the main header */
            text-align: center;
            margin-top: 30px;
        }
        h2 {
            margin-top: 20px;
        }
        .table {
            background-color: #e9ecef; /* Ash color for the table */
            width: 100%;
            margin-top: 20px;
        }
        .table th {
            background-color: #343a40; /* Black color for column headers */
            color: white;
        }
        .table td {
            background-color: #f8f9fa; /* Light background for data cells */
        }
        .form-control {
            margin-bottom: 10px;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
            border: none;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .btn-sm {
            padding: 5px 10px;
        }
        .actions form {
            display: inline-block;
            margin-right: 10px;
        }
        .add-form {
            max-width: 500px; /* Limit the width of the insert form */
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Saved Recipes</h1>

        <!-- Table of saved recipes -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>SavedID</th>
                    <th>UserID</th>
                    <th>Username</th>
                    <th>RecipeID</th>
                    <th>Recipe Name</th>
                    <th>SaveDate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($savedRecipes as $saved): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($saved['SavedID']); ?></td>
                        <td><?php echo htmlspecialchars($saved['UserID']); ?></td>
                        <td><?php
                            // Find the username based on UserID
                            $user = array_filter($users, function ($u) use ($saved) {
                                return $u['UserID'] == $saved['UserID'];
                            });
                            echo htmlspecialchars(array_shift($user)['Username']);
                        ?></td>
                        <td><?php echo htmlspecialchars($saved['RecipeID']); ?></td>
                        <td><?php
                            // Find the recipe name based on RecipeID
                            $recipe = array_filter($recipes, function ($r) use ($saved) {
                                return $r['RecipeID'] == $saved['RecipeID'];
                            });
                            echo htmlspecialchars(array_shift($recipe)['Name']);
                        ?></td>
                        <td><?php echo htmlspecialchars($saved['SaveDate']); ?></td>
                        <td class="actions">
                            <!-- Edit form -->
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="savedID" value="<?php echo $saved['SavedID']; ?>">
                                <select name="userID" required class="form-control">
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['UserID']; ?>" 
                                            <?php echo ($user['UserID'] == $saved['UserID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['Username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="recipeID" required class="form-control">
                                    <?php foreach ($recipes as $recipe): ?>
                                        <option value="<?php echo $recipe['RecipeID']; ?>" 
                                            <?php echo ($recipe['RecipeID'] == $saved['RecipeID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($recipe['Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-warning btn-sm">Update</button>
                            </form>

                            <!-- Delete form -->
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="savedID" value="<?php echo $saved['SavedID']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Form to add a new saved recipe -->
        <h2>Add Saved Recipe</h2>
        <form action="" method="POST" class="add-form">
            <input type="hidden" name="action" value="add">
            
            <!-- UserID Dropdown -->
            <div class="form-group">
                <label for="userID">User</label>
                <select name="userID" id="userID" required class="form-control">
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['UserID']; ?>"><?php echo htmlspecialchars($user['Username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- RecipeID Dropdown -->
            <div class="form-group">
                <label for="recipeID">Recipe</label>
                <select name="recipeID" id="recipeID" required class="form-control">
                    <?php foreach ($recipes as $recipe): ?>
                        <option value="<?php echo $recipe['RecipeID']; ?>"><?php echo htmlspecialchars($recipe['Name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-custom">Save Recipe</button>
        </form>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
