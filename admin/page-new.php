<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\Content;

Auth::requireLogin();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    cms_require_post();
    try {
        $id = Content::createPage([
            'title' => $_POST['title'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'description' => $_POST['description'] ?? '',
            'eyebrow' => $_POST['eyebrow'] ?? 'Catalog',
            'noun' => $_POST['noun'] ?? 'items',
        ], MICROCMS_SITE_ROOT);
        cms_flash('Page created. Add elements below.');
        header('Location: page.php?id=' . $id);
        exit;
    } catch (Throwable $e) {
        cms_flash($e->getMessage(), 'error');
        header('Location: page-new.php');
        exit;
    }
}

cms_layout_start('New page', 'pages');
?>
<section class="panel">
    <h1>New page</h1>
    <p class="lead">Creates a Ventures-style catalog page and inserts it in the navbar before Resumes.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <div class="form-grid">
            <label>Title
                <input type="text" name="title" required placeholder="Talks" />
            </label>
            <label>Slug
                <input type="text" name="slug" required placeholder="talks" pattern="[a-z0-9\-]+" />
                <span class="hint">URL path: /slug/</span>
            </label>
            <label class="full">Description
                <input type="text" name="description" placeholder="Short meta description" />
            </label>
            <label>Eyebrow
                <input type="text" name="eyebrow" value="Catalog" />
            </label>
            <label>Count noun
                <input type="text" name="noun" value="items" placeholder="talks" />
            </label>
        </div>
        <div class="actions">
            <button class="btn btn--primary" type="submit">Create page</button>
            <a class="btn btn--ghost" href="pages.php">Cancel</a>
        </div>
    </form>
</section>
<?php cms_layout_end(); ?>
