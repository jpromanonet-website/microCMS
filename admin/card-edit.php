<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\Content;

Auth::requireLogin();

$pageId = (int) ($_GET['page_id'] ?? $_POST['page_id'] ?? 0);
$cardId = (int) ($_GET['id'] ?? 0);
$page = Content::pageById($pageId);
if (!$page) {
    cms_flash('Page not found.', 'error');
    header('Location: pages.php');
    exit;
}

$card = $cardId ? Content::cardById($cardId) : null;
if ($cardId && (!$card || (int) $card['page_id'] !== $pageId)) {
    cms_flash('Element not found.', 'error');
    header('Location: page.php?id=' . $pageId);
    exit;
}

$type = (string) $page['card_type'];
$slug = (string) $page['slug'];
$mediaSection = $slug === 'portfolio' ? 'portfolio' : $slug;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    cms_require_post();

    $data = [
        'title' => $_POST['title'] ?? '',
        'category' => $_POST['category'] ?? '',
        'image_src' => $card['image_src'] ?? '',
        'url' => $_POST['url'] ?? '',
        'secondary_url' => $_POST['secondary_url'] ?? '',
        'brief' => $_POST['brief'] ?? '',
        'author' => $_POST['author'] ?? '',
        'status' => $_POST['status'] ?? '',
        'label' => $_POST['label'] ?? '',
        'description' => $_POST['description'] ?? '',
        'file_name' => $card['file_name'] ?? '',
        'lang' => $_POST['lang'] ?? '',
    ];

    if (!empty($_FILES['image']['name'])) {
        $upload = cms_handle_upload('image', $mediaSection);
        if ($upload['ok']) {
            $data['image_src'] = $upload['filename'];
        } else {
            cms_flash($upload['error'] ?? 'Image upload failed', 'error');
            header('Location: card-edit.php?page_id=' . $pageId . ($cardId ? '&id=' . $cardId : ''));
            exit;
        }
    }

    if ($type === 'resume' && !empty($_FILES['pdf']['name'])) {
        $upload = cms_handle_upload('pdf', 'pdfs', ['pdf']);
        if ($upload['ok']) {
            $data['file_name'] = $upload['filename'];
        } else {
            cms_flash($upload['error'] ?? 'PDF upload failed', 'error');
            header('Location: card-edit.php?page_id=' . $pageId . ($cardId ? '&id=' . $cardId : ''));
            exit;
        }
    }

    $savedId = Content::saveCard($cardId ?: null, $pageId, $data);
    cms_flash($cardId ? 'Element updated.' : 'Element created.');
    header('Location: card-edit.php?page_id=' . $pageId . '&id=' . $savedId);
    exit;
}

$c = $card ?? [
    'title' => '',
    'category' => '',
    'image_src' => '',
    'url' => '',
    'secondary_url' => '',
    'brief' => '',
    'author' => '',
    'status' => '',
    'label' => '',
    'description' => '',
    'file_name' => '',
    'lang' => '',
];

cms_layout_start(($cardId ? 'Edit' : 'New') . ' element', 'pages');
?>
<section class="panel">
    <h1><?= $cardId ? 'Edit element' : 'New element' ?></h1>
    <p class="lead">Page: <?= cms_e((string) $page['title']) ?> · type <?= cms_e($type) ?></p>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <input type="hidden" name="page_id" value="<?= $pageId ?>" />
        <div class="form-grid">
            <label class="full">Title
                <input type="text" name="title" value="<?= cms_e((string) $c['title']) ?>" required />
            </label>

            <?php if ($type !== 'resume'): ?>
                <label class="full">Category / filter
                    <input type="text" name="category" value="<?= cms_e((string) $c['category']) ?>" />
                </label>
            <?php else: ?>
                <label>Language code
                    <input type="text" name="lang" value="<?= cms_e((string) $c['lang']) ?>" placeholder="es" />
                </label>
                <label>Label
                    <input type="text" name="label" value="<?= cms_e((string) $c['label']) ?>" placeholder="Español" />
                </label>
            <?php endif; ?>

            <?php if ($type === 'project'): ?>
                <label>Live URL
                    <input type="text" name="url" value="<?= cms_e((string) $c['url']) ?>" placeholder="https://… or #" />
                </label>
                <label>GitHub URL
                    <input type="text" name="secondary_url" value="<?= cms_e((string) $c['secondary_url']) ?>" />
                </label>
            <?php elseif ($type === 'book'): ?>
                <label>Buying link
                    <input type="text" name="url" value="<?= cms_e((string) $c['url']) ?>" />
                </label>
                <label>Author
                    <input type="text" name="author" value="<?= cms_e((string) $c['author']) ?>" />
                </label>
                <label>Status
                    <input type="text" name="status" value="<?= cms_e((string) $c['status']) ?>" placeholder="coming_soon" />
                </label>
                <label class="full">Brief
                    <textarea name="brief"><?= cms_e((string) ($c['brief'] ?? '')) ?></textarea>
                </label>
            <?php elseif ($type === 'resume'): ?>
                <label class="full">Description
                    <textarea name="description"><?= cms_e((string) ($c['description'] ?? '')) ?></textarea>
                </label>
                <div class="full file-field">
                    <span class="file-field__label">Upload PDF</span>
                    <div class="file-drop" data-file-drop>
                        <div class="file-drop__icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 16V4M12 4l-4 4M12 4l4 4M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="file-drop__copy">
                            <span class="file-drop__title">Drop a PDF here or browse</span>
                            <span class="file-drop__name" data-file-name data-empty="<?= !empty($c['file_name']) ? 'Current: ' . cms_e((string) $c['file_name']) : 'No file selected' ?>"><?= !empty($c['file_name']) ? 'Current: ' . cms_e((string) $c['file_name']) : 'No file selected' ?></span>
                            <span class="file-drop__hint">PDF only</span>
                        </div>
                        <input type="file" name="pdf" accept="application/pdf" />
                    </div>
                </div>
            <?php else: ?>
                <label class="full">URL
                    <input type="text" name="url" value="<?= cms_e((string) $c['url']) ?>" placeholder="https://…" />
                </label>
            <?php endif; ?>

            <?php if ($type !== 'resume'): ?>
                <div class="full file-field">
                    <span class="file-field__label">Upload image</span>
                    <div class="file-drop" data-file-drop>
                        <div class="file-drop__icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 16V4M12 4l-4 4M12 4l4 4M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="file-drop__copy">
                            <span class="file-drop__title">Drop an image here or browse</span>
                            <span class="file-drop__name" data-file-name data-empty="<?= !empty($c['image_src']) ? 'Current: ' . cms_e((string) $c['image_src']) : 'No file selected' ?>"><?= !empty($c['image_src']) ? 'Current: ' . cms_e((string) $c['image_src']) : 'No file selected' ?></span>
                            <span class="file-drop__hint">PNG, JPG, WebP, GIF or SVG</span>
                        </div>
                        <input type="file" name="image" accept="image/*" />
                    </div>
                    <?php if (!empty($c['image_src'])): ?>
                        <div class="file-preview">
                            <img src="media.php?section=<?= cms_e($mediaSection) ?>&file=<?= cms_e((string) $c['image_src']) ?>" alt="" />
                            <span class="hint">Current image preview</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="actions">
            <button class="btn btn--primary" type="submit">Save element</button>
            <a class="btn btn--ghost" href="page.php?id=<?= $pageId ?>">Back to page</a>
        </div>
    </form>
</section>
<?php cms_layout_end(); ?>
