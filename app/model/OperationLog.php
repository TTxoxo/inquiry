<?php

declare(strict_types=1);

namespace app\model;

class OperationLog extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'user_id', 'site_id', 'action', 'target_type', 'target_id', 'content', 'ip', 'created_at'];
    }

    protected function tableName(): string
    {
        return 'operation_logs';
    }
}
