<?php

declare(strict_types=1);

namespace app\model;

class Form extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'site_id', 'name', 'code', 'description', 'status', 'created_at', 'updated_at'];
    }

    protected function tableName(): string
    {
        return 'forms';
    }
}
