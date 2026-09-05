<?php
declare(strict_types=1);

namespace MicroCMS;

final class Paths
{
    public static function detectSiteRoot(string $cmsRoot): string
    {
        $configured = Env::get('SITE_ROOT', '');
        if ($configured !== null && $configured !== '') {
            if (preg_match('#^[A-Za-z]:[\\\\/]#', $configured) || str_starts_with($configured, '/')) {
                $resolved = realpath($configured);
            } else {
                $resolved = realpath($cmsRoot . DIRECTORY_SEPARATOR . $configured);
            }
            if ($resolved !== false) {
                return $resolved;
            }
        }

        $parent = dirname($cmsRoot);
        if (is_file($parent . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'bootstrap.php')) {
            return $parent;
        }

        $sibling = $parent . DIRECTORY_SEPARATOR . 'website';
        if (is_file($sibling . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'bootstrap.php')) {
            return $sibling;
        }

        // Fallback: assume parent is the site (deployed layout)
        return $parent;
    }
}
