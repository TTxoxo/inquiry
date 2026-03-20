<?php

declare(strict_types=1);

namespace app\common\middleware;

use app\admin\service\AuthSessionService;
use app\admin\service\AuthorizationService;
use think\Response;

final class RolePermissionMiddleware
{
    public function __construct(
        private readonly AuthSessionService $sessionService = new AuthSessionService(),
        private readonly AuthorizationService $authorizationService = new AuthorizationService()
    ) {
    }

    public function handle(array $request, callable $next): Response
    {
        $path = (string) ($request['path'] ?? '/');
        if (!$this->authorizationService->canAccess($this->sessionService->user(), $path)) {
            return Response::json([
                'code' => 4031,
                'message' => 'Permission denied',
                'data' => (object) [],
                'errors' => (object) [],
            ], 200);
        }

        return $next($request);
    }
}
