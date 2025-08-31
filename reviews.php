<?php
session_start();
require_once 'dbconnect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            // Add a new review
            $userID = $_POST['userID'];
            $recipeID = $_POST['recipeID'];
            $rating = $_POST['rating'];
            $reviewText = $_POST['reviewText'];
            $reviewDate = date('Y-m-d H:i:s');  // Current date and time

            $stmt = $conn->prepare("INSERT INTO Reviews (UserID, RecipeID, Rating, ReviewText, ReviewDate) 
                                    VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $userID, $recipeID, $rating, $reviewText, $reviewDate);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit') {
            // Edit an existing review
            $reviewID = $_POST['reviewID'];
            $userID = $_POST['userID'];
            $recipeID = $_POST['recipeID'];
            $rating = $_POST['rating'];
            $reviewText = $_POST['reviewText'];

            $stmt = $conn->prepare("UPDATE Reviews 
                                    SET UserID = ?, RecipeID = ?, Rating = ?, ReviewText = ? 
                                    WHERE ReviewID = ?");
            $stmt->bind_param("iiisi", $userID, $recipeID, $rating, $reviewText, $reviewID);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            // Delete a review
            $reviewID = $_POST['reviewID'];

            $stmt = $conn->prepare("DELETE FROM Reviews WHERE ReviewID = ?");
            $stmt->bind_param("i", $reviewID);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch all reviews
$result = $conn->query("SELECT * FROM Reviews");
$reviews = $result->fetch_all(MYSQLI_ASSOC);

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
    <title>Reviews Management</title>

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
        <h1>Reviews</h1>

        <!-- Table of reviews -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ReviewID</th>
                    <th>UserID</th>
                    <th>Username</th>
                    <th>RecipeID</th>
                    <th>Recipe Name</th>
                    <th>Rating</th>
                    <th>ReviewText</th>
                    <th>ReviewDate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($review['ReviewID']); ?></td>
                        <td><?php echo htmlspecialchars($review['UserID']); ?></td>
                        <td><?php
                            // Find the username based on UserID
                            $user = array_filter($users, function ($u) use ($review) {
                                return $u['UserID'] == $review['UserID'];
                            });
                            echo htmlspecialchars(array_shift($user)['Username']);
                        ?></td>
                        <td><?php echo htmlspecialchars($review['RecipeID']); ?></td>
                        <td><?php
                            // Find the recipe name based on RecipeID
                            $recipe = array_filter($recipes, function ($r) use ($review) {
                                return $r['RecipeID'] == $review['RecipeID'];
                            });
                            echo htmlspecialchars(array_shift($recipe)['Name']);
                        ?></td>
                        <td><?php echo htmlspecialchars($review['Rating']); ?></td>
                        <td><?php echo htmlspecialchars($review['ReviewText']); ?></td>
                        <td><?php echo htmlspecialchars($review['ReviewDate']); ?></td>
                        <td class="actions">
                            <!-- Edit form -->
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="reviewID" value="<?php echo $review['ReviewID']; ?>">
                                <select name="userID" required class="form-control">
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['UserID']; ?>" 
                                            <?php echo ($user['UserID'] == $review['UserID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['Username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="recipeID" required class="form-control">
                                    <?php foreach ($recipes as $recipe): ?>
                                        <option value="<?php echo $recipe['RecipeID']; ?>" 
                                            <?php echo ($recipe['RecipeID'] == $review['RecipeID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($recipe['Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="rating" value="<?php echo $review['Rating']; ?>" required class="form-control">
                                <textarea name="reviewText" required class="form-control"><?php echo $review['ReviewText']; ?></textarea>
                                <button type="submit" class="btn btn-warning btn-sm">Update</button>
                            </form>

                            <!-- Delete form -->
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="reviewID" value="<?php echo $review['ReviewID']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Form to add a new review -->
        <h2>Add Review</h2>
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

            <!-- Rating -->
            <div class="form-group">
                <label for="rating">Rating (1-5)</label>
                <input type="number" name="rating" id="rating" min="1" max="5" required class="form-control">
            </div>

            <!-- Review Text -->
            <div class="form-group">
                <label for="reviewText">Review Text</label>
                <textarea name="reviewText" id="reviewText" required class="form-control"></textarea>
            </div>

            <button type="submit" class="btn btn-custom">Save Review</button>
        </form>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>

