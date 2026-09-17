<?php
declare(strict_types=1);

/**
 * Catalog renderer for CMS pages (custom + lifestyle).
 * Expected: $pageSlug set by the thin /{slug}/index.php stub.
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
$cardType = (string) ($page['card_type'] ?: 'generic');
$cards = Content::cardsForPage((int) $page['id']);

require APP_ROOT . '/includes/header.php';
render_page_header((string) $page['title'], '', (string) $page['eyebrow']);

$mediaSection = $pageSlug === 'portfolio' ? 'portfolio' : $pageSlug;
?>

<main id="main" class="layout layout--with-sidebar">
    <div>
        <?php if ($cardType === 'language'): ?>
            <?php
            $grouped = [];
            foreach ($cards as $card) {
                $lang = trim((string) ($card['category'] ?? ''));
                if ($lang === '') {
                    $lang = trim((string) ($card['label'] ?? ''));
                }
                if ($lang === '') {
                    $lang = 'Other';
                }
                $key = strtolower($lang);
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'name' => $lang,
                        'cards' => [],
                    ];
                }
                $grouped[$key]['cards'][] = $card;
            }

            uasort($grouped, static function (array $a, array $b): int {
                $diff = count($b['cards']) <=> count($a['cards']);
                return $diff !== 0 ? $diff : strcasecmp($a['name'], $b['name']);
            });
            ?>
            <p class="catalog-count"><?= count($grouped) ?> <?= e($noun) ?></p>
            <?php if ($grouped === []): ?>
                <p class="catalog-empty">No languages yet. Add them from the CMS.</p>
            <?php endif; ?>
            <?php foreach ($grouped as $group):
                $langName = (string) $group['name'];
                $langCards = $group['cards'];
                $certCount = count($langCards);
            ?>
                <section class="lang-section reveal">
                    <h2 class="lang-section__title">
                        <?= e($langName) ?>
                        <span class="lang-section__count">(<?= $certCount ?> <?= $certCount === 1 ? 'certificate' : 'certificates' ?>)</span>
                    </h2>
                    <div class="catalog-grid">
                        <?php foreach ($langCards as $card):
                            $title = (string) ($card['title'] ?? 'Certificate');
                            $image = (string) ($card['image_src'] ?? '');
                            $link = (string) ($card['url'] ?? '');
                            $imgSrc = $image !== '' ? media_url($mediaSection, $image) : '';
                        ?>
                            <article class="catalog-item">
                                <div
                                    class="catalog-item__media<?= $imgSrc !== '' ? ' catalog-item__media--zoomable' : '' ?>"
                                    <?= $imgSrc !== '' ? lightbox_data_attrs($imgSrc, $title, $link, '', 'View certificate') : '' ?>
                                >
                                    <?php if ($imgSrc !== ''): ?>
                                        <img src="<?= e($imgSrc) ?>" alt="<?= e($title) ?>" loading="lazy" />
                                    <?php else: ?>
                                        <div class="catalog-item__placeholder" aria-hidden="true"><?= e(strtoupper(substr($langName, 0, 1))) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="catalog-item__body">
                                    <span class="catalog-item__meta"><?= e($langName) ?></span>
                                    <h2 class="catalog-item__title"><?= e($title) ?></h2>
                                    <div class="catalog-item__actions">
                                        <?php if ($link !== '' && $link !== '#'): ?>
                                            <a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer">View certificate</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

        <?php elseif ($cardType === 'music'): ?>
            <p class="catalog-count"><?= count($cards) ?> <?= e($noun) ?></p>
            <?php if ($cards === []): ?>
                <p class="catalog-empty">No songs yet. Add SoundCloud links from the CMS.</p>
            <?php endif; ?>
            <div class="catalog-grid">
                <?php foreach ($cards as $card):
                    $title = (string) ($card['title'] ?? 'Untitled track');
                    $link = (string) ($card['url'] ?? '');
                ?>
                    <article class="catalog-item">
                        <div class="catalog-item__media catalog-item__media--icon" aria-hidden="true">
                            <span class="catalog-item__icon">♪</span>
                        </div>
                        <div class="catalog-item__body">
                            <span class="catalog-item__meta">Track</span>
                            <h2 class="catalog-item__title"><?= e($title) ?></h2>
                            <div class="catalog-item__actions">
                                <?php if ($link !== '' && $link !== '#'): ?>
                                    <a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer">SoundCloud</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php elseif ($cardType === 'podcast'): ?>
            <p class="catalog-count"><?= count($cards) ?> <?= e($noun) ?></p>
            <?php if ($cards === []): ?>
                <p class="catalog-empty">No podcast chapters yet. Add Spotify links from the CMS.</p>
            <?php endif; ?>
            <div class="catalog-grid">
                <?php foreach ($cards as $card):
                    $title = (string) ($card['title'] ?? 'Untitled chapter');
                    $link = (string) ($card['url'] ?? '');
                    $image = (string) ($card['image_src'] ?? '');
                    $imgSrc = $image !== '' ? media_url($mediaSection, $image) : '';
                ?>
                    <article class="catalog-item">
                        <div
                            class="catalog-item__media<?= $imgSrc !== '' ? ' catalog-item__media--zoomable' : ' catalog-item__media--icon' ?>"
                            <?= $imgSrc !== '' ? lightbox_data_attrs($imgSrc, $title, $link, '', 'Open on Spotify') : '' ?>
                        >
                            <?php if ($imgSrc !== ''): ?>
                                <img src="<?= e($imgSrc) ?>" alt="<?= e($title) ?>" loading="lazy" />
                            <?php else: ?>
                                <span class="catalog-item__icon">▶</span>
                            <?php endif; ?>
                        </div>
                        <div class="catalog-item__body">
                            <span class="catalog-item__meta">Chapter</span>
                            <h2 class="catalog-item__title"><?= e($title) ?></h2>
                            <div class="catalog-item__actions">
                                <?php if ($link !== '' && $link !== '#'): ?>
                                    <a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer">Spotify</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php elseif ($cardType === 'sport'): ?>
            <?php
            $kmCard = null;
            $exercises = [];
            foreach ($cards as $card) {
                if (($card['status'] ?? '') === 'km') {
                    $kmCard = $card;
                } else {
                    $exercises[] = $card;
                }
            }
            $kmValue = $kmCard ? (int) preg_replace('/[^\d]/', '', (string) ($kmCard['brief'] ?? '0')) : 0;
            ?>
            <div class="sport-stack">
                <article class="sport-hero reveal">
                    <span class="sport-hero__meta">Running</span>
                    <p class="sport-hero__value"><?= $kmValue ?></p>
                    <h2 class="sport-hero__title">Km ran</h2>
                    <p class="sport-hero__note">Total kilometers logged so far.</p>
                </article>

                <h3 class="sport-exercises__heading">Exercises</h3>
                <?php if ($exercises === []): ?>
                    <p class="catalog-empty">No exercises yet. Add them from the CMS.</p>
                <?php endif; ?>
                <div class="catalog-grid">
                    <?php foreach ($exercises as $card):
                        $title = (string) ($card['title'] ?? 'Exercise');
                        $count = (int) preg_replace('/[^\d]/', '', (string) ($card['brief'] ?? '0'));
                        $note = (string) ($card['description'] ?? '');
                    ?>
                        <article class="catalog-item">
                            <div class="catalog-item__media catalog-item__media--icon" aria-hidden="true">
                                <span class="catalog-item__icon catalog-item__icon--count"><?= $count ?></span>
                            </div>
                            <div class="catalog-item__body">
                                <span class="catalog-item__meta">Exercise</span>
                                <h2 class="catalog-item__title"><?= e($title) ?></h2>
                                <?php if ($note !== ''): ?>
                                    <p class="catalog-item__brief"><?= e($note) ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php elseif ($cardType === 'recipe'): ?>
            <p class="catalog-count"><?= count($cards) ?> <?= e($noun) ?></p>
            <?php if ($cards === []): ?>
                <p class="catalog-empty">No recipes yet. Add them from the CMS.</p>
            <?php endif; ?>
            <div class="catalog-grid">
                <?php foreach ($cards as $card):
                    $title = (string) ($card['title'] ?? 'Recipe');
                    $image = (string) ($card['image_src'] ?? '');
                    $recipe = (string) (($card['description'] ?? '') ?: ($card['brief'] ?? ''));
                    $imgSrc = $image !== '' ? media_url($mediaSection, $image) : '';
                ?>
                    <article class="catalog-item">
                        <div
                            class="catalog-item__media<?= $imgSrc !== '' ? ' catalog-item__media--zoomable' : '' ?>"
                            <?= $imgSrc !== '' ? lightbox_data_attrs($imgSrc, $title) : '' ?>
                        >
                            <?php if ($imgSrc !== ''): ?>
                                <img src="<?= e($imgSrc) ?>" alt="<?= e($title) ?>" loading="lazy" />
                            <?php endif; ?>
                        </div>
                        <div class="catalog-item__body">
                            <span class="catalog-item__meta">Recipe</span>
                            <h2 class="catalog-item__title"><?= e($title) ?></h2>
                            <?php if ($recipe !== ''): ?>
                                <div class="recipe-card__text"><?= nl2br(e($recipe)) ?></div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <?php
            $items = [];
            foreach ($cards as $card) {
                $items[] = Content::cardToLegacy($cardType === 'art' ? 'generic' : $cardType, $card);
            }
            $categories = unique_categories($items);
            ?>
            <div class="toolbar">
                <?php if ($categories): ?>
                    <div class="filter-row" role="group" aria-label="Filter by category">
                        <button type="button" class="filter-btn is-active" data-filter="all">All</button>
                        <?php foreach ($categories as $category): ?>
                            <button type="button" class="filter-btn" data-filter="<?= e(strtolower($category)) ?>"><?= e($category) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                        <div
                            class="catalog-item__media<?= $image !== '' ? ' catalog-item__media--zoomable' : '' ?>"
                            <?= $image !== '' ? lightbox_data_attrs(media_url($mediaSection, $image), $title, $link) : '' ?>
                        >
                            <?php if ($image !== ''): ?>
                                <img src="<?= e(media_url($mediaSection, $image)) ?>" alt="<?= e($title) ?>" loading="lazy" />
                            <?php endif; ?>
                        </div>
                        <div class="catalog-item__body">
                            <?php if ($category !== ''): ?>
                                <span class="catalog-item__meta"><?= e($category) ?></span>
                            <?php endif; ?>
                            <h2 class="catalog-item__title"><?= e($title) ?></h2>
                            <div class="catalog-item__actions">
                                <?php if ($link !== '' && $link !== '#'): ?>
                                    <a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer">View</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="catalog-empty is-hidden" data-catalog-empty>No items match that filter.</p>
        <?php endif; ?>
    </div>

    <aside class="sidebar">
        <h2>On this site</h2>
        <?php render_sidebar_nav($activeNav); ?>
    </aside>
</main>

<?php require APP_ROOT . '/includes/footer.php'; ?>
