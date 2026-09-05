<?php
declare(strict_types=1);

namespace MicroCMS;

final class MediaStore
{
    /**
     * @return array{ok:bool, filename?:string, error?:string, storage?:string}
     */
    public static function storeUpload(array $file, string $section, array $allowedExt): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file'];
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed (code ' . (int) $file['error'] . ')'];
        }

        $original = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return ['ok' => false, 'error' => 'Invalid file type'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Invalid upload'];
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9._-]+/', '-', pathinfo($original, PATHINFO_FILENAME)) ?: 'file';
        $filename = strtolower($safeBase) . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $section = trim($section, '/');

        $mime = (string) (mime_content_type($tmp) ?: ($file['type'] ?? 'application/octet-stream'));
        $bytes = file_get_contents($tmp);
        if ($bytes === false) {
            return ['ok' => false, 'error' => 'Could not read upload'];
        }

        // Prefer filesystem when writable (same layout as existing media)
        $fsDir = $section === 'pdfs'
            ? MICROCMS_SITE_ROOT . '/assets/pdfs'
            : MICROCMS_SITE_ROOT . '/assets/media/' . $section;
        if (!is_dir($fsDir)) {
            @mkdir($fsDir, 0775, true);
        }
        if (is_dir($fsDir) && is_writable($fsDir)) {
            $dest = $fsDir . '/' . $filename;
            if (@move_uploaded_file($tmp, $dest) || @file_put_contents($dest, $bytes) !== false) {
                return ['ok' => true, 'filename' => $filename, 'storage' => 'disk'];
            }
        }

        // Fallback: MySQL (www-data cannot write site assets on this host)
        $stmt = Database::pdo()->prepare(
            'INSERT INTO media_files (section, filename, mime, data) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE mime = VALUES(mime), data = VALUES(data)'
        );
        $stmt->bindValue(1, $section);
        $stmt->bindValue(2, $filename);
        $stmt->bindValue(3, $mime);
        $stmt->bindValue(4, $bytes, \PDO::PARAM_LOB);
        $stmt->execute();

        return ['ok' => true, 'filename' => $filename, 'storage' => 'db'];
    }

    public static function fetch(string $section, string $filename): ?array
    {
        $section = trim($section, '/');
        $filename = basename(str_replace('\\', '/', $filename));

        $fsPath = $section === 'pdfs'
            ? MICROCMS_SITE_ROOT . '/assets/pdfs/' . $filename
            : MICROCMS_SITE_ROOT . '/assets/media/' . $section . '/' . $filename;

        if (is_file($fsPath)) {
            return [
                'mime' => mime_content_type($fsPath) ?: 'application/octet-stream',
                'data' => file_get_contents($fsPath),
                'path' => $fsPath,
            ];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT mime, data FROM media_files WHERE section = ? AND filename = ? LIMIT 1'
        );
        $stmt->execute([$section, $filename]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $data = $row['data'];
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        return [
            'mime' => (string) $row['mime'],
            'data' => $data,
            'path' => null,
        ];
    }

    public static function exists(string $section, string $filename): bool
    {
        $section = trim($section, '/');
        $filename = basename(str_replace('\\', '/', $filename));

        $fsPath = $section === 'pdfs'
            ? MICROCMS_SITE_ROOT . '/assets/pdfs/' . $filename
            : MICROCMS_SITE_ROOT . '/assets/media/' . $section . '/' . $filename;
        if (is_file($fsPath)) {
            return true;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM media_files WHERE section = ? AND filename = ? LIMIT 1'
        );
        $stmt->execute([$section, $filename]);
        return (bool) $stmt->fetchColumn();
    }
}
