<?php

declare(strict_types=1);

namespace app\model;

class FormTrackingConfig extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'form_id', 'tracking_type', 'config_json', 'status', 'created_at', 'updated_at'];
    }

    protected function tableName(): string
    {
        return 'form_tracking_configs';
    }
}
