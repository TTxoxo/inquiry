<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\service\AdminCrudService;
use app\admin\service\AdminViewService;
use app\admin\service\AuthSessionService;
use app\common\controller\BaseController;
use RuntimeException;
use Throwable;

abstract class AdminModuleController extends BaseController
{
    public function __construct(
        protected readonly AuthSessionService $sessionService = new AuthSessionService(),
        protected readonly AdminViewService $viewService = new AdminViewService(),
        protected readonly AdminCrudService $crudService = new AdminCrudService(),
    ) {
    }

    protected function renderModule(string $title, string $viewFile, array $extra = []): \think\Response
    {
        $user = $this->sessionService->user();
        if ($user === null) {
            return \think\Response::redirect('/admin/login');
        }

        return $this->view($this->viewService->render($title, root_path($viewFile), $user, $this->sessionService->csrfToken(), $extra));
    }

    protected function currentUser(): array
    {
        $user = $this->sessionService->user();
        if ($user === null) {
            throw new RuntimeException('Unauthenticated', 4010);
        }

        return $user;
    }

    protected function handleAction(callable $callback): \think\Response
    {
        try {
            return $this->success($callback());
        } catch (Throwable $exception) {
            $code = (int) $exception->getCode();
            if ($code === 4003) {
                return $this->error(4003, '越权访问');
            }
            if ($code === 4010) {
                return $this->error(4010, 'Unauthenticated');
            }
            return $this->error($code > 0 ? $code : 5000, $exception->getMessage() === '' ? 'Action failed' : $exception->getMessage());
        }
    }
}
