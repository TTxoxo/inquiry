<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\service\AdminCrudService;
use app\admin\service\AdminModuleRegistry;
use app\admin\service\AuthSessionService;
use app\admin\service\LoginLogService;
use app\common\controller\BaseController;
use app\model\User;
use RuntimeException;
use Throwable;

final class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthSessionService $sessionService = new AuthSessionService(),
        private readonly LoginLogService $loginLogService = new LoginLogService(),
        private readonly User $userModel = new User(),
        private readonly AdminCrudService $crudService = new AdminCrudService(),
    ) {
    }

    public function loginPage(): \think\Response
    {
        if ($this->sessionService->check()) {
            return \think\Response::redirect('/admin/dashboard');
        }

        $viewData = [
            'csrf_token' => $this->sessionService->csrfToken(),
            'last_username' => '',
            'error_message' => '',
        ];
        ob_start();
        require root_path('app/admin/view/auth/login.html');
        $content = (string) ob_get_clean();

        return $this->view($content);
    }

    public function login(array $input): \think\Response
    {
        $username = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $ip = $this->ip();
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if (!$this->sessionService->validateCsrf(isset($input['_csrf_token']) ? (string) $input['_csrf_token'] : null)) {
            return $this->error(4030, 'CSRF token invalid');
        }

        if ($username === '' || $password === '') {
            return $this->error(422, 'Validation failed', [], [
                'username' => $username === '' ? 'Username is required' : '',
                'password' => $password === '' ? 'Password is required' : '',
            ]);
        }

        try {
            if ($this->loginLogService->tooManyFailures($ip, $username)) {
                $this->loginLogService->record(null, $username, 0, $ip, $userAgent);
                return $this->error(4290, 'Too many login failures, please try again later');
            }

            $user = $this->userModel->findByUsername($username);
            if ($user === null || (int) $user['status'] !== 1 || !password_verify($password, (string) $user['password'])) {
                $this->loginLogService->record($user === null ? null : (int) $user['id'], $username, 0, $ip, $userAgent);
                return $this->error(4011, 'Username or password incorrect');
            }

            $this->sessionService->login($user);
            $now = date('Y-m-d H:i:s');
            $this->userModel->updateLoginMeta((int) $user['id'], $ip, $now);
            $this->loginLogService->record((int) $user['id'], $username, 1, $ip, $userAgent);

            return $this->success(['redirect' => '/admin/dashboard']);
        } catch (Throwable $exception) {
            return $this->mapException($exception, 'Login failed');
        }
    }

    public function logout(): \think\Response
    {
        $this->sessionService->logout();

        return $this->success(['redirect' => '/admin/login']);
    }

    public function dashboard(): \think\Response
    {
        $user = $this->sessionService->user();
        if ($user === null) {
            return \think\Response::redirect('/admin/login');
        }

        $viewData = [
            'page_title' => 'Dashboard',
            'content_template' => root_path('app/admin/view/auth/dashboard.html'),
            'user' => $user,
            'csrf_token' => $this->sessionService->csrfToken(),
            'nav_items' => (new AdminModuleRegistry())->nav(),
        ];
        ob_start();
        require root_path('app/admin/view/layout/admin.html');
        $content = (string) ob_get_clean();

        return $this->view($content);
    }

    public function dashboardStats(array $input): \think\Response
    {
        $user = $this->sessionService->user();
        if ($user === null) {
            return $this->error(4010, 'Unauthenticated');
        }

        try {
            return $this->success(['stats' => $this->crudService->dashboard($user)]);
        } catch (Throwable $exception) {
            return $this->mapException($exception, 'Dashboard load failed');
        }
    }

    private function ip(): string
    {
        return mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), 0, 45);
    }

    private function mapException(Throwable $exception, string $fallback): \think\Response
    {
        if ($exception instanceof RuntimeException && $exception->getCode() === 5001) {
            return $this->error(5001, 'Database connection failed');
        }
        if ((int) $exception->getCode() === 4003) {
            return $this->error(4003, '越权访问');
        }

        return $this->error(5000, $exception->getMessage() === '' ? $fallback : $exception->getMessage());
    }
}
