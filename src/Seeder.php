<?php
declare(strict_types=1);

namespace MicroCMS;

final class Seeder
{
    public static function seedIfEmpty(string $siteRoot): void
    {
        $pdo = Database::pdo();

        self::seedAdmin($pdo);
        self::seedSettings($pdo);
        self::seedHome($pdo);
        self::seedPagesAndCards($pdo, $siteRoot);
    }

    private static function seedAdmin(\PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $user = Env::get('ADMIN_USER', 'admin') ?: 'admin';
        $pass = Env::get('ADMIN_PASS', 'changeme') ?: 'changeme';
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);
    }

    private static function seedSettings(\PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaults = [
            'name' => 'Juan P. Romano',
            'short' => 'JPR',
            'tagline' => 'Engineering Manager, builder, and writer based in Buenos Aires.',
            'email' => 'contact@jpromano.net',
            'phone' => '',
            'blog' => 'https://jpromanonet.medium.com',
            'linkedin' => 'https://www.linkedin.com/in/jpromanonet/',
            'github' => 'https://github.com/jpromanonet',
            'x' => 'https://x.com/jpromanonet',
            'instagram' => 'https://instagram.com/jpromanonet',
            'ga_id' => 'G-73GRBEG00T',
            'medium_feed' => 'https://medium.com/feed/@jpromanonet',
            'medium_user_id' => '768cb0ffbcaf',
        ];

        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');
        foreach ($defaults as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    }

    private static function seedHome(\PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM home_blocks')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $blocks = [
            'hero' => [
                'kicker' => 'Engineering Manager · Writer · Polyglot',
                'subtitle' => 'I ship products, lead teams, and write about software. Based in Buenos Aires — currently Engineering Manager at Hybrid Bee Technology.',
                'primary_cta_label' => 'Connect on LinkedIn',
                'primary_cta_url' => 'linkedin',
                'secondary_cta_label' => 'View portfolio',
                'secondary_cta_path' => '/portfolio/',
            ],
            'about' => [
                'paragraphs' => [
                    'I’m Juan, a software engineer and Engineering Manager based in Buenos Aires. I work across Java, Python (Django), C++, and JavaScript (Node, React, Angular, Vue), and I love turning messy problems into clean, shippable systems.',
                    'Right now I’m Engineering Manager at Hybrid Bee Technology, partnering with clients and building the custom tools that keep infrastructure projects moving. I’m also writing and building different ventures.',
                    'Before that I led front-end at OCA, managed engineering at Adviters, and directed software development at Andreani, plus years as a professor and technical writer for freeCodeCamp, Henry, and more. I’m a polyglot and a full-time nerd — in the best way.',
                ],
            ],
            'signals' => [
                'items' => [
                    ['label' => 'Now', 'value' => 'Engineering Manager · Hybrid Bee Technology'],
                    ['label' => 'Building', 'value' => 'Soup IT, Puestito, Mate Gestión and more'],
                    ['label' => 'Base', 'value' => 'Buenos Aires, Argentina'],
                ],
            ],
            'skills' => [
                'lead' => 'What I’m actively shipping with — more tools live in the drawer, but these get the most airtime.',
                'groups' => [
                    ['title' => 'Languages', 'items' => ['JavaScript', 'TypeScript', 'Python', 'Java', 'PHP', 'C#', 'C++', 'C', 'R', 'Ruby', 'Elixir', 'Perl', 'Scala']],
                    ['title' => 'Frameworks', 'items' => ['React', 'React Native', 'Vue', 'Angular', 'Node.js', 'Django', 'Flask', '.NET']],
                    ['title' => 'Style & UI', 'items' => ['HTML', 'CSS', 'Sass', 'Tailwind', 'Bootstrap']],
                    ['title' => 'Platforms & tools', 'items' => ['SQL', 'MySQL', 'Docker', 'Kubernetes', 'AWS', 'Azure', 'Linux', 'Git', 'NGINX', 'Apache', 'Jira']],
                ],
            ],
            'contact' => [
                'lead' => 'Ideas, collaborations, or a good conversation about building things.',
            ],
        ];

        $stmt = $pdo->prepare('INSERT INTO home_blocks (block_key, content_json) VALUES (?, ?)');
        foreach ($blocks as $key => $content) {
            $stmt->execute([$key, json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
    }

    private static function seedPagesAndCards(\PDO $pdo, string $siteRoot): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $systemPages = [
            [
                'slug' => 'portfolio',
                'title' => 'Portfolio',
                'description' => 'Selected projects by Juan P. Romano — web, desktop, games, and more.',
                'eyebrow' => 'Projects',
                'noun' => 'projects',
                'card_type' => 'project',
                'nav_order' => 10,
                'json' => 'projects',
            ],
            [
                'slug' => 'books',
                'title' => 'Books',
                'description' => 'Books by Juan P. Romano.',
                'eyebrow' => 'Writing',
                'noun' => 'books',
                'card_type' => 'book',
                'nav_order' => 20,
                'json' => 'books',
            ],
            [
                'slug' => 'writing',
                'title' => 'Writing',
                'description' => 'Articles and publications by Juan P. Romano — including automatic Medium posts.',
                'eyebrow' => 'Articles',
                'noun' => 'articles',
                'card_type' => 'writing',
                'nav_order' => 30,
                'json' => 'writing',
            ],
            [
                'slug' => 'ventures',
                'title' => 'Ventures',
                'description' => 'Ventures and products built by Juan P. Romano.',
                'eyebrow' => 'Business',
                'noun' => 'ventures',
                'card_type' => 'venture',
                'nav_order' => 40,
                'json' => 'ventures',
            ],
            [
                'slug' => 'news',
                'title' => 'News',
                'description' => 'Press and media coverage featuring Juan P. Romano.',
                'eyebrow' => 'Coverage',
                'noun' => 'mentions',
                'card_type' => 'news',
                'nav_order' => 50,
                'json' => 'news',
            ],
            [
                'slug' => 'resumes',
                'title' => 'Resumes',
                'description' => 'Download Juan P. Romano’s CV in Spanish and English.',
                'eyebrow' => 'CV',
                'noun' => 'resumes',
                'card_type' => 'resume',
                'nav_order' => 900,
                'json' => 'resumes',
            ],
        ];

        $pageStmt = $pdo->prepare(
            'INSERT INTO pages (slug, title, description, eyebrow, noun, card_type, is_system, show_in_nav, nav_order)
             VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?)'
        );
        $cardStmt = $pdo->prepare(
            'INSERT INTO cards (
                page_id, title, category, image_src, url, secondary_url, brief, author, status,
                label, description, file_name, lang, sort_order
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($systemPages as $page) {
            $pageStmt->execute([
                $page['slug'],
                $page['title'],
                $page['description'],
                $page['eyebrow'],
                $page['noun'],
                $page['card_type'],
                $page['nav_order'],
            ]);
            $pageId = (int) $pdo->lastInsertId();
            $items = self::readJson($siteRoot, $page['json']);
            $order = 0;
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $mapped = self::mapJsonItem($page['card_type'], $item);
                $cardStmt->execute([
                    $pageId,
                    $mapped['title'],
                    $mapped['category'],
                    $mapped['image_src'],
                    $mapped['url'],
                    $mapped['secondary_url'],
                    $mapped['brief'],
                    $mapped['author'],
                    $mapped['status'],
                    $mapped['label'],
                    $mapped['description'],
                    $mapped['file_name'],
                    $mapped['lang'],
                    $order++,
                ]);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private static function readJson(string $siteRoot, string $name): array
    {
        $file = $siteRoot . '/assets/data/' . $name . '.json';
        if (!is_file($file)) {
            return [];
        }
        $raw = file_get_contents($file);
        if ($raw === false || $raw === '') {
            return [];
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $item
     * @return array{
     *   title: string, category: string, image_src: string, url: string, secondary_url: string,
     *   brief: ?string, author: string, status: string, label: string, description: ?string,
     *   file_name: string, lang: string
     * }
     */
    private static function mapJsonItem(string $cardType, array $item): array
    {
        $title = (string) ($item['title'] ?? 'Untitled');
        $category = (string) ($item['category'] ?? '');
        $image = (string) ($item['imageSrc'] ?? '');
        $url = '';
        $secondary = '';
        $brief = null;
        $author = '';
        $status = '';
        $label = '';
        $description = null;
        $file = '';
        $lang = '';

        switch ($cardType) {
            case 'project':
                $url = (string) ($item['liveUrl'] ?? '');
                $secondary = (string) ($item['githubUrl'] ?? '');
                break;
            case 'book':
                $url = (string) ($item['buyingLink'] ?? '');
                $brief = (string) ($item['brief'] ?? '');
                $author = (string) ($item['author'] ?? '');
                $status = (string) ($item['status'] ?? '');
                break;
            case 'writing':
            case 'venture':
            case 'news':
            case 'generic':
                $url = (string) ($item['url'] ?? '');
                break;
            case 'resume':
                $file = (string) ($item['file'] ?? '');
                $label = (string) ($item['label'] ?? '');
                $description = (string) ($item['description'] ?? '');
                $lang = (string) ($item['lang'] ?? '');
                break;
        }

        return [
            'title' => $title,
            'category' => $category,
            'image_src' => $image,
            'url' => $url,
            'secondary_url' => $secondary,
            'brief' => $brief,
            'author' => $author,
            'status' => $status,
            'label' => $label,
            'description' => $description,
            'file_name' => $file,
            'lang' => $lang,
        ];
    }
}
