<?php

namespace AuthSystem\Core\Session;

use AuthSystem\Core\Debug\DebugHelper;


class SessionManager
{
    private static bool $started = false;


    public static function start(): void
    {
        if (!self::$started) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            self::$started = true;
        }
    }


    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }


    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }


    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }


    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }


    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }


    public static function destroy(): void
    {
        self::start();
        session_destroy();
        self::$started = false;
    }


    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }


    public static function isLoggedIn(): bool
    {
        return self::has('admin_id') && self::has('admin_username');
    }


    public static function getAdminId(): ?int
    {
        return self::get('admin_id');
    }


    public static function getAdminUsername(): ?string
    {
        return self::get('admin_username');
    }


    public static function setAdminLogin(int $adminId, string $username): void
    {
        self::regenerate();
        self::set('admin_id', $adminId);
        self::set('admin_username', $username);
        self::set('login_time', time());

        DebugHelper::logSession('admin_login', [
            'admin_id' => $adminId,
            'username' => $username
        ]);
    }


    public static function clearAdminLogin(): void
    {
        $username = self::get('admin_username');

        self::remove('admin_id');
        self::remove('admin_username');
        self::remove('login_time');

        DebugHelper::logSession('admin_logout', [
            'username' => $username
        ]);
    }


    public static function isExpired(int $maxLifetime = 3600): bool
    {
        $loginTime = self::get('login_time');
        if (!$loginTime) {
            return true;
        }

        return (time() - $loginTime) > $maxLifetime;
    }


    public static function updateActivity(): void
    {
        self::set('last_activity', time());
    }


    public static function setFlashMessage(string $type, string $message): void
    {
        self::start();
        $_SESSION['flash_messages'][$type] = $message;
    }


    public static function getFlashMessage(string $type): ?string
    {
        self::start();
        $message = $_SESSION['flash_messages'][$type] ?? null;
        if ($message) {
            unset($_SESSION['flash_messages'][$type]);
        }
        return $message;
    }


    public static function hasFlashMessage(string $type): bool
    {
        self::start();
        return isset($_SESSION['flash_messages'][$type]);
    }


    public static function clearFlashMessages(): void
    {
        self::start();
        unset($_SESSION['flash_messages']);
    }
}