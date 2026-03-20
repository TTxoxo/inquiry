<?php

declare(strict_types=1);

namespace app\model;

class FormField extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'form_id', 'name', 'label', 'type', 'is_required', 'sort', 'settings_json', 'created_at', 'updated_at'];
    }

    protected function tableName(): string
    {
        return 'form_fields';
    }
}
