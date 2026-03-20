<?php

declare(strict_types=1);

namespace app\model;

class SiteNotifyEmail extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'site_id', 'email', 'status', 'created_at', 'updated_at'];
    }

    protected function tableName(): string
    {
        return 'site_notify_emails';
    }
}
