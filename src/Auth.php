<?php
declare(strict_types=1);

namespace MicroCMS;

final class Auth
{
    private const SESSION_KEY = 'microcms_user_id';

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('microcms_session');
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }
    }

    public static function attempt(string $username, string $password): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id, password_hash FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        self::startSession();
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        $_SESSION['microcms_username'] = $username;
        return true;
    }

    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION[self::SESSION_KEY]);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    public static function username(): string
    {
        self::startSession();
        return (string) ($_SESSION['microcms_username'] ?? '');
    }

    public static function userId(): int
    {
        self::startSession();
        return (int) ($_SESSION[self::SESSION_KEY] ?? 0);
    }

    /**
     * Update username and/or password for the logged-in user.
     * Requires current password. New password optional (empty = keep).
     */
    public static function updateAccount(string $currentPassword, string $newUsername, string $newPassword = ''): void
    {
        $id = self::userId();
        if ($id <= 0) {
            throw new \RuntimeException('Not authenticated');
        }

        $stmt = Database::pdo()->prepare('SELECT id, username, password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($currentPassword, (string) $user['password_hash'])) {
            throw new \InvalidArgumentException('Current password is incorrect');
        }

        $newUsername = trim($newUsername);
        if ($newUsername === '' || strlen($newUsername) < 3) {
            throw new \InvalidArgumentException('Username must be at least 3 characters');
        }
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $newUsername)) {
            throw new \InvalidArgumentException('Username may only contain letters, numbers, dots, _ and -');
        }

        $check = Database::pdo()->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
        $check->execute([$newUsername, $id]);
        if ($check->fetch()) {
            throw new \InvalidArgumentException('That username is already taken');
        }

        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                throw new \InvalidArgumentException('New password must be at least 6 characters');
            }
            $upd = Database::pdo()->prepare('UPDATE users SET username = ?, password_hash = ? WHERE id = ?');
            $upd->execute([$newUsername, password_hash($newPassword, PASSWORD_DEFAULT), $id]);
        } else {
            $upd = Database::pdo()->prepare('UPDATE users SET username = ? WHERE id = ?');
            $upd->execute([$newUsername, $id]);
        }

        $_SESSION['microcms_username'] = $newUsername;
    }

    public static function csrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::startSession();
        $session = (string) ($_SESSION['csrf_token'] ?? '');
        return $session !== '' && is_string($token) && hash_equals($session, $token);
    }
}
