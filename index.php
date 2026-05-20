<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    require_csrf();

    $identifier = clean_text($_POST['identifier'] ?? '', 120);
    $password = (string)($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        flash('danger', 'Enter your email or username and password.');
        redirect('index.php');
    }

    $user = fetch_one_prepared(
        'SELECT ' . user_select_columns('u') . ' FROM users u WHERE u.Email = ? OR u.Username = ? LIMIT 1',
        'ss',
        [$identifier, $identifier]
    );

    if ($user && verify_password_compat($password, $user['PasswordHash'] ?? null)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['UserID'];

        if (password_needs_upgrade($user['PasswordHash'] ?? null)) {
            upgrade_user_password_hash((int)$user['UserID'], $password);
        }

        $destination = $_SESSION['redirect_after_login'] ?? 'homepage.php';
        unset($_SESSION['redirect_after_login']);
        flash('success', 'Welcome back, ' . ($user['Username'] ?? 'explorer') . '.');
        redirect($destination);
    }

    flash('danger', 'Invalid username/email or password.');
    redirect('index.php');
}

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('homepage.php');
}

$authTab = $_SESSION['auth_tab'] ?? 'login';
unset($_SESSION['auth_tab']);
$pageTitle = 'Sign in | ' . APP_NAME;
$bodyClass = 'auth-page';
$hideNav = true;
$hideFooter = true;
require __DIR__ . '/header.php';
?>

<div class="auth-layout">
    <section class="auth-copy">
        <span class="eyebrow"><i class="fa-solid fa-pepper-hot"></i> Cultural food stories</span>
        <h1 class="display-title mt-3">Explore cuisine through flavor, memory, and place.</h1>
        <p class="section-copy">A polished recipe and culture hub for browsing dishes, saving favorites, reviewing meals, and managing the details behind every tradition.</p>
        <div class="hero-panel">
            <span class="meta-pill"><i class="fa-solid fa-utensils"></i> Recipes</span>
            <span class="meta-pill"><i class="fa-solid fa-earth-asia"></i> Cultural history</span>
            <span class="meta-pill"><i class="fa-solid fa-star"></i> Reviews</span>
            <span class="meta-pill"><i class="fa-solid fa-bookmark"></i> Saved collections</span>
        </div>
    </section>

    <section class="auth-card-wrap">
        <div class="auth-card">
            <div class="d-flex align-items-center gap-2 mb-4">
                <span class="brand-mark"><i class="fa-solid fa-bowl-food"></i></span>
                <div>
                    <div class="fw-black fw-bold"><?php echo APP_NAME; ?></div>
                    <div class="small text-muted">Cuisine Explorer</div>
                </div>
            </div>

            <div id="signIn" class="auth-form <?php echo $authTab === 'signup' ? '' : 'active'; ?>">
                <h2 class="section-title fs-1">Welcome back</h2>
                <p class="muted-text mb-4">Sign in to manage recipes and keep your favorites close.</p>
                <form method="post" action="index.php" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label" for="identifier">Email or username</label>
                        <input class="form-control" type="text" id="identifier" name="identifier" autocomplete="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="loginPassword">Password</label>
                        <input class="form-control" type="password" id="loginPassword" name="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Sign in</button>
                </form>
                <div class="text-center mt-4">
                    <span class="muted-text">New here?</span>
                    <button class="btn btn-link fw-bold p-0 align-baseline" type="button" id="signUpButton">Create an account</button>
                </div>
            </div>

            <div id="signup" class="auth-form <?php echo $authTab === 'signup' ? 'active' : ''; ?>">
                <h2 class="section-title fs-1">Create account</h2>
                <p class="muted-text mb-4">Join the table and start saving food traditions.</p>
                <form method="post" action="register.php" novalidate>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="signUp" value="1">
                    <div class="mb-3">
                        <label class="form-label" for="fName">Username</label>
                        <input class="form-control" type="text" name="fName" id="fName" maxlength="50" autocomplete="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="signupEmail">Email</label>
                        <input class="form-control" type="email" name="email" id="signupEmail" maxlength="100" autocomplete="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="signupPassword">Password</label>
                        <input class="form-control" type="password" name="password" id="signupPassword" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirmPassword">Confirm password</label>
                        <input class="form-control" type="password" name="confirm_password" id="confirmPassword" minlength="8" autocomplete="new-password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Sign up</button>
                </form>
                <div class="text-center mt-4">
                    <span class="muted-text">Already have an account?</span>
                    <button class="btn btn-link fw-bold p-0 align-baseline" type="button" id="signInButton">Sign in</button>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
