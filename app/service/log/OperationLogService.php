<?php

declare(strict_types=1);

namespace app\service\log;

use app\model\OperationLog;

final class OperationLogService
{
    public function __construct(private readonly OperationLog $operationLogModel = new OperationLog())
    {
    }

    public function record(?int $userId, ?int $siteId, string $action, string $targetType, ?int $targetId, string $content, string $ip): int
    {
        return $this->operationLogModel->insert([
            'user_id' => $userId,
            'site_id' => $siteId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'content' => mb_substr($content, 0, 500),
            'ip' => mb_substr($ip, 0, 45),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
