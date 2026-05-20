<?php
declare(strict_types=1);

require_once __DIR__ . '/dbconnect.php';

const APP_NAME = 'Cultural Cuisine Explorer';
const DEFAULT_RECIPE_IMAGE = 'assets/images/default-recipe.svg';
const DEFAULT_PROFILE_IMAGE = 'assets/images/default-profile.svg';
const MAX_IMAGE_BYTES = 3145728; // 3 MB

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    if (empty($_SESSION['session_started_at'])) {
        $_SESSION['session_started_at'] = time();
        session_regenerate_id(true);
    }
}

start_secure_session();

function db(): mysqli
{
    global $conn;
    return $conn;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function text_substr(string $value, int $start, int $length): string
{
    return function_exists('mb_substr') ? mb_substr($value, $start, $length) : substr($value, $start, $length);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function local_redirect_target(string $fallback = 'homepage.php'): string
{
    $target = (string)($_SERVER['HTTP_REFERER'] ?? $fallback);
    if (preg_match('#^https?://#i', $target)) {
        $targetHost = parse_url($target, PHP_URL_HOST);
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        if (!$targetHost || strcasecmp((string)$targetHost, (string)$currentHost) !== 0) {
            return $fallback;
        }
    }
    return $target;
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'homepage.php';
        redirect('index.php');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => in_array($type, ['success', 'danger', 'warning', 'info'], true) ? $type : 'info',
        'message' => $message,
    ];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function render_flashes(): void
{
    foreach (get_flashes() as $flash) {
        echo '<div class="alert alert-' . e($flash['type']) . ' alert-dismissible fade show app-alert" role="alert">';
        echo e($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
        flash('danger', 'Security check failed. Please try again.');
        redirect(local_redirect_target());
    }
}

function clean_text(mixed $value, int $maxLength = 255): string
{
    $value = trim((string)($value ?? ''));
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    return text_substr($value, 0, $maxLength);
}

function clean_long_text(mixed $value, int $maxLength = 5000): string
{
    $value = trim((string)($value ?? ''));
    return text_substr($value, 0, $maxLength);
}

function int_value(mixed $value, int $default = 0): int
{
    return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : $default;
}

function prepared_query(string $sql, string $types = '', array $params = []): ?mysqli_stmt
{
    $stmt = db()->prepare($sql);
    if (!$stmt) {
        error_log('Prepare failed: ' . db()->error . ' SQL: ' . $sql);
        return null;
    }

    if ($types !== '') {
        $bindParams = [$types];
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        if (!call_user_func_array([$stmt, 'bind_param'], $bindParams)) {
            error_log('Bind failed: ' . $stmt->error);
            $stmt->close();
            return null;
        }
    }

    if (!$stmt->execute()) {
        error_log('Execute failed: ' . $stmt->error . ' SQL: ' . $sql);
        $stmt->close();
        return null;
    }

    return $stmt;
}

function fetch_all_prepared(string $sql, string $types = '', array $params = []): array
{
    $stmt = prepared_query($sql, $types, $params);
    if (!$stmt) {
        return [];
    }

    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function fetch_one_prepared(string $sql, string $types = '', array $params = []): ?array
{
    $rows = fetch_all_prepared($sql, $types, $params);
    return $rows[0] ?? null;
}

function run_prepared(string $sql, string $types = '', array $params = []): bool
{
    $stmt = prepared_query($sql, $types, $params);
    if (!$stmt) {
        return false;
    }
    $stmt->close();
    return true;
}

function db_has_column(string $table, string $column): bool
{
    static $cache = [];

    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    $key = strtolower($table . '.' . $column);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $tableSafe = "`" . $table . "`";
    $columnSafe = db()->real_escape_string($column);

    $result = db()->query("SHOW COLUMNS FROM $tableSafe LIKE '$columnSafe'");

    if (!$result) {
        $cache[$key] = false;
        return false;
    }

    $cache[$key] = $result->num_rows > 0;
    $result->free();

    return $cache[$key];
}

function recipe_select_columns(string $alias = 'r'): string
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    $columns = [
        "{$prefix}RecipeID",
        "{$prefix}Name",
        "{$prefix}Description",
        "{$prefix}Region",
        "{$prefix}CuisineType",
    ];

    foreach (['ImagePath', 'CreatedBy', 'PrepTime', 'CookTime', 'Servings', 'Difficulty', 'CreatedAt', 'UpdatedAt'] as $column) {
        $columns[] = db_has_column('recipes', $column)
            ? "{$prefix}{$column}"
            : "NULL AS {$column}";
    }

    return implode(', ', $columns);
}

function user_select_columns(string $alias = 'u'): string
{
    $prefix = $alias !== '' ? $alias . '.' : '';
    $columns = ["{$prefix}UserID", "{$prefix}Username", "{$prefix}Email", "{$prefix}PasswordHash"];

    foreach (['ProfileImage', 'Bio', 'CreatedAt'] as $column) {
        $columns[] = db_has_column('users', $column)
            ? "{$prefix}{$column}"
            : "NULL AS {$column}";
    }

    return implode(', ', $columns);
}

function normalize_asset_path(?string $path, string $fallback): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return $fallback;
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (is_file(__DIR__ . '/' . $path)) {
        return $path;
    }

    return $fallback;
}

function recipe_image(?string $path): string
{
    return normalize_asset_path($path, DEFAULT_RECIPE_IMAGE);
}

function profile_image(?string $path): string
{
    return normalize_asset_path($path, DEFAULT_PROFILE_IMAGE);
}

function upload_image(string $field, string $subdir, int $maxBytes = MAX_IMAGE_BYTES): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return [null, null];
    }

    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return [null, 'The image upload failed. Please choose another file.'];
    }

    if (($file['size'] ?? 0) > $maxBytes) {
        return [null, 'Images must be 3 MB or smaller.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, 'Invalid upload request.'];
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    if ($mime === '' && function_exists('mime_content_type')) {
        $mime = (string)mime_content_type($file['tmp_name']);
    }

    if (!isset($allowed[$mime])) {
        return [null, 'Please upload a JPG, PNG, WEBP, or GIF image.'];
    }

    $originalExt = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($originalExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return [null, 'The image file extension is not allowed.'];
    }

    $safeSubdir = preg_replace('/[^a-z0-9_-]/i', '', $subdir);
    $uploadDir = __DIR__ . '/assets/uploads/' . $safeSubdir;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return [null, 'Upload folder could not be created.'];
    }

    $filename = $safeSubdir . '_' . bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [null, 'Could not save the uploaded image. Check folder permissions.'];
    }

    return ['assets/uploads/' . $safeSubdir . '/' . $filename, null];
}

