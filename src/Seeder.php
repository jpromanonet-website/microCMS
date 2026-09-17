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
        self::seedPagesAndCards($pdo);
        self::ensureLifestylePages($pdo);
    }

    /**
     * Art / Languages / Music / Sports / Cooking — always ensure they exist (safe on existing DBs).
     */
    public static function ensureLifestylePages(\PDO $pdo): void
    {
        $pages = [
            [
                'slug' => 'art',
                'title' => 'My Art',
                'description' => 'Artwork and visual pieces by Juan P. Romano.',
                'eyebrow' => 'Gallery',
                'noun' => 'artwork to show',
                'card_type' => 'art',
                'nav_order' => 910,
            ],
            [
                'slug' => 'languages',
                'title' => 'Languages',
                'description' => 'Languages I speak — with certificates and proof.',
                'eyebrow' => 'Polyglot',
                'noun' => 'languages that I\'m speaking',
                'card_type' => 'language',
                'nav_order' => 920,
            ],
            [
                'slug' => 'music',
                'title' => 'My Music',
                'description' => 'Tracks and SoundCloud links.',
                'eyebrow' => 'Sound',
                'noun' => 'songs published',
                'card_type' => 'music',
                'nav_order' => 930,
            ],
            [
                'slug' => 'podcasts',
                'title' => 'Podcasts',
                'description' => 'Podcast chapters and Spotify links.',
                'eyebrow' => 'Audio',
                'noun' => 'chapters recorded',
                'card_type' => 'podcast',
                'nav_order' => 935,
            ],
            [
                'slug' => 'sports',
                'title' => 'Sports',
                'description' => 'Running kilometers and training exercises.',
                'eyebrow' => 'Training',
                'noun' => 'Km ran',
                'card_type' => 'sport',
                'nav_order' => 940,
            ],
            [
                'slug' => 'cooking',
                'title' => 'Cooking',
                'description' => 'Recipes I have cooked.',
                'eyebrow' => 'Kitchen',
                'noun' => 'recipes cooked',
                'card_type' => 'recipe',
                'nav_order' => 950,
            ],
        ];

        $insert = $pdo->prepare(
            'INSERT INTO pages (slug, title, description, eyebrow, noun, card_type, is_system, show_in_nav, nav_order)
             VALUES (?, ?, ?, ?, ?, ?, 1, 0, ?)'
        );
        $find = $pdo->prepare('SELECT id FROM pages WHERE slug = ? LIMIT 1');

        foreach ($pages as $page) {
            $find->execute([$page['slug']]);
            $existingId = $find->fetchColumn();
            if ($existingId) {
                // Keep editable copy — only lock system flags / type for lifestyle pages
                $pdo->prepare(
                    'UPDATE pages SET is_system = 1, show_in_nav = 0, card_type = ?, nav_order = ? WHERE id = ?'
                )->execute([$page['card_type'], $page['nav_order'], (int) $existingId]);
                continue;
            }

            $insert->execute([
                $page['slug'],
                $page['title'],
                $page['description'],
                $page['eyebrow'],
                $page['noun'],
                $page['card_type'],
                $page['nav_order'],
            ]);
        }

        $find->execute(['sports']);
        $sportsId = (int) ($find->fetchColumn() ?: 0);
        if ($sportsId > 0) {
            $kmCheck = $pdo->prepare("SELECT id FROM cards WHERE page_id = ? AND status = 'km' LIMIT 1");
            $kmCheck->execute([$sportsId]);
            if (!$kmCheck->fetchColumn()) {
                $pdo->prepare(
                    'INSERT INTO cards (page_id, title, status, brief, sort_order)
                     VALUES (?, ?, ?, ?, 0)'
                )->execute([$sportsId, 'Kilometers ran', 'km', '0']);
            }
        }
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

    private static function seedPagesAndCards(\PDO $pdo): void
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
            ],
            [
                'slug' => 'books',
                'title' => 'Books',
                'description' => 'Books by Juan P. Romano.',
                'eyebrow' => 'Writing',
                'noun' => 'books',
                'card_type' => 'book',
                'nav_order' => 20,
            ],
            [
                'slug' => 'writing',
                'title' => 'Writing',
                'description' => 'Articles and publications by Juan P. Romano — including automatic Medium posts.',
                'eyebrow' => 'Articles',
                'noun' => 'articles',
                'card_type' => 'writing',
                'nav_order' => 30,
            ],
            [
                'slug' => 'ventures',
                'title' => 'Ventures',
                'description' => 'Ventures and products built by Juan P. Romano.',
                'eyebrow' => 'Business',
                'noun' => 'ventures',
                'card_type' => 'venture',
                'nav_order' => 40,
            ],
            [
                'slug' => 'news',
                'title' => 'News',
                'description' => 'Press and media coverage featuring Juan P. Romano.',
                'eyebrow' => 'Coverage',
                'noun' => 'mentions',
                'card_type' => 'news',
                'nav_order' => 50,
            ],
            [
                'slug' => 'resumes',
                'title' => 'Resumes',
                'description' => 'Download Juan P. Romano’s CV in Spanish and English.',
                'eyebrow' => 'CV',
                'noun' => 'resumes',
                'card_type' => 'resume',
                'nav_order' => 900,
            ],
        ];

        $pageStmt = $pdo->prepare(
            'INSERT INTO pages (slug, title, description, eyebrow, noun, card_type, is_system, show_in_nav, nav_order)
             VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?)'
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
        }
    }
}
