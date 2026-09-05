<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use MicroCMS\Auth;

Auth::startSession();

function cms_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cms_flash(?string $message = null, string $type = 'ok'): ?array
{
    Auth::startSession();
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function cms_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }
    if (!Auth::verifyCsrf($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
}

/**
 * @return array{ok:bool, filename?:string, error?:string, storage?:string}
 */
function cms_handle_upload(string $field, string $subdir, array $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']): array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return ['ok' => false, 'error' => 'No file'];
    }

    return \MicroCMS\MediaStore::storeUpload($_FILES[$field], $subdir, $allowedExt);
}

function cms_icon_user(): string
{
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z" stroke="currentColor" stroke-width="1.8"/><path d="M4.5 20.2a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
}

function cms_icon_logout(): string
{
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 7V5.8A1.8 1.8 0 0 1 11.8 4h6.4A1.8 1.8 0 0 1 20 5.8v12.4A1.8 1.8 0 0 1 18.2 20h-6.4A1.8 1.8 0 0 1 10 18.2V17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M4 12h10M8 8l-4 4 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function cms_icon_menu(): string
{
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
}

function cms_layout_start(string $title, string $active = ''): void
{
    $user = Auth::username();
    $flash = cms_flash();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="color-scheme" content="light dark" />
    <title><?= cms_e($title) ?> — microCMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/admin.css" />
    <script>
      (function () {
        try {
          var t = localStorage.getItem('jpr-theme') || 'light';
          document.documentElement.setAttribute('data-theme', t);
        } catch (e) {
          document.documentElement.setAttribute('data-theme', 'light');
        }
      })();
    </script>
</head>
<body>
<header class="topbar">
    <a class="topbar__brand" href="index.php">microCMS</a>
    <nav class="topbar__nav" id="admin-nav">
        <a class="<?= $active === 'dashboard' ? 'is-active' : '' ?>" href="index.php">Dashboard</a>
        <a class="<?= $active === 'settings' ? 'is-active' : '' ?>" href="settings.php">Settings</a>
        <a class="<?= $active === 'home' ? 'is-active' : '' ?>" href="home.php">Home</a>
        <a class="<?= $active === 'pages' ? 'is-active' : '' ?>" href="pages.php">Pages</a>
    </nav>
    <div class="topbar__actions">
        <a
            class="icon-btn<?= $active === 'account' ? ' is-active' : '' ?>"
            href="account.php"
            title="Account (<?= cms_e($user) ?>)"
            aria-label="Account settings for <?= cms_e($user) ?>"
        ><?= cms_icon_user() ?></a>
        <button type="button" class="icon-btn theme-toggle" id="theme-toggle" aria-label="Switch theme">
            <svg class="theme-toggle__moon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M21 14.3A8.5 8.5 0 0 1 9.7 3 7 7 0 1 0 21 14.3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            </svg>
            <svg class="theme-toggle__sun" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                <path d="M12 2v2.2M12 19.8V22M4.2 4.2l1.6 1.6M18.2 18.2l1.6 1.6M2 12h2.2M19.8 12H22M4.2 19.8l1.6-1.6M18.2 5.8l1.6-1.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>
        <a class="icon-btn" href="logout.php" title="Logout" aria-label="Logout"><?= cms_icon_logout() ?></a>
        <button
            type="button"
            class="icon-btn topbar__menu-btn"
            id="nav-toggle"
            aria-label="Open menu"
            aria-expanded="false"
            aria-controls="admin-nav"
        ><?= cms_icon_menu() ?></button>
    </div>
</header>
<main class="wrap">
    <?php if ($flash): ?>
        <div class="flash flash--<?= cms_e((string) $flash['type']) ?>"><?= cms_e((string) $flash['message']) ?></div>
    <?php endif; ?>
<?php
}

function cms_layout_end(): void
{
    ?>
</main>
<script src="assets/admin.js"></script>
</body>
</html>
    <?php
}
