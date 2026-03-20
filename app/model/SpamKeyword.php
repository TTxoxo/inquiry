<?php

declare(strict_types=1);

namespace app\model;

class SpamKeyword extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'keyword', 'status', 'created_at', 'updated_at'];
    }

    protected function tableName(): string
    {
        return 'spam_keywords';
    }
}
