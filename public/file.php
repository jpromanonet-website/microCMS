<?php
declare(strict_types=1);

/**
 * Public media endpoint — serves disk files or DB-stored uploads.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use MicroCMS\MediaStore;

$section = preg_replace('/[^a-z0-9_-]/i', '', (string) ($_GET['section'] ?? '')) ?? '';
$file = basename(str_replace('\\', '/', (string) ($_GET['file'] ?? '')));

if ($section === '' || $file === '' || $file === '.' || $file === '..') {
    http_response_code(404);
    exit('Not found');
}

$media = MediaStore::fetch($section, $file);
if ($media === null || $media['data'] === false || $media['data'] === null) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: ' . ($media['mime'] ?: 'application/octet-stream'));
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . strlen((string) $media['data']));
echo $media['data'];
exit;
