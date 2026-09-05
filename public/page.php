<?php
declare(strict_types=1);

/**
 * Front controller for custom CMS catalog pages (no physical folder required).
 * Used by .htaccess rewrite: /{slug}/ -> microCMS/public/page.php?slug={slug}
 */

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

use MicroCMS\Content;

$pageSlug = strtolower(trim((string) ($_GET['slug'] ?? '')));
$pageSlug = preg_replace('/[^a-z0-9-]/', '', $pageSlug) ?? '';

if ($pageSlug === '') {
    http_response_code(404);
    echo 'Page not found';
    exit;
}

$page = Content::pageBySlug($pageSlug);
if (!$page || (int) $page['is_system'] === 1) {
    http_response_code(404);
    echo 'Page not found';
    exit;
}

require __DIR__ . '/catalog.php';
