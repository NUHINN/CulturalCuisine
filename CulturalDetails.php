<?php
session_start();
require_once 'dbconnect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            // Add a new cultural detail
            $recipeID = $_POST['recipeID'];
            $history = $_POST['history'];
            $festivals = $_POST['festivals'];
            $significance = $_POST['significance'];

            $stmt = $conn->prepare("INSERT INTO CulturalDetails (RecipeID, History, Festivals, Significance) 
                                    VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $recipeID, $history, $festivals, $significance);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit') {
            // Edit an existing cultural detail
            $detailID = $_POST['detailID'];
            $recipeID = $_POST['recipeID'];
            $history = $_POST['history'];
            $festivals = $_POST['festivals'];
            $significance = $_POST['significance'];

            $stmt = $conn->prepare("UPDATE CulturalDetails 
                                    SET RecipeID = ?, History = ?, Festivals = ?, Significance = ? 
                                    WHERE DetailID = ?");
            $stmt->bind_param("isssi", $recipeID, $history, $festivals, $significance, $detailID);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            // Delete a cultural detail
            $detailID = $_POST['detailID'];

            $stmt = $conn->prepare("DELETE FROM CulturalDetails WHERE DetailID = ?");
            $stmt->bind_param("i", $detailID);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch all cultural details
$result = $conn->query("SELECT * FROM CulturalDetails");
$culturalDetails = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all recipes for the dropdown
$recipesResult = $conn->query("SELECT RecipeID, Name FROM Recipes");
$recipes = $recipesResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cultural Details Management</title>

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
        <h1 class="text-center my-4">Cultural Details Management</h1>

        <!-- Display Cultural Details -->
        <div class="card">
            <div class="card-header">
                <h3>Cultural Details List</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>DetailID</th>
                            <th>RecipeID</th>
                            <th>History</th>
                            <th>Festivals</th>
                            <th>Significance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($culturalDetails as $detail): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['DetailID']); ?></td>
                                <td><?php echo htmlspecialchars($detail['RecipeID']); ?></td>
                                <td><?php echo htmlspecialchars($detail['History']); ?></td>
                                <td><?php echo htmlspecialchars($detail['Festivals']); ?></td>
                                <td><?php echo htmlspecialchars($detail['Significance']); ?></td>
                                <td>
                                    <!-- Edit form -->
                                    <form action="" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="detailID" value="<?php echo $detail['DetailID']; ?>">
                                        <select name="recipeID" class="form-control" required>
                                            <option value="<?php echo htmlspecialchars($detail['RecipeID']); ?>" selected><?php echo htmlspecialchars($detail['RecipeID']); ?></option>
                                            <?php
                                            // Fetch RecipeIDs for the dropdown
                                            $recipes = $conn->query("SELECT RecipeID, Name FROM Recipes");
                                            while ($row = $recipes->fetch_assoc()) {
                                                echo '<option value="' . $row['RecipeID'] . '">' . $row['Name'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <input type="text" name="history" value="<?php echo htmlspecialchars($detail['History']); ?>" class="form-control" required>
                                        <input type="text" name="festivals" value="<?php echo htmlspecialchars($detail['Festivals']); ?>" class="form-control" required>
                                        <input type="text" name="significance" value="<?php echo htmlspecialchars($detail['Significance']); ?>" class="form-control" required>
                                        <button type="submit" class="btn btn-warning btn-sm">Update</button>
                                    </form>

                                    <!-- Delete form -->
                                    <form action="" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="detailID" value="<?php echo $detail['DetailID']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Cultural Detail Form -->
        <div class="card">
            <div class="card-header">
                <h3>Add New Cultural Detail</h3>
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
                    
                    <!-- Cultural Details -->
                    <div class="mb-3">
                        <label for="history" class="form-label">History</label>
                        <input type="text" class="form-control" id="history" name="history" required>
                    </div>
                    <div class="mb-3">
                        <label for="festivals" class="form-label">Festivals</label>
                        <input type="text" class="form-control" id="festivals" name="festivals" required>
                    </div>
                    <div class="mb-3">
                        <label for="significance" class="form-label">Significance</label>
                        <input type="text" class="form-control" id="significance" name="significance">
                    </div>
                    <button type="submit" class="btn btn-custom">Add Cultural Detail</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
