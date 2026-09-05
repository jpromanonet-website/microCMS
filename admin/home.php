<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\Content;

Auth::requireLogin();

$blockKeys = ['hero', 'about', 'signals', 'skills', 'contact'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    cms_require_post();
    $block = (string) ($_POST['block'] ?? '');
    if (!in_array($block, $blockKeys, true)) {
        cms_flash('Unknown block.', 'error');
        header('Location: home.php');
        exit;
    }

    if ($block === 'hero') {
        Content::saveHomeBlock('hero', [
            'kicker' => trim((string) ($_POST['kicker'] ?? '')),
            'subtitle' => trim((string) ($_POST['subtitle'] ?? '')),
            'primary_cta_label' => trim((string) ($_POST['primary_cta_label'] ?? '')),
            'primary_cta_url' => trim((string) ($_POST['primary_cta_url'] ?? 'linkedin')),
            'secondary_cta_label' => trim((string) ($_POST['secondary_cta_label'] ?? '')),
            'secondary_cta_path' => trim((string) ($_POST['secondary_cta_path'] ?? '/portfolio/')),
        ]);
    } elseif ($block === 'about') {
        $raw = (string) ($_POST['paragraphs'] ?? '');
        $paragraphs = array_values(array_filter(array_map('trim', preg_split("/\R\s*\R/u", $raw) ?: []), static fn($p) => $p !== ''));
        Content::saveHomeBlock('about', ['paragraphs' => $paragraphs]);
    } elseif ($block === 'signals') {
        $labels = $_POST['signal_label'] ?? [];
        $values = $_POST['signal_value'] ?? [];
        $items = [];
        if (is_array($labels) && is_array($values)) {
            foreach ($labels as $i => $label) {
                $label = trim((string) $label);
                $value = trim((string) ($values[$i] ?? ''));
                if ($label === '' && $value === '') {
                    continue;
                }
                $items[] = ['label' => $label, 'value' => $value];
            }
        }
        Content::saveHomeBlock('signals', ['items' => $items]);
    } elseif ($block === 'skills') {
        $titles = $_POST['group_title'] ?? [];
        $itemsRaw = $_POST['group_items'] ?? [];
        $groups = [];
        if (is_array($titles) && is_array($itemsRaw)) {
            foreach ($titles as $i => $title) {
                $title = trim((string) $title);
                $list = array_values(array_filter(array_map('trim', explode(',', (string) ($itemsRaw[$i] ?? '')))));
                if ($title === '' && $list === []) {
                    continue;
                }
                $groups[] = ['title' => $title, 'items' => $list];
            }
        }
        Content::saveHomeBlock('skills', [
            'lead' => trim((string) ($_POST['lead'] ?? '')),
            'groups' => $groups,
        ]);
    } elseif ($block === 'contact') {
        Content::saveHomeBlock('contact', [
            'lead' => trim((string) ($_POST['lead'] ?? '')),
        ]);
    }

    cms_flash('Home block saved.');
    header('Location: home.php#' . $block);
    exit;
}

$blocks = Content::homeBlocks();
$hero = $blocks['hero'] ?? [];
$about = $blocks['about'] ?? [];
$signals = $blocks['signals'] ?? [];
$skills = $blocks['skills'] ?? [];
$contact = $blocks['contact'] ?? [];

cms_layout_start('Home', 'home');
?>
<section class="panel">
    <h1>Home content</h1>
    <p class="lead">Sections stay fixed — you can edit copy only. Stats, Medium, and recent projects stay dynamic.</p>
</section>

