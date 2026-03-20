<?php

declare(strict_types=1);

namespace app\admin\service;

final class AuthSessionService
{
    private const SESSION_KEY = 'admin_auth';
    private const CSRF_KEY = '_csrf_token';

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = $this->isHttps();
        session_name('enterprise_inquiry_admin');
        session_set_cookie_params([
            'lifetime' => 7200,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', '7200');
        session_start();
    }

    public function login(array $user): void
    {
        $this->start();
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'user_id' => (int) $user['id'],
            'role_type' => $this->resolveRoleType($user),
            'site_id' => (int) $user['site_id'],
            'username' => (string) $user['username'],
            'real_name' => (string) ($user['nickname'] ?? $user['username']),
        ];
    }

    public function logout(): void
    {
        $this->start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $params['path'] ?: '/', $params['domain'] ?: '', (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }

    public function user(): ?array
    {
        $this->start();
        $user = $_SESSION[self::SESSION_KEY] ?? null;

        return is_array($user) ? $user : null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function csrfToken(): string
    {
        $this->start();
        if (!isset($_SESSION[self::CSRF_KEY]) || !is_string($_SESSION[self::CSRF_KEY]) || $_SESSION[self::CSRF_KEY] === '') {
            $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CSRF_KEY];
    }

    public function validateCsrf(?string $token): bool
    {
        $currentToken = $this->csrfToken();

        return is_string($token) && $token !== '' && hash_equals($currentToken, $token);
    }

    private function resolveRoleType(array $user): string
    {
        return (int) ($user['is_super_admin'] ?? 0) === 1 ? 'super_admin' : 'site_admin';
    }

    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}
