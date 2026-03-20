<?php

declare(strict_types=1);

namespace app\common\middleware;

use app\admin\service\AuthSessionService;
use think\Response;

final class AdminAuthMiddleware
{
    public function __construct(private readonly AuthSessionService $sessionService = new AuthSessionService())
    {
    }

    public function handle(array $request, callable $next): Response
    {
        if (!$this->sessionService->check()) {
            return Response::redirect('/admin/login');
        }

        if (($request['method'] ?? 'GET') === 'POST') {
            $token = $request['input']['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
            if (!$this->sessionService->validateCsrf(is_string($token) ? $token : null)) {
                return Response::json([
                    'code' => 4030,
                    'message' => 'CSRF token invalid',
                    'data' => (object) [],
                    'errors' => (object) [],
                ], 200);
            }
        }

        return $next($request);
    }
}
