<?php
declare(strict_types=1);

/**
 * Generic catalog renderer (Ventures-style) for custom CMS pages.
 * Expected: $pageSlug set by the thin /{slug}/index.php stub, or inferred from folder.
 */

use MicroCMS\Content;

if (!isset($pageSlug) || !is_string($pageSlug) || $pageSlug === '') {
    $fromQuery = strtolower(trim((string) ($_GET['slug'] ?? '')));
    $fromQuery = preg_replace('/[^a-z0-9-]/', '', $fromQuery) ?? '';
    if ($fromQuery !== '') {
        $pageSlug = $fromQuery;
    } else {
        $pageSlug = basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
    }
}

$page = Content::pageBySlug($pageSlug);
if (!$page) {
    http_response_code(404);
    echo 'Page not found';
    exit;
}

$pageTitle = (string) $page['title'];
$pageDescription = (string) $page['description'];
$activeNav = (string) $page['slug'];
$noun = (string) ($page['noun'] ?: 'items');

$items = [];
foreach (Content::cardsForPage((int) $page['id']) as $card) {
    $items[] = Content::cardToLegacy('generic', $card);
}
$categories = unique_categories($items);

require APP_ROOT . '/includes/header.php';
render_page_header((string) $page['title'], '', (string) $page['eyebrow']);
?>

<main id="main" class="layout layout--with-sidebar">
    <div>
        <div class="toolbar">
            <div class="filter-row" role="group" aria-label="Filter by category">
                <button type="button" class="filter-btn is-active" data-filter="all">All</button>
                <?php foreach ($categories as $category): ?>
                    <button type="button" class="filter-btn" data-filter="<?= e(strtolower($category)) ?>"><?= e($category) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <p class="catalog-count" data-catalog-count data-noun="<?= e($noun) ?>" aria-live="polite"><?= count($items) ?> <?= e($noun) ?></p>

        <div class="catalog-grid" data-catalog>
            <?php foreach ($items as $item):
                $title = (string) ($item['title'] ?? 'Untitled');
                $category = (string) ($item['category'] ?? '');
                $image = (string) ($item['imageSrc'] ?? '');
                $link = (string) ($item['url'] ?? '#');
                $search = strtolower($title . ' ' . $category);
            ?>
                <article
                    class="catalog-item"
                    data-item
                    data-category="<?= e(strtolower($category)) ?>"
                    data-search="<?= e($search) ?>"
                >
                    <div class="catalog-item__media">
                        <?php if ($image !== ''): ?>
                            <img src="<?= e(media_url($pageSlug, $image)) ?>" alt="<?= e($title) ?>" loading="lazy" />
                        <?php endif; ?>
                    </div>
                    <div class="catalog-item__body">
                        <?php if ($category !== ''): ?>
                            <span class="catalog-item__meta"><?= e($category) ?></span>
                        <?php endif; ?>
                        <h2 class="catalog-item__title"><?= e($title) ?></h2>
                        <div class="catalog-item__actions">
                            <?php if ($link !== '' && $link !== '#'): ?>
                                <a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer">Visit</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="catalog-empty is-hidden" data-catalog-empty>No items match that filter.</p>
    </div>

    <aside class="sidebar">
        <h2>On this site</h2>
        <?php render_sidebar_nav($activeNav); ?>
    </aside>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
