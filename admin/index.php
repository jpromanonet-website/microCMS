<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\Content;
use MicroCMS\Database;

Auth::requireLogin();

$pages = Content::pages();
$elements = (int) Database::pdo()->query('SELECT COUNT(*) FROM cards')->fetchColumn();

cms_layout_start('Dashboard', 'dashboard');
?>
<section class="panel">
    <h1>Dashboard</h1>
    <p class="lead">Edit existing catalogs, home copy, contact links, or create new nav pages (Ventures-style).</p>
    <div class="grid-cards">
        <div class="stat"><strong><?= count($pages) ?></strong><span>Pages</span></div>
        <div class="stat"><strong><?= $elements ?></strong><span>Elements</span></div>
    </div>
    <div class="actions">
        <a class="btn btn--primary" href="pages.php">Manage pages</a>
        <a class="btn btn--ghost" href="home.php">Edit home</a>
        <a class="btn btn--ghost" href="settings.php">Site settings</a>
        <a class="btn btn--ghost" href="page-new.php">New page</a>
    </div>
</section>

<section class="panel">
    <h2>Pages</h2>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Type</th>
                <th>Nav</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pages as $page): ?>
            <tr>
                <td><?= cms_e((string) $page['title']) ?></td>
                <td><code>/<?= cms_e((string) $page['slug']) ?>/</code></td>
                <td>
                    <?php if ((int) $page['is_system'] === 1): ?>
                        <span class="badge badge--muted">system</span>
                    <?php else: ?>
                        <span class="badge">custom</span>
                    <?php endif; ?>
                </td>
                <td><?= (int) $page['show_in_nav'] === 1 ? 'yes' : 'no' ?></td>
                <td><a href="page.php?id=<?= (int) $page['id'] ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php cms_layout_end(); ?>
