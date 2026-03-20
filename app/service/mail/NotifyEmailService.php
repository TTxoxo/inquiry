<?php

declare(strict_types=1);

namespace app\service\mail;

use app\model\SiteNotifyEmail;

final class NotifyEmailService
{
    public function __construct(private readonly SiteNotifyEmail $notifyEmailModel = new SiteNotifyEmail())
    {
    }

    public function getActiveBySiteId(int $siteId): array
    {
        $rows = $this->notifyEmailModel->findAllBy(['site_id' => $siteId, 'status' => 1], '`id` ASC');

        return array_values(array_filter(array_map(static fn (array $row): string => (string) $row['email'], $rows)));
    }
}
