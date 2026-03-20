<?php

declare(strict_types=1);

namespace app\admin\controller;

final class EmailLogsController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('邮件发送日志', 'app/admin/view/email-logs/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listEmailLogs($this->currentUser())]);
    }
    public function retry(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['item' => $this->crudService->retryEmailLog($this->currentUser(), (int) ($input['id'] ?? 0))]);
    }
}
