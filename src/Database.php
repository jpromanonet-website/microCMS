<?php
declare(strict_types=1);

namespace MicroCMS;

final class Database
{
    private static ?\PDO $pdo = null;

    public static function pdo(): \PDO
    {
        if (self::$pdo instanceof \PDO) {
            return self::$pdo;
        }

        $host = Env::require('DB_HOST');
        $port = Env::get('DB_PORT', '3306') ?: '3306';
        $name = Env::require('DB_NAME');
        $user = Env::require('DB_USER');
        $pass = Env::get('DB_PASS', '') ?? '';

        $serverDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $server = new \PDO($serverDsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        $dbName = str_replace('`', '``', $name);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        self::$pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
