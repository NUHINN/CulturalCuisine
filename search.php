<?php
require_once __DIR__ . '/functions.php';

$q = clean_text($_GET['q'] ?? '', 120);
$region = clean_text($_GET['region'] ?? '', 80);
$cuisine = clean_text($_GET['cuisine'] ?? '', 80);
$tag = clean_text($_GET['tag'] ?? '', 80);
$rating = int_value($_GET['rating'] ?? 0, 0);
$sort = clean_text($_GET['sort'] ?? 'highest_rated', 40);

$where = ['1=1'];
$types = '';
$params = [];

if ($q !== '') {
    $where[] = '(r.Name LIKE CONCAT("%", ?, "%")
        OR r.Description LIKE CONCAT("%", ?, "%")
        OR r.Region LIKE CONCAT("%", ?, "%")
        OR r.CuisineType LIKE CONCAT("%", ?, "%")
        OR EXISTS (SELECT 1 FROM recipetags tq WHERE tq.RecipeID = r.RecipeID AND tq.TagName LIKE CONCAT("%", ?, "%")))';
    $types .= 'sssss';
    array_push($params, $q, $q, $q, $q, $q);
}

if ($region !== '') {
    $where[] = 'r.Region = ?';
    $types .= 's';
    $params[] = $region;
}

if ($cuisine !== '') {
    $where[] = 'r.CuisineType = ?';
    $types .= 's';
    $params[] = $cuisine;
}

if ($tag !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM recipetags tf WHERE tf.RecipeID = r.RecipeID AND tf.TagName = ?)';
    $types .= 's';
    $params[] = $tag;
}

if ($rating >= 1 && $rating <= 5) {
    $where[] = 'COALESCE((SELECT AVG(rv2.Rating) FROM reviews rv2 WHERE rv2.RecipeID = r.RecipeID), 0) >= ?';
    $types .= 'i';
    $params[] = $rating;
}

$orderBy = match ($sort) {
    'name' => 'r.Name ASC',
    'newest' => db_has_column('recipes', 'CreatedAt') ? 'r.CreatedAt DESC, r.RecipeID DESC' : 'r.RecipeID DESC',
    default => 'AvgRating DESC, ReviewCount DESC, r.Name ASC',
};

$recipes = fetch_all_prepared(
    'SELECT ' . recipe_select_columns('r') . ',
        COALESCE((SELECT AVG(rv.Rating) FROM reviews rv WHERE rv.RecipeID = r.RecipeID), 0) AS AvgRating,
        (SELECT COUNT(*) FROM reviews rv WHERE rv.RecipeID = r.RecipeID) AS ReviewCount,
        (SELECT GROUP_CONCAT(DISTINCT t.TagName ORDER BY t.TagName SEPARATOR ',') FROM recipetags t WHERE t.RecipeID = r.RecipeID) AS Tags
     FROM recipes r
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY ' . $orderBy,
    $types,
    $params
);

$regions = fetch_all_prepared("SELECT DISTINCT Region FROM recipes WHERE Region IS NOT NULL AND Region <> '' ORDER BY Region ASC");
$cuisines = fetch_all_prepared("SELECT DISTINCT CuisineType FROM recipes WHERE CuisineType IS NOT NULL AND CuisineType <> '' ORDER BY CuisineType ASC");
$tags = fetch_all_prepared("SELECT DISTINCT TagName FROM recipetags WHERE TagName IS NOT NULL AND TagName <> '' ORDER BY TagName ASC");

