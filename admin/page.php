<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\Content;

Auth::requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$page = Content::pageById($id);
if (!$page) {
    cms_flash('Page not found.', 'error');
    header('Location: pages.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    cms_require_post();
    $action = (string) ($_POST['action'] ?? 'save');

    try {
        if ($action === 'delete_page') {
            Content::deletePage($id, MICROCMS_SITE_ROOT);
            cms_flash('Page deleted.');
            header('Location: pages.php');
            exit;
        }

        if ($action === 'delete_card') {
            Content::deleteCard((int) ($_POST['card_id'] ?? 0));
            cms_flash('Element deleted.');
            header('Location: page.php?id=' . $id);
            exit;
        }

        Content::updatePage($id, [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'eyebrow' => $_POST['eyebrow'] ?? '',
            'noun' => $_POST['noun'] ?? 'items',
            'show_in_nav' => (string) ($_POST['show_in_nav'] ?? '1') === '1',
        ]);
        cms_flash('Page updated.');
        header('Location: page.php?id=' . $id);
        exit;
    } catch (Throwable $e) {
        cms_flash($e->getMessage(), 'error');
        header('Location: page.php?id=' . $id);
        exit;
    }
}

$cards = Content::cardsForPage($id);
$slug = (string) $page['slug'];
$type = (string) $page['card_type'];
$isSystem = (int) $page['is_system'] === 1;
$mediaSection = $slug === 'portfolio' ? 'portfolio' : $slug;

cms_layout_start((string) $page['title'], 'pages');
?>
<section class="panel">
    <h1><?= cms_e((string) $page['title']) ?></h1>
    <p class="lead">
        <code>/<?= cms_e($slug) ?>/</code>
        · <?= $isSystem ? 'system page' : 'custom catalog' ?>
        · card type <strong><?= cms_e($type) ?></strong>
    </p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <input type="hidden" name="action" value="save" />
        <div class="form-grid">
            <label>Title
                <input type="text" name="title" value="<?= cms_e((string) $page['title']) ?>" required />
            </label>
            <label>Eyebrow
                <input type="text" name="eyebrow" value="<?= cms_e((string) $page['eyebrow']) ?>" />
            </label>
            <label class="full">Description
                <input type="text" name="description" value="<?= cms_e((string) $page['description']) ?>" />
            </label>
            <label>Count noun
                <input type="text" name="noun" value="<?= cms_e((string) $page['noun']) ?>" />
            </label>
            <label>Show in navbar
                <select name="show_in_nav">
                    <option value="1" <?= (int) $page['show_in_nav'] === 1 ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= (int) $page['show_in_nav'] === 0 ? 'selected' : '' ?>>No</option>
                </select>
            </label>
        </div>
        <div class="actions">
            <button class="btn btn--primary" type="submit">Save page</button>
            <a class="btn btn--ghost" href="card-edit.php?page_id=<?= $id ?>">Add element</a>
            <a class="btn btn--ghost" href="pages.php">Back</a>
        </div>
    </form>
    <?php if (!$isSystem): ?>
        <form method="post" onsubmit="return confirm('Delete this custom page and its elements?');" style="margin-top:1rem">
            <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
            <input type="hidden" name="action" value="delete_page" />
            <button class="btn btn--danger" type="submit">Delete page</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Elements (<?= count($cards) ?>)</h2>
    <table>
        <thead>
            <tr>
                <th></th>
                <th>Title</th>
                <th>Category</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$cards): ?>
            <tr><td colspan="4">No elements yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($cards as $card):
            $img = (string) $card['image_src'];
        ?>
            <tr>
                <td>
                    <?php if ($img !== ''): ?>
                        <img class="thumb" src="media.php?section=<?= cms_e($mediaSection) ?>&file=<?= cms_e($img) ?>" alt="" />
                    <?php endif; ?>
                </td>
                <td><?= cms_e((string) $card['title']) ?></td>
                <td><?= cms_e((string) $card['category']) ?></td>
                <td style="white-space:nowrap">
                    <a href="card-edit.php?page_id=<?= $id ?>&id=<?= (int) $card['id'] ?>">Edit</a>
                    ·
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this element?');">
                        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
                        <input type="hidden" name="action" value="delete_card" />
                        <input type="hidden" name="card_id" value="<?= (int) $card['id'] ?>" />
                        <button class="btn btn--danger" type="submit" style="padding:0.2rem 0.45rem;font-size:0.8rem">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php cms_layout_end(); ?>
