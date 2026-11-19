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
        $roles = $_POST['signal_role'] ?? [];
        $names = $_POST['signal_name'] ?? [];
        $urls = $_POST['signal_url'] ?? [];
        $values = $_POST['signal_value'] ?? [];
        $items = [];
        if (is_array($labels)) {
            foreach ($labels as $i => $label) {
                $label = trim((string) $label);
                $kind = strtolower($label);

                // Base stays a single plain value, like before.
                if ($kind === 'base') {
                    $value = trim((string) ($values[$i] ?? ''));
                    if ($label === '' && $value === '') {
                        continue;
                    }
                    $items[] = [
                        'label' => $label !== '' ? $label : 'Base',
                        'value' => $value,
                        'entries' => $value !== ''
                            ? [['role' => '', 'name' => $value, 'url' => '']]
                            : [],
                    ];
                    continue;
                }

                $groupRoles = is_array($roles[$i] ?? null) ? $roles[$i] : [];
                $groupNames = is_array($names[$i] ?? null) ? $names[$i] : [];
                $groupUrls = is_array($urls[$i] ?? null) ? $urls[$i] : [];
                $max = max(count($groupRoles), count($groupNames), count($groupUrls));
                $entries = [];
                $allowRole = $kind === 'now';
                for ($j = 0; $j < $max; $j++) {
                    $role = $allowRole ? trim((string) ($groupRoles[$j] ?? '')) : '';
                    $name = trim((string) ($groupNames[$j] ?? ''));
                    $url = trim((string) ($groupUrls[$j] ?? ''));
                    if ($role === '' && $name === '' && $url === '') {
                        continue;
                    }
                    if ($name === '' && $role !== '') {
                        $name = $role;
                        $role = '';
                    }
                    $entries[] = [
                        'role' => $role,
                        'name' => $name,
                        'url' => $url,
                    ];
                }
                if ($label === '' && $entries === []) {
                    continue;
                }
                $items[] = [
                    'label' => $label,
                    'entries' => $entries,
                ];
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

/** @param array<string,mixed> $item */
$normalizeSignalForAdmin = static function (array $item): array {
    $label = trim((string) ($item['label'] ?? ''));
    $kind = strtolower($label);
    $entries = [];
    if (isset($item['entries']) && is_array($item['entries'])) {
        foreach ($item['entries'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $role = trim((string) ($entry['role'] ?? ''));
            $name = trim((string) ($entry['name'] ?? ''));
            $url = trim((string) ($entry['url'] ?? ''));
            if ($role === '' && $name === '' && $url === '') {
                continue;
            }
            $entries[] = ['role' => $role, 'name' => $name, 'url' => $url];
        }
    }

    if ($kind === 'base') {
        $value = trim((string) ($item['value'] ?? ''));
        if ($value === '' && $entries !== []) {
            $parts = [];
            foreach ($entries as $entry) {
                $name = trim((string) ($entry['name'] ?? ''));
                if ($name !== '') {
                    $parts[] = $name;
                }
            }
            $value = implode(', ', $parts);
        }
        return [
            'label' => $label !== '' ? $label : 'Base',
            'mode' => 'simple',
            'value' => $value,
            'entries' => [],
        ];
    }

    if ($entries === []) {
        $value = trim((string) ($item['value'] ?? ''));
        if ($value !== '') {
            $parts = preg_split('/\s*,\s*/u', $value) ?: [];
            foreach ($parts as $part) {
                $part = trim((string) $part);
                if ($part === '' || preg_match('/^and more$/iu', $part)) {
                    continue;
                }
                $role = '';
                $name = $part;
                if ($kind === 'now' && preg_match('/^(.+?)\s+at\s+(.+)$/iu', $part, $m)) {
                    $role = trim($m[1]);
                    $name = trim($m[2]);
                }
                $entries[] = ['role' => $role, 'name' => $name, 'url' => ''];
            }
        }
    }
    if ($entries === []) {
        $entries[] = ['role' => '', 'name' => '', 'url' => ''];
    }
    return [
        'label' => $label,
        'mode' => $kind === 'now' ? 'work' : 'project',
        'value' => '',
        'entries' => $entries,
    ];
};

$signalItems = [];
foreach (($signals['items'] ?? []) as $item) {
    if (is_array($item)) {
        $signalItems[] = $normalizeSignalForAdmin($item);
    }
}
while (count($signalItems) < 3) {
    $defaults = [
        ['label' => 'Now', 'mode' => 'work'],
        ['label' => 'Building', 'mode' => 'project'],
        ['label' => 'Base', 'mode' => 'simple'],
    ];
    $fallback = $defaults[count($signalItems)] ?? ['label' => '', 'mode' => 'project'];
    $signalItems[] = [
        'label' => $fallback['label'],
        'mode' => $fallback['mode'],
        'value' => $fallback['mode'] === 'simple' ? 'Buenos Aires, Argentina' : '',
        'entries' => $fallback['mode'] === 'simple' ? [] : [['role' => '', 'name' => '', 'url' => '']],
    ];
}

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
    <p class="hint" style="margin-top:-0.35rem">Now uses Role + Name + URL. Building is Name + URL only. Base stays a single value.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= cms_e(Auth::csrfToken()) ?>" />
        <input type="hidden" name="block" value="signals" />
        <?php foreach ($signalItems as $gi => $item):
            $mode = (string) ($item['mode'] ?? 'project');
        ?>
            <?php if ($mode === 'simple'): ?>
                <div class="list-editor" style="margin-top:0.85rem">
                    <div class="form-grid">
                        <label>Group label
                            <input type="text" name="signal_label[<?= (int) $gi ?>]" value="<?= cms_e((string) ($item['label'] ?? 'Base')) ?>" />
                        </label>
                        <label class="full">Value
                            <input type="text" name="signal_value[<?= (int) $gi ?>]" value="<?= cms_e((string) ($item['value'] ?? '')) ?>" placeholder="Buenos Aires, Argentina" />
                        </label>
                    </div>
                </div>
            <?php else:
                $withRole = $mode === 'work';
                $rowClass = $withRole ? 'list-editor__row list-editor__row--signal' : 'list-editor__row list-editor__row--signal-lite';
            ?>
                <div class="list-editor" data-list-editor style="margin-top:0.85rem">
                    <div class="list-editor__head">
                        <label class="list-editor__title" style="display:grid;gap:0.25rem;flex:1">
                            Group label
                            <input type="text" name="signal_label[<?= (int) $gi ?>]" value="<?= cms_e((string) ($item['label'] ?? '')) ?>" placeholder="Now / Building" />
                        </label>
                        <button
                            type="button"
                            class="btn btn--ghost btn--small"
                            data-list-add
                            data-list-name="signal"
                            aria-label="Add entry"
                        >+ Add</button>
                    </div>
                    <template data-list-template>
                        <div class="<?= cms_e($rowClass) ?>">
                            <?php if ($withRole): ?>
                                <input type="text" name="signal_role[<?= (int) $gi ?>][]" value="" placeholder="Role" />
                            <?php endif; ?>
                            <input type="text" name="signal_name[<?= (int) $gi ?>][]" value="" placeholder="Name" />
                            <input type="url" name="signal_url[<?= (int) $gi ?>][]" value="" placeholder="https://..." />
                            <button type="button" class="list-editor__remove" data-list-remove aria-label="Remove">&times;</button>
                        </div>
                    </template>
                    <div class="list-editor__rows" data-list-rows>
                        <?php foreach (($item['entries'] ?? []) as $entry): ?>
                            <div class="<?= cms_e($rowClass) ?>">
                                <?php if ($withRole): ?>
                                    <input type="text" name="signal_role[<?= (int) $gi ?>][]" value="<?= cms_e((string) ($entry['role'] ?? '')) ?>" placeholder="Role" />
                                <?php endif; ?>
                                <input type="text" name="signal_name[<?= (int) $gi ?>][]" value="<?= cms_e((string) ($entry['name'] ?? '')) ?>" placeholder="Name" />
                                <input type="url" name="signal_url[<?= (int) $gi ?>][]" value="<?= cms_e((string) ($entry['url'] ?? '')) ?>" placeholder="https://..." />
                                <button type="button" class="list-editor__remove" data-list-remove aria-label="Remove">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
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
