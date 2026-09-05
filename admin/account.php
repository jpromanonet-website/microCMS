<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;

Auth::requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    cms_require_post();
    try {
        Auth::updateAccount(
            (string) ($_POST['current_password'] ?? ''),
            (string) ($_POST['username'] ?? ''),
            (string) ($_POST['new_password'] ?? '')
        );
        cms_flash('Account updated.');
        header('Location: account.php');
        exit;
    } catch (Throwable $e) {
        cms_flash($e->getMessage(), 'error');
        header('Location: account.php');
        exit;
    }
}

cms_layout_start('Account', 'account');
?>
<section class="panel">
    <h1>Account</h1>
    <p class="lead">Change your admin username and password.</p>
    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <div class="form-grid">
            <label>Username
                <input type="text" name="username" value="<?= cms_e(Auth::username()) ?>" required minlength="3" autocomplete="username" />
            </label>
            <label>Current password
                <input type="password" name="current_password" required autocomplete="current-password" />
            </label>
            <label class="full">New password
                <input type="password" name="new_password" minlength="6" autocomplete="new-password" />
                <span class="hint">Leave blank to keep the current password. Minimum 6 characters if changing.</span>
            </label>
        </div>
        <div class="actions">
            <button class="btn btn--primary" type="submit">Save account</button>
            <a class="btn btn--ghost" href="index.php">Back</a>
        </div>
    </form>
</section>
<?php cms_layout_end(); ?>
