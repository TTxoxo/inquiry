<?php

declare(strict_types=1);

namespace app\model;

class SmtpConfig extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'site_id', 'host', 'port', 'username', 'password', 'encryption', 'from_email', 'from_name', 'status', 'created_at', 'updated_at'];
    }

    protected function tableName(): string
    {
        return 'smtp_configs';
    }
}
