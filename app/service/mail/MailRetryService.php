<?php

declare(strict_types=1);

namespace app\service\mail;

use app\model\EmailSendLog;

final class MailRetryService
{
    public function __construct(private readonly EmailSendLog $emailSendLogModel = new EmailSendLog())
    {
    }

    public function failedBySiteId(int $siteId, int $limit = 20): array
    {
        return $this->emailSendLogModel->findAllBy(['site_id' => $siteId, 'status' => 0], '`id` DESC', $limit);
    }
}
