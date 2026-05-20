<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $userId = (int)current_user_id();
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    $user = fetch_one_prepared('SELECT UserID, PasswordHash FROM users WHERE UserID = ? LIMIT 1', 'i', [$userId]);

    if (!$user || !verify_password_compat($currentPassword, $user['PasswordHash'] ?? null)) {
        flash('danger', 'Current password is incorrect.');
    } elseif (strlen($newPassword) < 8) {
        flash('danger', 'New password must be at least 8 characters.');
    } elseif ($newPassword !== $confirmPassword) {
        flash('danger', 'New password and confirmation do not match.');
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $ok = run_prepared('UPDATE users SET PasswordHash = ? WHERE UserID = ?', 'si', [$newHash, $userId]);
        flash($ok ? 'success' : 'danger', $ok ? 'Password updated successfully.' : 'Could not update password.');
        redirect('profile.php');
    }

    redirect('changepassword.php');
}

$pageTitle = 'Change Password | ' . APP_NAME;
require __DIR__ . '/header.php';
?>

<div class="page-shell">
    <section class="content-card mx-auto" style="max-width: 620px;">
        <span class="eyebrow"><i class="fa-solid fa-key"></i> Account security</span>
        <h1 class="section-title mt-2">Change password</h1>
        <p class="section-copy">Legacy MD5 passwords are upgraded automatically after successful sign-in or password change.</p>

        <form method="post" class="mt-4">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label" for="current_password">Current password</label>
                <input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="new_password">New password</label>
                <input class="form-control" type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="mb-4">
                <label class="form-label" for="confirm_password">Confirm new password</label>
                <input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="submit">Update password</button>
                <a class="btn btn-outline-secondary" href="profile.php">Back to profile</a>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
