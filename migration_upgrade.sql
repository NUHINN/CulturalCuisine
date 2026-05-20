-- Cultural Cuisine Explorer presentation upgrade migration
-- Safe to run after importing db-file.sql. It adds fields and indexes without deleting existing data.

START TRANSACTION;

ALTER TABLE `recipes`
  ADD COLUMN IF NOT EXISTS `ImagePath` VARCHAR(255) NULL AFTER `CuisineType`,
  ADD COLUMN IF NOT EXISTS `CreatedBy` INT(11) NULL AFTER `ImagePath`,
  ADD COLUMN IF NOT EXISTS `PrepTime` INT UNSIGNED NULL AFTER `CreatedBy`,
  ADD COLUMN IF NOT EXISTS `CookTime` INT UNSIGNED NULL AFTER `PrepTime`,
  ADD COLUMN IF NOT EXISTS `Servings` INT UNSIGNED NULL AFTER `CookTime`,
  ADD COLUMN IF NOT EXISTS `Difficulty` VARCHAR(20) NULL DEFAULT 'Easy' AFTER `Servings`,
  ADD COLUMN IF NOT EXISTS `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `Difficulty`,
  ADD COLUMN IF NOT EXISTS `UpdatedAt` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `CreatedAt`;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `ProfileImage` VARCHAR(255) NULL AFTER `PasswordHash`,
  ADD COLUMN IF NOT EXISTS `Bio` TEXT NULL AFTER `ProfileImage`,
  ADD COLUMN IF NOT EXISTS `CreatedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `Bio`;

CREATE INDEX IF NOT EXISTS `idx_recipes_region` ON `recipes` (`Region`);
CREATE INDEX IF NOT EXISTS `idx_recipes_cuisine` ON `recipes` (`CuisineType`);
CREATE INDEX IF NOT EXISTS `idx_recipes_created_at` ON `recipes` (`CreatedAt`);
CREATE INDEX IF NOT EXISTS `idx_recipes_created_by` ON `recipes` (`CreatedBy`);
CREATE INDEX IF NOT EXISTS `idx_recipetags_tag` ON `recipetags` (`TagName`);
CREATE INDEX IF NOT EXISTS `idx_reviews_rating` ON `reviews` (`Rating`);
CREATE INDEX IF NOT EXISTS `idx_reviews_recipe_date` ON `reviews` (`RecipeID`, `ReviewDate`);
CREATE INDEX IF NOT EXISTS `idx_savedrecipes_user_recipe` ON `savedrecipes` (`UserID`, `RecipeID`);
CREATE INDEX IF NOT EXISTS `idx_users_email` ON `users` (`Email`);

COMMIT;

-- Password note:
-- Existing MD5 hashes cannot be converted without knowing the original passwords.
-- The upgraded PHP login verifies old MD5 hashes once, then replaces them with password_hash().