$pageTitle = 'Browse Recipes | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mb-4">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-magnifying-glass"></i> Browse</span>
                <h1 class="section-title">Find recipes by flavor and culture</h1>
                <p class="section-copy mb-0">Search names, descriptions, regions, cuisine types, and tags. Then filter by rating or sort the results for quick discovery.</p>
            </div>
        </div>

        <form method="get" action="search.php" class="filter-bar">
            <div>
                <label class="form-label" for="q">Search</label>
                <input class="form-control" type="search" id="q" name="q" value="<?php echo e($q); ?>" placeholder="Biryani, festive, spicy...">
            </div>
            <div>
                <label class="form-label" for="region">Region</label>
                <select class="form-select" id="region" name="region">
                    <option value="">All regions</option>
                    <?php foreach ($regions as $row): ?>
                        <option value="<?php echo e($row['Region']); ?>" <?php echo $region === $row['Region'] ? 'selected' : ''; ?>><?php echo e($row['Region']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label" for="cuisine">Cuisine</label>
                <select class="form-select" id="cuisine" name="cuisine">
                    <option value="">All cuisines</option>
                    <?php foreach ($cuisines as $row): ?>
                        <option value="<?php echo e($row['CuisineType']); ?>" <?php echo $cuisine === $row['CuisineType'] ? 'selected' : ''; ?>><?php echo e($row['CuisineType']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label" for="tag">Tag</label>
                <select class="form-select" id="tag" name="tag">
                    <option value="">All tags</option>
                    <?php foreach ($tags as $row): ?>
                        <option value="<?php echo e($row['TagName']); ?>" <?php echo $tag === $row['TagName'] ? 'selected' : ''; ?>><?php echo e($row['TagName']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label" for="rating">Rating</label>
                <select class="form-select" id="rating" name="rating">
                    <option value="0">Any rating</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo $rating === $i ? 'selected' : ''; ?>><?php echo $i; ?>+ stars</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="form-label" for="sort">Sort</label>
                <select class="form-select" id="sort" name="sort">
                    <option value="highest_rated" <?php echo $sort === 'highest_rated' ? 'selected' : ''; ?>>Highest rated</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-2"></i>Apply</button>
                <a class="btn btn-outline-secondary" href="search.php">Reset</a>
            </div>
        </form>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-list"></i> Results</span>
                <h2 class="section-title"><?php echo count($recipes); ?> recipe<?php echo count($recipes) === 1 ? '' : 's'; ?> found</h2>
            </div>
        </div>

        <?php if (empty($recipes)): ?>
            <div class="empty-state">
                <h3 class="h5 fw-bold">No recipes match those filters.</h3>
                <p class="mb-0">Try a broader keyword, remove the rating filter, or add more recipe data from the management pages.</p>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <?php $tagList = array_filter(array_map('trim', explode(',', (string)($recipe['Tags'] ?? '')))); ?>
                    <article class="recipe-card">
                        <img class="recipe-thumb" src="<?php echo e(recipe_image($recipe['ImagePath'] ?? null)); ?>" alt="<?php echo e($recipe['Name']); ?>">
                        <div class="recipe-card-body">
                            <div class="recipe-meta">
                                <?php if (!empty($recipe['Region'])): ?><span class="meta-pill"><i class="fa-solid fa-location-dot"></i><?php echo e($recipe['Region']); ?></span><?php endif; ?>
                                <?php if (!empty($recipe['CuisineType'])): ?><span class="meta-pill"><i class="fa-solid fa-bowl-rice"></i><?php echo e($recipe['CuisineType']); ?></span><?php endif; ?>
                            </div>
                            <h3 class="recipe-title"><?php echo e($recipe['Name']); ?></h3>
                            <p class="muted-text flex-grow-1"><?php echo e(truncate_text($recipe['Description'] ?? '', 145)); ?></p>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <span><?php echo render_stars((float)$recipe['AvgRating']); ?></span>
                                <span class="small muted-text"><?php echo number_format((float)$recipe['AvgRating'], 1); ?> / 5</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php foreach (array_slice($tagList, 0, 4) as $tagName): ?>
                                    <a class="tag-pill" href="search.php?tag=<?php echo urlencode($tagName); ?>"><?php echo e($tagName); ?></a>
                                <?php endforeach; ?>
                            </div>
                            <a class="btn btn-primary mt-auto" href="recipe_detail.php?id=<?php echo (int)$recipe['RecipeID']; ?>">Open recipe</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
