<?php

declare(strict_types=1);

namespace app\admin\controller;

final class LoginLogsController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('登录日志', 'app/admin/view/login-logs/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listLoginLogs($this->currentUser())]);
    }
}
