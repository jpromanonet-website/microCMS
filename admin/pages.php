<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\Content;

Auth::requireLogin();
$pages = Content::pages();

cms_layout_start('Pages', 'pages');
?>
<section class="panel">
    <h1>Pages</h1>
    <p class="lead">System pages keep their specialized templates. Custom pages use the Ventures-style catalog.</p>
    <div class="actions">
        <a class="btn btn--primary" href="page-new.php">New page</a>
    </div>
</section>

<section class="panel">
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
        <?php foreach ($pages as $page): ?>
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
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php cms_layout_end(); ?>