function verify_password_compat(string $password, ?string $storedHash): bool
{
    $storedHash = (string)$storedHash;
    if ($storedHash === '') {
        return false;
    }

    if (password_verify($password, $storedHash)) {
        return true;
    }

    return preg_match('/^[a-f0-9]{32}$/i', $storedHash) === 1 && hash_equals(strtolower($storedHash), md5($password));
}

function password_needs_upgrade(?string $storedHash): bool
{
    $storedHash = (string)$storedHash;
    return preg_match('/^[a-f0-9]{32}$/i', $storedHash) === 1 || password_needs_rehash($storedHash, PASSWORD_DEFAULT);
}

function upgrade_user_password_hash(int $userId, string $password): void
{
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    run_prepared('UPDATE users SET PasswordHash = ? WHERE UserID = ?', 'si', [$newHash, $userId]);
}

function current_user(): ?array
{
    $userId = current_user_id();
    if (!$userId) {
        return null;
    }
    return fetch_one_prepared('SELECT ' . user_select_columns('u') . ' FROM users u WHERE u.UserID = ? LIMIT 1', 'i', [$userId]);
}

function recipe_meta_text(array $recipe): string
{
    $parts = array_filter([
        $recipe['Region'] ?? '',
        $recipe['CuisineType'] ?? '',
        $recipe['Difficulty'] ?? '',
    ]);
    return implode(' / ', $parts);
}

function render_stars(float $rating): string
{
    $rounded = (int)round($rating);
    $html = '<span class="stars" aria-label="' . e(number_format($rating, 1)) . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="' . ($i <= $rounded ? 'fa-solid' : 'fa-regular') . ' fa-star"></i>';
    }
    $html .= '</span>';
    return $html;
}

function truncate_text(?string $text, int $length = 130): string
{
    $text = trim((string)$text);
    if (text_length($text) <= $length) {
        return $text;
    }
    return rtrim(text_substr($text, 0, $length - 3)) . '...';
}

function get_recipe_options(): array
{
    return fetch_all_prepared('SELECT RecipeID, Name FROM recipes ORDER BY Name ASC');
}

function get_user_options(): array
{
    return fetch_all_prepared('SELECT UserID, Username, Email FROM users ORDER BY Username ASC, Email ASC');
}

function is_recipe_saved(int $userId, int $recipeId): bool
{
    $row = fetch_one_prepared(
        'SELECT SavedID FROM savedrecipes WHERE UserID = ? AND RecipeID = ? LIMIT 1',
        'ii',
        [$userId, $recipeId]
    );
    return $row !== null;
}

function save_recipe_for_user(int $userId, int $recipeId): bool
{
    if (is_recipe_saved($userId, $recipeId)) {
        return true;
    }
    return run_prepared('INSERT INTO savedrecipes (UserID, RecipeID) VALUES (?, ?)', 'ii', [$userId, $recipeId]);
}

function unsave_recipe_for_user(int $userId, int $recipeId): bool
{
    return run_prepared('DELETE FROM savedrecipes WHERE UserID = ? AND RecipeID = ?', 'ii', [$userId, $recipeId]);
}

function delete_recipe_with_dependents(int $recipeId): bool
{
    db()->begin_transaction();
    try {
        foreach (['ingredients', 'culturaldetails', 'recipetags', 'reviews', 'savedrecipes'] as $table) {
            $stmt = db()->prepare("DELETE FROM `$table` WHERE RecipeID = ?");
            if (!$stmt) {
                throw new RuntimeException(db()->error);
            }
            $stmt->bind_param('i', $recipeId);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();
        }

        $stmt = db()->prepare('DELETE FROM recipes WHERE RecipeID = ?');
        if (!$stmt) {
            throw new RuntimeException(db()->error);
        }
        $stmt->bind_param('i', $recipeId);
        if (!$stmt->execute()) {
            throw new RuntimeException($stmt->error);
        }
        $stmt->close();
        db()->commit();
        return true;
    } catch (Throwable $exception) {
        db()->rollback();
        error_log('Recipe delete failed: ' . $exception->getMessage());
        return false;
    }
}
