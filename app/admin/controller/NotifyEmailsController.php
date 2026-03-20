<?php

declare(strict_types=1);

namespace app\admin\controller;

final class NotifyEmailsController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('通知邮箱', 'app/admin/view/notify-emails/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listNotifyEmails($this->currentUser())]);
    }
    public function save(array $input): \think\Response
    {
        $errors = (new \app\admin\validate\NotifyEmailsValidate())->check($input);
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        return $this->handleAction(fn (): array => ['item' => $this->crudService->saveNotifyEmail($this->currentUser(), $input)]);
    }
    public function delete(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ((function () use ($input): array {
            $this->crudService->deleteRecord($this->currentUser(), 'notify-emails', (int) ($input['id'] ?? 0));
            return [];
        })()));
    }
}
