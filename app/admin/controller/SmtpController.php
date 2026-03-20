<?php

declare(strict_types=1);

namespace app\admin\controller;

final class SmtpController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('SMTP 配置', 'app/admin/view/smtp/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listSmtp($this->currentUser())]);
    }
    public function save(array $input): \think\Response
    {
        $errors = (new \app\admin\validate\SmtpValidate())->check($input);
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        return $this->handleAction(fn (): array => ['item' => $this->crudService->saveSmtp($this->currentUser(), $input)]);
    }
    public function delete(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ((function () use ($input): array {
            $this->crudService->deleteRecord($this->currentUser(), 'smtp', (int) ($input['id'] ?? 0));
            return [];
        })()));
    }
}
