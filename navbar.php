<?php
$activePage = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? ''));
$navItems = [
    ['homepage.php', 'Home', 'fa-house'],
    ['search.php', 'Browse', 'fa-magnifying-glass'],
    ['Recipe.php', 'Recipes', 'fa-utensils'],
    ['Ingredients.php', 'Ingredients', 'fa-carrot'],
    ['CulturalDetails.php', 'Culture', 'fa-earth-asia'],
    ['recipetags.php', 'Tags', 'fa-tags'],
    ['reviews.php', 'Reviews', 'fa-star'],
    ['savedrecipe.php', 'Saved', 'fa-bookmark'],
];
?>
<nav class="navbar navbar-expand-lg app-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand" href="homepage.php">
            <span class="brand-mark"><i class="fa-solid fa-bowl-food"></i></span>
            <span><?php echo APP_NAME; ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <?php if (is_logged_in()): ?>
                    <?php foreach ($navItems as [$href, $label, $icon]): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $activePage === strtolower($href) ? 'active' : ''; ?>" href="<?php echo e($href); ?>">
                                <i class="fa-solid <?php echo e($icon); ?>"></i><?php echo e($label); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-avatar dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user"></i> Account
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-id-card me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="changepassword.php"><i class="fa-solid fa-key me-2"></i>Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="search.php"><i class="fa-solid fa-magnifying-glass"></i>Browse</a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-sm ms-lg-2" href="index.php">Sign in</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
