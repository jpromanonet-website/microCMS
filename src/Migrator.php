<?php
declare(strict_types=1);

namespace MicroCMS;

final class Migrator
{
    public static function migrate(): void
    {
        $pdo = Database::pdo();

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS home_blocks (
    block_key VARCHAR(64) NOT NULL PRIMARY KEY,
    content_json JSON NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(64) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    eyebrow VARCHAR(100) NOT NULL DEFAULT '',
    noun VARCHAR(50) NOT NULL DEFAULT 'items',
    card_type VARCHAR(32) NOT NULL DEFAULT 'generic',
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    show_in_nav TINYINT(1) NOT NULL DEFAULT 1,
    nav_order INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS cards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    title VARCHAR(500) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    image_src VARCHAR(255) NOT NULL DEFAULT '',
    url VARCHAR(1000) NOT NULL DEFAULT '',
    secondary_url VARCHAR(1000) NOT NULL DEFAULT '',
    brief TEXT NULL,
    author VARCHAR(255) NOT NULL DEFAULT '',
    status VARCHAR(50) NOT NULL DEFAULT '',
    label VARCHAR(100) NOT NULL DEFAULT '',
    description TEXT NULL,
    file_name VARCHAR(255) NOT NULL DEFAULT '',
    lang VARCHAR(10) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cards_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_cards_page_sort (page_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS media_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(64) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    mime VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
    data LONGBLOB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_media_section_file (section, filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }
}
