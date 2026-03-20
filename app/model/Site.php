<?php

declare(strict_types=1);

namespace app\model;

class Site extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'name', 'code', 'domain', 'status', 'created_at', 'updated_at'];
    }

    protected function tableName(): string
    {
        return 'sites';
    }
}
