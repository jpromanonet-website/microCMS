<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\Content;

Auth::requireLogin();

$keys = [
    'name', 'short', 'tagline', 'email', 'phone',
    'blog', 'linkedin', 'github', 'x', 'instagram',
    'ga_id', 'medium_feed', 'medium_user_id',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    cms_require_post();
    $values = [];
    foreach ($keys as $key) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    Content::saveSettings($values);
    cms_flash('Settings saved.');
    header('Location: settings.php');
    exit;
}

$settings = Content::settings();
cms_layout_start('Settings', 'settings');
?>
<section class="panel">
    <h1>Site settings</h1>
    <p class="lead">Contact, social links, and site identity. Used across header, footer, and contact.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <div class="form-grid">
            <label>Display name
                <input type="text" name="name" value="<?= cms_e($settings['name'] ?? '') ?>" required />
            </label>
            <label>Short brand
                <input type="text" name="short" value="<?= cms_e($settings['short'] ?? '') ?>" required />
            </label>
            <label class="full">Tagline
                <input type="text" name="tagline" value="<?= cms_e($settings['tagline'] ?? '') ?>" />
            </label>
            <label>Email
                <input type="email" name="email" value="<?= cms_e($settings['email'] ?? '') ?>" />
            </label>
            <label>Phone
                <input type="text" name="phone" value="<?= cms_e($settings['phone'] ?? '') ?>" placeholder="+54 …" />
            </label>
            <label>LinkedIn
                <input type="url" name="linkedin" value="<?= cms_e($settings['linkedin'] ?? '') ?>" />
            </label>
            <label>GitHub
                <input type="url" name="github" value="<?= cms_e($settings['github'] ?? '') ?>" />
            </label>
            <label>X / Twitter
                <input type="url" name="x" value="<?= cms_e($settings['x'] ?? '') ?>" />
            </label>
            <label>Instagram
                <input type="url" name="instagram" value="<?= cms_e($settings['instagram'] ?? '') ?>" />
            </label>
            <label>Blog / Medium
                <input type="url" name="blog" value="<?= cms_e($settings['blog'] ?? '') ?>" />
            </label>
            <label>Google Analytics ID
                <input type="text" name="ga_id" value="<?= cms_e($settings['ga_id'] ?? '') ?>" />
            </label>
            <label>Medium feed URL
                <input type="url" name="medium_feed" value="<?= cms_e($settings['medium_feed'] ?? '') ?>" />
            </label>
            <label>Medium user id
                <input type="text" name="medium_user_id" value="<?= cms_e($settings['medium_user_id'] ?? '') ?>" />
            </label>
        </div>
        <div class="actions">
            <button class="btn btn--primary" type="submit">Save settings</button>
        </div>
    </form>
</section>
<?php cms_layout_end(); ?>
