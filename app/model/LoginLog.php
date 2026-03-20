<?php

declare(strict_types=1);

namespace app\model;

class LoginLog extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'user_id', 'username', 'status', 'ip', 'user_agent', 'created_at'];
    }

    protected function tableName(): string
    {
        return 'login_logs';
    }
}
