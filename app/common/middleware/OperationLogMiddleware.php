<?php

declare(strict_types=1);

namespace app\common\middleware;

use app\admin\service\AuthSessionService;
use app\model\User;
use Throwable;
use think\Response;

final class OperationLogMiddleware
{
    public function __construct(
        private readonly AuthSessionService $sessionService = new AuthSessionService(),
        private readonly User $userModel = new User()
    ) {
    }

    public function handle(array $request, callable $next): Response
    {
        $response = $next($request);

        if (($request['method'] ?? 'GET') !== 'POST') {
            return $response;
        }

        $path = (string) ($request['path'] ?? '');
        if ($path === '/admin/login') {
            return $response;
        }

        $sessionUser = $this->sessionService->user();
        if ($sessionUser === null) {
            return $response;
        }

        try {
            $statement = $this->userModel->pdo()->prepare(
                sprintf('INSERT INTO `%s` (`user_id`, `site_id`, `action`, `target_type`, `target_id`, `content`, `ip`, `created_at`) VALUES (:user_id, :site_id, :action, :target_type, :target_id, :content, :ip, :created_at)', $this->userModel->table('operation_logs'))
            );
            $statement->execute([
                'user_id' => (int) $sessionUser['user_id'],
                'site_id' => (int) $sessionUser['site_id'],
                'action' => $path,
                'target_type' => 'admin_request',
                'target_id' => null,
                'content' => mb_substr(sprintf('%s %s', $request['method'] ?? 'POST', $path), 0, 500),
                'ip' => mb_substr((string) ($request['ip'] ?? ''), 0, 45),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }

        return $response;
    }
}
