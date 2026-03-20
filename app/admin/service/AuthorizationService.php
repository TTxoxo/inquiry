<?php

declare(strict_types=1);

namespace app\admin\service;

final class AuthorizationService
{
    /**
     * @var array<string, list<string>>
     */
    private array $permissions = [
        '/admin/dashboard' => ['super_admin', 'site_admin'],
        '/admin/password' => ['super_admin', 'site_admin'],
        '/admin/logout' => ['super_admin', 'site_admin'],
    ];

    public function canAccess(?array $sessionUser, string $path): bool
    {
        if ($sessionUser === null) {
            return false;
        }

        $roleType = (string) ($sessionUser['role_type'] ?? '');
        if ($roleType === '') {
            return false;
        }

        $allowedRoles = $this->permissions[$path] ?? ['super_admin'];

        return in_array($roleType, $allowedRoles, true);
    }
}
