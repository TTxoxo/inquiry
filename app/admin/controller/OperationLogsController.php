<?php

declare(strict_types=1);

namespace app\admin\controller;

final class OperationLogsController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('操作日志', 'app/admin/view/operation-logs/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listOperationLogs($this->currentUser())]);
    }
}
