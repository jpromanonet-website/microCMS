<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use MicroCMS\Auth;
use MicroCMS\MediaStore;

Auth::requireLogin();

$section = preg_replace('/[^a-z0-9_-]/i', '', (string) ($_GET['section'] ?? '')) ?? '';
$file = basename(str_replace('\\', '/', (string) ($_GET['file'] ?? '')));

if ($section === '' || $file === '' || $file === '.' || $file === '..') {
    http_response_code(404);
    exit;
}

$media = MediaStore::fetch($section, $file);
if ($media === null || $media['data'] === false || $media['data'] === null) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . ($media['mime'] ?: 'application/octet-stream'));
header('Cache-Control: private, max-age=3600');
echo $media['data'];
exit;
