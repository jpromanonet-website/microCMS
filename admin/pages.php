<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\Content;

Auth::requireLogin();
$pages = Content::pages();
$lifestyleSlugs = Content::lifestyleSlugs();
$sitePages = [];
$hobbyPages = [];
foreach ($pages as $page) {
    if (in_array((string) $page['slug'], $lifestyleSlugs, true)) {
        $hobbyPages[] = $page;
    } else {
        $sitePages[] = $page;
    }
}

cms_layout_start('Pages', 'pages');

$renderPageRows = static function (array $list): void {
    foreach ($list as $page): ?>
            <tr>
                <td>
                    <?= cms_e((string) $page['title']) ?>
                    <?php if ((int) $page['is_system'] === 1): ?>
                        <span class="badge badge--muted">system</span>
                    <?php else: ?>
                        <span class="badge">custom</span>
                    <?php endif; ?>
                </td>
                <td><code>/<?= cms_e((string) $page['slug']) ?>/</code></td>
                <td><?= cms_e((string) $page['card_type']) ?></td>
                <td><?= (int) $page['nav_order'] ?></td>
                <td><a href="page.php?id=<?= (int) $page['id'] ?>">Manage</a></td>
            </tr>
    <?php endforeach;
};
?>
<section class="panel">
    <h1>Pages</h1>
    <p class="lead">System pages keep their specialized templates. Custom pages use the Ventures-style catalog.</p>
    <div class="actions">
        <a class="btn btn--primary" href="page-new.php">New page</a>
    </div>
</section>

<section class="panel">
    <h2 class="panel-subtitle">Site</h2>
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Element type</th>
                <th>Order</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($sitePages === []): ?>
            <tr><td colspan="5">No pages yet.</td></tr>
        <?php else: ?>
            <?php $renderPageRows($sitePages); ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</section>

<?php if ($hobbyPages !== []): ?>
<section class="panel">
    <h2 class="panel-subtitle">Hobbies</h2>
    <p class="lead" style="margin-top:0">Art, languages, music, sports, and cooking — edited here, shown under the Hobbies menu on the site.</p>
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Element type</th>
                <th>Order</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php $renderPageRows($hobbyPages); ?>
        </tbody>
    </table>
    </div>
</section>
<?php endif; ?>
<?php cms_layout_end(); ?>
