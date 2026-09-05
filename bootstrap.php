<?php
declare(strict_types=1);

/**
 * Bootstraps MicroCMS: env, DB, migrate, seed.
 * Safe to include from the public site and from the admin.
 */

define('MICROCMS_ROOT', __DIR__);

spl_autoload_register(static function (string $class): void {
    $prefix = 'MicroCMS\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = MICROCMS_ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use MicroCMS\Env;
use MicroCMS\Migrator;
use MicroCMS\Paths;
use MicroCMS\Seeder;

Env::load(MICROCMS_ROOT);

if (!defined('MICROCMS_SITE_ROOT')) {
    define('MICROCMS_SITE_ROOT', Paths::detectSiteRoot(MICROCMS_ROOT));
}

Migrator::migrate();
Seeder::seedIfEmpty(MICROCMS_SITE_ROOT);
