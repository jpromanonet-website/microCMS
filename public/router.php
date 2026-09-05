<?php
declare(strict_types=1);

/**
 * 404 router for custom CMS pages when no physical /{slug}/ folder exists.
 * Configured via ErrorDocument in the site .htaccess.
 */

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

use MicroCMS\Content;

$slug = strtolower(trim((string) ($_GET['slug'] ?? '')));
$slug = preg_replace('/[^a-z0-9-]/', '', $slug) ?? '';

if ($slug === '') {
    $uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
    $base = defined('BASE_URL') ? (string) BASE_URL : '';
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }
    $uri = trim($uri, '/');
    // Only single-segment paths: "talks", not "a/b"
    if ($uri !== '' && !str_contains($uri, '/')) {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($uri)) ?? '';
    }
}

if ($slug === '') {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

$page = Content::pageBySlug($slug);
if (!$page || (int) $page['is_system'] === 1) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

http_response_code(200);
$pageSlug = $slug;
require __DIR__ . '/catalog.php';
