<?php

declare(strict_types=1);

namespace app\admin\service;

final class AdminAccessService
{
    public function isSuperAdmin(array $user): bool
    {
        return (string) ($user['role_type'] ?? '') === 'super_admin';
    }

    public function enforceSiteAccess(array $user, ?int $siteId): void
    {
        if ($siteId === null || $siteId <= 0 || $this->isSuperAdmin($user)) {
            return;
        }

        if ((int) ($user['site_id'] ?? 0) !== $siteId) {
            throw new \RuntimeException('越权访问', 4003);
        }
    }

    public function scopeSiteId(array $user, ?int $requestedSiteId = null): ?int
    {
        if ($this->isSuperAdmin($user)) {
            return $requestedSiteId;
        }

        return (int) ($user['site_id'] ?? 0);
    }
}