<section class="panel" id="hero">
    <h2>Hero</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <input type="hidden" name="block" value="hero" />
        <div class="form-grid">
            <label class="full">Kicker
                <input type="text" name="kicker" value="<?= cms_e((string) ($hero['kicker'] ?? '')) ?>" />
            </label>
            <label class="full">Subtitle
                <textarea name="subtitle"><?= cms_e((string) ($hero['subtitle'] ?? '')) ?></textarea>
            </label>
            <label>Primary CTA label
                <input type="text" name="primary_cta_label" value="<?= cms_e((string) ($hero['primary_cta_label'] ?? '')) ?>" />
            </label>
            <label>Primary CTA target
                <select name="primary_cta_url">
                    <?php foreach (['linkedin', 'github', 'blog', 'email'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= (($hero['primary_cta_url'] ?? '') === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="hint">Maps to a setting URL / email</span>
            </label>
            <label>Secondary CTA label
                <input type="text" name="secondary_cta_label" value="<?= cms_e((string) ($hero['secondary_cta_label'] ?? '')) ?>" />
            </label>
            <label>Secondary CTA path
                <input type="text" name="secondary_cta_path" value="<?= cms_e((string) ($hero['secondary_cta_path'] ?? '/portfolio/')) ?>" />
            </label>
        </div>
        <div class="actions"><button class="btn btn--primary" type="submit">Save hero</button></div>
    </form>
</section>

<section class="panel" id="about">
    <h2>About</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <input type="hidden" name="block" value="about" />
        <label class="full">Paragraphs
            <span class="hint">Separate paragraphs with a blank line. Keep plain text (links stay managed in template for LinkedIn/portfolio/Medium).</span>
            <textarea name="paragraphs" style="min-height:220px"><?= cms_e(implode("\n\n", $about['paragraphs'] ?? [])) ?></textarea>
        </label>
        <div class="actions"><button class="btn btn--primary" type="submit">Save about</button></div>
    </form>
</section>

<section class="panel" id="signals">
    <h2>Signals (Now / Building / Base)</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <input type="hidden" name="block" value="signals" />
        <div class="form-grid">
            <?php
            $items = $signals['items'] ?? [['label' => '', 'value' => ''], ['label' => '', 'value' => ''], ['label' => '', 'value' => '']];
            while (count($items) < 3) {
                $items[] = ['label' => '', 'value' => ''];
            }
            foreach ($items as $item):
            ?>
                <label>Label
                    <input type="text" name="signal_label[]" value="<?= cms_e((string) ($item['label'] ?? '')) ?>" />
                </label>
                <label>Value
                    <input type="text" name="signal_value[]" value="<?= cms_e((string) ($item['value'] ?? '')) ?>" />
                </label>
            <?php endforeach; ?>
        </div>
        <div class="actions"><button class="btn btn--primary" type="submit">Save signals</button></div>
    </form>
</section>

<section class="panel" id="skills">
    <h2>Skills</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <input type="hidden" name="block" value="skills" />
        <label class="full">Lead
            <textarea name="lead"><?= cms_e((string) ($skills['lead'] ?? '')) ?></textarea>
        </label>
        <?php
        $groups = $skills['groups'] ?? [];
        while (count($groups) < 4) {
            $groups[] = ['title' => '', 'items' => []];
        }
        foreach ($groups as $group):
        ?>
            <div class="form-grid" style="margin-top:0.75rem">
                <label>Group title
                    <input type="text" name="group_title[]" value="<?= cms_e((string) ($group['title'] ?? '')) ?>" />
                </label>
                <label>Items (comma-separated)
                    <input type="text" name="group_items[]" value="<?= cms_e(implode(', ', $group['items'] ?? [])) ?>" />
                </label>
            </div>
        <?php endforeach; ?>
        <div class="actions"><button class="btn btn--primary" type="submit">Save skills</button></div>
    </form>
</section>

<section class="panel" id="contact">
    <h2>Contact section</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <input type="hidden" name="block" value="contact" />
        <label class="full">Lead
            <textarea name="lead"><?= cms_e((string) ($contact['lead'] ?? '')) ?></textarea>
        </label>
        <p class="hint">Email, phone, and social URLs are edited in Settings. Phone appears here when set.</p>
        <div class="actions"><button class="btn btn--primary" type="submit">Save contact</button></div>
    </form>
</section>
<?php cms_layout_end(); ?>
