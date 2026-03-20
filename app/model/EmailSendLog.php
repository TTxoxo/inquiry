<?php

declare(strict_types=1);

namespace app\model;

class EmailSendLog extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'site_id', 'inquiry_id', 'to_email', 'subject', 'status', 'error_message', 'sent_at', 'created_at'];
    }

    protected function tableName(): string
    {
        return 'email_send_logs';
    }
}
