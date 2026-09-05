<?php
declare(strict_types=1);

namespace MicroCMS;

final class Content
{
    /** @var array<string, string>|null */
    private static ?array $settingsCache = null;

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $homeCache = null;

    public static function settings(): array
    {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }

        $rows = Database::pdo()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
        self::$settingsCache = $out;
        return $out;
    }

    public static function setting(string $key, string $default = ''): string
    {
        $all = self::settings();
        return $all[$key] ?? $default;
    }

    /** @param array<string, string> $values */
    public static function saveSettings(array $values): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($values as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        self::$settingsCache = null;
    }

    /** @return array<string, array<string, mixed>> */
    public static function homeBlocks(): array
    {
        if (self::$homeCache !== null) {
            return self::$homeCache;
        }

        $rows = Database::pdo()->query('SELECT block_key, content_json FROM home_blocks')->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $decoded = json_decode((string) $row['content_json'], true);
            $out[(string) $row['block_key']] = is_array($decoded) ? $decoded : [];
        }
        self::$homeCache = $out;
        return $out;
    }

    public static function homeBlock(string $key): array
    {
        $blocks = self::homeBlocks();
        return $blocks[$key] ?? [];
    }

    /** @param array<string, mixed> $content */
    public static function saveHomeBlock(string $key, array $content): void
    {
        $allowed = ['hero', 'about', 'signals', 'skills', 'contact'];
        if (!in_array($key, $allowed, true)) {
            throw new \InvalidArgumentException('Unknown home block');
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO home_blocks (block_key, content_json) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE content_json = VALUES(content_json)'
        );
        $stmt->execute([$key, json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        self::$homeCache = null;
    }

    /** @return list<array<string, mixed>> */
    public static function pages(bool $navOnly = false): array
    {
        $sql = 'SELECT * FROM pages';
        if ($navOnly) {
            $sql .= ' WHERE show_in_nav = 1';
        }
        $sql .= ' ORDER BY nav_order ASC, title ASC';
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function pageBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function pageById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Build public nav: system pages (except resumes) + custom pages + resumes + teaching dropdown.
     *
     * @return list<array<string, mixed>>
     */
    public static function navItems(): array
    {
        $pages = self::pages(true);
        $beforeResumes = [];
        $resumes = null;

        foreach ($pages as $page) {
            if ($page['slug'] === 'resumes') {
                $resumes = $page;
                continue;
            }
            $beforeResumes[] = $page;
        }

        $items = [];
        foreach ($beforeResumes as $page) {
            $slug = (string) $page['slug'];
            $isSystem = (int) $page['is_system'] === 1;
            // Custom pages use /c/?slug=… because www-data cannot mkdir /{slug}/
            $path = $isSystem
                ? '/' . $slug . '/'
                : '/c/?slug=' . rawurlencode($slug);
            $items[] = [
                'label' => (string) $page['title'],
                'path' => $path,
                'key' => $slug,
            ];
        }

        if ($resumes) {
            $items[] = [
                'label' => (string) $resumes['title'],
                'path' => '/resumes/',
                'key' => 'resumes',
            ];
        }

        $items[] = [
            'label' => 'Teaching',
            'key' => 'teaching',
            'children' => [
                ['label' => 'Learning IA', 'url' => 'https://learningiaforfree.vercel.app/'],
                ['label' => 'Learning to Code', 'url' => 'https://learningtocodeforfree.vercel.app/'],
            ],
        ];

        return $items;
    }

    /** @return list<array<string, mixed>> */
    public static function cardsForPage(int $pageId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM cards WHERE page_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$pageId]);
        return $stmt->fetchAll();
    }

    public static function cardById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM cards WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Return cards in the legacy JSON shape expected by the public templates.
     *
     * @return list<array<string, mixed>>
     */
    public static function cardsAsLegacyJson(string $jsonName): array
    {
        $map = [
            'projects' => 'portfolio',
            'books' => 'books',
            'writing' => 'writing',
            'ventures' => 'ventures',
            'news' => 'news',
            'resumes' => 'resumes',
        ];
        $slug = $map[$jsonName] ?? $jsonName;
        $page = self::pageBySlug($slug);
        if (!$page) {
            return [];
        }

        $cards = self::cardsForPage((int) $page['id']);
        $type = (string) $page['card_type'];
        $out = [];

        foreach ($cards as $card) {
            $out[] = self::cardToLegacy($type, $card);
        }

        return $out;
    }

    /** @param array<string, mixed> $card */
    public static function cardToLegacy(string $type, array $card): array
    {
        switch ($type) {
            case 'project':
                return [
                    'title' => $card['title'],
                    'imageSrc' => $card['image_src'],
                    'liveUrl' => $card['url'] !== '' ? $card['url'] : '#',
                    'githubUrl' => $card['secondary_url'],
                    'category' => $card['category'],
                ];
            case 'book':
                return [
                    'title' => $card['title'],
                    'imageSrc' => $card['image_src'],
                    'brief' => (string) ($card['brief'] ?? ''),
                    'buyingLink' => $card['url'],
                    'category' => $card['category'],
                    'author' => $card['author'],
                    'price' => '',
                    'status' => $card['status'],
                ];
            case 'resume':
                return [
                    'title' => $card['title'],
                    'file' => $card['file_name'],
                    'lang' => $card['lang'],
                    'label' => $card['label'],
                    'description' => (string) ($card['description'] ?? ''),
                ];
            default:
                return [
                    'title' => $card['title'],
                    'imageSrc' => $card['image_src'],
                    'url' => $card['url'] !== '' ? $card['url'] : '#',
                    'category' => $card['category'],
                ];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function createPage(array $data, string $siteRoot): int
    {
        $slug = self::slugify((string) $data['slug']);
        if ($slug === '' || in_array($slug, [
            'microcms', 'admin', 'assets', 'includes', 'cache', 'tools', 'c',
            'portfolio', 'books', 'writing', 'ventures', 'news', 'resumes',
        ], true)) {
            throw new \InvalidArgumentException('Invalid slug');
        }
        if (self::pageBySlug($slug)) {
            throw new \InvalidArgumentException('Slug already exists');
        }

        // Insert custom pages just before resumes
        $resumeOrder = 900;
        $resume = self::pageBySlug('resumes');
        if ($resume) {
            $resumeOrder = (int) $resume['nav_order'];
        }
        $maxBefore = Database::pdo()->prepare(
            'SELECT COALESCE(MAX(nav_order), 0) FROM pages WHERE slug != ? AND nav_order < ?'
        );
        $maxBefore->execute(['resumes', $resumeOrder]);
        $navOrder = max(60, ((int) $maxBefore->fetchColumn()) + 10);
        if ($navOrder >= $resumeOrder) {
            $navOrder = $resumeOrder - 10;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO pages (slug, title, description, eyebrow, noun, card_type, is_system, show_in_nav, nav_order)
             VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?)'
        );
        $stmt->execute([
            $slug,
            trim((string) $data['title']),
            trim((string) ($data['description'] ?? '')),
            trim((string) ($data['eyebrow'] ?? 'Catalog')),
            trim((string) ($data['noun'] ?? 'items')) ?: 'items',
            'generic',
            $navOrder,
        ]);
        $id = (int) Database::pdo()->lastInsertId();

        // Best-effort: physical stubs help some hosts, but routing works without them
        // when www-data cannot write into the site root.
        try {
            self::ensurePageDirectory($siteRoot, $slug);
        } catch (Throwable $e) {
            // ignore
        }
        try {
            self::ensureMediaDirectory($siteRoot, $slug);
        } catch (Throwable $e) {
            // ignore
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function updatePage(int $id, array $data): void
    {
        $page = self::pageById($id);
        if (!$page) {
            throw new \InvalidArgumentException('Page not found');
        }

        $stmt = Database::pdo()->prepare(
            'UPDATE pages SET title = ?, description = ?, eyebrow = ?, noun = ?, show_in_nav = ? WHERE id = ?'
        );
        $stmt->execute([
            trim((string) $data['title']),
            trim((string) ($data['description'] ?? '')),
            trim((string) ($data['eyebrow'] ?? '')),
            trim((string) ($data['noun'] ?? 'items')) ?: 'items',
            !empty($data['show_in_nav']) ? 1 : 0,
            $id,
        ]);
    }

    public static function deletePage(int $id, string $siteRoot): void
    {
        $page = self::pageById($id);
        if (!$page) {
            return;
        }
        if ((int) $page['is_system'] === 1) {
            throw new \InvalidArgumentException('System pages cannot be deleted');
        }

        $slug = (string) $page['slug'];
        $stmt = Database::pdo()->prepare('DELETE FROM pages WHERE id = ?');
        $stmt->execute([$id]);

        $dir = $siteRoot . DIRECTORY_SEPARATOR . $slug;
        if (is_dir($dir)) {
            $index = $dir . DIRECTORY_SEPARATOR . 'index.php';
            if (is_file($index)) {
                unlink($index);
            }
            // Remove dir only if empty
            $left = scandir($dir);
            if (is_array($left) && count(array_diff($left, ['.', '..'])) === 0) {
                rmdir($dir);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function saveCard(?int $id, int $pageId, array $data): int
    {
        if ($id === null) {
            $sort = self::nextSortOrder($pageId);
        } else {
            $existing = self::cardById($id);
            $sort = (int) ($existing['sort_order'] ?? 0);
        }

        $fields = [
            trim((string) ($data['title'] ?? 'Untitled')) ?: 'Untitled',
            trim((string) ($data['category'] ?? '')),
            trim((string) ($data['image_src'] ?? '')),
            trim((string) ($data['url'] ?? '')),
            trim((string) ($data['secondary_url'] ?? '')),
            ($data['brief'] ?? null) !== null && $data['brief'] !== '' ? (string) $data['brief'] : null,
            trim((string) ($data['author'] ?? '')),
            trim((string) ($data['status'] ?? '')),
            trim((string) ($data['label'] ?? '')),
            ($data['description'] ?? null) !== null && $data['description'] !== '' ? (string) $data['description'] : null,
            trim((string) ($data['file_name'] ?? '')),
            trim((string) ($data['lang'] ?? '')),
            $sort,
        ];

        if ($id) {
            $stmt = Database::pdo()->prepare(
                'UPDATE cards SET title=?, category=?, image_src=?, url=?, secondary_url=?, brief=?, author=?,
                 status=?, label=?, description=?, file_name=?, lang=?, sort_order=? WHERE id=? AND page_id=?'
            );
            $stmt->execute([...$fields, $id, $pageId]);
            return $id;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO cards (
                page_id, title, category, image_src, url, secondary_url, brief, author, status,
                label, description, file_name, lang, sort_order
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$pageId, ...$fields]);
        return (int) Database::pdo()->lastInsertId();
    }

    /** Newest updates first (lower sort_order = earlier in the list). */
    public static function nextSortOrder(int $pageId): int
    {
        $countStmt = Database::pdo()->prepare('SELECT COUNT(*) FROM cards WHERE page_id = ?');
        $countStmt->execute([$pageId]);
        if ((int) $countStmt->fetchColumn() === 0) {
            return 0;
        }

        $stmt = Database::pdo()->prepare('SELECT MIN(sort_order) FROM cards WHERE page_id = ?');
        $stmt->execute([$pageId]);
        return ((int) $stmt->fetchColumn()) - 1;
    }

    /** Move wrongly-appended newest rows to the front (one-time / safe to repeat). */
    public static function promoteLatestAppendedElements(): void
    {
        $pages = Database::pdo()->query('SELECT id FROM pages')->fetchAll();
        foreach ($pages as $page) {
            $pageId = (int) $page['id'];
            $latest = Database::pdo()->prepare(
                'SELECT id, sort_order FROM cards WHERE page_id = ? ORDER BY id DESC LIMIT 1'
            );
            $latest->execute([$pageId]);
            $row = $latest->fetch();
            if (!$row) {
                continue;
            }

            $maxOrder = Database::pdo()->prepare('SELECT MAX(sort_order) FROM cards WHERE page_id = ?');
            $maxOrder->execute([$pageId]);
            $max = (int) $maxOrder->fetchColumn();

            // Only fix rows that were appended at the end with the old MAX+1 behavior
            if ((int) $row['sort_order'] !== $max) {
                continue;
            }

            $minOrder = Database::pdo()->prepare('SELECT MIN(sort_order) FROM cards WHERE page_id = ?');
            $minOrder->execute([$pageId]);
            $min = (int) $minOrder->fetchColumn();
            if ((int) $row['sort_order'] === $min) {
                continue; // already first
            }

            $upd = Database::pdo()->prepare('UPDATE cards SET sort_order = ? WHERE id = ?');
            $upd->execute([$min - 1, (int) $row['id']]);
        }
    }

    public static function deleteCard(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM cards WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function ensurePageDirectory(string $siteRoot, string $slug): void
    {
        $dir = $siteRoot . DIRECTORY_SEPARATOR . $slug;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $index = $dir . DIRECTORY_SEPARATOR . 'index.php';
        $php = <<<'PHP'
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$catalogCandidates = [
    dirname(__DIR__) . '/microCMS/public/catalog.php',
    dirname(__DIR__) . '/../microCMS/public/catalog.php',
];
foreach ($catalogCandidates as $catalog) {
    if (is_file($catalog)) {
        require $catalog;
        exit;
    }
}

http_response_code(500);
echo 'microCMS catalog renderer not found';
PHP;
        file_put_contents($index, $php);
    }

    public static function ensureMediaDirectory(string $siteRoot, string $slug): void
    {
        $dir = $siteRoot . '/assets/media/' . $slug;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public static function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    public static function clearCache(): void
    {
        self::$settingsCache = null;
        self::$homeCache = null;
    }
}
