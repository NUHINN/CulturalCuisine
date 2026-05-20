# Cultural Cuisine Explorer

A modernized PHP + MySQL recipe and cultural food website with login/register, recipe browsing, recipe images, ingredients, cultural details, tags, reviews, saved recipes, profile image upload, and safer CRUD management pages.

## What Changed

- Redesigned the UI with a warm cultural food theme, modern cards, responsive navigation, hero visuals, stats, featured recipes, recipe cards, empty states, alerts, and a polished footer.
- Added reusable PHP structure: `functions.php`, `header.php`, `navbar.php`, `footer.php`, and `logout.php`.
- Added `recipe_detail.php` with image, metadata, ingredients, cultural history, festivals, significance, tags, average rating, reviews, and save/unsave.
- Added recipe image upload and profile image upload with MIME, extension, size, and upload path validation.
- Added safe default visual assets in `assets/images/` and upload folders in `assets/uploads/`.
- Improved search by recipe name, description, region, cuisine type, and tags, with filters for region, cuisine, tag, rating, and sorting.
- Replaced unsafe registration SQL with prepared statements.
- Replaced new password storage with `password_hash()` and `password_verify()`.
- Added backward-compatible login for old MD5 hashes. After a successful login, the old MD5 hash is upgraded automatically.
- Added CSRF protection to important POST forms.
- Escaped output with `htmlspecialchars()` through the shared `e()` helper.
- Removed exposed SMTP credentials from `contact.php`; SMTP values now come from environment variables.
- Added `migration_upgrade.sql` for non-destructive database upgrades.

## Project Files

Core pages:

- `index.php` - modern login/register page
- `homepage.php` - dashboard homepage
- `search.php` - recipe browse/search/filter page
- `recipe_detail.php` - full recipe detail page
- `Recipe.php` - recipe management with image upload
- `Ingredients.php` - ingredient management
- `CulturalDetails.php` - cultural detail management
- `recipetags.php` - tag management
- `reviews.php` - review management
- `savedrecipe.php` - saved recipe management
- `profile.php` - user profile, bio, profile image, saved recipes, reviews
- `changepassword.php` - secure password change
- `contact.php` - contact form shell with optional SMTP environment configuration
- `logout.php` - session logout

Shared files:

- `dbconnect.php`
- `functions.php`
- `header.php`
- `navbar.php`
- `footer.php`
- `style.css`
- `style1.css`
- `script.js`
- `migration_upgrade.sql`

## Database Setup in XAMPP/phpMyAdmin

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Open phpMyAdmin: `http://localhost/phpmyadmin`.
3. Create a database named `culturalcuisineexplorer`.
4. Import `db-file.sql`.
5. Import `migration_upgrade.sql`.
6. Confirm these new columns exist:
   - `recipes.ImagePath`
   - `recipes.CreatedBy`
   - `recipes.PrepTime`
   - `recipes.CookTime`
   - `recipes.Servings`
   - `recipes.Difficulty`
   - `recipes.CreatedAt`
   - `users.ProfileImage`
   - `users.Bio`
   - `users.CreatedAt`

## Run Locally

1. Copy the `CulturalCuisine-main` folder into your XAMPP `htdocs` folder.
2. Confirm the database credentials in `dbconnect.php`:

```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "culturalcuisineexplorer";
```

3. Open the site:

```text
http://localhost/CulturalCuisine-main/index.php
```

4. Register a new account or sign in with an existing account.

## Upload Folder Permissions

The app writes uploaded images to:

- `assets/uploads/recipes/`
- `assets/uploads/profiles/`

On Windows/XAMPP this usually works automatically. If uploads fail, make sure those folders are writable by Apache. The included `.htaccess` blocks PHP-like files inside `assets/uploads/`.

## Optional SMTP Contact Setup

`contact.php` no longer stores secrets in the code. To send email through Brevo SMTP, configure environment variables in your Apache/XAMPP environment:

- `BREVO_SMTP_USER`
- `BREVO_SMTP_KEY`
- `CONTACT_FROM_EMAIL`
- `CONTACT_TO_EMAIL`

Without these values, the contact form validates input and shows a setup notice instead of exposing credentials.

## Security Notes

- New passwords use `password_hash()`.
- Existing MD5 passwords are supported only for migration. When a user successfully signs in, their password hash is upgraded automatically.
- Important POST forms include CSRF tokens.
- SQL operations use prepared statements.
- User output is escaped.
- Image uploads validate MIME type, extension, file size, and destination folder.
- Logged-in pages use `auth.php`.

## Presentation Checklist

- Register a new user.
- Log in and log out.
- Add a recipe with an image.
- Add ingredients, cultural details, tags, reviews, and a saved recipe.
- Browse recipes from `search.php` and test filters.
- Open a recipe detail page and test save/unsave.
- Upload a profile image and update your bio.
- Change password, then sign in again.
