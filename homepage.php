<?php
session_start();

require_once 'dbconnect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(120deg, #ffcc00, #ff9900); /* Gradient from yellow to orange */
            background-size: cover;
            background-attachment: fixed;
        }

        header {
            background: linear-gradient(120deg, #ffcc00, #ff9900); /* Matching gradient */
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        header .logo {
            font-size: 24px;
            font-weight: bold;
            color: #000; /* Black color for the logo text */
        }

        header .nav-buttons {
            display: flex;
            gap: 15px;
        }

        header .nav-buttons a {
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            background-color: #333;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        header .nav-buttons a:hover {
            background-color: #444;
        }

        .container {
            max-width: 1200px;
            margin: 50px auto;
            text-align: center;
        }

        h1 {
            font-size: 48px;
            color: #333;
            margin-bottom: 20px;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
        }

        h1 .highlight {
            color: #ff9900;
        }

        .box-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 40px;
        }

        .box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 180px;
            height: 180px;
            margin: 15px;
            background-color: #fff;
            color: #333;
            text-align: center;
            font-size: 18px;
            text-transform: capitalize;
            text-decoration: none;
            border-radius: 10px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            padding: 20px;
        }

        .box i {
            font-size: 36px;
            margin-bottom: 10px;
            color: #ff9900;
        }

        .box:hover {
            background-color: #ffcc00;
            color: #fff;
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.4);
        }

        /* About Section Styling */
        .about {
            background-color: #fff;
            padding: 40px 20px;
            margin: 50px auto;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            max-width: 800px;
        }

        .about h2 {
            font-size: 32px;
            color: #333;
            margin-bottom: 20px;
        }

        .about p {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
        }

        /* Footer Styling */
        footer {
            background-color: #333;
            color: #fff;
            padding: 20px 0;
            text-align: center;
            margin-top: 40px;
        }

        footer .social-icons {
            margin-top: 10px;
        }

        footer .social-icons a {
            color: #ffcc00;
            font-size: 24px;
            margin: 0 10px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        footer .social-icons a:hover {
            color: #ff9900;
        }

        footer .footer-text {
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<!-- Header Section -->
<header>
    <div class="logo">Cultural Cuisine Explorer</div>
    <div class="nav-buttons">
        <a href="contact.php">Contact Us</a>
    </div>
</header>

<div class="container">
    <h1>Explore <span class="highlight">Natural</span> and Spicy Cuisine</h1>

    <!-- Box container to hold the links -->
    <div class="box-container">
        <!-- Box for Recipes table -->
        <a href="recipe.php" class="box">
            <i class="fas fa-utensils"></i>
            <div class="box-title">Recipes</div>
        </a>

        <!-- Box for Ingredients table -->
        <a href="Ingredients.php" class="box">
            <i class="fas fa-carrot"></i>
            <div class="box-title">Ingredients</div>
        </a>

        <!-- Box for CulturalDetails table -->
        <a href="CulturalDetails.php" class="box">
            <i class="fas fa-globe"></i>
            <div class="box-title">Cultural Details</div>
        </a>

        <!-- Box for Saved Recipes table -->
        <a href="savedrecipe.php" class="box">
            <i class="fas fa-bookmark"></i>
            <div class="box-title">Saved Recipes</div>
        </a>

        <!-- Box for Reviews table -->
        <a href="Reviews.php" class="box">
            <i class="fas fa-star"></i>
            <div class="box-title">Reviews</div>
        </a>

        <!-- Box for RecipeTags table -->
        <a href="RecipeTags.php" class="box">
            <i class="fas fa-tags"></i>
            <div class="box-title">Recipe Tags</div>
        </a>
    </div>

    <!-- About Section -->
    <div class="about">
        <h2>About Us</h2>
        <p>Cultural Cuisine Explorer is your go-to platform for discovering delicious and authentic recipes from various cultures around the world. We bring you a rich collection of recipes, ingredients, and cultural details to inspire your culinary journey. Whether you are a seasoned chef or an aspiring cook, our website provides the tools and knowledge to help you explore and enjoy the diverse flavors of global cuisine.</p>
    </div>
</div>

<!-- Footer -->
<footer>
    <div class="social-icons">
        <a href="#" class="fab fa-facebook-f"></a>
        <a href="#" class="fab fa-twitter"></a>
        <a href="#" class="fab fa-instagram"></a>
        <a href="#" class="fab fa-linkedin-in"></a>
    </div>
    <div class="footer-text">
        &copy; 2025 Cultural Cuisine Explorer. All Rights Reserved.
    </div>
</footer>

</body>
</html>
