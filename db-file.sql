-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 11, 2025 at 11:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `culturalcuisineexplorer`
--

-- --------------------------------------------------------

--
-- Table structure for table `culturaldetails`
--

CREATE TABLE `culturaldetails` (
  `DetailID` int(11) NOT NULL,
  `RecipeID` int(11) DEFAULT NULL,
  `History` text DEFAULT NULL,
  `Festivals` text DEFAULT NULL,
  `Significance` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `culturaldetails`
--

INSERT INTO `culturaldetails` (`DetailID`, `RecipeID`, `History`, `Festivals`, `Significance`) VALUES
(3, 15, 'hh', 'bd', 'kk'),
(4, 17, 'hh', 'bd', 'bd');

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `IngredientID` int(11) NOT NULL,
  `RecipeID` int(11) DEFAULT NULL,
  `IngredientName` varchar(100) DEFAULT NULL,
  `Measurement` varchar(50) DEFAULT NULL,
  `Substitutes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`IngredientID`, `RecipeID`, `IngredientName`, `Measurement`, `Substitutes`) VALUES
(15, 15, 'bd', 'bd', 'potato'),
(16, 17, 'biryani', 'bd', 'potato');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `RecipeID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Region` varchar(50) DEFAULT NULL,
  `CuisineType` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`RecipeID`, `Name`, `Description`, `Region`, `CuisineType`) VALUES
(15, 'biryani', 'boiled rice with mutton', 'bangladeshi', 'int'),
(17, 'lolo', 'boiled rice with mutton', 'bangladeshi', 'local'),
(23, 'kk', 'kk', 'kk', 'kk');

-- --------------------------------------------------------

--
-- Table structure for table `recipetags`
--

CREATE TABLE `recipetags` (
  `TagID` int(11) NOT NULL,
  `RecipeID` int(11) DEFAULT NULL,
  `TagName` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipetags`
--

INSERT INTO `recipetags` (`TagID`, `RecipeID`, `TagName`) VALUES
(1, 15, 'local');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `ReviewID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `RecipeID` int(11) DEFAULT NULL,
  `Rating` int(11) DEFAULT NULL CHECK (`Rating` between 1 and 5),
  `ReviewText` text DEFAULT NULL,
  `ReviewDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`ReviewID`, `UserID`, `RecipeID`, `Rating`, `ReviewText`, `ReviewDate`) VALUES
(1, 8, 15, 3, 'bad', '2025-01-03 11:31:00'),
(4, 8, 15, 5, '\r\n', '2025-01-05 02:48:53');

-- --------------------------------------------------------

--
-- Table structure for table `savedrecipes`
--

CREATE TABLE `savedrecipes` (
  `SavedID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `RecipeID` int(11) DEFAULT NULL,
  `SaveDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `savedrecipes`
--

INSERT INTO `savedrecipes` (`SavedID`, `UserID`, `RecipeID`, `SaveDate`) VALUES
(1, 8, 15, '2025-01-03 11:26:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `PasswordHash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `Email`, `PasswordHash`) VALUES
(8, 'nuhhin', 'admin@123', '202cb962ac59075b964b07152d234b70'),
(9, 'nuhhin', 'admin@234', '289dff07669d7a23de0ef88d2f7129e7');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `culturaldetails`
--
ALTER TABLE `culturaldetails`
  ADD PRIMARY KEY (`DetailID`),
  ADD KEY `RecipeID` (`RecipeID`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`IngredientID`),
  ADD KEY `RecipeID` (`RecipeID`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`RecipeID`);

--
-- Indexes for table `recipetags`
--
ALTER TABLE `recipetags`
  ADD PRIMARY KEY (`TagID`),
  ADD KEY `RecipeID` (`RecipeID`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`ReviewID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `RecipeID` (`RecipeID`);

--
-- Indexes for table `savedrecipes`
--
ALTER TABLE `savedrecipes`
  ADD PRIMARY KEY (`SavedID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `RecipeID` (`RecipeID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `culturaldetails`
--
ALTER TABLE `culturaldetails`
  MODIFY `DetailID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `IngredientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `RecipeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `recipetags`
--
ALTER TABLE `recipetags`
  MODIFY `TagID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `ReviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `savedrecipes`
--
ALTER TABLE `savedrecipes`
  MODIFY `SavedID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `culturaldetails`
--
ALTER TABLE `culturaldetails`
  ADD CONSTRAINT `culturaldetails_ibfk_1` FOREIGN KEY (`RecipeID`) REFERENCES `recipes` (`RecipeID`);

--
-- Constraints for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD CONSTRAINT `ingredients_ibfk_1` FOREIGN KEY (`RecipeID`) REFERENCES `recipes` (`RecipeID`);

--
-- Constraints for table `recipetags`
--
ALTER TABLE `recipetags`
  ADD CONSTRAINT `recipetags_ibfk_1` FOREIGN KEY (`RecipeID`) REFERENCES `recipes` (`RecipeID`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`RecipeID`) REFERENCES `recipes` (`RecipeID`);

--
-- Constraints for table `savedrecipes`
--
ALTER TABLE `savedrecipes`
  ADD CONSTRAINT `savedrecipes_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `savedrecipes_ibfk_2` FOREIGN KEY (`RecipeID`) REFERENCES `recipes` (`RecipeID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
