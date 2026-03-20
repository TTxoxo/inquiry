<?php

declare(strict_types=1);

namespace app\service\log;

use app\model\LoginLog;

final class LoginLogService
{
    public function __construct(private readonly LoginLog $loginLogModel = new LoginLog())
    {
    }

    public function record(?int $userId, string $username, int $status, string $ip, string $userAgent): int
    {
        return $this->loginLogModel->insert([
            'user_id' => $userId,
            'username' => $username,
            'status' => $status,
            'ip' => mb_substr($ip, 0, 45),
            'user_agent' => mb_substr($userAgent, 0, 500),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
