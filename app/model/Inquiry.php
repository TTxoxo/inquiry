<?php

declare(strict_types=1);

namespace app\model;

class Inquiry extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'site_id', 'form_id', 'source_url', 'ip', 'user_agent', 'payload_json', 'status', 'created_at', 'updated_at'];
    }

    protected function tableName(): string
    {
        return 'inquiries';
    }
}
