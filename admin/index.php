<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\Content;
use MicroCMS\Database;

Auth::requireLogin();

$pages = Content::pages();
$pdo = Database::pdo();
$elements = (int) $pdo->query('SELECT COUNT(*) FROM cards')->fetchColumn();
$withImage = (int) $pdo->query("SELECT COUNT(*) FROM cards WHERE image_src IS NOT NULL AND image_src != ''")->fetchColumn();
$mediaFiles = 0;
try {
    $mediaFiles = (int) $pdo->query('SELECT COUNT(*) FROM media_files')->fetchColumn();
} catch (Throwable $e) {
    $mediaFiles = 0;
}

$perPage = [];
$maxCount = 1;
foreach ($pages as $page) {
    $count = count(Content::cardsForPage((int) $page['id']));
    $perPage[] = [
        'title' => (string) $page['title'],
        'slug' => (string) $page['slug'],
        'count' => $count,
        'custom' => (int) $page['is_system'] === 0,
        'id' => (int) $page['id'],
    ];
    $maxCount = max($maxCount, $count);
}

// Donut slices from per-page counts (skip empty to keep chart readable, but include if all empty)
$donutSource = array_values(array_filter($perPage, static fn(array $p): bool => $p['count'] > 0));
if ($donutSource === []) {
    $donutSource = $perPage;
}
$palette = ['#3d8bfd', '#38b2ac', '#ecc94b', '#68d391', '#f6ad55', '#b794f4', '#f687b3', '#76e4f7', '#a0aec0'];
$totalForDonut = max(1, array_sum(array_column($donutSource, 'count')));

$donut = [];
$offset = 0.0;
foreach ($donutSource as $i => $row) {
    $value = $row['count'] > 0 ? $row['count'] : 0;
    $pct = ($value / $totalForDonut) * 100;
    $donut[] = [
        'label' => $row['title'],
        'count' => $value,
        'pct' => $pct,
        'color' => $palette[$i % count($palette)],
        'offset' => $offset,
    ];
    $offset += $pct;
}

$coverage = $elements > 0 ? (int) round(($withImage / $elements) * 100) : 0;

cms_layout_start('Dashboard', 'dashboard');
?>
<section class="panel">
    <h1>Dashboard</h1>
    <p class="lead">Overview of your site content — pages, elements, and media.</p>
    <div class="grid-cards">
        <div class="stat"><strong><?= count($pages) ?></strong><span>Pages</span></div>
        <div class="stat"><strong><?= $elements ?></strong><span>Elements</span></div>
        <div class="stat"><strong><?= $coverage ?>%</strong><span>With image</span></div>
        <div class="stat"><strong><?= $mediaFiles ?></strong><span>CMS uploads</span></div>
    </div>
    <div class="actions">
        <a class="btn btn--primary" href="pages.php">Manage pages</a>
        <a class="btn btn--ghost" href="home.php">Edit home</a>
        <a class="btn btn--ghost" href="settings.php">Site settings</a>
        <a class="btn btn--ghost" href="page-new.php">New page</a>
    </div>
</section>

<section class="charts">
    <div class="panel chart-panel">
        <h2>Elements per page</h2>
        <p class="lead">How content is distributed across catalogs.</p>
        <div class="bar-chart" role="img" aria-label="Bar chart of elements per page">
            <?php foreach ($perPage as $i => $row):
                $width = $maxCount > 0 ? round(($row['count'] / $maxCount) * 100) : 0;
                $color = $palette[$i % count($palette)];
            ?>
                <a class="bar-row" href="page.php?id=<?= (int) $row['id'] ?>">
                    <span class="bar-row__label"><?= cms_e($row['title']) ?></span>
                    <span class="bar-row__track">
                        <span class="bar-row__fill" style="--bar-w: <?= $width ?>%; --bar-color: <?= cms_e($color) ?>"></span>
                    </span>
                    <span class="bar-row__value"><?= (int) $row['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel chart-panel">
        <h2>Share by section</h2>
        <p class="lead">Relative weight of each page in the catalog.</p>
        <div class="donut-wrap">
            <div
                class="donut"
                style="background: conic-gradient(<?php
                    $parts = [];
                    $cursor = 0.0;
                    foreach ($donut as $slice) {
                        $start = $cursor;
                        $end = $cursor + $slice['pct'];
                        $parts[] = $slice['color'] . ' ' . $start . '% ' . $end . '%';
                        $cursor = $end;
                    }
                    if ($elements === 0) {
                        echo 'var(--line) 0% 100%';
                    } else {
                        echo implode(', ', $parts);
                    }
                ?>)"
                role="img"
                aria-label="Donut chart of content share"
            >
                <div class="donut__hole">
                    <strong><?= $elements ?></strong>
                    <span>total</span>
                </div>
            </div>
            <ul class="donut-legend">
                <?php foreach ($donut as $slice): ?>
                    <li>
                        <span class="swatch" style="background: <?= cms_e($slice['color']) ?>"></span>
                        <span class="donut-legend__label"><?= cms_e($slice['label']) ?></span>
                        <span class="donut-legend__meta"><?= (int) $slice['count'] ?> · <?= $elements > 0 ? round($slice['pct']) : 0 ?>%</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>

<section class="panel">
    <h2>Media coverage</h2>
    <p class="lead">Elements that already have an image attached.</p>
    <div class="meter" aria-label="Image coverage <?= $coverage ?>%">
        <div class="meter__fill" style="--meter: <?= $coverage ?>%"></div>
    </div>
    <div class="meter-meta">
        <span><?= $withImage ?> with image</span>
        <span><?= max(0, $elements - $withImage) ?> without</span>
        <span><?= $mediaFiles ?> uploaded via CMS</span>
    </div>
</section>

<section class="panel">
    <h2>Pages</h2>
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Type</th>
                <th>Elements</th>
                <th>Nav</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($perPage as $row): ?>
            <?php
            $pageMeta = null;
            foreach ($pages as $p) {
                if ((int) $p['id'] === (int) $row['id']) {
                    $pageMeta = $p;
                    break;
                }
            }
            ?>
            <tr>
                <td><?= cms_e($row['title']) ?></td>
                <td><code>/<?= cms_e($row['slug']) ?>/</code></td>
                <td>
                    <?php if (!$row['custom']): ?>
                        <span class="badge badge--muted">system</span>
                    <?php else: ?>
                        <span class="badge">custom</span>
                    <?php endif; ?>
                </td>
                <td><?= (int) $row['count'] ?></td>
                <td><?= $pageMeta && (int) $pageMeta['show_in_nav'] === 1 ? 'yes' : 'no' ?></td>
                <td><a href="page.php?id=<?= (int) $row['id'] ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php cms_layout_end(); ?>
