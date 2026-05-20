<?php
require_once __DIR__ . '/auth.php';

$user = current_user();

$stats = [
    'recipes' => (int)(fetch_one_prepared('SELECT COUNT(*) AS total FROM recipes')['total'] ?? 0),
    'regions' => (int)(fetch_one_prepared("SELECT COUNT(DISTINCT Region) AS total FROM recipes WHERE Region IS NOT NULL AND Region <> ''")['total'] ?? 0),
    'reviews' => (int)(fetch_one_prepared('SELECT COUNT(*) AS total FROM reviews')['total'] ?? 0),
    'saved' => (int)(fetch_one_prepared('SELECT COUNT(*) AS total FROM savedrecipes')['total'] ?? 0),
];

$createdOrder = db_has_column('recipes', 'CreatedAt') ? 'r.CreatedAt DESC,' : '';
$featuredRecipes = fetch_all_prepared(
    'SELECT ' . recipe_select_columns('r') . ',
        COALESCE((SELECT AVG(rv.Rating) FROM reviews rv WHERE rv.RecipeID = r.RecipeID), 0) AS AvgRating,
        (SELECT COUNT(*) FROM reviews rv WHERE rv.RecipeID = r.RecipeID) AS ReviewCount,
        (SELECT GROUP_CONCAT(DISTINCT t.TagName ORDER BY t.TagName SEPARATOR \',\') FROM recipetags t WHERE t.RecipeID = r.RecipeID) AS Tags
     FROM recipes r
     ORDER BY AvgRating DESC, ' . $createdOrder . ' r.RecipeID DESC
     LIMIT 6'
);

$popularCuisines = fetch_all_prepared(
    "SELECT CuisineType, COUNT(*) AS Total
     FROM recipes
     WHERE CuisineType IS NOT NULL AND CuisineType <> ''
     GROUP BY CuisineType
     ORDER BY Total DESC, CuisineType ASC
     LIMIT 6"
);

$dashboardCards = [
    ['Recipe.php', 'Manage Recipes', 'Create image-rich recipe records.', 'fa-utensils'],
    ['Ingredients.php', 'Ingredients', 'Map ingredients, measurements, and substitutes.', 'fa-carrot'],
    ['CulturalDetails.php', 'Culture Notes', 'Document history, festivals, and significance.', 'fa-earth-asia'],
    ['search.php', 'Browse & Filter', 'Search by name, region, cuisine, tags, and rating.', 'fa-magnifying-glass'],
    ['savedrecipe.php', 'Saved Recipes', 'Review saved collections and user bookmarks.', 'fa-bookmark'],
    ['profile.php', 'Profile', 'Update your profile image, bio, and account details.', 'fa-id-card'],
];

$pageTitle = 'Home | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<section class="hero">
    <div class="hero-inner">
        <span class="eyebrow"><i class="fa-solid fa-mortar-pestle"></i> Welcome<?php echo $user ? ', ' . e($user['Username']) : ''; ?></span>
        <h1 class="display-title mt-3">A richer way to explore cultural cuisine.</h1>
        <p class="section-copy">Browse recipes, learn their cultural roots, save favorites, and manage the ingredients and stories that make every dish memorable.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="search.php"><i class="fa-solid fa-magnifying-glass me-2"></i>Browse recipes</a>
            <a class="btn btn-soft" href="Recipe.php"><i class="fa-solid fa-plus me-2"></i>Add recipe</a>
        </div>
        <div class="hero-panel">
            <span class="meta-pill"><i class="fa-solid fa-map-location-dot"></i><?php echo (int)$stats['regions']; ?> regions</span>
            <span class="meta-pill"><i class="fa-solid fa-bowl-food"></i><?php echo (int)$stats['recipes']; ?> recipes</span>
            <span class="meta-pill"><i class="fa-solid fa-star"></i><?php echo (int)$stats['reviews']; ?> reviews</span>
            <span class="meta-pill"><i class="fa-solid fa-bookmark"></i><?php echo (int)$stats['saved']; ?> saved</span>
        </div>
    </div>
</section>

<div class="page-shell">
    <section class="mb-5">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-chart-simple"></i> Dashboard</span>
                <h2 class="section-title">Quick actions</h2>
            </div>
        </div>
        <div class="dashboard-grid">
            <?php foreach ($dashboardCards as [$href, $title, $copy, $icon]): ?>
                <a class="content-card d-block text-reset" href="<?php echo e($href); ?>">
                    <div class="d-flex align-items-start gap-3">
                        <span class="brand-mark"><i class="fa-solid <?php echo e($icon); ?>"></i></span>
                        <div>
                            <h3 class="h5 fw-bold mb-1"><?php echo e($title); ?></h3>
                            <p class="muted-text mb-0"><?php echo e($copy); ?></p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-5">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo (int)$stats['recipes']; ?></div>
                <div class="stat-label">Recipes cataloged</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo (int)$stats['regions']; ?></div>
                <div class="stat-label">Regions represented</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo (int)$stats['reviews']; ?></div>
                <div class="stat-label">Community reviews</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo (int)$stats['saved']; ?></div>
                <div class="stat-label">Saved recipes</div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-fire"></i> Featured</span>
                <h2 class="section-title">Top recipes</h2>
            </div>
            <a class="btn btn-outline-primary" href="search.php">View all</a>
        </div>

        <?php if (empty($featuredRecipes)): ?>
            <div class="empty-state">No recipes yet. Add your first recipe to bring the homepage to life.</div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($featuredRecipes as $recipe): ?>
                    <?php $tags = array_filter(array_map('trim', explode(',', (string)($recipe['Tags'] ?? '')))); ?>
                    <article class="recipe-card">
                        <img class="recipe-thumb" src="<?php echo e(recipe_image($recipe['ImagePath'] ?? null)); ?>" alt="<?php echo e($recipe['Name']); ?>">
                        <div class="recipe-card-body">
                            <div class="recipe-meta">
                                <?php if (!empty($recipe['Region'])): ?><span class="meta-pill"><i class="fa-solid fa-location-dot"></i><?php echo e($recipe['Region']); ?></span><?php endif; ?>
                                <?php if (!empty($recipe['CuisineType'])): ?><span class="meta-pill"><i class="fa-solid fa-bowl-rice"></i><?php echo e($recipe['CuisineType']); ?></span><?php endif; ?>
                            </div>
                            <h3 class="recipe-title"><?php echo e($recipe['Name']); ?></h3>
                            <p class="muted-text flex-grow-1"><?php echo e(truncate_text($recipe['Description'] ?? '', 120)); ?></p>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <span><?php echo render_stars((float)$recipe['AvgRating']); ?></span>
                                <span class="small muted-text"><?php echo number_format((float)$recipe['AvgRating'], 1); ?> (<?php echo (int)$recipe['ReviewCount']; ?>)</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                    <span class="tag-pill"><?php echo e($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <a class="btn btn-primary mt-auto" href="recipe_detail.php?id=<?php echo (int)$recipe['RecipeID']; ?>">View details</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="mb-5">
        <div class="section-heading">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-globe"></i> Popular cuisines</span>
                <h2 class="section-title">Where the table travels</h2>
            </div>
        </div>
        <?php if (empty($popularCuisines)): ?>
            <div class="empty-state">Cuisine types will appear here as recipes are added.</div>
        <?php else: ?>
            <div class="dashboard-grid">
                <?php foreach ($popularCuisines as $cuisine): ?>
                    <a class="stat-card text-reset" href="search.php?cuisine=<?php echo urlencode((string)$cuisine['CuisineType']); ?>">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-value fs-2"><?php echo (int)$cuisine['Total']; ?></div>
                                <div class="stat-label"><?php echo e($cuisine['CuisineType']); ?></div>
                            </div>
                            <span class="brand-mark"><i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="content-card">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="eyebrow"><i class="fa-solid fa-seedling"></i> About</span>
                <h2 class="section-title mt-2">Built for recipes with context.</h2>
                <p class="section-copy mb-0">Cultural Cuisine Explorer connects each dish with ingredients, origins, festivals, significance, reviews, and personal saved lists. It is designed to feel like a real food culture product while keeping the original PHP and MySQL foundation intact.</p>
            </div>
            <div class="col-lg-5">
                <div class="info-list">
                    <div class="info-item"><strong>Explore</strong> Search recipes by name, culture, tags, and rating.</div>
                    <div class="info-item"><strong>Document</strong> Keep ingredient and cultural detail records organized.</div>
                    <div class="info-item"><strong>Personalize</strong> Save recipes and update your profile.</div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
