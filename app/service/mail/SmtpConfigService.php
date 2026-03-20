<?php

declare(strict_types=1);

namespace app\service\mail;

use app\model\SmtpConfig;

final class SmtpConfigService
{
    public function __construct(private readonly SmtpConfig $smtpConfigModel = new SmtpConfig())
    {
    }

    public function getActiveBySiteId(int $siteId): ?array
    {
        return $this->smtpConfigModel->findOneBy(['site_id' => $siteId, 'status' => 1], '`id` DESC');
    }
}
