<?php
declare(strict_types=1);

namespace MicroCMS;

final class Env
{
    private static bool $loaded = false;

    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $cmsRoot): void
    {
        if (self::$loaded) {
            return;
        }

        $path = $cmsRoot . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($path)) {
            $example = $cmsRoot . DIRECTORY_SEPARATOR . '.env.example';
            if (is_file($example)) {
                copy($example, $path);
            }
        }

        if (is_file($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    if (!str_contains($line, '=')) {
                        continue;
                    }
                    [$key, $value] = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if (
                        (str_starts_with($value, '"') && str_ends_with($value, '"'))
                        || (str_starts_with($value, "'") && str_ends_with($value, "'"))
                    ) {
                        $value = substr($value, 1, -1);
                    }
                    self::$values[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$values[$key] ?? $default;
    }

    public static function require(string $key): string
    {
        $value = self::get($key);
        if ($value === null || $value === '') {
            throw new \RuntimeException("Missing required .env key: {$key}");
        }
        return $value;
    }
}
