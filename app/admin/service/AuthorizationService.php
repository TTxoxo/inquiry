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
        '/admin/dashboard/stats' => ['super_admin', 'site_admin'],
        '/admin/password' => ['super_admin', 'site_admin'],
        '/admin/logout' => ['super_admin', 'site_admin'],
        '/admin/sites' => ['super_admin', 'site_admin'],
        '/admin/sites/list' => ['super_admin', 'site_admin'],
        '/admin/sites/save' => ['super_admin', 'site_admin'],
        '/admin/sites/delete' => ['super_admin'],
        '/admin/site-users' => ['super_admin', 'site_admin'],
        '/admin/site-users/list' => ['super_admin', 'site_admin'],
        '/admin/site-users/save' => ['super_admin', 'site_admin'],
        '/admin/site-users/delete' => ['super_admin', 'site_admin'],
        '/admin/forms' => ['super_admin', 'site_admin'],
        '/admin/forms/list' => ['super_admin', 'site_admin'],
        '/admin/forms/save' => ['super_admin', 'site_admin'],
        '/admin/forms/delete' => ['super_admin', 'site_admin'],
        '/admin/fields' => ['super_admin', 'site_admin'],
        '/admin/fields/list' => ['super_admin', 'site_admin'],
        '/admin/fields/save' => ['super_admin', 'site_admin'],
        '/admin/fields/delete' => ['super_admin', 'site_admin'],
        '/admin/embed' => ['super_admin', 'site_admin'],
        '/admin/embed/list' => ['super_admin', 'site_admin'],
        '/admin/inquiries' => ['super_admin', 'site_admin'],
        '/admin/inquiries/list' => ['super_admin', 'site_admin'],
        '/admin/inquiries/detail' => ['super_admin', 'site_admin'],
        '/admin/inquiries/export' => ['super_admin', 'site_admin'],
        '/admin/smtp' => ['super_admin', 'site_admin'],
        '/admin/smtp/list' => ['super_admin', 'site_admin'],
        '/admin/smtp/save' => ['super_admin', 'site_admin'],
        '/admin/smtp/delete' => ['super_admin', 'site_admin'],
        '/admin/notify-emails' => ['super_admin', 'site_admin'],
        '/admin/notify-emails/list' => ['super_admin', 'site_admin'],
        '/admin/notify-emails/save' => ['super_admin', 'site_admin'],
        '/admin/notify-emails/delete' => ['super_admin', 'site_admin'],
        '/admin/email-logs' => ['super_admin', 'site_admin'],
        '/admin/email-logs/list' => ['super_admin', 'site_admin'],
        '/admin/email-logs/retry' => ['super_admin', 'site_admin'],
        '/admin/tracking' => ['super_admin', 'site_admin'],
        '/admin/tracking/list' => ['super_admin', 'site_admin'],
        '/admin/tracking/save' => ['super_admin', 'site_admin'],
        '/admin/tracking/delete' => ['super_admin', 'site_admin'],
        '/admin/spam-keywords' => ['super_admin', 'site_admin'],
        '/admin/spam-keywords/list' => ['super_admin', 'site_admin'],
        '/admin/spam-keywords/save' => ['super_admin', 'site_admin'],
        '/admin/spam-keywords/delete' => ['super_admin', 'site_admin'],
        '/admin/operation-logs' => ['super_admin', 'site_admin'],
        '/admin/operation-logs/list' => ['super_admin', 'site_admin'],
        '/admin/login-logs' => ['super_admin', 'site_admin'],
        '/admin/login-logs/list' => ['super_admin', 'site_admin'],
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
